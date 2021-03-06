<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Dominic O'Brien (dominicnobrien@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
 *
 * Fairness is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * Fairness is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
  ********************************************************************************/
/*
 * $Revision: 7166 $
 * $Id: UserSettingFactory.class.php 7166 2012-06-26 22:32:24Z ipso $
 * $Date: 2012-06-27 06:32:24 +0800 (Wed, 27 Jun 2012) $
 */

/**
 * @package Core
 */
class UserSettingFactory extends Factory {
	protected $table = 'user_setting';
	protected $pk_sequence_name = 'user_setting_id_seq'; //PK Sequence name

    function _getFactoryOptions( $name ) {

		$retval = NULL;
		switch( $name ) {
			case 'type':
				$retval = array(
								10 => TTi18n::gettext('Public'),
								20 => TTi18n::gettext('Private'),
									);
				break;
		}

		return $retval;
	}

    function _getVariableToFunctionMap( $data ) {
		$variable_function_map = array(
										'id' => 'ID',
										'user_id' => 'User',
                                        'first_name' => FALSE,
										'last_name' => FALSE,
										'type_id' => 'Type',
										'type' => FALSE,
										'name' => 'Name',
                                        'value' => 'Value',
										'deleted' => 'Deleted',
										);
		return $variable_function_map;
	}

	function isUniqueName($name) {
		if ( $this->getUser() == FALSE ) {
			return FALSE;
		}

		$name = trim($name);
		if ( $name == '' ) {
			return FALSE;
		}

		$ph = array(
					'user_id' => $this->getUser(),
					'name' => $name,
					);

		$query = 'select id from '. $this->getTable() .'
					where user_id = ?
						AND name = ?
						AND deleted = 0';
		$name_id = $this->db->GetOne($query, $ph);
		Debug::Arr($name_id,'Unique Name: '. $name , __FILE__, __LINE__, __METHOD__,10);

		if ( $name_id === FALSE ) {
			return TRUE;
		} else {
			if ($name_id == $this->getId() ) {
				return TRUE;
			}
		}

		return FALSE;
	}

    function getUser() {
		if ( isset($this->data['user_id']) ) {
			return $this->data['user_id'];
		}
        return FALSE;
	}
	function setUser($id) {
		$id = trim($id);

		$ulf = TTnew( 'UserListFactory' );

		if ( $this->Validator->isResultSetWithRows(	'user_id',
															$ulf->getByID($id),
															TTi18n::gettext('Invalid User')
															) ) {
			$this->data['user_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}
    function getType() {
		if ( isset($this->data['type_id']) ) {
			return (int)$this->data['type_id'];
		}

		return FALSE;
	}
    function setType($type) {
		$type = trim($type);

		$key = Option::getByValue($type, $this->getOptions('type') );
		if ($key !== FALSE) {
			$type = $key;
		}

		if ( $this->Validator->inArrayKey(	'type',
											$type,
											TTi18n::gettext('Incorrect Type'),
											$this->getOptions('type')) ) {

			$this->data['type_id'] = $type;

			return FALSE;
		}

		return FALSE;
	}

	function getName() {
		if ( isset($this->data['name']) ) {
			return $this->data['name'];
		}

		return FALSE;
	}
	function setName($value) {
		$value = trim($value);
		if (	$this->Validator->isLength(	'name',
											$value,
											TTi18n::gettext('Name is too short or too long'),
											1,250)
				AND
						$this->Validator->isTrue(		'name',
														$this->isUniqueName($value),
														TTi18n::gettext('Name already exists')
														)

						) {

			$this->data['name'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getValue() {
		if ( isset($this->data['value']) ) {
			return $this->data['value'];
		}

		return FALSE;
	}
	function setValue($value) {
		$value = trim($value);
		if (	$this->Validator->isLength(	'value',
											$value,
											TTi18n::gettext('Value is too short or too long'),
											1,4096)
						) {

			$this->data['value'] = $value;

			return TRUE;
		}

		return FALSE;
	}


	function preSave() {
		return TRUE;
	}

	function postSave() {
		$this->removeCache( $this->getUser().$this->getName() );
		return TRUE;
	}

    function setObjectFromArray( $data ) {
		if ( is_array( $data ) ) {
			$variable_function_map = $this->getVariableToFunctionMap();
			foreach( $variable_function_map as $key => $function ) {
				if ( isset($data[$key]) ) {

					$function = 'set'.$function;
					switch( $key ) {
						default:
							if ( method_exists( $this, $function ) ) {
								$this->$function( $data[$key] );
							}
							break;
					}
				}
			}

			$this->setCreatedAndUpdatedColumns( $data );

			return TRUE;
		}

		return FALSE;
	}

	function getObjectAsArray( $include_columns = NULL ) {
		$variable_function_map = $this->getVariableToFunctionMap();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {

					$function = 'get'.$function_stub;
					switch( $variable ) {
					    case 'first_name':
						case 'last_name':
                            $data[$variable] = $this->getColumn( $variable );
                            break;
						case 'type':
							$function = 'get'.$variable;
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
						default:
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = $this->$function();
							}
							break;
					}

				}
			}
			$this->getCreatedAndUpdatedColumns( $data, $include_columns );
		}

		return $data;
	}

	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action,  TTi18n::getText('User Setting - Name').': '. $this->getName() .' '. TTi18n::getText('Value').': '. $this->getValue(), NULL, $this->getTable() );
	}

    static function getUserSetting( $user_id, $name ) {
        $uslf = new UserSettingListFactory();
        $uslf->getByUserIdAndName( $user_id, $name );
        if ( $uslf->getRecordCount() == 1 ) {
            $us_obj = $uslf->getCurrent();
            $retarr = $us_obj->getObjectAsArray();
            return $retarr;
        }

        return FALSE;
    }

    static function setUserSetting( $user_id, $name, $value, $type_id = 10 ) {
        $row = array(
            'user_id' => $user_id,
            'name' => $name,
            'value' => $value,
            'type_id' => $type_id
        );
        $uslf = new UserSettingListFactory();
        $uslf->getByUserIdAndName( $user_id, $name );
        if ( $uslf->getRecordCount() == 1 ) {
            $usf = $uslf->getCurrent();
            $row = array_merge( $usf->getObjectAsArray(), $row );
        } else {
            $usf = new UserSettingFactory();
        }

        Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);
        $usf->setObjectFromArray( $row );
        if ( $usf->isValid() ) {
            $usf->Save();
        }

        return FALSE;

    }

    static function deleteUserSetting( $user_id, $name ) {
        $uslf = new UserSettingListFactory();
        $uslf->getByUserIdAndName( $user_id, $name );
        if ( $uslf->getRecordCount() == 1 ) {
            $usf = $uslf->getCurrent();
            $usf->setDeleted(TRUE);
            if ( $usf->isValid() ) {
                $usf->Save();
            }
        }

        return FALSE;
    }
}
?>

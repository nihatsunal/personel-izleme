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
 * $Revision: 2196 $
 * $Id: APIUserWage.class.php 2196 2008-10-14 16:08:54Z ipso $
 * $Date: 2008-10-14 09:08:54 -0700 (Tue, 14 Oct 2008) $
 */

/**
 * @package API\Users
 */
class APIUserWage extends APIFactory {
	protected $main_class = 'UserWageFactory';

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Get default branch data for creating new branches.
	 * @return array
	 */
	function getUserWageDefaultData( $user_id = NULL ) {
		$company_obj = $this->getCurrentCompanyObject();

		Debug::Text('Getting wage default data...', __FILE__, __LINE__, __METHOD__,10);

		//If user_id is passed, check for other wage entries, if none, default to the employees hire date.
		if ( $user_id > 0 ) {
			//Check for existing wage entries.
			$uwlf = TTnew( 'UserWageListFactory' );
			$uwlf->getLastWageByUserId($user_id);
			if ( $uwlf->getRecordCount() == 1 ) {
				Debug::Text('Previous wage entry already exists...', __FILE__, __LINE__, __METHOD__,10);
				$effective_date = time();
			} else {
				Debug::Text('Trying to use hire date...', __FILE__, __LINE__, __METHOD__,10);
				$ulf = TTnew( 'UserListFactory' );
				$ulf->getByIdAndCompanyId( $user_id, $this->getCurrentCompanyObject()->getId() );
				if ( $ulf->getRecordCount() > 0 ) {
					$effective_date = $ulf->getCurrent()->getHireDate();
				}
			}
		} else {
			Debug::Text('No user specified...', __FILE__, __LINE__, __METHOD__,10);
			$effective_date = time();
		}

		$data = array(
						'company_id' => $company_obj->getId(),
						'type_id' => 10,
						'wage' => '0.00',
						'hourly_rate' => '0.00',
						'effective_date' => TTDate::getAPIDate( 'DATE', $effective_date ),
						'labor_burden_percent' => 0,
						'weekly_time' => (3600*40), //40hrs/week
					);

		return $this->returnHandler( $data );
	}

	/**
	 * Get branch data for one or more branches.
	 * @param array $data filter data
	 * @return array
	 */
	function getUserWage( $data = NULL, $disable_paging = FALSE ) {
		if ( !$this->getPermissionObject()->Check('wage','enabled')
				OR !( $this->getPermissionObject()->Check('wage','view') OR $this->getPermissionObject()->Check('wage','view_own') OR $this->getPermissionObject()->Check('wage','view_child')  ) ) {
			return $this->getPermissionObject()->PermissionDenied();
		}
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
		$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'wage', 'view' );

		$blf = TTnew( 'UserWageListFactory' );
		$blf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], NULL, $data['filter_sort'] );
		Debug::Text('Record Count: '. $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $blf->getRecordCount() > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $blf->getRecordCount() );

			$this->setPagerObject( $blf );

			foreach( $blf as $b_obj ) {
				$retarr[] = $b_obj->getObjectAsArray( $data['filter_columns'], $data['filter_data']['permission_children_ids'] );

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $blf->getCurrentRow() );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			return $this->returnHandler( $retarr );
		}

		return $this->returnHandler( TRUE ); //No records returned.
	}

	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonUserWageData( $data ) {
		return Misc::arrayIntersectByRow( $this->getUserWage( $data, TRUE ) );
	}

	/**
	 * Validate branch data for one or more branches.
	 * @param array $data branch data
	 * @return array
	 */
	function validateUserWage( $data ) {
		return $this->setUserWage( $data, TRUE );
	}

	/**
	 * Set branch data for one or more branches.
	 * @param array $data branch data
	 * @return array
	 */
	function setUserWage( $data, $validate_only = FALSE ) {
		$validate_only = (bool)$validate_only;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('wage','enabled')
				OR !( $this->getPermissionObject()->Check('wage','edit') OR $this->getPermissionObject()->Check('wage','edit_own') OR $this->getPermissionObject()->Check('wage','edit_child') OR $this->getPermissionObject()->Check('wage','add') ) ) {
			return  $this->getPermissionObject()->PermissionDenied();
		}

		if ( $validate_only == TRUE ) {
			Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
		}

		extract( $this->convertToMultipleRecords($data) );
		Debug::Text('Received data for: '. $total_records .' UserWages', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'UserWageListFactory' );
				$lf->StartTransaction();
				if ( isset($row['id']) AND $row['id'] > 0 ) {
					//Modifying existing object.
					//Get wage object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if (
							  $validate_only == TRUE
							  OR
								(
								$this->getPermissionObject()->Check('wage','edit')
									OR ( $this->getPermissionObject()->Check('wage','edit_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE )
								) ) {

							Debug::Text('Row Exists, getting current data: ', $row['id'], __FILE__, __LINE__, __METHOD__, 10);
							$lf = $lf->getCurrent();
							$row = array_merge( $lf->getObjectAsArray(), $row );
						} else {
							$primary_validator->isTrue( 'permission', FALSE, TTi18n::gettext('Edit permission denied') );
						}
					} else {
						//Object doesn't exist.
						$primary_validator->isTrue( 'id', FALSE, TTi18n::gettext('Edit permission denied, record does not exist') );
					}
				} else {
					//Adding new object, check ADD permissions.
					$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('wage','add'), TTi18n::gettext('Add permission denied') );
				}
				Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid();
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

					$lf->setObjectFromArray( $row );

					if ( $validate_only == TRUE ) {
						$lf->validate_only = TRUE;
					}

					$is_valid = $lf->isValid();
					if ( $is_valid == TRUE ) {
						Debug::Text('Saving data...', __FILE__, __LINE__, __METHOD__, 10);
						if ( $validate_only == TRUE ) {
							$save_result[$key] = TRUE;
						} else {
							$save_result[$key] = $lf->Save();
						}
						$validator_stats['valid_records']++;
					}
				}

				if ( $is_valid == FALSE ) {
					Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

					$lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

					if ( $primary_validator->isValid() == FALSE ) {
						$validator[$key] = $primary_validator->getErrorsArray();
					} else {
						$validator[$key] = $lf->Validator->getErrorsArray();
					}
				} elseif ( $validate_only == TRUE ) {
					$lf->FailTransaction();
				}


				$lf->CommitTransaction();

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			if ( $validator_stats['valid_records'] > 0 AND $validator_stats['total_records'] == $validator_stats['valid_records'] ) {
				if ( $validator_stats['total_records'] == 1 ) {
					return $this->returnHandler( $save_result[$key] ); //Single valid record
				} else {
					return $this->returnHandler( TRUE, 'SUCCESS', TTi18n::getText('MULTIPLE RECORDS SAVED'), $save_result, $validator_stats ); //Multiple valid records
				}
			} else {
				return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('INVALID DATA'), $validator, $validator_stats );
			}
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Delete one or more branchs.
	 * @param array $data branch data
	 * @return array
	 */
	function deleteUserWage( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('wage','enabled')
				OR !( $this->getPermissionObject()->Check('wage','delete') OR $this->getPermissionObject()->Check('wage','delete_own') OR $this->getPermissionObject()->Check('wage','delete_child') ) ) {
			return  $this->getPermissionObject()->PermissionDenied();
		}

		Debug::Text('Received data for: '. count($data) .' UserWages', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'UserWageListFactory' );
				$lf->StartTransaction();
				if ( is_numeric($id) ) {
					//Modifying existing object.
					//Get branch object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if ( $this->getPermissionObject()->Check('wage','delete')
								OR ( $this->getPermissionObject()->Check('wage','delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE ) ) {
							Debug::Text('Record Exists, deleting record: ', $id, __FILE__, __LINE__, __METHOD__, 10);
							$lf = $lf->getCurrent();
						} else {
							$primary_validator->isTrue( 'permission', FALSE, TTi18n::gettext('Delete permission denied') );
						}
					} else {
						//Object doesn't exist.
						$primary_validator->isTrue( 'id', FALSE, TTi18n::gettext('Delete permission denied, record does not exist') );
					}
				} else {
					$primary_validator->isTrue( 'id', FALSE, TTi18n::gettext('Delete permission denied, record does not exist') );
				}

				//Debug::Arr($lf, 'AData: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid();
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Attempting to delete record...', __FILE__, __LINE__, __METHOD__, 10);
					$lf->setDeleted(TRUE);

					$is_valid = $lf->isValid();
					if ( $is_valid == TRUE ) {
						Debug::Text('Record Deleted...', __FILE__, __LINE__, __METHOD__, 10);
						$save_result[$key] = $lf->Save();
						$validator_stats['valid_records']++;
					}
				}

				if ( $is_valid == FALSE ) {
					Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

					$lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

					if ( $primary_validator->isValid() == FALSE ) {
						$validator[$key] = $primary_validator->getErrorsArray();
					} else {
						$validator[$key] = $lf->Validator->getErrorsArray();
					}
				}

				$lf->CommitTransaction();

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			if ( $validator_stats['valid_records'] > 0 AND $validator_stats['total_records'] == $validator_stats['valid_records'] ) {
				if ( $validator_stats['total_records'] == 1 ) {
					return $this->returnHandler( $save_result[$key] ); //Single valid record
				} else {
					return $this->returnHandler( TRUE, 'SUCCESS', TTi18n::getText('MULTIPLE RECORDS SAVED'), $save_result, $validator_stats ); //Multiple valid records
				}
			} else {
				return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('INVALID DATA'), $validator, $validator_stats );
			}
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Copy one or more branches.
	 * @param array $data branch IDs
	 * @return array
	 */
	function copyUserWage( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		Debug::Text('Received data for: '. count($data) .' UserWages', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$src_rows = $this->getUserWage( array('filter_data' => array('id' => $data) ), TRUE );
		if ( is_array( $src_rows ) AND count($src_rows) > 0 ) {
			Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
			foreach( $src_rows as $key => $row ) {
				unset($src_rows[$key]['id']); //Clear fields that can't be copied
			}
			//Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

			return $this->setUserWage( $src_rows ); //Save copied rows
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Calculate salaried employees hourly rate based on wage and weekly hours.
	 * @param float $wage Wage
	 * @param float $weekly_hours Weekly Hours
	 * @param int $wage_type_id Wage Type ID
	 * @return float
	 */
	function getHourlyRate( $wage, $weekly_hours, $wage_type_id = 10 ) {
		if ( $wage == '' ) {
			return '0.00';
		}

		if ( $weekly_hours == '' ) {
			return '0.00';
		}

		if ( $wage_type_id == '' ) {
			return '0.00';
		}

		$uwf = TTnew( 'UserWageFactory' );
		$uwf->setType( $wage_type_id );
		$uwf->setWage( $wage );
		$uwf->setWeeklyTime( TTDate::parseTimeUnit($weekly_hours) );
		$hourly_rate = $uwf->calcHourlyRate();

		return $this->returnHandler( $hourly_rate );
	}

}
?>

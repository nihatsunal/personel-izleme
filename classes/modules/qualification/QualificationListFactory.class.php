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
 * $Revision: 5369 $
 * $Id: QualificationListFactory.class.php 5369 2011-10-21 19:37:24Z ipso $
 * $Date: 2011-10-22 03:37:24 +0800 (Sat, 22 Oct 2011) $
 */

/**
 * @package
 */
class QualificationListFactory extends QualificationFactory implements IteratorAggregate {

	function getAll($limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		$query = '
					select 	*
					from	'. $this->getTable() .'
					WHERE deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	function getById($id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		$this->rs = $this->getCache($id);
		if ( $this->rs === FALSE ) {
			$ph = array(
						'id' => $id,
						);

			$query = '
						select 	*
						from	'. $this->getTable() .'
						where	id = ?
							AND deleted = 0';
			$query .= $this->getWhereSQL( $where );
			$query .= $this->getSortSQL( $order );

			$this->ExecuteSQL( $query, $ph );

			$this->saveCache($this->rs,$id);
		}

		return $this;
	}

	static function getNameById( $id ) {
		if ( $id == '') {
			return FALSE;
		}

		$lf = new QualificationListFactory();
		$lf = $lf->getById( $id );
		if ( $lf->getRecordCount() > 0 ) {
			$obj = $lf->getCurrent();
			return $obj->getName();
		}

		return FALSE;
	}

    function getByCompanyId($id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'id' => 'asc', 'name' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$ph = array(
					'company_id' => $id,
					);

		$query = '
					select 	*
					from	'. $this->getTable() .'
					where	company_id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

    function getByIdAndCompanyId($id, $company_id, $order = NULL) {
		if ( $id == '' ) {
			return FALSE;
		}

		if ( $company_id == '' ) {
			return FALSE;
		}

		$ph = array(
					'company_id' => $company_id,
					'id' => $id,
					);

		$query = '
					select 	*
					from 	'. $this->getTable() .'
					where	company_id = ?
						AND	id = ?
						AND deleted = 0';
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL($query,$ph);

		return $this;
	}

    function getByCompanyIDAndGroupID($company_id, $id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => $company_id,
					'group_id' => $id,
					);

		$query = '
					select 	*
					from	'. $this->getTable() .'
					where 	company_id = ?
						AND group_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

        $this->ExecuteSQL($query,$ph);

        return $this;
	}

    function getByCompanyIdAndTypeId($company_id, $type_id, $order = NULL) {
        if ( $company_id == '') {
			return FALSE;
		}

		if ( $type_id == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => $company_id,
					'type_id' => $type_id,
					);

		$query = '
					select 	*
					from	'. $this->getTable() .'
					where 	company_id = ?
						AND type_id = ?
						AND deleted = 0';

		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL($query,$ph);

		return $this;
    }



    function getAPISearchByCompanyIdAndArrayCriteria( $company_id, $filter_data, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {

    	if ( $company_id == '') {
			return FALSE;
		}

        if ( isset( $filter_data['qualification_group_id'] ) ) {
            $filter_data['group_id'] =  $filter_data['qualification_group_id'];
        }
        if ( isset( $filter_data['qualification_type_id'] ) ) {
            $filter_data['type_id'] = $filter_data['qualification_type_id'];
        }
        if ( isset( $filter_data['qualification_id'] ) ) {
            $filter_data['id'] = $filter_data['qualification_id'];
        }

		if ( !is_array($order) ) {
			//Use Filter Data ordering if its set.
			if ( isset($filter_data['sort_column']) AND $filter_data['sort_order']) {
				$order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
			}
		}

		$additional_order_fields = array('type_id', 'group');

		$sort_column_aliases = array(
									 'type' => 'type_id',
                                     'group' => 'd.name',
									 );

		$order = $this->getColumnsFromAliases( $order, $sort_column_aliases );


		if ( $order == NULL ) {
			$order = array( 'type_id' => 'asc', 'name' => 'asc');
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		Debug::Arr($order,'Order Data:', __FILE__, __LINE__, __METHOD__,10);
		Debug::Arr($filter_data,'Filter Data:', __FILE__, __LINE__, __METHOD__,10);

		$uf = new UserFactory();
        $qgf = new QualificationGroupFactory();

		$ph = array(
					'company_id' => $company_id,
					);

		$query = '
					select 	a.*,
                            d.name as group,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from 	'. $this->getTable() .' as a
                        LEFT JOIN '. $qgf->getTable() .' as d ON ( a.group_id = d.id AND d.deleted = 0)
						LEFT JOIN '. $uf->getTable() .' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN '. $uf->getTable() .' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	a.company_id = ?';



		$query .= ( isset($filter_data['permission_children_ids']) ) ? $this->getWhereClauseSQL( 'a.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['id']) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['id'], 'numeric_list', $ph ) : NULL;
		//$query .= ( isset($filter_data['exclude_id']) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph ) : NULL;

		if ( isset($filter_data['type']) AND trim($filter_data['type']) != '' AND !isset($filter_data['type_id']) ) {
			$filter_data['type_id'] = Option::getByFuzzyValue( $filter_data['type'], $this->getOptions('type') );
		}
		$query .= ( isset($filter_data['type_id']) ) ? $this->getWhereClauseSQL( 'a.type_id', $filter_data['type_id'], 'numeric_list', $ph ) : NULL;

		$query .= ( isset($filter_data['name']) ) ? $this->getWhereClauseSQL( 'a.name', $filter_data['name'], 'text_metaphone', $ph ) : NULL;

        $query .= ( isset($filter_data['description']) ) ? $this->getWhereClauseSQL( 'a.description', $filter_data['description'], 'text', $ph ) : NULL;

        $query .= ( isset($filter_data['group_id']) ) ? $this->getWhereClauseSQL( 'a.group_id', $filter_data['group_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['group']) ) ? $this->getWhereClauseSQL( 'd.name', $filter_data['group'], 'text', $ph ) : NULL;

		$query .= ( isset($filter_data['tag']) ) ? $this->getWhereClauseSQL( 'a.id', array( 'company_id' => $company_id, 'object_type_id' => 250, 'tag' => $filter_data['tag'] ), 'tag', $ph ) : NULL;

        if ( isset($filter_data['created_date']) AND trim($filter_data['created_date']) != '' ) {
			$date_filter = $this->getDateRangeSQL( $filter_data['created_date'], 'a.created_date' );
			if ( $date_filter != FALSE ) {
				$query  .=	' AND '. $date_filter;
			}
			unset($date_filter);
		}
		if ( isset($filter_data['updated_date']) AND trim($filter_data['updated_date']) != '' ) {
			$date_filter = $this->getDateRangeSQL( $filter_data['updated_date'], 'a.updated_date' );
			if ( $date_filter != FALSE ) {
				$query  .=	' AND '. $date_filter;
			}
			unset($date_filter);
		}

		$query .= ( isset($filter_data['created_by']) ) ? $this->getWhereClauseSQL( array('a.created_by','y.first_name','y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph ) : NULL;

        $query .= ( isset($filter_data['updated_by']) ) ? $this->getWhereClauseSQL( array('a.updated_by','z.first_name','z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph ) : NULL;


		$query .= 	'
						AND a.deleted = 0
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );
        //Debug::Arr( $ph, 'Query: '.$query, __FILE__, __LINE__, __METHOD__, 10 );
		$this->ExecuteSQL($query,$ph,$limit,$page);

		return $this;
	}
}
?>

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
 * $Revision: 7487 $
 * $Id: ViewScheduleDay.php 7487 2012-08-15 22:35:09Z ipso $
 * $Date: 2012-08-15 15:35:09 -0700 (Wed, 15 Aug 2012) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');
require_once(Environment::getBasePath() .'classes/misc/arr_multisort.class.php');

//Debug::setVerbosity(11);

if ( !$permission->Check('schedule','enabled')
		OR !( $permission->Check('schedule','view') OR $permission->Check('schedule','view_own') OR $permission->Check('schedule','view_child')) ) {
	$permission->Redirect( FALSE ); //Redirect
}

$smarty->assign('title', TTi18n::gettext($title = 'Schedules')); // See index.php
//BreadCrumb::setCrumb($title);

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'page',
												'sort_column',
												'sort_order',
												'filter_data',
												'serialize_filter_data',
												//'filter_start_date',
												//'filter_user_id'
												) ) );

URLBuilder::setURL($_SERVER['SCRIPT_NAME'],
											array(
													'sort_column' => $sort_column,
													'sort_order' => $sort_order,
													'page' => $page
												) );

//Data is coming in serialized from Schedule Month.
$serialize_filter_data = unserialize( base64_decode( urldecode( $serialize_filter_data ) ) );

if ( isset( $filter_data['start_date'] ) AND $filter_data['start_date'] != '' ) {
	$filter_data['start_date'] = TTDate::parseDateTime($filter_data['start_date']);
} else {
	$filter_data['start_date'] = TTDate::getBeginWeekEpoch( time() );
}
$filter_data = array_merge( $serialize_filter_data, $filter_data);

//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
$hlf = TTnew( 'HierarchyListFactory' );
$permission_children_ids = $hlf->getHierarchyChildrenByCompanyIdAndUserIdAndObjectTypeID( $current_company->getId(), $current_user->getId() );
if ( $permission->Check('schedule','view') == FALSE ) {
	if ( $permission->Check('schedule','view_child') == FALSE ) {
		$permission_children_ids = array();
	}
	if ( $permission->Check('schedule','view_own') ) {
		$permission_children_ids[] = $current_user->getId();
	}

	$filter_data['permission_children_ids'] = $permission_children_ids;
}

$action = Misc::findSubmitButton();
switch ($action) {
	default:
		$user_ids = array();

		if ( $filter_data['start_date'] != '' ) {
			$start_date = $filter_data['start_date'] = TTDate::getBeginDayEpoch( $filter_data['start_date'] );
			$end_date = $filter_data['end_date'] = TTDate::getEndDayEpoch($start_date);
		}

		Debug::text(' Start Date: '. TTDate::getDate('DATE+TIME', $start_date) .' End Date: '. TTDate::getDate('DATE+TIME', $end_date) , __FILE__, __LINE__, __METHOD__,10);

		$sf = TTnew( 'ScheduleFactory' );
		$raw_schedule_shifts = $sf->getScheduleArray(  $filter_data );
		if ( is_array($raw_schedule_shifts) ) {
			foreach( $raw_schedule_shifts as $day_epoch => $day_schedule_shifts ) {
				foreach ( $day_schedule_shifts as $day_schedule_shift ) {
					$user_ids[] = $day_schedule_shift['user_id'];

					//$day_schedule_shift['is_owner'] = $permission->isOwner( $u_obj->getCreatedBy(), $u_obj->getId() );
					//$day_schedule_shift['is_child'] = $permission->isChild( $u_obj->getId(), $permission_children_ids );
					$day_schedule_shift['is_owner'] = $permission->isOwner( $day_schedule_shift['user_created_by'], $day_schedule_shift['user_id'] );
					$day_schedule_shift['is_child'] = $permission->isChild( $day_schedule_shift['user_id'], $permission_children_ids );

					$tmp_schedule_shifts[$day_epoch][$day_schedule_shift['branch']][$day_schedule_shift['department']][] = $day_schedule_shift;
					//echo 'User ID: '. $day_schedule_shift['user_id'] .' Date: '. $day_epoch .' Total Time: '. ($day_schedule_shift['total_time']/3600) ."<br>\n";
					if ( $day_schedule_shift['status_id'] == 10 ) { //Working
						if ( isset($schedule_shift_totals[$day_epoch]['total_time']) ) {
							$schedule_shift_totals[$day_epoch]['total_time'] += $day_schedule_shift['total_time'];
						} else {
							$schedule_shift_totals[$day_epoch]['total_time'] = $day_schedule_shift['total_time'];
						}
						$schedule_shift_totals[$day_epoch]['users'][] = $day_schedule_shift['user_id'];

					} elseif ( $day_schedule_shift['status_id'] == 20 ) { //Absent
						if ( isset($schedule_shift_totals[$day_epoch]['absent_total_time']) ) {
							$schedule_shift_totals[$day_epoch]['absent_total_time'] += $day_schedule_shift['total_time'];
						} else {
							$schedule_shift_totals[$day_epoch]['absent_total_time'] = $day_schedule_shift['total_time'];
						}
						$schedule_shift_totals[$day_epoch]['absent_users'][] = $day_schedule_shift['user_id'];
					}
				}
			}
		}
		$user_ids = array_unique($user_ids);

		//Total up employees/time per day.
		if ( isset($schedule_shift_totals) ) {
			foreach( $schedule_shift_totals as $day_epoch => $total_arr) {
				if ( !isset($total_arr['users']) ) {
					$total_arr['users'] = array();
				}
				$schedule_shift_totals[$day_epoch]['total_users'] = count(array_unique($total_arr['users']));
			}
		}
		//print_r($schedule_shift_totals);
		//var_dump($tmp_schedule_shifts);

		if ( isset($tmp_schedule_shifts) ) {
			//Sort Branches/Departments first
			foreach ( $tmp_schedule_shifts as $day_epoch => $day_tmp_schedule_shift ) {
				ksort($day_tmp_schedule_shift);
				$tmp_schedule_shifts[$day_epoch] = $day_tmp_schedule_shift;

				foreach ( $day_tmp_schedule_shift as $branch => $department_schedule_shifts ) {
					ksort($tmp_schedule_shifts[$day_epoch][$branch]);
				}
			}

			//Sort each department by start time.
			foreach ( $tmp_schedule_shifts as $day_epoch => $day_tmp_schedule_shift ) {
				foreach ( $day_tmp_schedule_shift as $branch => $department_schedule_shifts ) {
					foreach ( $department_schedule_shifts as $department => $department_schedule_shift ) {
						$schedule_shifts[$day_epoch][$branch][$department] = Sort::multiSort( $department_schedule_shift, 'start_time', 'last_name' );
					}
				}
			}
		}
		//print_r($schedule_shifts);

		if ( isset($start_date) AND isset($end_date) ) {
			$calendar_array = TTDate::getCalendarArray($start_date, $end_date, $current_user_prefs->getStartWeekDay(), FALSE);
			//var_dump($calendar_array);
		}
		$smarty->assign_by_ref('calendar_array', $calendar_array);

		$hlf = TTnew( 'HolidayListFactory' );
		$holiday_array = $hlf->getArrayByPolicyGroupUserId( $user_ids, $start_date, $end_date );
		//var_dump($holiday_array);

		$smarty->assign_by_ref('holidays', $holiday_array);

		$smarty->assign_by_ref('schedule_shifts', $schedule_shifts);
		$smarty->assign_by_ref('schedule_shift_totals', $schedule_shift_totals);

		$smarty->assign_by_ref('action', $action );

		break;
}
Debug::writeToLog();
$smarty->display('schedule/ViewScheduleDay.tpl');
?>

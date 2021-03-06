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
 * $Revision: 4104 $
 * $Id: EditHoliday.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

//Debug::setVerbosity( 11 );

if ( !$permission->Check('holiday_policy','enabled')
		OR !( $permission->Check('holiday_policy','edit') OR $permission->Check('holiday_policy','edit_own') ) ) {
	$permission->Redirect( FALSE ); //Redirect
}

$smarty->assign('title', TTi18n::gettext($title = 'Edit Holiday')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'holiday_policy_id',
												'id',
												'data'
												) ) );

if ( isset($data['date_stamp'] ) ) {
	$data['date_stamp'] = TTDate::parseDateTime($data['date_stamp']);
}

$hf = TTnew( 'HolidayFactory' );

$action = Misc::findSubmitButton();
$action = strtolower($action);
switch ($action) {
	case 'submit':
		//Debug::setVerbosity(11);
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$hf->setId( $data['id'] );
		if ( isset($data['holiday_policy_id'] ) ) {
			$hf->setHolidayPolicyId( $data['holiday_policy_id'] );
		}
		//Set datestamp first.
		$hf->setDateStamp( $data['date_stamp'] );
		$hf->setName( $data['name'] );


		if ( $hf->isValid() ) {
			$hf->Save();

			Redirect::Page( URLBuilder::getURL( array('id' => $data['holiday_policy_id']), 'HolidayList.php') );

			break;
		}

	default:
		if ( isset($id) AND $id != '' ) {
			BreadCrumb::setCrumb($title);

			$hlf = TTnew( 'HolidayListFactory' );
			$hlf->getByIdAndHolidayPolicyID( $id, $holiday_policy_id );
			if ( $hlf->getRecordCount() > 0 ) {
				foreach ($hlf as $h_obj) {
					//Debug::Arr($station,'Department', __FILE__, __LINE__, __METHOD__,10);

					$data = array(
										'id' => $h_obj->getId(),
										'holiday_policy_id' => $h_obj->getHolidayPolicyID(),
										'date_stamp' => $h_obj->getDateStamp(),
										'name' => $h_obj->getName(),
										'created_date' => $h_obj->getCreatedDate(),
										'created_by' => $h_obj->getCreatedBy(),
										'updated_date' => $h_obj->getUpdatedDate(),
										'updated_by' => $h_obj->getUpdatedBy(),
										'deleted_date' => $h_obj->getDeletedDate(),
										'deleted_by' => $h_obj->getDeletedBy()
									);
				}
				$holiday_policy_id = $h_obj->getHolidayPolicyID();
			}
		} elseif ( $action != 'submit' ) {
			$data = array(
						'date_stamp' => TTDate::getTime(),
						'holiday_policy_id' => $holiday_policy_id
						);
		}

		$smarty->assign_by_ref('holiday_policy_id', $holiday_policy_id);
		$smarty->assign_by_ref('data', $data);

		break;
}

$smarty->assign_by_ref('hf', $hf);

$smarty->display('policy/EditHoliday.tpl');
?>
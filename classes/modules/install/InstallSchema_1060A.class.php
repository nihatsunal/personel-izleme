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
 * $Revision: 1246 $
 * $Id: InstallSchema_1001B.class.php 1246 2007-09-14 23:47:42Z ipso $
 * $Date: 2007-09-14 16:47:42 -0700 (Fri, 14 Sep 2007) $
 */

/**
 * @package Module_Install
 */
class InstallSchema_1060A extends InstallSchema_Base {

	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion() , __FILE__, __LINE__, __METHOD__,9);

		return TRUE;
	}

	function postInstall() {
		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__,9);

		//Go through each permission group, and enable affordable_care report for for anyone who can view W2's.
		$clf = TTnew( 'CompanyListFactory' );
		$clf->getAll();
		if ( $clf->getRecordCount() > 0 ) {
			foreach( $clf as $c_obj ) {
				Debug::text('Company: '. $c_obj->getName(), __FILE__, __LINE__, __METHOD__,9);
				if ( $c_obj->getStatus() != 30 ) {
					$pclf = TTnew( 'PermissionControlListFactory' );
					$pclf->getByCompanyId( $c_obj->getId(), NULL, NULL, NULL, array( 'name' => 'asc' ) ); //Force order to prevent references to columns that haven't been created yet.
					if ( $pclf->getRecordCount() > 0 ) {
						foreach( $pclf as $pc_obj ) {
							Debug::text('Permission Group: '. $pc_obj->getName(), __FILE__, __LINE__, __METHOD__,9);
							$plf = TTnew( 'PermissionListFactory' );
							$plf->getByCompanyIdAndPermissionControlIdAndSectionAndName( $c_obj->getId(), $pc_obj->getId(), 'report', array('view_formW2'));
							if ( $plf->getRecordCount() > 0 ) {
								Debug::text('Found permission group with view_formW2 enabled: '. $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__,9);
								$pc_obj->setPermission(
													   array(   'report' => array(
																					'view_affordable_care' => TRUE,
																				  )
															)
													   );
							} else {
								Debug::text('Permission group does NOT have view_formW2 enabled...', __FILE__, __LINE__, __METHOD__,9);
							}
						}
					}
				}
			}
		}

		//Go through all stations and disable ones that don't have any employees activated for them.
		//This greatly speeds up station checks, as most stations never have employees activated.
		$query = 'update station set status_id = 10
					where status_id = 20
					AND
					(
						( user_group_selection_type_id = 20 AND NOT EXISTS( select b.id from station_user_group as b WHERE id = b.station_id ) )
						AND
						( branch_selection_type_id = 20 AND NOT EXISTS( select c.id from station_branch as c WHERE id = c.station_id ) )
						AND
						( department_selection_type_id = 20 AND NOT EXISTS( select d.id from station_department as d WHERE id = d.station_id ) )
						AND
						NOT EXISTS( select f.id from station_exclude_user as f WHERE id = f.station_id )
						AND
						NOT EXISTS( select e.id from station_include_user as e WHERE id = e.station_id )
					)
					AND ( deleted = 0 )';
		$this->getDatabaseConnection()->Execute($query);

		return TRUE;
	}
}
?>

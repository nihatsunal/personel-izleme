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
 * $Id: EditHelp.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('help','enabled')
		OR !( $permission->Check('help','edit') OR $permission->Check('help','edit_own') ) ) {
			
	$permission->Redirect( FALSE ); //Redirect
	
}

$smarty->assign('title', TTi18n::gettext($title = 'Edit Help')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'help_data'
												) ) );

$hf = TTnew( 'HelpFactory' );
		
switch ($action) {
	case 'submit':
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$hf->setId($help_data['id']);
		$hf->setStatus($help_data['status']);
		$hf->setType($help_data['type']);
		
		if ( isset($help_data['private']) ) {
			$hf->setPrivate( TRUE );
		} else {
			$hf->setPrivate( FALSE );
		}
		
		$hf->setHeading($help_data['heading']);
		$hf->setBody($help_data['body']);
		$hf->setKeywords($help_data['keywords']);

		if ( $hf->isValid() ) {
			$hf->Save();
			
			Redirect::Page( URLBuilder::getURL(NULL, 'HelpList.php') );
			
			break;
		}
        
	default:
		if ( isset($id) ) {
			BreadCrumb::setCrumb($title);
			
			$hlf = TTnew( 'HelpListFactory' );
			
			$hlf->getById($id);
			
			foreach ($hlf as $help_obj) {
				//Debug::Arr($station,'Department', __FILE__, __LINE__, __METHOD__,10);
			
				$help_data = array(
								'id' => $help_obj->GetId(),
								'type' => $help_obj->getType(),
								'status' => $help_obj->getStatus(),
								'heading' => $help_obj->getHeading(),
								'body' => $help_obj->getBody(),
								'keywords' => $help_obj->getKeywords(),
								'private' => $help_obj->getPrivate(),								
								'created_date' => $help_obj->getCreatedDate(),
								'created_by' => $help_obj->getCreatedBy(),
								'updated_date' => $help_obj->getUpdatedDate(),
								'updated_by' => $help_obj->getUpdatedBy(),
								'deleted_date' => $help_obj->getDeletedDate(),
								'deleted_by' => $help_obj->getDeletedBy(),
								'deleted' => $help_obj->getDeleted()	
								);	
			}
		}

		//Select box options;
		$help_data['status_options'] = $hf->getOptions('status');
		$help_data['type_options'] = $hf->getOptions('type');

		$smarty->assign_by_ref('help_data', $help_data);
				
		break;
}

$smarty->assign_by_ref('hf', $hf);

$smarty->display('help/EditHelp.tpl');
?>
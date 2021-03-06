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
 * $Revision: 1396 $
 * $Id: CheckForUpdate.php 1396 2007-11-07 16:49:35Z ipso $
 * $Date: 2007-11-07 08:49:35 -0800 (Wed, 07 Nov 2007) $
 */
/*
 * Checks for any version updates...
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

//
//Check system requirements.
//
if ( PRODUCTION == TRUE ) {
	Debug::Text('Checking system requirements... '. TTDate::getDate('DATE+TIME', time() ), __FILE__, __LINE__, __METHOD__,10);
	$install_obj = new Install();
	$failed_requirment_requirements = $install_obj->getFailedRequirements( FALSE, array('clean_cache', 'file_checksums') );

	$sslf = new SystemSettingListFactory();
	$sslf->getByName('valid_install_requirements');
	if ( $sslf->getRecordCount() == 1 ) {
		$obj = $sslf->getCurrent();
	} else {
		$obj = new SystemSettingListFactory();
	}
	$obj->setName( 'valid_install_requirements' );
	if ( is_array( $failed_requirment_requirements ) AND count($failed_requirment_requirements) > 1 ) {
		$obj->setValue( 0 );
		Debug::Text('Failed system requirements: '. implode($failed_requirment_requirements), __FILE__, __LINE__, __METHOD__,10);
		TTLog::addEntry( 0, 510, 'Failed system requirements: '. implode($failed_requirment_requirements), 0, 'company' );
	} else {
		$obj->setValue( 1 );
	}
	if ( $obj->isValid() ) {
		$obj->Save();
	}
	unset($install_obj, $sslf, $obj, $check_all_requirements);
	Debug::Text('Checking system requirements complete... '. TTDate::getDate('DATE+TIME', time() ), __FILE__, __LINE__, __METHOD__,10);
}

//
// Purge database tables
//
if ( !isset($config_vars['other']['disable_database_purging'])
		OR isset($config_vars['other']['disable_database_purging']) AND $config_vars['other']['disable_database_purging'] != TRUE ) {
	PurgeDatabase::Execute();
}

//
// Clean cache directories
// - Make sure cache directory is set, and log/storage directories are not contained within it.
//
if ( !isset($config_vars['other']['disable_cache_purging'])
		OR isset($config_vars['other']['disable_cache_purging']) AND $config_vars['other']['disable_cache_purging'] != TRUE ) {

	if ( isset($config_vars['cache']['dir'])
			AND $config_vars['cache']['dir'] != ''
			AND strpos( $config_vars['path']['log'], $config_vars['cache']['dir'] ) === FALSE
			AND strpos( $config_vars['path']['storage'], $config_vars['cache']['dir'] ) === FALSE ) {

		Debug::Text('Purging Cache directory: '. $config_vars['cache']['dir'] .' - '. TTDate::getDate('DATE+TIME', time() ), __FILE__, __LINE__, __METHOD__,10);
		$install_obj = new Install();
		$install_obj->cleanCacheDirectory();
		Debug::Text('Purging Cache directory complete: '. TTDate::getDate('DATE+TIME', time() ), __FILE__, __LINE__, __METHOD__,10);
	} else {
		Debug::Text('Cache directory is invalid: '. TTDate::getDate('DATE+TIME', time() ), __FILE__, __LINE__, __METHOD__,10);
	}
}
Debug::writeToLog();
Debug::Display();
?>

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
 * $Id: Login.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */

$skip_db_error_exception = TRUE; //Skips DB error redirect
try {
	require_once('../includes/global.inc.php');
} catch(Exception $e) {
	echo 'FAIL (100)';
	exit;
}
//Debug::setVerbosity(11);

//Confirm database connection is up and maintenance jobs have run recently...
$cjlf = TTnew( 'CronJobListFactory' );
$cjlf->getMostRecentlyRun();
if ( $cjlf->getRecordCount() > 0 ) {
	$last_run_date_diff = time()-$cjlf->getCurrent()->getLastRunDate();
	if ( $last_run_date_diff > 1800 ) { //Must run in the last 30mins.
		echo 'FAIL! (200)';
		exit;
	}
}

//If caching is enabled, make sure cache directory exists and is writeable.
if ( isset($config_vars['cache']['enable']) AND $config_vars['cache']['enable'] == TRUE ) {
	if ( file_exists($config_vars['cache']['dir']) == FALSE ) {
		echo 'FAIL! (300)';
		exit;
	} else {
		if ( is_writeable( $config_vars['cache']['dir'] ) == FALSE ) {
			echo 'FAIL (310)';
			exit;
		}
	}
}

//Everything is good.
echo 'OK';
?>
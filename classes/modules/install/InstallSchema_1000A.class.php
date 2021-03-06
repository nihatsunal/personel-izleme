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
 * $Revision: 8371 $
 * $Id: InstallSchema_1000A.class.php 8371 2012-11-22 21:18:57Z ipso $
 * $Date: 2012-11-22 13:18:57 -0800 (Thu, 22 Nov 2012) $
 */

/**
 * @package Modules\Install
 */
class InstallSchema_1000A extends InstallSchema_Base {

	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion() , __FILE__, __LINE__, __METHOD__,9);

		return TRUE;
	}


	function postInstall() {
		global $config_vars;

		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__,9);

		$maint_base_path = Environment::getBasePath() . DIRECTORY_SEPARATOR .'maint'. DIRECTORY_SEPARATOR;
		if ( PHP_OS == 'WINNT' ) {
			$cron_job_base_command =  'php-win.exe '. $maint_base_path;
		} else {
			$cron_job_base_command =  'php '. $maint_base_path;
		}
		Debug::text('Cron Job Base Command: '. $cron_job_base_command, __FILE__, __LINE__, __METHOD__,9);

		// >> /dev/null 2>&1
		$cjf = TTnew( 'CronJobFactory' );
		$cjf->setName('AddPayPeriod');
		$cjf->setMinute(0);
		$cjf->setHour(0);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'AddPayPeriod.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' );
		$cjf->setName('AddUserDate');
		$cjf->setMinute(15);
		$cjf->setHour(0);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'AddUserDate.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' );
		$cjf->setName('calcExceptions');
		$cjf->setMinute(30);
		$cjf->setHour(0);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'calcExceptions.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' );
		$cjf->setName('AddRecurringPayStubAmendment');
		$cjf->setMinute(45);
		$cjf->setHour(0);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'AddRecurringPayStubAmendment.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' );
		$cjf->setName('AddRecurringHoliday');
		$cjf->setMinute(55);
		$cjf->setHour(0);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'AddRecurringHoliday.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' );
		$cjf->setName('UserCount');
		$cjf->setMinute(15);
		$cjf->setHour(1);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'UserCount.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' );
		$cjf->setName('AddRecurringScheduleShift');
		$cjf->setMinute('20,50');
		$cjf->setHour('*');
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'AddRecurringScheduleShift.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' );
		$cjf->setName('CheckForUpdate');
		$cjf->setMinute( rand(0,59) ); //Random time once a day for load balancing
		$cjf->setHour( rand(0,23) ); //Random time once a day for load balancing
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'CheckForUpdate.php');
		$cjf->Save();

		return TRUE;

	}
}
?>

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
 * $Revision: 2095 $
 * $Id: PayrollDeduction.class.php 2095 2008-09-01 07:04:25Z ipso $
 * $Date: 2008-09-01 00:04:25 -0700 (Mon, 01 Sep 2008) $
 */

/**
 * @package GovernmentForms
 */
class GovernmentForms {
	var $objs = NULL;

	var $tcpdf_dir = '../tcpdf/'; //TCPDF class directory.
	var $fpdi_dir = '../fpdi/'; //FPDI class directory.

	function __construct() {
		return TRUE;
	}

	function getFormObject( $form, $country = NULL, $province = NULL, $district = NULL ) {
		$class_name = 'GovernmentForms';
		$class_directory = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'country';
		if ( $country != '' ) {
			$class_name .= '_' . strtoupper( $country );
			$class_directory .= DIRECTORY_SEPARATOR . strtolower($country);
		}
		if ( $province != '' ) {
			$class_name .= '_' . strtoupper( $province );
			$class_directory .=  DIRECTORY_SEPARATOR . strtolower($province);
		}
		if ( $district != '' ) {
			$class_name .= '_' . strtoupper( $district );
			$class_directory .= DIRECTORY_SEPARATOR . strtolower($district);
		}
		$class_name .= '_'.$form;

		$class_file_name = $class_directory . DIRECTORY_SEPARATOR . strtolower($form) .'.class.php';
		Debug::text('Class Directory: '. $class_directory, __FILE__, __LINE__, __METHOD__, 10);
		Debug::text('Class File Name: '. $class_file_name, __FILE__, __LINE__, __METHOD__, 10);
		Debug::text('Class Name: '. $class_name, __FILE__, __LINE__, __METHOD__, 10);

		if ( file_exists( $class_file_name ) ) {
			include_once( $class_file_name );

			$obj = new $class_name;
			$obj->setClassDirectory( $class_directory );

			return $obj;
		} else {
			Debug::text('Class File does not exist!', __FILE__, __LINE__, __METHOD__, 10);
		}

		return FALSE;
	}

	function addForm( $obj ) {
		if ( is_object( $obj ) ) {
			$this->objs[] = $obj;

			return TRUE;
		}

		return FALSE;
	}

	function validateXML( $xml, $schema_file ) {
		Debug::text('Schema File: '. $schema_file, __FILE__, __LINE__, __METHOD__, 10);
		if ( class_exists( 'DomDocument' ) AND file_exists($schema_file) ) {
			libxml_use_internal_errors( TRUE );

			$dom = new DomDocument;
			$dom->loadXML( $xml );

			if ( $dom->schemaValidate( $schema_file ) ) {
				Debug::Text('Schema is valid!', __FILE__, __LINE__, __METHOD__,10);
				return TRUE;
			} else {
				Debug::Text('Schema is NOT valid!', __FILE__, __LINE__, __METHOD__,10);

				$errors = libxml_get_errors();
				foreach ( $errors as $error ) {
					Debug::Text('XML Error (Line: '. $error->line.'): '. $error->message, __FILE__, __LINE__, __METHOD__,10);
				}
				
				return array(
								'api_retval' => FALSE,
								//'api_request'
								//'api_pager'
								'api_details' => array(
												'code' => 'VALIDATION',
												'description' => $error->message,
												)
								);				
				//return FALSE;
			}
		} else {
			Debug::Text('DomDocument not available!', __FILE__, __LINE__, __METHOD__,10);
			return TRUE;
		}

		return FALSE;
	}

	function Output( $type ) {
		$type = strtolower($type);

		//Initialize PDF object so all subclasses can access it.
		//Loop through all objects and combine the output from each into a single document.
		if ( $type == 'pdf' ) {
			if ( !class_exists( 'tcpdf' ) ) {
				require_once( dirname(__FILE__). DIRECTORY_SEPARATOR . $this->tcpdf_dir . DIRECTORY_SEPARATOR .'tcpdf.php');
			}

			if ( !class_exists( 'fpdi' ) ) {
				require_once( dirname(__FILE__). DIRECTORY_SEPARATOR . $this->fpdi_dir . DIRECTORY_SEPARATOR .'fpdi.php');
			}

			$pdf = new FPDI( 'P', 'pt' );
			$pdf->setMargins(0,0,0,0);
			$pdf->SetAutoPageBreak(FALSE);
			$pdf->setFontSubsetting(FALSE);

			foreach( $this->objs as $obj ) {
				$obj->setPDFObject( $pdf );
				$obj->Output( $type );
			}

			return $pdf->Output('','S');
		} elseif ( $type == 'efile' ) {
			foreach( $this->objs as $obj ) {
				return $obj->Output( $type );
			}
		} elseif ( $type == 'xml' ) {
			//Since multiple XML sections may need to be joined together,
			//We must pass the XML object between each form and  build the entire XML object completely
			//then output it all at once at the end.
			$xml = NULL;
			$xml_schema = NULL;
			foreach( $this->objs as $obj ) {
				if ( is_object( $xml ) ) {
					$obj->setXMLObject($xml);
				}

				$obj->Output( $type );
				if ( isset($obj->xml_schema) ) {
					$xml_schema = $obj->getClassDirectory() . DIRECTORY_SEPARATOR . 'schema' . DIRECTORY_SEPARATOR . $obj->xml_schema;
				}

				if ( $xml == NULL AND is_object( $obj->getXMLObject() ) ) {
					$xml = $obj->getXMLObject();
				}
			}

			if ( is_object( $xml ) ) {
				$output = $xml->asXML();

				$xml_validation_retval = $this->validateXML( $output, $xml_schema );
				if ( $xml_validation_retval !== TRUE ) {					
					Debug::text('XML Schema is invalid! Malformed XML!', __FILE__, __LINE__, __METHOD__, 10);
					//$output = FALSE;
					$output = $xml_validation_retval;
				}
			} else {
				Debug::text('No XML object!', __FILE__, __LINE__, __METHOD__, 10);
				$output = FALSE;
			}

			return $output;
		}
	}
}
?>

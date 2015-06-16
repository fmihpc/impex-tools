<?php

// This file is part of the FMI IMPEx tools.
//
// Copyright 2014- Finnish Meteorological Institute
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

// =====================================================================================================
// 								          getMostRelevantRun.php
//
// Goes through the tree.xml file and tries to find the simulation run that best matches the given
// parameter set.
//
//	$params = array
//		string 'Object'		 	   x  Object. E.g. 'Mars', 'Earth'
//		integer 'RunCount'		  	  Number of simulation runs returned. Default = 1
//		array 'SW_parameters'      x  Associative array of solar wind parameters that are used in the
//									  comparison. Possible keys are 'SW_Density', 'SW_Bx', ....
//
//	Parameters indicated by 'x' are mandatory.
// =====================================================================================================


// -----------------------------------------------
// Criteria for sorting elements in S_diff array.
// Elements are arrays.
// -----------------------------------------------

function sort_S_diff($a, $b) {
	if ($a['S_diff'] == $b['S_diff']) return 0;
	return ($a['S_diff'] > $b['S_diff']) ? 1 : -1;
}


// ------------------------------------------------------------------------
// Parse the user given function string.
// 1. Remove all suspicious characters
// 2. Add $ sign to predefined parameter names to make them php variables.
// ------------------------------------------------------------------------

function parse_function($str, $defined_params) {
	
	$allowed_params = array_diff($defined_params, array("SW_Function", "SW_Pressure", "Solar_F10.7"));
	
	// Clean the string and leave only allowed characters
	$new_str = preg_replace('/[^a-zA-Z0-9_\.\(\)\+\-\*\/]/s', '', $str);

	// add $ sign to all predefined parameter names to make them php variables
	foreach($allowed_params as $param) {
		$new_str = str_replace($param , '$' . $param, $new_str);
	}
	return $new_str;
}


function check_func_str($str) {
	// Give some randow values to all possible input params
	$SW_Density = 5000000;
	$SW_Temperature = 80000;
	$SW_Utot = 450000;
	$SW_Bx = 10e-9;
	$SW_By = 5e-9;
	$SW_Bz = 5e-9;
	$SW_Btot = 10e-9;
	
	$status = eval("return($str);");
	if ($status === false) return false;
	if ($status == NULL) return false;
	return true;	
}

function compute_SW_Function($str) {
	global $SW_Density;
	global $SW_Temperature;
	global $SW_Utot;
	global $SW_Bx;
	global $SW_By;
	global $SW_Bz;
	global $SW_Btot;
	
	return (eval("return($str);"));
}


// -------------------------------------------
// The web service method: getMostRelevantRun
// -------------------------------------------

function getMostRelevantRun($params) {

	error_reporting(E_ERROR | E_PARSE);	// Don't report E_WARNING e.g. division by zero

	global $tree_file;				// Name of the FMI HYB tree.xml file
	global $tree_gumics_file;		// Name of the FMI GUMICS tree.xml file
		
	// --------------------------------
	// Log caller info and method name
	// --------------------------------

	log_method("getMostRelevantRun", $params);

	// --------------------------------------------------
	// Define the names of the allowed search parameters
	// --------------------------------------------------
	
	$defined_params = array(
		"SW_Density",		// Number density of H+ ions in solar wind
		"SW_Temperature",	// Temperature of H+ ions in solar wind
		"SW_Utot",			// Flow velocity of H+ ions in solar wind
		"SW_Btot",			// Total magnetic field of IMF
		"SW_Bx",			// X component of IMF
		"SW_By",			// Y component of IMF
		"SW_Bz",			// Z component of IMF
		"SW_Function",		// User defined function (= combination of predefined SW_ parameters)
		"SW_Pressure",		// Pressure of  H+ ions in solar wind
		"Solar_F10.7"		// Solar 10.7 cm flux index
	);


	// -------------------------------------------------------------------------------------
	// Default scale values for different objects. If the input parameter 'Object' is not
	// listed here then the Earth is used as the default object. Naturally user may define
	// the scale parameters as input parameters. 
	// Default scales in various planetary environments are from Slavin & Holzer:
	// Solar Wind Flow About the Terrestrial Planets Modeling Bow Shock Position and Shape,
	// Journal of Geophysical Research, Vol 86, No. A13, pp. 11401-11418, December 1, 1981. 
	// -------------------------------------------------------------------------------------

	$scale_values = array(
		"Mercury" => array(
			"SW_Density"  => 73.0e6,
			"SW_Temperature"  => 170000,
			"SW_Utot"  => 430000,
			"SW_Btot"  => 46.0e-9,
			"SW_Bx" => 46.0e-9,
			"SW_By" => 46.0e-9,
			"SW_Bz" => 46.0e-9
		),
		"Venus" => array(
			"SW_Density"  => 14.0e6,
			"SW_Temperature"  => 100000,
			"SW_Utot"  => 430000,
			"SW_Btot"  => 10.0e-9,
			"SW_Bx" => 10.0e-9,
			"SW_By" => 10.0e-9,
			"SW_Bz" => 10.0e-9
		),
		"Earth" => array(
			"SW_Density"  => 7.0e6,
			"SW_Temperature"  => 80000,
			"SW_Utot"  => 430000,
			"SW_Btot"  => 6.0e-9,
			"SW_Bx" => 6.0e-9,
			"SW_By" => 6.0e-9,
			"SW_Bz" => 6.0e-9
		),
		"Mars" => array(
			"SW_Density"  => 3.0e6,
			"SW_Temperature"  => 61000,
			"SW_Utot"  => 430000,
			"SW_Btot"  => 3.3e-9,
			"SW_Bx" => 3.3e-9,
			"SW_By" => 3.3e-9,
			"SW_Bz" => 3.3e-9
		),
		"Comet" => array(
			"SW_Density"  => 3.0e6,
			"SW_Temperature"  => 61000,
			"SW_Utot"  => 430000,
			"SW_Btot"  => 3.3e-9,
			"SW_Bx" => 3.3e-9,
			"SW_By" => 3.3e-9,
			"SW_Bz" => 3.3e-9
		)
	);
			

	// ---------------------------------------------------------------------------------------------------------
	// Check that there are no illegal input parameters defined. These should already be stripped out by SOAP ?
	// ---------------------------------------------------------------------------------------------------------

	foreach($params as $key => $value) {
		switch ($key) {
			case "Object"  : break;
			case "RunCount"    : break;
			case "SW_parameters" : break;
			default : return (log_message(ERROR_ILLEGAL_PARAM . $key)); break;
		}
	}

	if (!isset($params->{'SW_parameters'})) return(log_message(ERROR_INPUT_PARAM_NOT_DEFINED . 'SW_parameters'));
	foreach($params->{'SW_parameters'} as $sw_quantity => $parameter) {
		if (!in_array($sw_quantity, $defined_params)) return(log_message(ERROR_SW_PARAM_NOT_DEFINED . $sw_quantity));
		if (!isset($parameter->{'value'})) return(log_message(ERROR_SW_PARAM_NOT_DEFINED . $sw_quantity . "['value']"));
		foreach ((array) $parameter as $key => $value) {
			switch ($key) {
				case "value"    : break;
				case "weight"   : break;
				case "scale"    : break;
				case "function" : break;
				default : return (log_message(ERROR_ILLEGAL_EXTRA_PARAM . $key)); break;
			}
		}
	}


	// ------------------------------------------------
	// Get the input parameters and check their values
	// ------------------------------------------------

	// *** Object ***
	
	if (!isset($params->{'Object'})) return(log_message(ERROR_INPUT_PARAM_NOT_DEFINED . 'Object'));
	$object = ucwords(strtolower(trim($params->{'Object'})));	// First letter capitalized other small. e.g. "Mars"


	// *** RunCount ***
	
	$run_count =  isset($params->{'RunCount'}) ? intval($params->{'RunCount'}) : 1;


	// *** SW_parameters ***

	$scale_object = (in_array($object, array_keys($scale_values))) ? $object : "Earth";

	if (isset($params->{'SW_parameters'}->{'SW_Function'})) {
		if (!isset($params->{'SW_parameters'}->{'SW_Function'}->{'scale'})) return (log_message(ERROR_SCALE_VALUE . $key));
	}
	
	$search_params = array();	// Collect user defined parameters in here
	foreach ($params->{'SW_parameters'} as $key => $parameter) {
		$search_params[$key] = array(
			'value'    => floatval($parameter->{'value'}),
			'weight'   => isset($parameter->{'weight'}) ? floatval($parameter->{'weight'}) : 1.0,
			'scale'    => isset($parameter->{'scale'}) ? floatval($parameter->{'scale'}) : $scale_values[$scale_object][$key],
			'function' => isset($parameter->{'function'}) ? $parameter->{'function'} : ""
		);
		if ($search_params[$key]['scale'] == 0.0) return (log_message(ERROR_SCALE_VALUE . $key));
	}


	// ------------------------------------------------------------------
	// Check and handle the user defined SW_Function parameter 
	// ------------------------------------------------------------------

	if (isset($search_params['SW_Function'])) {
		$func_str_user = $search_params['SW_Function']['function'];
		if ($func_str_user !== "") {
			$func_str_php = parse_function($func_str_user, $defined_params);
			if (check_func_str($func_str_php) === false) {
				return (log_message(ERROR_FUNCTION_PARSE));
			}
		}
	}


	// ------------------------------------------------------------------
	// Now all input parameters have been checked and they should be OK.
	// ------------------------------------------------------------------

	// ----------------------------------------------------
	// Generate a json structure from the input parameters
	// ----------------------------------------------------
	
	$input_json = '{';		// Opening brackett of 'input' object
	$input_json .= '"Object" : "' . $object . '",';
	$input_json .= '"RunCount" : ' . $run_count . ',';
	$input_json .= '"SW_parameters" : {';
	
	foreach($params->{'SW_parameters'} as $sw_quantity => $parameter) {
		$input_json .= '"' . $sw_quantity . '" : {';
		foreach ((array) $parameter as $key => $value) {
			$input_json .= '"' . $key . '" : ';
			if ($key == "function")
				$input_json .= '"' . $value . '",';
			else
				$input_json .= $value . ",";			
		}
		$input_json = substr($input_json, 0, -1);		// Remove ',' at the end

		$input_json .= '},';		// Closing brackett of $sw_quantity
	}
	if (substr($input_json, -1) == ",") $input_json = substr($input_json, 0, -1);		// Remove ',' at the end
	
	$input_json .= '}';		// Closing brackett of 'SW_parameters' object
	$input_json .= '}';		// Closing brackett of 'input' object

	// -----------------------------------
	// Load the tree.xml file into memory
	// -----------------------------------


	if ($object === "Earth") {
		$tree_xml = simplexml_load_file($tree_gumics_file);
	}
	else {
		$tree_xml = simplexml_load_file($tree_file);
	}
	
	if ($tree_xml === false) return(log_message(ERROR_TREEXML_LOAD_FAIL));


	// --------------------------------------------------------------
	// Get all solar wind parameters for each run for current object
	// --------------------------------------------------------------

	$run_array = array();
	foreach($tree_xml->{'SimulationRun'} as $run ) {
		if (ucwords(strtolower(trim($run->{'SimulatedRegion'}))) === $object) {
			// Get n, T and U, vx, vy, vz
			foreach($run->{'InputPopulation'} as $InputPopulation) {
				if ($InputPopulation->{'Name'} == "solarwind_H+") {
					$SW_Density = floatval($InputPopulation->{'PopulationDensity'});
					if ($InputPopulation->{'PopulationDensity'}['Units'] == "cm^-3") $SW_Density *= 1e6;	// Convert to m^-3
					$SW_Temperature = floatval($InputPopulation->{'PopulationTemperature'});			
					$SW_Utot = floatval($InputPopulation->{'PopulationFlowSpeed'});
					if ($InputPopulation->{'PopulationFlowSpeed'}['Units'] == "km/s") $SW_Utot *= 1e3;	// Convert to m/s
				}
			}
		
			// Get B, Bx, By, Bz
			foreach($run->{'InputField'} as $InputField) {
				if ($InputField->{'Name'} == "IMF") {
					$B_array = preg_split("/[\s,]+/", trim($InputField->{'FieldValue'}));
					$SW_Bx = floatval($B_array[0]);
					$SW_By = floatval($B_array[1]);
					$SW_Bz = floatval($B_array[2]);
					if ($InputField->{'Units'} == "nT") {	// Convert to T
						$SW_Bx *= 1.0e-9;
						$SW_By *= 1.0e-9;
						$SW_Bz *= 1.0e-9;
					}
					$SW_Btot  = sqrt($SW_Bx*$SW_Bx + $SW_By*$SW_By + $SW_Bz*$SW_Bz);
				}
			}
			
			// Get Solar F10.7 flux and tilt angles
			foreach($run->{'InputParameter'} as $InputParameter) {
				if ($InputParameter->{'Name'} == "SolarFlux") {
					$SF_sw = floatval($InputParameter->{'Property'}->{'PropertyValue'});			
				}

				if ($InputParameter->{'Name'} == "Derived Parameters") {
					foreach($InputParameter->{'Property'} as $Property) {
						if (strpos($Property->{'Name'}, "xz plane") !== false) {
							$Tilt_xz_sw = floatval($Property->{'PropertyValue'});
						}
						if (strpos($Property->{'Name'}, "yz plane") !== false) {
							$Tilt_yz_sw = floatval($Property->{'PropertyValue'});
						}
					}
				}
			}
			
			// Compute the user defined SW_Function parameter 
			if (isset($func_str_php)) {
				$SW_Function = eval("return($func_str_php);");
			}

			$run_array[] = array(
				'ResourceID'   => $run->{'ResourceID'},
				'ResourceName' => $run->{'ResourceHeader'}->{'ResourceName'},
				'SW_Density'   => $SW_Density,
				'SW_Temperature'  => $SW_Temperature,
				'SW_Utot' => $SW_Utot,
// 				'vx_sw'   => -$vx_sw, 
// 				'vy_sw'   => 0.0,
// 				'vz_sw'   => 0.0,
				'SW_Btot' => $SW_Btot,
				'SW_Bx'   => $SW_Bx,
				'SW_By'   => $SW_By,
				'SW_Bz'   => $SW_Bz,
				'Solar_F10.7' => isset($SF_sw) ? $SF_sw : null,
				'Tilt_xz_sw'  => isset($Tilt_xz_sw)  ? $Tilt_xz_sw  : null, 
				'Tilt_yz_sw'  => isset($Tilt_yz_sw)  ? $Tilt_yz_sw  : null,
				'SW_Function' => isset($SW_Function) ? $SW_Function : null,
				
				'Diffs' => array(),		// Difference factor for each individual parameter
				'S_diff' => 0.0			// Sum of all difference factors
			);
		}
	}


	// ---------------------------------------------------------------
	// For all matching runs compute difference factors and their sum
	// ---------------------------------------------------------------

	for ($i = 0; $i < count($run_array) ; $i++) {
		foreach($search_params as $key => $param) {
			$diff_factor = $param['weight']*pow(($param['value'] - $run_array[$i][$key])/$param['scale'], 2);
			$run_array[$i]['Diffs'][$key] = $diff_factor;
			$run_array[$i]['S_diff'] += $diff_factor;
		}
	}

	if ($run_count < count($run_array))
		$count = $run_count;
	else
		$count = count($run_array);

	usort($run_array, "sort_S_diff");


	// ---------------------
	// Generate JSON output
	// ---------------------

	$json = '{';								// The JSON opening curly brace
	$json .= '"input" : ' . $input_json . ',';	// Add the input parameters
	$json .= '"runs" : [';						// Opening brackett of 'runs' array
	

	for ($i = 0; $i < $count ; $i++) {
		$json .= '{';
		$json .= '"ResourceID" : "' . $run_array[$i]['ResourceID'] . '",';
		$json .= '"ResourceName" : "' . $run_array[$i]['ResourceName'] . '",';
		$json .= '"S_diff" : "' . $run_array[$i]['S_diff'] . '",';
		
		// Write relevances for individual parameters
		$json .= '"S_diff_n" : {';
		$j = 0;
		foreach ($search_params as $key => $param) {
			$json .= '"' . $key . '" : "' . $run_array[$i]['Diffs'][$key] . '"';
			if ($j < count($search_params)-1) $json .= ',';
			$j++;
		}
		$json .= '},';

		// Write run values for all individual parameters
		$json .= '"Param_values" : {';
		$j = 0;
		$first = true;
		foreach ($defined_params as $key) {
			if (isset($run_array[$i][$key]) and ($run_array[$i][$key] !== null)) {
				if ($first == false) 
					{ $json .= ","; }
				else 
					$first = false;
				$json .= '"' . $key . '" : "' . $run_array[$i][$key] . '"';
			}
		}
// 		foreach ($search_params as $key => $param) {
// 			$json .= '"' . $key . '" : "' . $run_array[$i][$key] . '"';
// 			if ($j < count($search_params)-1) $json .= ',';
// 			$j++;
// 		}
		$json .= '}';	// End of Param_values
		$json .= '}';	// End of current run
		
		if ($i < $count-1) $json .= ',';
	}

	$json .= ']';	// Closing brackett of 'runs' array
	$json .= '}';	// The JSON closing curly brace

	return $json;

}	// THE END
?>

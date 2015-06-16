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

// ######################################################################################################
//
// 								          getMostRelevantRun.php
//
// ######################################################################################################
// ======================================================================================================
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
// #####################################################################################################

// =====================================================================================================
// Version 2.0  2015-05-31	First release of the new code.
// =====================================================================================================


function getMostRelevantRun($params) {

	// --------------------------------
	// Log caller info and method name
	// --------------------------------

	log_method("getMostRelevantRun", $params);


	// ----------------------------------------------------------------------------
	// Define a structure to handle Input parameters.
	// The user given values will be stored in the 'data' field.
	// The 'data' field is initially set to default value.
	// ----------------------------------------------------------------------------

	// Solar wind parameter
	$SW_parameter = [
		'value'		=> ['type' => 'float',  'mandatory' => true,  'data' => null],
		'scale'		=> ['type' => 'float',  'mandatory' => true,  'data' => null],
		'weight'	=> ['type' => 'float',  'mandatory' => false, 'data' => 1.0 ],
	];

	// Solar wind parameter with function field
	$SW_parameter_func = [
		'value'		=> ['type' => 'float',  'mandatory' => true,  'data' => null],
		'scale'		=> ['type' => 'float',  'mandatory' => true,  'data' => null],
		'weight'	=> ['type' => 'float',  'mandatory' => false, 'data' => 1.0 ],
		'function'	=> ['type' => 'string', 'mandatory' => false, 'data' => null]
	];

	$InputParams = [
		'Object'		=> ['type' => 'string',  'mandatory' => true,  'data' => null],
		'RunCount'		=> ['type' => 'integer', 'mandatory' => false, 'data' => 1],
		'SW_parameters'	=> [	// Note: PHP copies the array $SW_parameter. No reference.
			'SW_Density'	 => ['type' => 'array', 'mandatory' => false, 'data' => $SW_parameter],			// Number density of H+ ions in solar wind
			'SW_Temperature' =>	['type' => 'array', 'mandatory' => false, 'data' => $SW_parameter],			// Temperature of H+ ions in solar wind
			'SW_Utot'		 => ['type' => 'array', 'mandatory' => false, 'data' => $SW_parameter],			// Flow velocity of H+ ions in solar wind
			'SW_Btot'		 => ['type' => 'array', 'mandatory' => false, 'data' => $SW_parameter],			// Total magnetic field of IMF
			'SW_Bx'			 => ['type' => 'array', 'mandatory' => false, 'data' => $SW_parameter],			// X component of IMF
			'SW_By'			 => ['type' => 'array', 'mandatory' => false, 'data' => $SW_parameter],			// Y component of IMF
			'SW_Bz'			 => ['type' => 'array', 'mandatory' => false, 'data' => $SW_parameter],			// Z component of IMF
			'SW_Function'	 => ['type' => 'array', 'mandatory' => false, 'data' => $SW_parameter_func],	// User defined function (= combination of predefined SW_ parameters)
			'SW_Pressure'	 => ['type' => 'array', 'mandatory' => false, 'data' => $SW_parameter],			// Pressure of  H+ ions in solar wind
			'Solar_F10.7'	 => ['type' => 'array', 'mandatory' => false, 'data' => $SW_parameter],			// Solar 10.7 cm flux index
		]
	];

	$defined_params = array_keys($InputParams['SW_parameters']);


	// --------------------------------------------------
	// Handle the user given input parameters ($params).
	// --------------------------------------------------

	// Check that mandatory parameters are included in user given input parameter
	// values ($params) and copy the values of all parameters to $InputParams.
	// Check also that given parameters are of proper type.
	// Set also default values to parameters that the user has not defined.
	// Note: check_input_params does not check nor set SW_parameters.
	$InputParams = check_input_params($InputParams, $params);

	// Check the user given SW_parameters
	if (!isset($params->{'SW_parameters'})) throw_error('Client', ERROR_INPUT_PARAM_NOT_DEFINED . 'SW_parameters');
	if (count($params->{'SW_parameters'}) == 0) throw_error('Client', ERROR_INPUT_PARAM_NOT_DEFINED . 'SW_parameters');
	foreach($params->{'SW_parameters'} as $sw_quantity => $parameter) {
		if (!in_array($sw_quantity, $defined_params)) throw_error('Client', ERROR_SW_PARAM_NOT_DEFINED . $sw_quantity);
		if (!isset($parameter->{'value'})) throw_error('Client', ERROR_SW_PARAM_NOT_DEFINED . $sw_quantity . "['value']");
		foreach ((array) $parameter as $key => $value) {
			if (!in_array($key, array_keys($SW_parameter_func))) throw_error('Client', ERROR_UNDEFINED_VARIBLE . $key);
		}
	}

	// Load the tree file into memory
	$tree_xml = ($InputParams['Object']['data'] === "Earth") ? get_Tree("GUMICS") : get_Tree("HYB");


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


	// ------------------------------------------------
	// Get the input parameters
	// ------------------------------------------------

	$object    = ucwords(strtolower(trim($params->{'Object'})));	// First letter capitalized other small. e.g. "Mars"
	$run_count = isset($params->{'RunCount'}) ? intval($params->{'RunCount'}) : 1;
	$scale_object = (in_array($object, array_keys($scale_values))) ? $object : "Earth";

	// Function string must have scale value set
	if (isset($params->{'SW_parameters'}->{'SW_Function'})) {
		if (!isset($params->{'SW_parameters'}->{'SW_Function'}->{'scale'})) throw_error('Client', ERROR_SCALE_VALUE . 'SW_Function');
	}

	$search_params = array();	// Collect user defined parameters in here
	foreach ($params->{'SW_parameters'} as $key => $parameter) {
		$search_params[$key] = array(
			'value'    => floatval($parameter->{'value'}),
			'weight'   => isset($parameter->{'weight'}) ? floatval($parameter->{'weight'}) : 1.0,
			'scale'    => isset($parameter->{'scale'}) ? floatval($parameter->{'scale'}) : $scale_values[$scale_object][$key],
			'function' => isset($parameter->{'function'}) ? $parameter->{'function'} : ""
		);
		if ($search_params[$key]['scale'] == 0.0) throw_error('Client', ERROR_SCALE_VALUE . $key);
	}


	// ------------------------------------------------------------------
	// Check and handle the user defined SW_Function parameter
	// ------------------------------------------------------------------

	if (isset($search_params['SW_Function'])) {
		$func_str_user = $search_params['SW_Function']['function'];
		if ($func_str_user !== "") {
			$func_str_php = parse_function($func_str_user, $defined_params);
			if (check_func_str($func_str_php) === false) {
				throw_error('Client', ERROR_FUNCTION_PARSE);
			}
		}
	}


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

	$input_json .= '}';		// Closing brace of 'SW_parameters' object
	$input_json .= '}';		// Closing brace of 'input' object


	// --------------------------------------------------------------
	// Get all solar wind parameters for each run for current object
	// --------------------------------------------------------------

	$run_array = array();
	foreach($tree_xml->{'SimulationRun'} as $run ) {
		if (ucwords(strtolower(trim($run->{'SimulatedRegion'}))) === $object) {

			// Test if this is a single simulation run or a set of simulation runs
//			if (isset($run->{'InputParameterFileURL'})) {	// Yes, this is a set of simu runs. Get the input parameters from a VOTable file.
			if (((string) $run->{'TemporalDependence'}) == "Yes") {	// Yes, this is a set of simu runs. Get the input parameters from a VOTable file.
				$vot_file = $run->{'InputPopulation'}->{'InputTableURL'};
				$vot_file = str_replace("http://impex-fp7.fmi.fi", "/var/www", $vot_file);	// Replace URL with local directory
				$vot_xml = simplexml_load_file($vot_file);
				if ($vot_xml === false) throw_error('Server',ERROR_TREEXML_LOAD_FAIL);

				// Number of data points
				$data_count = $vot_xml->{'RESOURCE'}->{'TABLE'}->{'DATA'}->{'TABLEDATA'}->{'TR'}->count();

				// Read the VOT file field names
				$field_name  = array();
				$field_value = array();
				foreach($vot_xml->{'RESOURCE'}->{'TABLE'}->{'FIELD'} as $field) {
					$name = (string) $field['name'];
					$field_name[] = $name;
					$field_value[$name] = 0.0;
				}

				// Go through all simulation runs in the VOT file
				// Each <TR> is a single run
				$row = 0;
				foreach($vot_xml->{'RESOURCE'}->{'TABLE'}->{'DATA'}->{'TABLEDATA'}->{'TR'} as $TR) {
					$col = 0;
					foreach ($TR->{'TD'} as $TD)		// Go through all fields
						$field_value[$field_name[$col++]] = (string) $TD;

					$ResourceName = $field_value['ResourceName'];
					$sub_ResourceID = $field_value['sub_ResourceID'];
					$dateTime = $field_value['Time'];
					$SW_Density = floatval($field_value['Density']);
					$SW_Temperature = floatval($field_value['Temperature']);
					$SW_Ux = floatval($field_value['Ux']);
					$SW_Uy = floatval($field_value['Uy']);
					$SW_Uz = floatval($field_value['Uz']);
					$SW_Bx = floatval($field_value['Bx']);
					$SW_By = floatval($field_value['By']);
					$SW_Bz = floatval($field_value['Bz']);
					$SW_Utot = sqrt($SW_Ux*$SW_Ux + $SW_Uy*$SW_Uy + $SW_Uz*$SW_Uz);
					$SW_Btot = sqrt($SW_Bx*$SW_Bx + $SW_By*$SW_By + $SW_Bz*$SW_Bz);

					// Compute the user defined SW_Function parameter
					if (isset($func_str_php)) {
						$SW_Function = eval("return($func_str_php);");
					}

					$run_array[] = array(
						'ResourceID'   => $run->{'ResourceID'} . "?" . $sub_ResourceID,
						'ResourceName' => $ResourceName,
						'NumericalOutput' => array(),
						'SW_Density'   => $SW_Density,
						'SW_Temperature'  => $SW_Temperature,
						'SW_Utot' => $SW_Utot,
						'SW_Btot' => $SW_Btot,
						'SW_Bx'   => $SW_Bx,
						'SW_By'   => $SW_By,
						'SW_Bz'   => $SW_Bz,
						'Solar_F10.7' => null,
						'Tilt_xz_sw'  => null,
						'Tilt_yz_sw'  => null,
						'SW_Function' => isset($SW_Function) ? $SW_Function : null,

						'Diffs' => array(),		// Difference factor for each individual parameter
						'S_diff' => 0.0			// Sum of all difference factors
					);

					$row++;
				}


			}
			else {		// This is a single simulation run

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
						$SW_Btot = sqrt($SW_Bx*$SW_Bx + $SW_By*$SW_By + $SW_Bz*$SW_Bz);
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
					'NumericalOutput' => array(),
					'SW_Density'   => $SW_Density,
					'SW_Temperature'  => $SW_Temperature,
					'SW_Utot' => $SW_Utot,
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
		$json .= '"NumericalOutput" : [';
		$j = 0;
		foreach($run_array[$i]['NumericalOutput'] as $NO) {
			$json .= '"' . $NO . '"';
			if ($j < count($run_array[$i]['NumericalOutput']) - 1)  $json .= ',';
			$j++;
		}
		$json.= '],';
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
		$json .= '}';	// End of Param_values
		$json .= '}';	// End of current run

		if ($i < $count-1) $json .= ',';
	}

	$json .= ']';	// Closing brackett of 'runs' array
	$json .= '}';	// The JSON closing curly brace

	return $json;

}	// THE END


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
?>

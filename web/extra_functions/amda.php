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

// ##########################################################################
//
//	              					amda.php
//
// ##########################################################################

// ============================================================================
// This php script contains functions for downloading spacecraft orbital data
// from AMDA web services.
// =============================================================================

define('AMDA_WSDL', 'http://cdpp1.cesr.fr/AMDA-NG/public/wsdl/Methods_AMDA.wsdl'); 	// AMDA web services

/**
* --------------------------------------------------------------------------
* Get spacecraft data from AMDA using AMDA web services.
* Here we grab the data in ASCII format. Other possibility is 'VOTable'.
*
* @param object $InputParams : Object defining all possible input variables
* @throws SoapFault          : If AMDA web services fail
* --------------------------------------------------------------------------
*/

function get_spacecraft_orbit_AMDA($Format, $InputParams) {

	// Convert interval (PT600S) into seconds
	$dv = new DateInterval($InputParams['Sampling']['data']);
	$secs = $dv->s + 60*$dv->i + 3600*$dv->h + 24*3600*$dv->d;

	// Get the orbit data file from AMDA
	$client = new SoapClient(AMDA_WSDL, array('cache_wsdl' => WSDL_CACHE_NONE));
	$AMDA_params = array(
		"userID" 	   => 'impex',
		"startTime"    => $InputParams['StartTime']['data'],
		"stopTime"     => $InputParams['StopTime']['data'],
		"sampling"     => $secs,
		"parameterID"  => $GLOBALS['Spacecrafts'][$InputParams['Spacecraft_name']['data']],
		"outputFormat" => "ASCII"
	);

	try {
		$data_url = $client->getParameter($AMDA_params);
	}
	catch (Exception $e) {
		throw_error('Server', ERROR_AMDA);
		exit();
	}

	// Download the file to local directory
	$amda_file_name = get_URL($data_url->dataFileURLs);

	// Check that the orbit is given in the same coordinate system as the simulation
	// and change the units of position coordinates from planetary radius to meters.
	$point_file_name = check_and_fix_AMDA($amda_file_name);

	clean_up([$amda_file_name]);	// Remove the temporary file

	if ($Format == "VOTable")
		return (amda_ascii_to_votable($point_file_name));
	else
		return ($point_file_name);
}


/**
* --------------------------------------------------------------------------------------
* Check that the spacecraft ephemeris data is in the same coordinate system
* as used in the simulation run.
* Also convert the units of position coordinates from planetary radius to
* meters.
* The resulting data file has four columns (Time, X, Y and Z).
*
* @param string $amda_file : Name (including path) of the local copy of AMDA data file
* @return string           : Name (including path) of the generated data file
* @throws SoapFault        : If coordinate systems don't match.
* -------------------------------------------------------------------------------------
*/

function check_and_fix_AMDA($amda_file) {
	global $metadata;

	$Format = get_file_format($amda_file);
	if ($Format == "ASCII") {
		$data_filename = TMP_DIR . "/hwa_" . random_string(10) . ".txt";
		$data_file = fopen($data_filename, "w");
		$coeff = $metadata['planet_radius'];

		$lines = file($amda_file);	// Read all lines into an array
		foreach($lines as $line) {
			if ($line[0] == "#") {
				// Comment line
				if (strpos($line, "AMDA") !== false) {	// Check the coordinate systems
					// Line is something like:
					// #mex_xyz - Type : Local Parameter @ CDPP/AMDA - Name : xyz_mso - Units : Rm - Size : 3 - Frame : MSO - Mission : MEX - Instrument : ephemeris - Dataset : orbit
					$params = explode("-", $line);
					$param_array = array();
					for ($i = 1; $i < count($params); $i++) {
						$key_value = explode(":",$params[$i]);
						$param_array[trim($key_value[0])] = trim($key_value[1]);
					}
					if (isset($param_array['Frame'])) {
						if ($param_array['Frame'] != $metadata['coordinate_system']) throw_error('Server', ERROR_AMDA_COORD . ": " . $metadata['coordinate_system'] . " vs. " . $param_array['Frame']);
					}
				}
			}
			else {
				// Data line:
				// 2011-01-10T12:05:00.000      2.48460     -2.27544      1.54321
				$comps = preg_split("/[\s]+/", $line);
				fprintf($data_file, "%s %e %e %e\n", $comps[0], $coeff*floatval($comps[1]), $coeff*floatval($comps[2]), $coeff*floatval($comps[3]));
			}
		}
		fclose($data_file);
		return($data_filename);
	}
	throw_error('Server', ERROR_AMDA);		// If format is not 'ASCII'
}


/**
* --------------------------------------------------------------------------------------
* Convert an AMDA returned ascii file into a VOTable file.
* This routine converts the units of position coordinates from planetary radius to
* meters.
* The resulting VOTable file has four fields (Time, X, Y and Z).
*
* @param string $point_file_name  : Name (including path) of the local copy of AMDA data file
* @return string                  : Name (including path) of the generated data file
* @throws SoapFault               : If conversion fails.
* -------------------------------------------------------------------------------------
*/

function amda_ascii_to_votable($point_file_name) {

	// Read the $point_file_name file into memory
	$orbit_table = file($point_file_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	clean_up([$point_file_name]);	// It is no longer needed

	// Define an array for all items:
	// 0   : Time
	// 1-3 : Position coordinates X, Y and Z
	$field_values = array();
	for ($i=0; $i<4; $i++) $field_values[$i] = array();

	foreach ($orbit_table as $line) {
		if ((substr($line,0,1) != "#") and ($line != "")) {
			$fields = preg_split("/[\s]+/", $line);
			$field_values[0][] = (substr($fields[0], -1) == "Z") ? $fields[0] : $fields[0] . "Z";
			for ($i=1;$i<4;$i++) $field_values[$i][] = $fields[$i];
		}
	}

	// Call the getVOTableURL method to convert data into VOTable file
	$getVOTable_params = array(
		'Table_name'  => "Spacecraft_orbit",
		'Description' => "AMDA provided spacecraft orbit data file converted to VOTable format by FMI web service" . "\n",
		'Fields'      => array(
			array('name' => 'Time', 'data' => $field_values[0]),
			array('name' => 'X', 'data' => $field_values[1]),
			array('name' => 'Y', 'data' => $field_values[2]),
			array('name' => 'Z', 'data' => $field_values[3]),
		)
	);

	$client = new SoapClient(METHODS_FILE_URL,  array('cache_wsdl' => WSDL_CACHE_NONE));

	try {
		$data_url = $client->getVOTableURL($getVOTable_params);
	}
	catch (Exception $e) {
		throw_error('Server', $e->getMessage());
		exit();
	}

	return(WWW_DATA_DIR . "/" . basename($data_url));
}

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
//	              				misc_functions.php
//
// ##########################################################################

// ==========================================================================
// This file defines some miscellaneous functions used by other php scripts.
// ==========================================================================


/**
* -----------------------------------------------------------------
* Generate a random string of specified length.
*
* @param integer $length  : Length of the resulting random string.
* @return string          : A random string of length $length
* -----------------------------------------------------------------
*/

function random_string($length) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	return (substr(str_shuffle($chars), 0, $length));
}


/**
* -----------------------------------------------------------------
* Generate a unit vector from a general vector
*
* @param array $vector  : Three component array of floats
* @throws SoapFault     : If vector length is zero
* @return array         : Three component array of unit length
* -----------------------------------------------------------------
*/

function unit_vector($vector) {
	$length = 0.0;
	$unit_v = array();
	for($i=0; $i<3; $i++) $length += pow($vector[$i], 2);
	if ($length == 0.0) throw_error('Client', ERROR_NORMAL_VECTOR_LEN);
	for($i=0; $i<3; $i++) $unit_v[$i] = $vector[$i]/sqrt($length);
	return $unit_v;
}


/**
* --------------------------------------------------------------------
* Generate a random string of specified length.
*
* @param file $log_file  : A file variable.
* @param string $key     : Name of the parameter
* @param object $value   : Value of the parameter (simple or complex)
* @param integer $intend : Intendation used in writing result to file
* --------------------------------------------------------------------
*/

function print_param($log_file, $key, $value, $intend) {
	$intend_base = "     ";
	for($i=0; $i<$intend; $i++) $intend_base .= "    ";

	if (is_object($value) || is_array($value)) {
		fwrite($log_file, $intend_base . $key . " => array (" . "\n");
		foreach ($value as $key1 => $value1) {
			print_param($log_file, $key1, $value1, $intend+1);
		}
		fwrite($log_file, $intend_base . ")" . "\n");
	}
	else {
		if (is_string($value) && (strlen($value) > 200)){
			// Probably a list of doubles ...
			$value_array = preg_split("/[\s,]+/", $value);
			fwrite($log_file, $intend_base . $key . " => array(" . count($value_array) . ")" . "\n");
		}
		else
			fwrite($log_file, $intend_base . $key . " => " . $value . "\n");
	}
}


/**
* -----------------------------------------------------------------------
* Log the name of the method and given parameters into log file.
* This is called before the request is processed.
*
* @param string $method_name  : Name of the called method
* @param object $params       : Input parameters to the methods (complex)
* ------------------------------------------------------------------------
*/

function log_method($method_name, $params) {
	global $_SERVER;

	$log_file = fopen(LOG_FILE, "a");
	if ($log_file === FALSE) return;
	$Date = date("j.n.Y H:i:s");
	$ip   = $_SERVER['REMOTE_ADDR'];
	$name = gethostbyaddr($ip);
	fwrite($log_file, "\n" . $Date . "  IP:" . $ip . " NAME: " . $name . "\n");
	fwrite($log_file, "    - " . $method_name . "\n");
	foreach ($params as $key => $value) {
		print_param($log_file, $key, $value, $intend = 1);
	}
	fclose($log_file);
}


/**
* -----------------------------------------------------------------------
* Write the name of the method and returned value to a log file.
* This is called after the request has been processed.
* The function returns the same input parameter ($message) that it
* was called with. This is because it is always called at the end
* of the function calls as : return (log_message($message)).
*
* @param string $message  : Returned value of the method. Typically an url.
* @return string          : Returns the input parameter $message.
* ------------------------------------------------------------------------
*/

function log_message($message) {
	global $_SERVER;

	$log_file = fopen(LOG_FILE, "a");
	if ($log_file === FALSE) return;
	$Date = date("j.n.Y H:i:s");
	$ip   = $_SERVER['REMOTE_ADDR'];
	$name = gethostbyaddr($ip);
	fwrite($log_file, $Date . "  IP:" . $ip . " NAME: " . $name);
	fwrite($log_file, "      returned value : " . $message . "\n");
	fclose($log_file);
	return ($message);
}


/**
* ---------------------------------------------------------------
* Write a given string into the debugging file.
* This is called before the request is processed.
*
* @param string $string  : String to be written into debug file.
* ---------------------------------------------------------------
*/

function debug_string($string) {
	$debug_file = fopen(DEBUG_FILE, "a");
	if ($debug_file === FALSE) return;
	fwrite($debug_file, $string . "\n");
	fclose($debug_file);
}


/**
* ----------------------------------------------------------------------------------
* Copy some metadata about the simulation run into $metadata variable
*
* @param SimpleXMLElement $tree_xml            : The whole tree
* @param SimpleXMLElement $simulation_run_elem : The SimulationRun element
* @param SimpleXMLElement $num_output_elem     : The NumericalOutput element
* @param array $InputParams                    : User given input parameters
* -----------------------------------------------------------------------------------
*/

function get_metadata($tree_xml, $simulation_run_elem, $num_output_elem, $InputParams) {
	global $metadata;

	$metadata['Title']               = (string) $tree_xml->{'SimulationModel'}->{'ResourceHeader'}->{'ResourceName'};
	$metadata['SimulationModel']     = (string) $simulation_run_elem->{'Model'}->{'ModelID'};
	$metadata['SimulationRun']       = (string) $simulation_run_elem->{'ResourceID'};
	$metadata['SimulationRun_Name']  = (string) $simulation_run_elem->{'ResourceHeader'}->{'ResourceName'};
	$metadata['NumericalOutput']     = (string) $num_output_elem->{'ResourceID'};
	$metadata['dir_name']			 = basename((string) $num_output_elem->{'ResourceID'});
	$metadata['Output_description']  = (string) $num_output_elem->{'ResourceHeader'}->{'Description'};
	$metadata['planet_name']         = (string) $simulation_run_elem->{'RegionParameter'}->{'SimulatedRegion'};
	$metadata['planet_radius']       = (string) $simulation_run_elem->{'RegionParameter'}->{'Radius'};
	$metadata['planet_radius_unit']  = (string) $simulation_run_elem->{'RegionParameter'}->{'Radius'}['Units'];
	$metadata['coordinate_system']   = (string) $simulation_run_elem->{'SimulationDomain'}->{'CoordinateSystem'}->{'CoordinateSystemName'};
	$metadata['Simulation_TimeStep'] = floatval(str_replace(array("PT","S"), array("",""), (string) $simulation_run_elem->{'SimulationTime'}->{'TimeStep'}));

	if ($metadata['planet_radius_unit'] == "km") {
		$metadata['planet_radius_unit'] = "m";
		$metadata['planet_radius'] = intval(1000.0*floatval($metadata['planet_radius']));
	}

	if (isset($InputParams['Spacecraft_name'])) {
		$metadata['spacecraft'] = $InputParams['Spacecraft_name']['data'];
		$metadata['starttime']  = $InputParams['StartTime']['data'];
		$metadata['stoptime']   = $InputParams['StopTime']['data'];
		$metadata['sampling']   = $InputParams['Sampling']['data'];
	}

	if (isset($InputParams['extraParams']['InterpolationMethod'])) $metadata['InterpolationMethod'] = $InputParams['extraParams']['InterpolationMethod']['data'];
	if (isset($InputParams['extraParams']['OutputFileType'])) $metadata['OutputFileType'] = $InputParams['extraParams']['OutputFileType']['data'];
}


/**
* ----------------------------------------------------------------------------
* Try to locate the nearest (in time) hc-file for given datetime in the
* GUMICS dynamical runs.
*
* @param string $sub_ResID  : The date string (YYYYMMDD_HHMMSS) in ResourceID
* ----------------------------------------------------------------------------
*/

function locate_hc_file($sub_ResID) {
	// search 3 minutes forward and backward in time
	$secs = strtotime(str_replace("_", "T", substr($sub_ResID,0,13)));	// Cut seconds away
	foreach ([0, 1, -1, 2, -2, 3, -3] as $offset) {
		$datestr = date('Ymd_His', $secs + 60*$offset);
		$hc_file = GUMICS_DYN_DIR . "/" . substr($datestr,0,8) . "/mstate" . $datestr . ".hc";
		if (is_file($hc_file)) {
			return $hc_file;
		}
	}
	throw_error('Server', ERROR_NO_HC_FILE);
}


/**
* ------------------------------------------------------------------------
* Run the hc_intpol command with given input parameters to compute
* interpolated values of physical quantities at given data points.
*
* @param string $hc_param_list : Comma separated list of physical
*                                 quantities
* @param string $hc_data_file  : Name of the .hc data file
* @param string $hc_input_file : Name of the file containing X,Y and Z
*                                 position coordinates for each point.
* @throws SoapFault            : If hcintpol fails for some reason.
* @return string               : Full path to the interpolated data file.
* ------------------------------------------------------------------------
*/

function run_hc_intpol($hc_param_list, $hc_data_file, $hc_input_file, $InterpolationMethod) {
	$z = ($InterpolationMethod == "NearestGridPoint") ? " -z " : " ";
	$hc_output_file = TMP_DIR . "/hwa_" . random_string(10) . "_intpol.txt";		// Output file containing interpolated data
//	$hc_output_file = tempnam(TMP_DIR, "hwa_") . "_intpol";		// Output file containing interpolated data
	$command = HCINTPOL . " -v " . $hc_param_list . $z . $hc_data_file . " < " . $hc_input_file . " > " . $hc_output_file;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_HCINTPOL);
	return($hc_output_file);
}


/**
* --------------------------------------------------------------------
* Check if a given point is inside a given region.
*
* @param array $point  : Array of X ([0]), Y ([1]) and Z ([2]).
* @param array $region : An array defining a cube.
*                        Elements Xmin, Xmax, Ymin, Ymax, Zmin,Z max.
* @return boolean      : True if point is inside the region.
* --------------------------------------------------------------------
*/

function point_inside($point, $region) {
	if ((floatval($point[0]) <= $region[0]) or (floatval($point[0]) >= $region[1])) return false;	// X
	if ((floatval($point[1]) <= $region[2]) or (floatval($point[1]) >= $region[3])) return false;	// Y
	if ((floatval($point[2]) <= $region[4]) or (floatval($point[2]) >= $region[5])) return false;	// Z
	return true;
}


/**
* --------------------------------------------------------------------
* If an error is detected during web service execution a SoapFault
* exception is thrown with information about the error.
*
* @param string $code      : 'Client' or 'Server' depending on
*                            the reason of the error.
* @param string $error_str : Detailed information about the error.
* @throws SoapFault        : Throws an exception every time.
* --------------------------------------------------------------------
*/

function throw_error($code, $error_str) {
	log_message($error_str);
	throw new SoapFault($code, $error_str, 'FMI');
}


/**
* ---------------------------------------------------------------------------------------
* Handle the actual interpolation of values of the physical quantities at the points
* specified in the user provided data file. For FMI the interpolation is done using
* hcintpol command.
*
* @param string $point_file_name      : Name of the user provided original data file
* @param object $num_output_elem      : SimpleXML element
* @param object $simulation_run_elem  : SimpleXML element
* @param object $InputParams          : Object containing input parameters to the method
* @return string                      : Name of the final interpolated data file
* @throws SoapFault                   : Throws an exception if an error occurs
* ---------------------------------------------------------------------------------------
*/

function handle_interpolation($point_file_name, $num_output_elem, $simulation_run_elem, $InputParams) {

	// -------------------------------------------------------------------------------
	// The hcintpol command is used to compute interpolated values for given data set.
	// For this command the input data set must be converted to a simple text file
	// with one data point per line and the coordinates of data points (X,Y,Z) in
	// columnns 1, 2 and 3. The units of X, Y and Z must be meter (m).
	// hcintpol returns the results in similar text format file with one column
	// added for each interpolated variable.
	// --------------------------------------------------------------------------------


	// Convert input data file to simple three column ascii format file
	$hc_input_file = convert_to_hc_ascii($point_file_name);

	// Get the name of the simulation run hc datafile
	$hc_data_file = get_hc_file($InputParams['ResourceID']['data'], $num_output_elem);

	// Convert variable names into those used by hcintpol
	// and generate a comma separated list of them.
	$hc_param_list = get_hc_variable_names(array_keys($InputParams['Variable']['data']));

	// Run the hcintpol command
	$hc_output_file = run_hc_intpol($hc_param_list, $hc_data_file, $hc_input_file, $InterpolationMethod = $InputParams['extraParams']['InterpolationMethod']['data']);


	// ----------------------------------------------------
	// If we have GUMICS earth run, then for all points
	// where r < r_inner_boundary mark data values as NaN.
	// ----------------------------------------------------

	if (get_simu_model() == "GUMICS") {
		handle_gumics_inner_boundary($simulation_run_elem, $hc_output_file, $remove_line = false, $skip_cols = 0);
	}

	// -------------------------------------------------
	// Convert interpolated ascii file to output format
	// -------------------------------------------------

	$final_file_name = convert_to_final($point_file_name, $hc_output_file, $InputParams);

	clean_up([$hc_input_file, $hc_output_file]);

	return ($final_file_name);
}


/**
* ---------------------------------------------------------------------------------------
* Handle the actual interpolation of values of the physical quantities at the points
* specified in the user provided data file. For FMI the interpolation is done using
* hcintpol command.
* For dynamical runs time is included as one the parameters and therefore the
* selected .hc file depends on the time.
*
* @param string $point_file_name      : Name of the user provided original data file
* @param object $simulation_run_elem  : SimpleXML element
* @param object $InputParams          : Object containing input parameters to the method
* @return string                      : Name of the final interpolated data file
* @throws SoapFault                   : Throws an exception if an error occurs
* ---------------------------------------------------------------------------------------
*/

function handle_dynamical_interpolation($point_file_name, $simulation_run_elem, $InputParams) {

	// Load the datetimes (unix seconds) of all GUMICS 10^5 runs into an array
	$run_datetime = file(GUMICS_DATETIME, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

	// Some variable declarations
	$tmp_file_name  = $point_file_name . "_tmp.txt";		// File containing interpolated data
	$hc_input_file  = $point_file_name . "_XYZ.txt";		// File containing single input data line for hcintpol
	$hc_output_file = $point_file_name . "_intpol.txt";		// Output file containing all interpolated data

	$hc_param_list = get_hc_variable_names(array_keys($InputParams['Variable']['data']));
	$InterpolationMethod = $InputParams['extraParams']['InterpolationMethod']['data'];
	$z = ($InterpolationMethod == "NearestGridPoint") ? " -z " : " ";
	$last_index = 0;
	$header_written = false;

	// Write the default header line to hcintpol's output file
	// It will be adjusted when first data line is written
	file_put_contents($hc_output_file, "# x y z " . str_replace("," , " ", $hc_param_list) . "\n");

	// ------------------------------------------------------
	// Go through all times in the satellite orbit data file
	// ------------------------------------------------------

	$spacecraft_trajectory = file($point_file_name);	// Read all lines into an array

	foreach($spacecraft_trajectory as $line) {

		if ($line[0] == '#') continue;
		$data = preg_split("/[\s]+/", trim($line));	// $data[0] = time, $data[1] = X, ...
		$spacecraft_time = strtotime($data[0]);

		$sub_ResID = "";
		$data_missing = false;

		// Locate the nearest time from the $run_array
		if ($spacecraft_time < $run_datetime[0]) $data_missing = true;
		if ($spacecraft_time > $run_datetime[count($run_datetime) - 1]) $data_missing = true;

		if (!$data_missing) {
			// Locate the times of previous and next simulation runs
			for ($i = $last_index; $i < count($run_datetime); $i++) {
				if ($spacecraft_time <= $run_datetime[$i]) {
					$dt1 = $spacecraft_time - $run_datetime[$i-1];	// Time diff to previous run time
					$dt2 = $run_datetime[$i] - $spacecraft_time;	// Time diff to next run time
					$sub_ResID = ($dt1 < $dt2) ? date("Ymd_His", $run_datetime[$i-1]) : date("Ymd_His", $run_datetime[$i]);
					$last_index = $i;
					break;
				}
			}

			$hc_data_file = GUMICS_DYN_DIR . "/" . substr($sub_ResID,0,8) . "/mstate" . $sub_ResID . ".hc";

			// Create input file for hcintpol
			$status = file_put_contents($hc_input_file, $data[1] . " " . $data[2] . " " . $data[3] . "\n");		// Write X, Y and Z
			if ($status == false) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

			// Execute hcintpol
			$command = HCINTPOL . " -v " . $hc_param_list . $z . $hc_data_file . " < " . $hc_input_file . " >> " . $tmp_file_name;
			exec($command, $output, $return_var);
			if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_HCINTPOL);

			// If the header has not been written then copy it from the output of the last hcintpol command
			// This is necessary as the hcintpol may write the parameters in different order as given in $hc_param_list
			if (!$header_written) {
				exec("sed -n 'x;" . '$p' . "' " . $tmp_file_name . " > " . $hc_output_file);	// Prints the second to last line
				$header_written = true;
			}
		}
		else {
			// No data for this time, write missing data markers
			$data_line =  $data[1] . " " . $data[2] . " " . $data[3];
			for ($i = 0; $i <= substr_count($hc_param_list, ','); $i++)  $data_line .= " -999";
			$status = file_put_contents($tmp_file_name, $data_line . "\n", FILE_APPEND);
			if ($status == false) throw_error('Server', ERROR_INTERNAL_TMP_FILE);
		}
	}

	exec("sed '/^#/d' " . $tmp_file_name . " >> " . $hc_output_file);	// Add data lines after the header line

	// We have GUMICS earth run, then for all points where r < r_inner_boundary mark data values as NaN.
	handle_gumics_inner_boundary($simulation_run_elem, $hc_output_file, $remove_line = false, $skip_cols = 0);

	$final_file_name = convert_to_final($point_file_name, $hc_output_file, $InputParams);

	clean_up([$tmp_file_name, $hc_input_file, $hc_output_file]);

	return ($final_file_name);
}


/**
* ---------------------------------------------------------------------------------------
* Get the basic (i.e. without any adaptions) grid cell size used in the simulation run.
* The element <GridCellSize> in the tree.xml gives the smallest cell size (i.e. with
* adaption).
*
* @param object $simulation_run_elem  : SimpleXML element
* @return float                       : Basic grid cell size
* ---------------------------------------------------------------------------------------
*/

function get_basic_grid_cell_size($simulation_run_elem) {
	$grid_structure = (string) $simulation_run_elem->{'SimulationDomain'}->{'GridStructure'};
	$grid_cell_size = preg_split("/[\s,]+/", (string) $simulation_run_elem->{'SimulationDomain'}->{'GridCellSize'});
	if ($grid_structure == "Constant") {
		return $grid_cell_size[0];
	}
	else {
		$grid_structure_array = preg_split("/[\s,]+/", $grid_structure);
		$levels = intval($grid_structure_array[1]);
		return(pow(2, $levels)*floatval($grid_cell_size[0]));
	}
}


/**
* -------------------------------------------------------------------------------------------
* Generate a plane mesh with given cell size ($resolution) and in given plane and
* the mesh into an ascii data file readable by hcintpol.
*
* @param object $simulation_run_elem  : SimpleXML element
* @param array $plane_point           : Three component vector defining a point in the plane
* @param string $plane                : Plane, either "XY", "YZ", or "XZ"
* @param float $resolution            : Size of single cell in the mesh. Cells are squares.
* @return float                       : Basic grid cell size
* @throws SoapFault                   : Throws an exception if file can't be written
* --------------------------------------------------------------------------------------------
*/

function write_plane_mesh($simulation_run_elem, $plane_point, $plane, $resolution) {

	// Create the 'ascii' file
//	$ascii_filename = tempnam("/tmp", "hwa_") . ".txt";
	$ascii_filename = TMP_DIR . "/hwa_" . random_string(10) . ".txt";
	$ascii_file = fopen($ascii_filename, "w");
	if ($ascii_file === FALSE) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	// Get the simulation box limits from tree.xml
	$box_min = preg_split("/[\s,]+/", $simulation_run_elem->{'SimulationDomain'}->{'ValidMin'});
	for($i=0; $i<3; $i++) $box_min[$i] = floatval($box_min[$i]);
	$box_max = preg_split("/[\s,]+/", $simulation_run_elem->{'SimulationDomain'}->{'ValidMax'});
	for($i=0; $i<3; $i++) $box_max[$i] = floatval($box_max[$i]);

	if ($plane == "XY") {
		$z = $plane_point[2];	// Z coordinate is fixed
		for ($x = $box_min[0]; $x <= $box_max[0]; $x += $resolution) {
			for ($y = $box_min[1]; $y <= $box_max[1]; $y += $resolution) {
				fprintf($ascii_file, "%e %e %e\n", $x, $y, $z);
			}
		}
	}

	if ($plane == "YZ") {
		$x = $plane_point[0];	// X coordinate is fixed
		for ($y = $box_min[1]; $y <= $box_max[1]; $y += $resolution) {
			for ($z = $box_min[2]; $z <= $box_max[2]; $z += $resolution) {
				fprintf($ascii_file, "%e %e %e\n", $x, $y, $z);
			}
		}
	}

	if ($plane == "XZ") {
		$y = $plane_point[1];	// Y coordinate is fixed
		for ($x = $box_min[0]; $x <= $box_max[0]; $x += $resolution) {
			for ($z = $box_min[2]; $z <= $box_max[2]; $z += $resolution) {
				fprintf($ascii_file, "%e %e %e\n", $x, $y, $z);
			}
		}
	}

	fclose($ascii_file);
	return ($ascii_filename);
}


/**
* ---------------------------------------------------------------------------------------
* Write information about single input parameter.
*
* @param string $key          : Name of the input parameter
* @param object $value        : Value of the input parameter (may be array)
* @param integer $intend      : Intendation (= number of space blocks)
* @return string              : Description text
* ---------------------------------------------------------------------------------------
*/

function write_param($key, $value, $intend) {
	$str = "";
	$intend_base = "     ";
	for($i=0; $i<$intend; $i++) $intend_base .= "    ";

	if (!in_array('data', array_keys($value))) {
		$str .= $intend_base . $key . " => [" . "\n";
		foreach ($value as $key1 => $value1) {
			$str .= write_param($key1, $value1, $intend + 1);
		}
		$str .= $intend_base . "]" . "\n";

	}
	else {
		$data = $value['data'];
		if (is_array($data)) {
			$str .=  $intend_base . $key . " => [" . implode(", ", array_keys($data)) . "]" . "\n";
		}
		else {
			$str .=  $intend_base . $key . " => " . $data . "\n";
		}
	}
	return ($str);
}


/**
* ---------------------------------------------------------------------------------------
* Write the input parameters as clean text block to be included in VOTable description.
*
* @param object $InputParams          : Object containing input parameters to the method
* @return string                      : Description text
* ---------------------------------------------------------------------------------------
*/

function write_InputParams($InputParams) {
	$str = "";
	foreach ($InputParams as $key => $value) {
		$str .= write_param($key, $value, $intend = 1);
	}
	return ($str);
}


/**
* ---------------------------------------------------------------------------------------
* Write information about the simulation to VOTable <DESCRIPTION> element.
*
* @param string $Description_header   : Title of the description text
* @param object $simulation_run_elem  : SimpleXML element
* @param object $InputParams          : Object containing input parameters to the method
* @return string                      : Description text
* ---------------------------------------------------------------------------------------
*/

function write_VOTable_info($Description_header, $InputParams) {
	global $metadata;				// Array containing some metadata

	$Description = $Description_header . "\n";
	$Description .= '  SimulationModel            : ' . $metadata['Title'] . "\n";
	$Description .= '  SimulationModel_ResourceID : ' . $metadata['SimulationModel'] . "\n";
	$Description .= '  SimulationRun_ResourceID   : ' . $metadata['SimulationRun'] . "\n";
	$Description .= '  NumericalOutput_ResourceID : ' . $metadata['NumericalOutput'] . "\n";
	$Description .= '  Content description        : ' . $metadata['Output_description'] . "\n";
	$Description .= '  Object                     : ' . $metadata['planet_name'] . "\n";
	$Description .= '  Object radius              : ' . $metadata['planet_radius'] . " " . $metadata['planet_radius_unit'] . "\n";
	$Description .= '  Coordinate system          : ' . $metadata['coordinate_system'] . "\n";
	$Description .= "\n";
	$Description .= "  Input parameters : " . "\n";
	$Description .= write_InputParams($InputParams);
	return($Description);
}


/**
* ---------------------------------------------------------------------------------------
* Write the standard header information into a xml file.
*
* @param string $Service_name   : Name of the web service method
* @return string                : Header text
* ---------------------------------------------------------------------------------------
*/

function write_VOTable_header($Service_name) {
	$header = "<?xml version='1.0'?>" . "\n";
	$header .= '<VOTABLE version="1.2"' . "\n";
	$header .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
	$header .= ' xsi:schemaLocation="http://www.ivoa.net/xml/VOTable/v1.2 http://www.ivoa.net/xml/VOTable/v1.2"' . "\n";
	$header .= ' xmlns="http://www.ivoa.net/xml/VOTable/v1.2">' . "\n";

	$header .= '<!--' . "\n";
	$header .= ' ! VOTable written by FMI web service ' . $Service_name . "\n";
	$header .= ' ! at ' . gmdate(DATE_ISO8601) . "\n";
	$header .= ' !-->' . "\n";
	return ($header);
}

/**
* ---------------------------------------------------------------------------------------
* Handle the computation of field lines of vector quantities (Magnetic field or velocity)
* starting at the points specified in the user provided data file.
*
* @param string $point_file_name      : Name of the user provided original data file
* @param object $num_output_elem      : SimpleXML element
* @param object $simulation_run_elem  : SimpleXML element
* @param object $InputParams          : Object containing input parameters to the method
* @return string                      : Name of the final field line data file
* @throws SoapFault                   : Throws an exception if an error occurs
* ---------------------------------------------------------------------------------------
*/

function handle_field_lines($point_file_name, $num_output_elem, $simulation_run_elem, $InputParams) {

	// Get the name of the hc datafile
	$hc_data_file = get_hc_file($InputParams['ResourceID']['data'], $num_output_elem);

	// Convert the input file into simple three column ascii file
	$start_point_file = convert_to_hc_ascii($point_file_name);

	// Field lines will be written into this file
	$field_lines_file = WWW_DATA_DIR . "/" . "ft_" . random_string(10) . ".txt";


	// -------------------------------------------------
	// Compute field lines by field tracer 'ft' program
	// -------------------------------------------------

	$ft_params = " ";	// Generate the parameters to the 'ft' program

	// Field tracer program computes field lines for either magnetic field or velocity field.
	// This is indicated by a single letter parameter ('B' for magnetic field, 'v' for velocity).
	$vars = array_keys($InputParams['Variable']['data']);
	if (array_intersect($vars, ["Bx","By","Bz","Btot"]) !== []) $ft_var = "B";
	if (array_intersect($vars, ["Ux","Uy","Uz","Utot"]) !== []) $ft_var = "v";

	$StopCondition_Radius = $InputParams['extraParams']['StopCondition_Radius']['data'];
	if ($StopCondition_Radius != null)  $ft_params .= "-r " . $StopCondition_Radius . " ";

	$StopCondition_Region = $InputParams['extraParams']['StopCondition_Region']['data'];
	if ($StopCondition_Region != null)  $ft_params .= "-l " . preg_replace("/[\s]+/",",", trim($StopCondition_Region)) . " ";

	$ft_params .= "-ms " . $InputParams['extraParams']['MaxSteps']['data'] . " ";
	$ft_params .= "-ss " . $InputParams['extraParams']['StepSize']['data'] . " ";
	$ft_params .= $ft_var . " ";
	$ft_params .= $hc_data_file . " ";
	$ft_params .= "-i " . $start_point_file . " ";

	$Direction = $InputParams['extraParams']['Direction']['data'];
	if ($Direction == "Backward") $ft_params = " -b" . $ft_params;

	// Compute single direction, either 'Backward' or 'Forward'
	$command = FTRACER . $ft_params . " > " . $field_lines_file;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_FIELD_LINE);

	// If Direction == 'Both' add 'Backward' to previously computed 'Forward'
	if ($Direction == "Both") {
		$ft_params = " -b" . $ft_params;		// Backward
		$command = FTRACER . $ft_params . " | sed '/^[#>%]/d' >> " . $field_lines_file;
		exec($command, $output, $return_var);
		if ($return_var != 0) throw_error('Server', ERROR_FIELD_LINE);
	}


	// ----------------------------------------------------
	// If we have GUMICS earth run, then for all points
	// where r < r_inner_boundary mark data values as NaN.
	// ----------------------------------------------------

	if (get_simu_model() == "GUMICS") {
		handle_gumics_inner_boundary($simulation_run_elem, $field_lines_file, $remove_line = true, $skip_cols = 1);
	}


	// ---------------------------------------------------------------
	// Convert the computed field line ascii file into VOTable format
	// ---------------------------------------------------------------

	// Read the generated field line file into memory
	$ft_table = file($field_lines_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

	// Define an array for all items:
	// 0   : field line number
	// 1-3 : Position coordinates X, Y and Z
	// 4-6 : Components of vector field (Bx,By,Bz) or (Ux,Uy,Uz)
	// 7   : Magnitude of vector field
	$field_values = array();
	for ($i=0;$i<=7;$i++) $field_values[$i] = array();

	foreach ($ft_table as $line) {
		if ((substr($line,0,1) != "#") and ($line != "")) {
			$fields = preg_split("/[\s]+/", $line);
			$field_values[0][] = intval($fields[0]);		// Field line number
			for ($i=1;$i<7;$i++) $field_values[$i][] = floatval($fields[$i]);
			$field_values[7][] = sqrt($fields[4]*$fields[4] + $fields[5]*$fields[5] +$fields[6]*$fields[6]);
		}
	}

	clean_up([$field_lines_file]);

	// Call the getVOTableURL method to convert data into VOTable file
	$Description_header = "Field lines for a " . $GLOBALS['metadata']['Title'] . " simulation run computed by getFieldLine FMI web service" . "\n";
	$field_name = ($ft_var == "B") ? "B" : "U";

	$getVOTable_params = array(
		'Table_name'  => $GLOBALS['metadata']['planet_name'] . "_" . $GLOBALS['metadata']['dir_name'],
		'Description' => write_VOTable_info($Description_header, $InputParams),
		'Fields'      => array(
			array('name' => 'X', 'data' => $field_values[1]),
			array('name' => 'Y', 'data' => $field_values[2]),
			array('name' => 'Z', 'data' => $field_values[3]),
			array('name' => $field_name . 'x', 'data' => $field_values[4]),
			array('name' => $field_name . 'y', 'data' => $field_values[5]),
			array('name' => $field_name . 'z', 'data' => $field_values[6]),
			array('name' => $field_name . 'tot', 'data' => $field_values[7]),
			array('name' => 'Line_no', 'datatype' => 'int', 'data' => $field_values[0])
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

	return(basename($data_url));
}


/**
* ---------------------------------------------------------------------------------------
* Handle the computation of particle trajectories. The starting points, velocities,
* masses and charges are specified in the user provided data file.
*
* @param string $point_file_name      : Name of the user provided original data file
* @param object $num_output_elem      : SimpleXML element
* @param object $simulation_run_elem  : SimpleXML element
* @param object $tree_xml			  : SimpleXML element
* @param object $InputParams          : Object containing input parameters to the method
* @return string                      : Name of the final field line data file
* @throws SoapFault                   : Throws an exception if an error occurs
* ---------------------------------------------------------------------------------------
*/

function handle_particle_trajectories($point_file_name, $num_output_elem, $simulation_run_elem, $tree_xml, $InputParams) {

	// Get the name of the hc datafile
	$hc_data_file = get_hc_file($InputParams['ResourceID']['data'], $num_output_elem);

	// ----------------------------------------------------------------
	// Particle trajectories are computed with the 'iontracer' program
	// ----------------------------------------------------------------

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// First we need to convert the input votable file into simple eighth column ascii file
	// The vot file must contain the required fields: X, Y, Z, Ux, Uy, Uz, Mass, Charge)
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	$initial_params_xyz = votable_to_hc_ascii_new($point_file_name, ["X","Y","Z","Ux","Uy","Uz","Mass","Charge"], true, 0.0);

	// Get the mass and charge of the test particles
	$param_lines = file($initial_params_xyz);
	$line_array = preg_split("/[\s]+/", trim($param_lines[1]));	// Read mass and charge from the first data line
	$mass   = $line_array[6];
	$charge = $line_array[7];

	// All masses and charges need to be same
	for ($i = 2; $i < count($param_lines); $i++) {
		$line_array = preg_split("/[\s]+/", trim($param_lines[$i]));
		if (($mass !== $line_array[6]) or ($charge !== $line_array[7])) throw_error('Server', ERROR_MASS_CHARGE);
	}

	// Fix the StepSize parameter
	$rel_mass   = floatval($mass)/$GLOBALS['mass_p'];
	$rel_charge = floatval($charge)/$GLOBALS['charge_e'];
	$InputParams['extraParams']['StepSize']['data'] = $GLOBALS['metadata']['Simulation_TimeStep']*$rel_mass/$rel_charge;

	// - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Generate the config file for the 'iontracer' program
	// - - - - - - - - - - - - - - - - - - - - - - - - - - -

	$cfg  = "HCF " . $hc_data_file . " " . round($rel_mass, 0) . " " . round($rel_charge,0) . "\n";
	$cfg .= "FORMATS matlab" . "\n";		// This is a ASCII format which we know how to read...
	$cfg .= "OUT_DIR /" . "\n";     		// This should create the file where the config file resides
    $cfg .= "TRACEVARS parID" . "\n";		// So we can tie each input point with it's number
    										// Notice the pairs are numbered as: Odd for forward, Even for backwards,
    										// so if asks for just forward, then you would get for starting point A, B, C => 1, 3, 5 as IDs.
	$cfg .= "BUNEMANVERSION U" . "\n";		// Default value when electron pressure is not included.
											// TODO: This could be defined automatically if present on tree.xml file
	$cfg .= "DIRECTION " . strtolower($InputParams['extraParams']['Direction']['data']) . "\n";
	$cfg .= "MAXSTEPS " . $InputParams['extraParams']['MaxSteps']['data'] . "\n";
	$cfg .= "STEPSIZE " . $InputParams['extraParams']['StepSize']['data'] . "\n";
	$cfg .= "INTPOLORDER " . (($InputParams['extraParams']['InterpolationMethod']['data'] === "Linear") ? "1" : "0") . "\n";
	$cfg .= "VERBOSE 0" . "\n";
	$cfg .= "OVERWRITE 1" . "\n";
	$cfg .= "ENDPOINTSONLY 0" . "\n";
	$cfg .= "PLANETARY_BOUNDARY " . $InputParams['extraParams']['StopCondition_Radius']['data'] . "\n";
	$box_limits = ['XMIN', 'XMAX', 'YMIN', 'YMAX', 'ZMIN', 'ZMAX'];
	for ($i = 0; $i < 6; $i++)
		$cfg .=  $box_limits[$i] . " " . $InputParams['extraParams']['StopCondition_Region']['data'][$i] . "\n";
	$cfg .= "EOC" . "\n";

	// Write the initial points
    $cfg .= "########### INITIAL POINTS SECTION ###########" . "\n";
	for ($i = 1; $i < count($param_lines); $i++) {
		$line_array = preg_split("/[\s]+/", trim($param_lines[$i]));
		if (point_inside($line_array, $InputParams['extraParams']['StopCondition_Region']['data'])) {
			for ($j = 0; $j < 6; $j++) $cfg .= $line_array[$j] . " ";
			$cfg .= round(floatval($line_array[6])/$GLOBALS['mass_p'], 0) . " ";
			$cfg .= round(floatval($line_array[7])/$GLOBALS['charge_e'], 0) . "\n";
		}
	}

	$config_file_name = TMP_DIR . "/" . "hwa_ion" . random_string(10) . ".cfg";
	if (file_put_contents($config_file_name, $cfg) === false)
		throw_error('Server', ERROR_INTERNAL_TMP_FILE);
	chmod($config_file_name, 0666);


	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Generate the command for running the 'iontracer' program.
	// The 'iontracer' will write the results into file
	// $config_file_name . "_trace_0.m" in the same directory as
	// the config file.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	$command = IONTRACER . " " . $config_file_name;
	exec($command, $output, $return_var);
	if ($return_var == 255) throw_error('Server', ERROR_POINTS_OUTSIDE);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_HCINTPOL);

	$trajectory_file = $config_file_name . "_trace_0.m";
	$trajectory_file_cfg = $config_file_name . "_trace_0.cfg";	// This is created by iontracer. We just need to delete it after use
	chmod($trajectory_file, 0666);


	// -----------------------------------------------------------------
	// Read the particle trajectories from $trajectory_file and add
	// time information to each point.
	// -----------------------------------------------------------------

	// Read the generated trajetories file into memory
	$traj_table = file($trajectory_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

	// The trajectory file contains four columns: X,Y,Z and particleID
	// particleID is odd for forward positions (Time > 0)
	// particleID is even for backward positions (Time < 0)
	// particleIDs 1 and 2 describe same particle, 1 forward, 2 backward, and so on...

	// First read individual particle data into an array $Particles
	// where the index is the particle number and the value is an array of X,Y,Z and time components
	$Particles = [];
	$particleID_old = 0;
	$Time = 0.0;
	for ($j = 9; $j < count($traj_table); $j++) {	// Data lines start at line 9
		$fields = preg_split("/[\s]+/", trim($traj_table[$j]));
		$particleID = intval($fields[3]);				// Original ID, odd for forward, even for backward
		$particleID_new = (int)(($particleID + 1)/2);	// New ID; 1,2 => 1; 3,4 => 2; ...
		$backward = ($particleID % 2 == 0);

		if ($particleID == $particleID_old) {	// Still same particle
			$Time += $backward ? -$StepSize : $StepSize;
		}
		else {	// New particle
			$Time = 0.0;
			$particleID_old = $particleID;
			if (($backward) and isset($Particles[$particleID_new])) continue;	// The first backward line is already read as the first forward line
		}

		if (!isset($Particles[$particleID_new])) {
			$Particles[$particleID_new] = array( "X" => [], "Y" => [], "Z" => [], "T" => []);
		}
		$Particles[$particleID_new]["X"][] = floatval($fields[0]);
		$Particles[$particleID_new]["Y"][] = floatval($fields[1]);
		$Particles[$particleID_new]["Z"][] = floatval($fields[2]);
		$Particles[$particleID_new]["T"][] = $Time;
	}

	clean_up([$trajectory_file, $config_file_name, $initial_params_xyz, $trajectory_file_cfg]);

	// ----------------------------------------------------------------------------------------
	// Convert the $Particles array into a continuos array of X,Y,Z,Time and ParticleID values
	// ----------------------------------------------------------------------------------------

	$Data = array("X" => [], "Y" => [], "Z" => [], "T" => [], "ParticleID" => []);
	foreach($Particles as $particle => $field) {
		for($i = 0; $i < count($field["X"]); $i++) {
			$Data["X"][] = $field["X"][$i];
			$Data["Y"][] = $field["Y"][$i];
			$Data["Z"][] = $field["Z"][$i];
			$Data["T"][] = $field["T"][$i];
			$Data["ParticleID"][] = $particle;
		}
	}

	// --------------------------------------------------
	// Write the trajectories into a VOTable format file
	// by using getVOTableURL method.
	// --------------------------------------------------

	$Description_header = "Particle trajectories for a " . $GLOBALS['metadata']['Title'] . " simulation run computed by getParticleTrajectory FMI web service" . "\n";
	$getVOTable_params = array(
		'Table_name'  => $GLOBALS['metadata']['planet_name'] . "_" . $GLOBALS['metadata']['dir_name'],
		'Description' => write_VOTable_info($Description_header, $InputParams),
		'Fields'      => array(
			array('name' => 'X', 'data' => $Data["X"]),
			array('name' => 'Y', 'data' => $Data["Y"]),
			array('name' => 'Z', 'data' => $Data["Z"]),
			array('name' => 'Seconds', 'datatype' => 'float', 'unit' => 's', 'ucd' => 'time', 'data' => $Data["T"]),
			array('name' => 'Particle_no', 'datatype' => 'int', 'data' => $Data["ParticleID"])
		)
	);

	$client = new SoapClient(METHODS_FILE_URL, array('cache_wsdl' => WSDL_CACHE_NONE));

	try {
		$data_url = $client->getVOTableURL($getVOTable_params);
	}
	catch (Exception $e) {
		throw_error('Server', $e->getMessage());
		exit();
	}

	return(basename($data_url));
}


/**
* ---------------------------------------------------------------------------------------
* Get the energy channel names and low and high limits of individual channels from
* the numerical output element.
*
* @param object $num_output_elem : SimpleXML element
* @return array                  : Associative array, Keys : channel names.
*								   Values: Low and High limits of energy channels.
* ---------------------------------------------------------------------------------------
*/

function get_energy_channels($num_output_elem) {
	$channels = [];
	foreach ($num_output_elem->{'Parameter'} as $parameter) {
		if ((string) $num_output_elem->{'Parameter'}->{'ParameterKey'} !== "EnergySpectra") continue;
		$range = $parameter->{'Particle'}->{'EnergyRange'};
		$channels['channel_info'] =  array('Low' => floatval($range->{'Low'}), 'High' => floatval($range->{'High'}), 'Units' => (string) $range->{'Units'});
		foreach ($parameter->{'Particle'}->{'EnergyRange'}->{'Bin'} as $bin) {
			$channels[(string) $bin->{'BandName'}] =  array('Low' => floatval($bin->{'Low'}), 'High' => floatval($bin->{'High'}));
		}
	}
	return($channels);
}


/**
* ---------------------------------------------------------------------------------------
* Handle the computation of particle energy spectra. The starting points, velocities,
* masses and charges are specified in the user provided data file.
*
* @param string $point_file_name      : Name of the user provided original data file
* @param object $num_output_elem      : SimpleXML element
* @param object $simulation_run_elem  : SimpleXML element
* @param object $InputParams          : Object containing input parameters to the method
* @param array $energy_channels       : Name, Low and High energies of individual channels
* @return string                      : Name of the final field line data file
* @throws SoapFault                   : Throws an exception if an error occurs
* ---------------------------------------------------------------------------------------
*/

function handle_spectra($point_file_name, $num_output_elem, $simulation_run_elem, $InputParams, $energy_channels) {

	// -------------------------------------------------------------------------------
	// The hcintpol command is used to compute interpolated values for given data set.
	// For this command the input data set must be converted to a simple text file
	// with one data point per line and the coordinates of data points (X,Y,Z) in
	// columnns 1, 2 and 3. The units of X, Y and Z must be meter (m).
	// hcintpol returns the results in similar text format file with one column
	// added for each interpolated variable.
	// --------------------------------------------------------------------------------


	// Convert input data file to simple three column ascii format file
	$hc_input_file = convert_to_hc_ascii($point_file_name);


	// Get the name of the simulation run hc datafile
	$hc_data_file = get_hc_file($InputParams['ResourceID']['data'], $num_output_elem);


	// Run the hcintpol command.
	// Note that when applied to a spectral .hc file  hcintpol returns all energy channels
	// regardless what is defined by -v option ($hc_param_list).
	$hc_output_file = run_hc_intpol($hc_param_list = "Ebin0", $hc_data_file, $hc_input_file, $InterpolationMethod = $InputParams['extraParams']['InterpolationMethod']['data']);


	// ----------------------------------------------------
	// If we have GUMICS earth run, then for all points
	// where r < r_inner_boundary mark data values as NaN.
	// ----------------------------------------------------

	if (get_simu_model() == "GUMICS") {
		handle_gumics_inner_boundary($simulation_run_elem, $hc_output_file, $remove_line = false, $skip_cols = 0);
	}

	// --------------------------------------------------
	// Convert interpolated ascii file to output format.
	// Currently only VOTable is supported.
	// --------------------------------------------------

	// Generate the <PARAM> element to VOTable describing energy channel limits
	$value = "";
	foreach ($energy_channels as $channel_name => $channel_limits) {
		if ($channel_name !== "channel_info") $value .= $channel_limits['Low'] . " ";
	}
	$value .= $energy_channels['channel_info']['High'];
	$VOT_params = array();
	$VOT_params[] = '<PARAM name="EnergyRange" unit="' . $energy_channels['channel_info']['Units'] . '" ucd="instr.param" datatype="float" arraysize="' . (count($energy_channels)) . '" value="' . $value . '"/>';

	// Fill some VOT table info
	$VOT_info = array(
		"Table_name"  => $GLOBALS['metadata']['planet_name'] . "_" . $GLOBALS['metadata']['dir_name'],
		"Description" => "Particle spectra of " . $GLOBALS['metadata']['Title'] . " simulation run computed by getDataPointSpectra FMI web service" . "\n",
		"Param"		  => $VOT_params
	);

	// Write the VOTable file
	$Input_format  = get_file_format($point_file_name);
 	switch ($Input_format) {
		case "ASCII"   : $final_file_name = ascii_to_votable_new($hc_output_file, $orig_file_name, $Variable_list); break;
 		case "VOTable" : $final_file_name = votable_to_votable_new($hc_output_file, $point_file_name, $VOT_info, $InputParams); break;
 	}

	clean_up([$hc_input_file, $hc_output_file]);

	return ($final_file_name);
}


/**
* -----------------------------------------------------------------------
* In given data file remove lines or mark as missing all data points
* where the point is inside a spherical region (GUMICS inner boundary).
*
* @param SimpleXMLElement $simulation_run_elem : SimulationRun element
* @param string $data_file    : Name of the data file (columnar text file).
* @param boolean $remove_line : Flag to remove line (true) or just mark
*                               data values as missing (false).
* @param integer $skip_cols   : Number of columns before the X column.
* @throws SoapFault           : Internal file handling error
* ------------------------------------------------------------------------
*/

function handle_gumics_inner_boundary($simulation_run_elem, $data_file, $remove_line, $skip_cols) {
	// If the inner boundary value is not defined then we can't do anything
	if (!isset($simulation_run_elem->{'RegionParameter'}->{'Property'}->{'PropertyValue'})) return;

	// Get the inner_boundary value from the SimulationRun element
	$inner_boundary = floatval((string) $simulation_run_elem->{'RegionParameter'}->{'Property'}->{'PropertyValue'});
	if ((string) $simulation_run_elem->{'RegionParameter'}->{'Property'}->{'Units'} == "km")
		$inner_boundary = 1000.0*$inner_boundary;		// convert to meters
	$r_limit_2 = $inner_boundary * $inner_boundary;		// Just to avoid square roots in distance computations

	// Create a temporary text file
	$tmp_filename = $data_file . "_tmp";
	$tmp_file = fopen($tmp_filename, "w");
	if ($tmp_file === FALSE) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	// Open the interpolated file
	$intpolfile = fopen($data_file, "r");
	if ($intpolfile === FALSE) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	// Read every line and check if r < $inner_boundary (i.e.  r^2 < ($inner_boundary)^2 )
	while (($line = fgets($intpolfile, 4096)) !== false) {
		if ($line[0] == '#') {		// Comment line, write and continue with next line
			fwrite($tmp_file, $line);
			continue;
		}

		$data = preg_split("/[\s,]+/", trim($line));
		$x = floatval($data[$skip_cols]);
		$y = floatval($data[$skip_cols + 1]);
		$z = floatval($data[$skip_cols + 2]);

		if ($x*$x + $y*$y + $z*$z >= $r_limit_2) {
			fwrite($tmp_file, $line);
			continue;
		}

		if ($remove_line) continue;		// Don't write this line into new file

		for ($i=0; $i < $skip_cols + 3; $i++) fwrite($tmp_file, $data[$i] . " ");		// Write all columns up to Z (= $column + 2)
		for ($i = $skip_cols + 3; $i < count($data); $i++)	fwrite($tmp_file," -999");	// Set the values in the rest of the columns to missing values
		fwrite($tmp_file, "\n");
	}

	fclose($tmp_file);
	fclose($intpolfile);
	if (rename($tmp_filename, $data_file) === false) throw_error('Server', ERROR_INTERNAL_TMP_FILE);
}


/**
* ----------------------------------------------------------------------------------
* Remove all temporary files.
*
* @param array $file_list	: List of files to be deleted
* -----------------------------------------------------------------------------------
*/

function clean_up($file_list) {
	foreach($file_list as $file) {
		if (is_file($file)) unlink($file);
	}
}



?>

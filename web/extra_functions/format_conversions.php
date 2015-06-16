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
//	              			format_conversions.php
//
// ##########################################################################

// ============================================================================
// This php script contains functions for converting data between various
// formats (ASCII, VOTable, netCDF).
// =============================================================================


/**
* -----------------------------------------------------------------------------------------
* Convert input file into hc ascii text file (i.e. file which hcintpol can read).
*
* @param string $File	         : File name
* @param float $Coeff_to_Meters	 : Coefficient to convert position coordinates into meters
* @return string                 : Full pathname of the converted hc ascii file
* -----------------------------------------------------------------------------------------
*/

function convert_to_hc_ascii($File) {
	$Format = get_file_format($File);
	switch ($Format) {
		case "ASCII"   : return(ascii_to_hc_ascii($File, 1.0));	break;
//		case "VOTable" : return(votable_to_hc_ascii($File)); 	break;
		case "VOTable" : return(votable_to_hc_ascii_new($File, ["X","Y","Z"], false, 1.0)); 	break;
		case "netCDF"  : return(netcdf_to_hc_ascii($File));		break;
		default        : throw_error('Server', ERROR_INPUT_FORMAT);	break;
	}
}


/**
* ---------------------------------------------------------------------------
* Convert interpolated hc_output_file file into final format file.
*
* @param string $orig_file_name	 : name of the original data file
* @param string $hc_output_file	 : name of hcintpol generated data file
* @param object $InputParams	 : object containing input parameters of the method call
* @return string                 : Full pathname of the final data file
* @throws SoapFault              : Internal error in file conversion
* ---------------------------------------------------------------------------
*/

function convert_to_final($orig_file_name, $hc_output_file, $InputParams) {
	$Input_format  = get_file_format($orig_file_name);
	$Output_format = $InputParams['extraParams']['OutputFileType']['data'];
	$Variable_list = $InputParams['Variable']['data'];
	$hc_param_list = get_hc_variable_names(array_keys($Variable_list));

 	switch ($Input_format . "_" . $Output_format) {
 		case "ASCII_ASCII"     : return( ascii_to_ascii($hc_output_file, $orig_file_name, $hc_param_list) );     break;
		case "ASCII_VOTable"   : return( ascii_to_votable_new($hc_output_file, $orig_file_name, $Variable_list) ); break;
 		case "ASCII_netCDF"    : return( ascii_to_netcdf($hc_output_file, $orig_file_name, $Variable_list) ); break;
		case "VOTable_ASCII"   : throw_error('Server', "VOTable to ASCII conversion not yet supported");	break;
 		case "VOTable_VOTable" : return( votable_to_votable($hc_output_file, $orig_file_name, $Variable_list) ); break;
 		case "VOTable_netCDF"  : return( votable_to_netcdf( $hc_output_file, $orig_file_name, $Variable_list) ); break;
 	}
}


/**
* ---------------------------------------------------------------------------
* Determine the multiplying coefficient to change the units into meters.
* The user given unit may be e.g. "6.371x10^3km". So there are two parts:
* 1. Extract the multiplying factor
* 2. Convert the unit to meters (m).
*
* @param string $var_name	 : Name of the physical variable (e.g. 'X','B', ...)
* @param string $unit		 : Unit string (e.g. 'km', 'nT', ...)
* @param string $param_name  : Name of the parameter
* @param string $param_value : Value of the parameter
* @return string             : Command string for stilts
* ---------------------------------------------------------------------------
*/

function set_coeff($var_name, $unit, $param_name, $param_value) {
	if ($unit == "m") return ("");	// No multiplication necessary

	// Get the possible multiplying number in the unit (e.g. $unit = "6.371x10+6m")
 	preg_match('/^([\d\.x\+\-]*)(\w+)/', $unit, $matches);		// The factor may be e.g 1.3x10-12
 	$mul = ($matches[1] == "") ? 1.0 : floatval($matches[1]);

	switch ($matches[2]) {
		case "m"   : $coeff = 1.0;			break;
		case "km"  : $coeff = 1000.0;		break;
		case "mi"  : $coeff = 1609.344;		break;
		case $param_name : $coeff = $param_value;	break;
		default    : $coeff = 1.0;
	}
	return ("cmd='replacecol " . $var_name . " " . ($mul*$coeff) . "*" . $var_name . "' ");		// Command for stilts
}


/**
* ---------------------------------------------------------------------------
* Convert the data from one unit to another.
*
* @param object $field	 : Array containing 'unit' and 'data' fields
* @return object         : Array containing converted data.
* ---------------------------------------------------------------------------
*/

function scale_data($field) {

	global $Units;
	global $Unit_conversion_table;

	// Get the possible multiplying factor in the unit
 	preg_match('/^([\d\.x\+\-]*)(\w+)/', $field["unit"], $matches);		// The factor may be e.g 1.3x10-12
 	$mul = ($matches[1] == "") ? 1.0 : floatval($matches[1]);		// $matches[1] contains the multiplication factor, $matches[2] contains the unit string
 	$unit = $matches[2];

	if (($matches[1] !== "") or in_array($unit, array_keys($Unit_conversion_table))) {
		$count = count($field["data"]);
		$coeff = isset($Unit_conversion_table[$unit]) ? $Unit_conversion_table[$unit] : 1.0;
		$new_array = array_fill(0, $count, 0.0);		// Define a new array for scaled data
		for($i = 0; $i < $count; $i++) $new_array[$i] = ($mul*$coeff)*floatval($field["data"][$i]);
		return $new_array;
	}
	else
		return $field['data'];	// Cannot convert, return original data
}


/**
* ---------------------------------------------------------------------------
* Multiply the data by given factor
*
* @param object $field	 : Array containing 'unit' and 'data' fields
* @param float $coeff	 : Multiplication coefficient
* @return object         : Array containing converted data.
* ---------------------------------------------------------------------------
*/

function scale_coeff($field, $coeff) {

	$count = count($field["data"]);
	$new_array = array_fill(0, $count, 0.0);		// Define a new array for scaled data
	for($i = 0; $i < $count; $i++) $new_array[$i] = $coeff*floatval($field["data"][$i]);
	return $new_array;
}


/**
* ---------------------------------------------------------------------------
* Set the given element as the first element in a given array
*
* @param float $value	 : Value to be set as the first element in array
* @param array $array	 : Array
* @return array          : Modified array
* ---------------------------------------------------------------------------
*/

function set_first($value, $array) {
	$index = array_search($value, $array);
	$new_array = $array;
	for ($i = $index; $i > 0; $i--) $new_array[$i] = $array[$i-1];
	$new_array[0] = $value;
	return ($new_array);
}


/**
* ---------------------------------------------------------------------------
* Convert the VOTable version number from 1.1 to 1.2.
* Stilts writes 1.1 files.
*
* @param string $vot_file_name	 : Name of the VOTable file
* ---------------------------------------------------------------------------
*/

function version_1_1_to_1_2($vot_file_name) {
	// Read the VOTable file into memory
	$vot_file = file_get_contents($vot_file_name);
	if ($vot_file === false) return (log_message(ERROR_INTERNAL_TMP_FILE));

	$old = array('version="1.1"', 'VOTable/v1.1');
	$new = array('version="1.2"', 'VOTable/v1.2');
	$new_content = str_replace($old, $new, $vot_file);

	file_put_contents($vot_file_name, $new_content);
}


/**
* --------------------------------------------------------------------------------
* Convert ASCII format data file to ASCII file that is readable by hcintpol.
* For hcintpol the file must contain three columns which define the X,Y and Z
* coordinates of the data point. The distance unit must be meter (m).
* The input file may contain 'time' as the first column. The next three
* columns must be X,Y and Z coordinates. Additional columns may exist but
* they are not written to output file.
*
* @param string $point_file_name : Path to the input ASCII file.
* @param float $coeff            : X,Y and Z values are multiplied by this value.
* @return string				 : Path to the output hcintpol ASCII file.
* @throws SoapFault      		 : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------
*/

function ascii_to_hc_ascii($point_file_name, $coeff) {

	// ---------------------------------------------------------------------------------
	// Check if the data point file is an ascii file with time info in the first column
	// ---------------------------------------------------------------------------------

	$tmp_file_name = $point_file_name . ".tmp";
	exec("sed '/^#/d' " . $point_file_name . " > " . $tmp_file_name);	// Remove comment lines (#)
	$first_elem = trim(exec("head -n 1 " .  $tmp_file_name . "| awk '{print $1}'"));	// First string in first data line
	$time_included = (strtotime($first_elem) !== false) ? true : false;

	// -----------------------------------------------------------------------------
	// For hcintpol we need to generate a text file with only three columns (X,Y,Z)
	// Time and other columns must be stripped out.
	// -----------------------------------------------------------------------------

	$XYZ_file_name = $point_file_name . "_XYZ";		// Original data file with only X,Y and Z columns remaining
	if ($time_included)
		$command = "awk '{print " . $coeff . "*$2," . $coeff . "*$3," . $coeff . "*$4}' " . $tmp_file_name . " > " . $XYZ_file_name;
	else
		$command = "awk '{print " . $coeff . "*$1," . $coeff . "*$2," . $coeff . "*$3}' " . $tmp_file_name . " > " . $XYZ_file_name;

	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	return ($XYZ_file_name);
}


/**
* --------------------------------------------------------------------------------
* Convert VOTable format data file to ASCII file that is readable by hcintpol
* For hcintpol the file must contain three columns which define the X,Y and Z
* coordinates of the data point. The distance unit must be meter (m).
* The input file may contain 'time'.
*
* @param string $point_file_name : Path to the input VOTable file.
* @throws SoapFault      		 : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------
*/

function votable_to_hc_ascii($point_file_name) {

	// -------------------------------------------------------------------
	// Get the names and units of the fields defined in the VOTable file.
	// -------------------------------------------------------------------

	$command = STILTS . " tpipe ifmt=votable cmd='meta Name Units' " . $point_file_name;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_VOTABLE_FILE);

	$vot_param_name = "";
	$vot_param_value = 1.0;
	// If there is a parameter it is defined in the first line
	if (substr($output[0],0,3) != "+--") {
		$vot_param = explode(":",$output[0]);
		$vot_param_name = trim($vot_param[0]);
		$vot_param_value = floatval(trim($vot_param[1]));
	}

	// Find the fields array
	$start = 1;
	while (!preg_match('/Name/',$output[$start])and ($start < count($output))) $start++;
	$fields = array();
	for ($i = $start + 2; $i < count($output)-1; $i++) {
		$elems = explode('|',$output[$i]);
		$fields[strtoupper(trim($elems[1]))] = array('unit' => trim($elems[2]));	// Define as array so there is room for more properties
	}
	if (!isset($fields["X"]) or !isset($fields["Y"]) or !isset($fields["Z"])) throw_error('Client', ERROR_VOTABLE_FIELDS);


	// ---------------------------------------------------------------------------------
	// For hcintpol we have to generate a XYZ file where the unit of distance is meter.
	// ---------------------------------------------------------------------------------

	// Determine the coefficients

	$cmd_coeff  = set_coeff("X", $fields["X"]["unit"], $vot_param_name, $vot_param_value);
	$cmd_coeff .= set_coeff("Y", $fields["Y"]["unit"], $vot_param_name, $vot_param_value);
	$cmd_coeff .= set_coeff("Z", $fields["Z"]["unit"], $vot_param_name, $vot_param_value);

	$XYZ_file_name = $point_file_name . "_XYZ";		// ASCII file with only X,Y and Z columns
	$command = STILTS . " tpipe ifmt=votable ofmt=ascii cmd='keepcols \"x y z\"' " . $cmd_coeff . $point_file_name . " | sed '/^#/d' > " . $XYZ_file_name;

	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Client', ERROR_VOTABLE_FIELDS);		// Error occurs if x, y or z is not found in the VOTable file

	return ($XYZ_file_name);
}


/**
* --------------------------------------------------------------------------------
* Convert VOTable format data file to ASCII file that is readable by hcintpol.
* The function writes only those fields that are listed in the array
* $required_fields. If $required_fields == NULL then all fields are written.
* If the VOTable file does not contain fields that are listed in $required_fields
* an exception is thrown.
* $coeff is a coefficient for converting X,Y and Z from planetary radius unit
* to meters.
* This function uses SimpleXML routines for reading the VOTable file.
*
* @param string $vot_file_name   : Path to the input VOTable file.
* @param array $required_fields  : Names of required fields in the .vot file
* @param boolean $header_flag    : Flag to indicate whether header line (#)
*                                  is written into output ascii file or not.
* @param float $coeff            : Coefficient for multiplying data values.
* @return string				 : Path to the output hcintpol ASCII file.
* @throws SoapFault      		 : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------
*/

function votable_to_hc_ascii_new($vot_file_name, $required_fields, $header_flag, $coeff) {

	global $Units;
	global $Unit_conversion_table;

	// Load the VOTable file (= xml file) into a SimpleXML object
	$vot_xml = simplexml_load_file($vot_file_name);
	if ($vot_xml === false) throw_error('Client', ERROR_VOTABLE_FILE);

	// Some checkings
	if ($vot_xml->{'RESOURCE'}->count() != 1) throw_error('Client', ERROR_VOTABLE_FILE);	// No multitable votable files
	if (!isset($vot_xml->{'RESOURCE'}->{'TABLE'}->{'DATA'}->{'TABLEDATA'})) throw_error('Client', ERROR_VOTABLE_FILE);
	if (!isset($vot_xml->{'RESOURCE'}->{'TABLE'}->{'FIELD'})) throw_error('Client', ERROR_VOTABLE_FILE);

	// Number of data points
	$data_count = $vot_xml->{'RESOURCE'}->{'TABLE'}->{'DATA'}->{'TABLEDATA'}->{'TR'}->count();

	// Read the VOT field attributes
	$fields = array();
	foreach($vot_xml->{'RESOURCE'}->{'TABLE'}->{'FIELD'} as $field) {
		$key = strtoupper((string) $field['name']);
		if (isset($field['ucd'])) {		// Check the ucd to set the names of X, Y and Z coordinates
			switch ($field['ucd']) {
				case "pos.cartesian.x" : $key = "X"; break;
				case "pos.cartesian.y" : $key = "Y"; break;
				case "pos.cartesian.z" : $key = "Z"; break;
			}
		}
		$fields[$key] = array (
			'name' => $field['name'],							// 'name' is as in VOT file
			'unit' => isset($field['unit']) ? $field['unit'] : "",
			'ucd'  => isset($field['ucd']) ? $field['ucd'] : "",
			'data' => array_fill(0, $data_count, "")	// Initialize the data array
		);
	}


	// Check that the votable file contains required fields
	$vot_field_names = array_keys($fields);		// Field names in uppercase

	if (($required_fields !== NULL) or (count($required_fields) !== 0)) {
		foreach($required_fields as $field)
			if (!in_array(strtoupper($field), $vot_field_names)) throw_error('Client', ERROR_MISSING_VOTABLE_FIELD . $field);
	}


	// Read the data for each field
	$row = 0;
	foreach($vot_xml->{'RESOURCE'}->{'TABLE'}->{'DATA'}->{'TABLEDATA'}->{'TR'} as $TR) {
		$col = 0;
		foreach ($TR->{'TD'} as $TD)
			$fields[$vot_field_names[$col++]]['data'][$row] = (string) $TD;
		$row++;
	}


	// Check which fields are written and order them so that always X,Y,Z will be written first
	if (($required_fields == NULL) or (count($required_fields) == 0)) {
		$writable_fields = $vot_field_names;
	} else {
		$writable_fields = $required_fields;
		for($i = 0; $i < count($required_fields); $i++) $writable_fields[$i] = strtoupper($writable_fields[$i]);
	}
	if (in_array("Z", $writable_fields)) $writable_fields = set_first("Z", $writable_fields);
	if (in_array("Y", $writable_fields)) $writable_fields = set_first("Y", $writable_fields);
	if (in_array("X", $writable_fields)) $writable_fields = set_first("X", $writable_fields);


	// Convert the units to those used by hcintpol.
	foreach($writable_fields as $field_name) {
		if ($fields[$field_name]["unit"] !== "") {
			if (!in_array($fields[$field_name]["unit"], array_keys($Units)))
				// Not found among the units used by hcintpol. Needs to be converted.
				// Note that $fields[$field_name]["unit"] may contain additional multiplication factor
				$fields[$field_name]['data'] = scale_data($fields[$field_name]);
		}
	}

	// Convert units from planet radius to meters
	if ($coeff > 0.1) {
		$fields['X']['data'] = scale_coeff($fields['X'], $coeff);
		$fields['Y']['data'] = scale_coeff($fields['Y'], $coeff);
		$fields['Z']['data'] = scale_coeff($fields['Z'], $coeff);
	}

	// Write the data into a columnar ascii file
	$XYZ_file_name = $vot_file_name . "_XYZ.txt";		// ASCII file
	$ascii_file = fopen($XYZ_file_name, "w");
	if ($ascii_file === FALSE) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	// Write the header if $header_flag is on
	if ($header_flag) {
		$header = "# ";
		foreach($writable_fields as $field_name) {
			$header .= get_hcintpol_var_name($fields[$field_name]["name"]) . " ";
		}
		fwrite($ascii_file, $header . "\n");
	}

	// Write the data values
	for($i = 0; $i < $data_count; $i++) {
		foreach($writable_fields as $field_name) {
			fwrite($ascii_file, $fields[$field_name]["data"][$i] . " ");
		}
		fwrite($ascii_file, "\n");
	}

	fclose($ascii_file);
	return ($XYZ_file_name);

}


/**
* --------------------------------------------------------------------------------
* Convert netCDF format data file to ASCII file that is readable by hcintpol.
*
* @param string $point_file_name   : Path to the input netCDF file.
* @return string				   : Path to the output hcintpol ASCII file.
* @throws SoapFault                : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------
*/

function netcdf_to_hc_ascii($point_file_name) {
	throw_error('Server', ERROR_INPUT_FORMAT . " netCDF");
}


/**
* -------------------------------------------------------------------------------------
* Combine input ASCII file with hcintpol generated ASCII file into a new ASCII file
* containing all fields (e.g. time) in the original file.
*
* @param string $hc_output_file    : Path to the output hcintpol ASCII file.
* @param string $point_file_name   : Path to the input ASCII file.
* @param string $param_list        : Comma separated list of variables.
* @return string                   : Path to the combined ASCII file.
* @throws SoapFault                : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------------
*/

function ascii_to_ascii($hc_output_file, $point_file_name, $param_list) {

	// Get the header line from the interpolated data file.
	// The header lists the order of parameters. It starts with "# x y z".
	// Then remove the header from hcintpol generated file.

	$header = exec("head -n 1 " . $hc_output_file);
	$header_array = preg_split("/[\s,]+/", $header);
	$command = "sed '/^#/d' " . $hc_output_file . " > " . $hc_output_file . "_2";		// remove the header
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);


	// Combine the (Time), X, Y and Z columns from the original data file to
	// the computed parameter values columns from the hcintpol generated file.
	// In this way the data points are expressed in their original units and
	// the possible time information is also preserved in the final data file.
	// Also the columns in the final data file are in the order defined by
	// user in $params->{'Variable'}.

	// Get the three or four first columns from the original data file

	$XYZ_file_name = $point_file_name . "_XYZ";

	$first_elem = trim(exec("sed '/^#/d' " . $point_file_name . " | head -n 1 | awk '{print $1}'"));
	$time_included = (strtotime($first_elem) !== false) ? true : false;

	if ($time_included) {
		$header_new = "#t x y z ";
		$command = "sed '/^#/d' " . $point_file_name . " | awk '{print $1,$2,$3,$4}' > " . $XYZ_file_name;	// Columns T,X,Y,Z
	} else {
		$header_new = "#x y z ";
		$command = "sed '/^#/d' " . $point_file_name . " | awk '{print $1,$2,$3}' > " . $XYZ_file_name;		// Columns X,Y,Z
	}
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	// Store each variable into its own file
	$params_given = explode(",", $param_list);
	$file_list = "";
	for ($i = 0; $i < count($params_given); $i++) {
		$file_name = $XYZ_file_name . "_" . $params_given[$i];
		$file_list .= " " . $file_name;
		for ($j = 4; $j < count($header_array); $j++)	// Header includes characters : # x y z
			if (trim($header_array[$j]) == $params_given[$i]) break;

		$header_new .= $params_given[$i] . " ";
		$command = "cat " . $hc_output_file . "_2 | awk '{print $" . $j . "}' > " . $file_name;
		exec($command, $output, $return_var);
		if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);
	}

	// Combine all data files into a single file
	exec("echo '" . $header_new . "' > " . $hc_output_file);
	$command = "paste -d ' ' " . $XYZ_file_name . $file_list . " >> " . $hc_output_file;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	$final_file_name = "hwa_" . random_string(10);
	$command = "cp " . $hc_output_file . " " . WWW_DATA_DIR . "/" . $final_file_name;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	return($final_file_name);
}


/**
* -------------------------------------------------------------------------------------
* Generate a VOTable output file when the input file was also VOTable
*
* @param string $hc_output_file    : Path to the output hcintpol ASCII file.
* @param string $point_file_name   : Path to the input VOTable file.
* @param array $param_description  : Array of variable descriptions.
* @return string                   : Path to the final VOTable file.
* @throws SoapFault                : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------------
*/

function votable_to_votable($hc_output_file, $point_file_name, $param_description) {

	global $hcintpol_names;
	global $Units;
	global $ucd_table;

	// Get the header line which lists the order of parameters. It starts with "# x y z"
	$header = exec("head -n 1 " . $hc_output_file);
	$header_array = preg_split("/[\s,]+/", $header);

	// Replace the old header with a new one in $hc_output_file
	$new_header = "#";
	for ($i = 1; $i < count($header_array); $i++) $new_header .= " " . $hcintpol_names[$header_array[$i]];
	$temp_file = $hc_output_file . "tmp";
	$command = "sed '1 s/^.*$/" . $new_header . "/g' " . $hc_output_file . " > " . $temp_file . " ; mv " . $temp_file . " " . $hc_output_file;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	// Generate command for 'stilts tpipe' to set the units, ucds and missing values of the interpolated fields
	$cmd = "cmd='delcols \"X Y Z\"' ";
	for ($i = 4; $i < count($header_array); $i++) {		// Skip "# x y z"
		$cmd .= "cmd='colmeta -units \"" . $Units[$header_array[$i]] . "\" \"" . $hcintpol_names[$header_array[$i]] . "\"' ";
		$cmd .= "cmd='colmeta -ucd \"" . $ucd_table[$header_array[$i]] . "\" \"" . $hcintpol_names[$header_array[$i]] . "\"' ";
		$cmd .= "cmd='colmeta -desc \"" . $param_description[$hcintpol_names[$header_array[$i]]] . "\" \"" . $hcintpol_names[$header_array[$i]] . "\"' ";
		$cmd .= "cmd='replaceval -999 null \"" . $hcintpol_names[$header_array[$i]] . "\"' ";
	}

	$votable_filename = $point_file_name . ".votable";
	$command = STILTS . " tpipe ifmt=ascii ofmt=votable " . $cmd . $hc_output_file . " > " . $votable_filename;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	// Combine the original votable file with the new interpolated data file
	$final_file_name = "hwa_" . random_string(10) . ".vot";
	$command = STILTS . " tjoin nin=2 ifmt1=votable ifmt2=votable ofmt=votable in1='" . $point_file_name . "' in2='" .  $votable_filename . "' out='" . WWW_DATA_DIR . "/" . $final_file_name . "'";
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	version_1_1_to_1_2(WWW_DATA_DIR . "/" . $final_file_name);
	clean_up([$votable_filename]);

	return($final_file_name);
}


/**
* -------------------------------------------------------------------------------------
* Generate a VOTable output file when the input file was ASCII file.
* Use stilts.
* This is old function. Should be replaced by 'ascii_to_votable_new'
*
* @param string $hc_output_file    : Path to the output hcintpol ASCII file.
* @param string $point_file_name   : Path to the input ASCII file.
* @param array $param_description  : Array of variable descriptions.
* @return string                   : Path to the final VOTable file.
* @throws SoapFault                : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------------
*/

function ascii_to_votable($hc_output_file, $point_file_name, $param_description) {

	require 'extra_functions/units.php';		// Units of different parameters
	require 'extra_functions/ucd.php';			// List of IVOA ucd's (Unified Content Descriptors)
	require 'extra_functions/hcintpol_variables.php';			// List of variables accepted by hcintpol


	// Get the header line which lists the order of parameters. It starts with "# x y z"
	$header = exec("head -n 1 " . $hc_output_file);
	$header_array = preg_split("/[\s,]+/", $header);

	// Generate command for 'stilts tpipe' to set the units, ucds and missing values of the interpolated fields
	$cmd = "";
	for ($i = 1; $i < count($header_array); $i++) {		// Skip "#"
		$cmd .= "cmd='colmeta -units \"" . $Units[$header_array[$i]] . "\" \"" . $header_array[$i] . "\"' ";
		$cmd .= "cmd='colmeta -ucd \"" . $ucd_table[$header_array[$i]] . "\" \"" . $header_array[$i] . "\"' ";
		$cmd .= "cmd='colmeta -desc \"" . $param_description[$header_array[$i]] . "\" \"" . $header_array[$i] . "\"' ";
		$cmd .= "cmd='replaceval -999 null \"" . $header_array[$i] . "\"' ";
	}

	$votable_filename = $point_file_name . ".votable";
	$command = STILTS . " tpipe ifmt=ascii ofmt=votable " . $cmd . $hc_output_file . " > " . $votable_filename;
//debug_string($command);

	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	// Copy the file to final destination
	$final_file_name = "hwa_" . random_string(10);
	$command = "cp " . $votable_filename . " " . WWW_DATA_DIR . "/" . $final_file_name;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);
	clean_up([$votable_filename]);

	return($final_file_name);
}


/**
* -------------------------------------------------------------------------------------
* Generate a VOTable output file when the input file was ASCII file.
*
* @param string $hc_output_file    : Path to the output hcintpol ASCII file.
* @param string $point_file_name   : Path to the input ASCII file.
* @param array $param_description  : Array of variable descriptions.
* @return string                   : Path to the final VOTable file.
* @throws SoapFault                : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------------
*/

function ascii_to_votable_new($hc_output_file, $point_file_name, $param_description) {

	global $metadata;				// Array containing some metadata
	global $hcintpol_names;
	global $Units;
	global $Unit_conversion_table;
	global $ucd_table;


	// Create the 'votable' file
	$votable_filename = TMP_DIR . "/VOT_" . random_string(10) . ".vot";
	$votfile = fopen($votable_filename, "w");
	if ($votfile === FALSE) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	// Write the header to 'votable' file
	fwrite($votfile,"<?xml version='1.0'?>" . "\n");
	fwrite($votfile,'<VOTABLE version="1.2"' . "\n");
	fwrite($votfile,' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n");
	fwrite($votfile,' xsi:schemaLocation="http://www.ivoa.net/xml/VOTable/v1.2 http://www.ivoa.net/xml/VOTable/v1.2"' . "\n");
	fwrite($votfile,' xmlns="http://www.ivoa.net/xml/VOTable/v1.2">' . "\n");

	fwrite($votfile,'<!--' . "\n");
	fwrite($votfile,' ! VOTable written by FMI web service' . "\n");
	fwrite($votfile,' ! at ' . gmdate(DATE_ISO8601) . "\n");
	fwrite($votfile,' !-->' . "\n");

	fwrite($votfile,'<RESOURCE>' . "\n");

	// Get the number of data lines in the interpolated file
	$rows = intval(exec("wc -l " . $hc_output_file . " | cut -d ' ' -f 1")) - 1;

	// Write some metadata information
	fwrite($votfile,'<TABLE name="' . $metadata['planet_name'] . "_" . $metadata['dir_name'] . '" nrows="' . $rows . '">'. "\n");
	fwrite($votfile,'<DESCRIPTION>' . "\n");
	fwrite($votfile,'  SimulationModel            : ' . $metadata['Title'] . "\n");
	fwrite($votfile,'  SimulationModel_ResourceID : ' . $metadata['SimulationModel'] . "\n");
	fwrite($votfile,'  SimulationRun_ResourceID   : ' . $metadata['SimulationRun'] . "\n");
	fwrite($votfile,'  NumericalOutput_ResourceID : ' . $metadata['NumericalOutput'] . "\n");
	fwrite($votfile,'  Content description        : ' . $metadata['Output_description'] . "\n");
	fwrite($votfile,'  Object                     : ' . $metadata['planet_name'] . "\n");
	fwrite($votfile,'  Object radius              : ' . $metadata['planet_radius'] . " " . $metadata['planet_radius_unit'] . "\n");
	fwrite($votfile,'  Coordinate system          : ' . $metadata['coordinate_system'] . "\n");
	if (isset($metadata['spacecraft']))
		fwrite($votfile,'  Spacecraft                 : ' . $metadata['spacecraft'] . "\n");
	if (isset($metadata['starttime']))
		fwrite($votfile,'  Start time                 : ' . $metadata['starttime'] . "\n");
	if (isset($metadata['stoptime']))
		fwrite($votfile,'  Stop time                  : ' . $metadata['stoptime'] . "\n");
	if (isset($metadata['sampling']))
		fwrite($votfile,'  Sampling                   : ' . $metadata['sampling'] . "\n");

	fwrite($votfile,'</DESCRIPTION>' . "\n");

	// Open the interpolated file
	$intpolfile = fopen($hc_output_file, "r");
	if ($intpolfile === FALSE) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	// Check if the time field is included as the first column in the original point file
	// (as in the AMDA spacecraft data file). If yes then include it in the final votfile.

	$tmp_file_name = $point_file_name . ".tmp";
	exec("sed '/^#/d' " . $point_file_name . " > " . $tmp_file_name);
// 	$first_elem = trim(exec("sed '/^#/d' " . $point_file_name . " | head -n 1 | awk '{print $1}'"));	// This gives error: sed: couldn't flush stdout: Broken pipe
	$first_elem = trim(exec("head -n 1 " .  $tmp_file_name . "| awk '{print $1}'"));
	$time_included = (strtotime($first_elem) !== false) ? true : false;


//	$first_elem = trim(exec("sed '/^#/d' " . $point_file_name . " | head -n 1 | awk '{print $1}'"));
//	$time_included = (strtotime($first_elem) !== false) ? true : false;
	if ($time_included) {
		$pointfile = fopen($point_file_name, "r");
		if ($pointfile === FALSE) throw_error('Server', ERROR_INTERNAL_TMP_FILE);
	}


	// Write the fields and their metadata. The fields are listed in the header of the interpolated file. It starts with "# x y z"
	$header = trim(fgets($intpolfile));
	$header_array = preg_split("/[\s,]+/", $header);
	$col_ID = 1;
	if ($time_included) {
		fwrite($votfile,'<FIELD ID="col' . $col_ID . '" arraysize="*" datatype="char" name="Time" ucd="time.epoch" xtype="dateTime"/>' . "\n");
		$col_ID++;
	}
	for ($i = 1; $i < count($header_array); $i++) {
		fwrite($votfile,'<FIELD ID="col' . $col_ID . '" ');
		fwrite($votfile,'datatype="float" ');
		fwrite($votfile,'name="' . $hcintpol_names[$header_array[$i]] . '" ');
		fwrite($votfile,'ucd="' . $ucd_table[$header_array[$i]] . '" ');
		fwrite($votfile,'unit="' . $Units[$header_array[$i]] . '"');
		if (isset($param_description[$header_array[$i]])) {
			fwrite($votfile,'>' . "\n");
			fwrite($votfile, "\t" . '<DESCRIPTION>' . $param_description[$header_array[$i]] . '</DESCRIPTION>' . "\n");
//			fwrite($votfile, "\t" . '<VALUES NULL="-1.e+31"/>' . "\n");
			fwrite($votfile,'</FIELD>' . "\n");
		}
		else
			fwrite($votfile,'/>' . "\n");
		$col_ID++;
	}

	// Write the data
	fwrite($votfile,'<DATA>' . "\n");
	fwrite($votfile,'<TABLEDATA>' . "\n");

	while (($line = fgets($intpolfile, 4096)) !== false) {
		$data = preg_split("/[\s,]+/", trim($line));
		fwrite($votfile,"\t" . "<TR>" . "\n");
		if ($time_included) {
			$timeline = fgets($pointfile, 4096);
			while (($timeline !== false) && ($timeline[0] == '#')) $timeline = fgets($pointfile, 4096);
			if ($timeline !== false) {
				$timeline_data = preg_split("/[\s,]+/", trim($timeline));
				fwrite($votfile,"\t\t" . '<TD>' . $timeline_data[0] . '</TD>' . "\n");
			}
			else throw_error('Server', ERROR_INTERNAL_TMP_FILE);
		}
		for ($i=0; $i < count($data); $i++) {
//		foreach ($data as $data_value) {
			$data_value = $data[$i];
			fwrite($votfile,"\t\t" . '<TD>');
			if (($data_value != "-999") && ($data_value != "0"))
				fprintf($votfile,"%e", floatval($data_value));
			else {
				if ($i > 2)
					fwrite($votfile, "NaN");
				else
					fprintf($votfile,"%e", floatval($data_value));
			}

			fwrite($votfile,'</TD>' . "\n");
		}
		fwrite($votfile,"\t" . '</TR>' . "\n");
	}

	fwrite($votfile,'</TABLEDATA>' . "\n");
	fwrite($votfile,'</DATA>' . "\n");
	fwrite($votfile,'</TABLE>' . "\n");
	fwrite($votfile,'</RESOURCE>' . "\n");
	fwrite($votfile,'</VOTABLE>' . "\n");

	fclose($votfile);
	if ($time_included) { fclose($pointfile); }



	// Copy the file to final destination
	$final_file_name = "hwa_" . random_string(10) . ".vot";
	$command = "cp " . $votable_filename . " " . WWW_DATA_DIR . "/" . $final_file_name;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);
	clean_up([$votable_filename]);

	return($final_file_name);
}


/**
* -------------------------------------------------------------------------------------
* Generate a netCDF output file when the input file was VOTable
*
* @param string $hc_output_file    : Path to the output hcintpol ASCII file.
* @param string $point_file_name   : Path to the input ASCII file.
* @param array $param_description  : Array of variable descriptions.
* @return string                   : Path to the final VOTable file.
* @throws SoapFault                : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------------
*/

function votable_to_netcdf($hc_output_file, $point_file_name, $param_description) {

	// Get the header line which lists the order of parameters. It starts with "# x y z"
	$header = exec("head -n 1 " . $hc_output_file);
	$header_array = preg_split("/[\s,]+/", $header);

	$netcdf_file = write_netCDF($hc_output_file, $point_file_name, $header_array, $param_description);
	if (substr($netcdf_file,0,5) == "error") return $netcdf_file;	// Error message


	// Generate command for 'stilts tpipe' to set the units, ucds and missing values of the interpolated fields
// 	$cmd = "cmd='delcols \"X Y Z\"' ";
// 	for ($i = 4; $i < count($header_array); $i++) {		// Skip "# x y z"
// 		$cmd .= "cmd='colmeta -units \"" . $Units[$header_array[$i]] . "\" \"" . $header_array[$i] . "\"' ";
// 		$cmd .= "cmd='colmeta -ucd \"" . $ucd_table[$header_array[$i]] . "\" \"" . $header_array[$i] . "\"' ";
// 		$cmd .= "cmd='colmeta -desc \"" . $param_description[$header_array[$i]] . "\" \"" . $header_array[$i] . "\"' ";
// 		$cmd .= "cmd='replaceval -999 null \"" . $header_array[$i] . "\"' ";
// 	}
//
// 	$votable_filename = $point_file_name . ".votable";
// 	$command = STILTS . " tpipe ifmt=ascii ofmt=votable " . $cmd . $hc_output_file . " > " . $votable_filename;
// 	exec($command, $output, $return_var);
// 	if ($return_var != 0) return(ERROR_INTERNAL_TMP_FILE);


	// Combine the original votable file with the new interpolated data file
	$final_file_name = "hwa_" . random_string(10) . ".nc";
	$command = "cp " . $netcdf_file . " " . WWW_DATA_DIR . "/" . $final_file_name;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);
	clean_up([$netcdf_file]);

	return($final_file_name);
}

/**
* -------------------------------------------------------------------------------------
* Generate a netCDF output file when the input file was ASCII
*
* @param string $hc_output_file    : Path to the output hcintpol ASCII file.
* @param string $point_file_name   : Path to the input ASCII file.
* @param array $param_description  : Array of variable descriptions.
* @return string                   : Path to the final VOTable file.
* @throws SoapFault                : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------------
*/

function ascii_to_netcdf($hc_output_file, $point_file_name, $param_description) {

	// Get the header line which lists the order of parameters. It starts with "# x y z"
	$header = exec("head -n 1 " . $hc_output_file);
	$header_array = preg_split("/[\s,]+/", $header);

	$netcdf_file = write_netCDF($hc_output_file, $point_file_name, $header_array, $param_description);
	if (substr($netcdf_file,0,5) == "error") return $netcdf_file;	// Error message


	// Copy the file to final destination
	$final_file_name = "hwa_" . random_string(10) . ".nc";
	$command = "cp " . $netcdf_file . " " . WWW_DATA_DIR . "/" . $final_file_name;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);
	clean_up([$netcdf_file]);

	return($final_file_name);
}


/**
* -------------------------------------------------------------------------------------
* Write a netCDF file.
*
* @param string $hc_output_file    : Path to the output hcintpol ASCII file.
* @param string $point_file_name   : Path to the input ASCII file.
* @param array $header_array       : Array of variable names.
* @param array $param_description  : Array of variable descriptions.
* @return string                   : Path to the final netCDF file.
* @throws SoapFault                : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------------
*/

function write_netCDF($hc_output_file, $point_file_name, $header_array, $param_description) {

	global $metadata;
	global $Units;
	global $Unit_conversion_table;
	global $hcintpol_names;
	global $hcintpol_variables;

	require 'extra_functions/units.php';		// Units of different parameters
	require 'extra_functions/hcintpol_variables.php';			// List of variables accepted by hcintpol

	// Create the cdl file
	$netcdf_cdl_filename = $point_file_name . ".cdl";
	$cdl_file = fopen($netcdf_cdl_filename,"w");
	if (!$cdl_file)  throw_error('Server',ERROR_INTERNAL_TMP_FILE);

	// Header
	fwrite($cdl_file, "netcdf " . "simulation {\n");

	// dimensions
	fwrite($cdl_file, "\n" . "dimensions:" . "\n");
	fwrite($cdl_file, "\tdim_1 = 1;\n");
	fwrite($cdl_file, "\tdimtime = 19;\n");
	fwrite($cdl_file, "\tdimname = 20;\n");
	$count = intval(exec("cat " .  $hc_output_file . " | wc -l")) - 1;	// Number of datapoints
	fwrite($cdl_file, "\tcount = " . $count . ";\n");

	// variables
	fwrite($cdl_file, "\n" . "variables:" . "\n");

	// x,y,z and interpolated variables
	for ($i = 1; $i < count($header_array); $i++) {		// Skip the '#' character
		fwrite($cdl_file, "\tfloat " . $hcintpol_names[$header_array[$i]] . "(count);\n");
		if (isset($Units[$header_array[$i]]))
			fwrite($cdl_file, "\t\t" . $hcintpol_names[$header_array[$i]] . ':units = "' . $Units[$header_array[$i]] . '";' . "\n");
		fwrite($cdl_file, "\t\t" . $hcintpol_names[$header_array[$i]] . ':missing_value = -999;' . "\n");
		if (isset($param_description[$header_array[$i]]))
			fwrite($cdl_file, "\t\t" . $hcintpol_names[$header_array[$i]] . ':long_name = "' . $param_description[$header_array[$i]] . ', ' . $hcintpol_names[$header_array[$i]] . '";' . "\n");
	}


	// Other variables
	fwrite($cdl_file, "\t" . 'char planetname(dimname);' . "\n");
	fwrite($cdl_file, "\t" . 'float r_planet(dim_1);' . "\n");
	fwrite($cdl_file, "\t\t" . 'r_planet:units = "' . $metadata['planet_radius_unit'] . '";' . "\n");

	// Global attributes:
	fwrite($cdl_file, "\n// global attributes:" . "\n");
	fwrite($cdl_file, "\t" . ':Title = "' .$metadata['Title'] . '";' . "\n");
	fwrite($cdl_file, "\t" . ':SimulationModel = "' . $metadata['SimulationModel'] . '";' . "\n");
	fwrite($cdl_file, "\t" . ':SimulationRun = "' . $metadata['SimulationRun'] . '";' . "\n");
	fwrite($cdl_file, "\t" . ':NumericalOutput = "' . $metadata['NumericalOutput'] . '";' . "\n");
	fwrite($cdl_file, "\t" . ':InterpolationMethod = "' . $metadata['InterpolationMethod'] . '";' . "\n");

	// data:
	fwrite($cdl_file, "\n" . "data:" . "\n");
	fwrite($cdl_file, "\t" . 'planetname = "' . $metadata['planet_name'] . '";' . "\n");
	fwrite($cdl_file, "\t" . 'r_planet = ' . $metadata['planet_radius'] . ';' . "\n");

	for ($i = 1; $i < count($header_array); $i++) {		// Skip the '#' character
		fwrite($cdl_file, $hcintpol_names[$header_array[$i]] . " =\n");
		$command = "cut -d ' ' -f " . $i . " " . $hc_output_file . " > " . $point_file_name . "_" . $i;
		exec($command, $output, $return_var);
		if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

		$dfile = fopen($point_file_name . "_" . $i, "r");
		$buffer = fgets($dfile, 4096); // Skip first line containing the parameter name
		$line = 1;
		while (($buffer = fgets($dfile, 4096)) !== false) {
			if ($line < $count)
				fwrite($cdl_file, trim($buffer) . ",");
			else
				fwrite($cdl_file, trim($buffer) . ";");
			$line++;
			if ($line % 10 == 0) {
				fwrite($cdl_file, "\n");
				$j = 0;
			}
		}
		fclose($dfile);
		fwrite($cdl_file, "\n");
	}
	fwrite($cdl_file,"}\n");
	fclose($cdl_file);

	$command = "ncgen -o " . $point_file_name . ".nc " . $netcdf_cdl_filename;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);
	clean_up([$netcdf_cdl_filename]);

	return($point_file_name . ".nc");
}

/**
* -------------------------------------------------------------------------------------
* Generate a VOTable output file when the input file is VOTable file.
* If time field is included in the input file then copy it into the output file.
*
* @param string $hc_output_file    : Path to the output hcintpol ASCII file.
* @param string $point_file_name   : Path to the input ASCII file.
* @param array $VOT_info           : Information about votable fields
* @param object $InputParams       : Input parameters
* @return string                   : Path to the final VOTable file.
* @throws SoapFault                : Internal error in writing data to tmp file.
* --------------------------------------------------------------------------------------
*/

function votable_to_votable_new($hc_output_file, $point_file_name, $VOT_info, $InputParams) {

	global $metadata;
	global $hcintpol_names;
	global $ucd_table;
	global $Units;

	// Create the 'votable' file
	$votable_filename = TMP_DIR . "/hwa_" . random_string(10) . ".vot";
	$votfile = fopen($votable_filename, "w");
	if ($votfile === FALSE) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	fwrite($votfile, write_VOTable_header(""));

	fwrite($votfile,'<RESOURCE>' . "\n");

	// Get the number of data lines in the interpolated file
	$rows = intval(exec("wc -l " . $hc_output_file . " | cut -d ' ' -f 1")) - 1;

	// Write some metadata information
	fwrite($votfile,'<TABLE name="' . $VOT_info["Table_name"] . '" nrows="' . $rows . '">'. "\n");
	fwrite($votfile,'<DESCRIPTION>' . "\n");
	fwrite($votfile,"  " . write_VOTable_info($VOT_info["Description"], $InputParams) . "\n");
	fwrite($votfile,'</DESCRIPTION>' . "\n");

	// Write VOTable <PARAM> elements
	foreach($VOT_info["Param"] as $param) {
		fwrite($votfile, $param . "\n");
	}

	// Open the interpolated file
	$intpolfile = fopen($hc_output_file, "r");
	if ($intpolfile === FALSE) throw_error('Server', ERROR_INTERNAL_TMP_FILE);

	// Check if the time field is included as a field in the original point file
	// (as in the AMDA spacecraft data file). If yes then include it in the final votfile.
	// Load the VOTable file (= xml file) into a SimpleXML object
	$vot_xml = simplexml_load_file($point_file_name);

	// Some checkings
	if ($vot_xml->{'RESOURCE'}->count() != 1) throw_error('Server', ERROR_VOTABLE_FILE);	// No multitable votable files
	if (!isset($vot_xml->{'RESOURCE'}->{'TABLE'}->{'DATA'}->{'TABLEDATA'})) throw_error('Server', ERROR_VOTABLE_FILE);
	if (!isset($vot_xml->{'RESOURCE'}->{'TABLE'}->{'FIELD'})) throw_error('Server', ERROR_VOTABLE_FILE);

	// Number of data points
	$data_count = $vot_xml->{'RESOURCE'}->{'TABLE'}->{'DATA'}->{'TABLEDATA'}->{'TR'}->count();

	// Read the VOT file field names and units
	$fields = array();
	foreach($vot_xml->{'RESOURCE'}->{'TABLE'}->{'FIELD'} as $field) {
		$fields[strtoupper((string) $field['name'])] = array (	// key is in uppercase
			'name' => $field['name'],							// 'name' is as in VOT file
			'unit' => isset($field['unit']) ? $field['unit'] : "",
			'data' => array_fill(0, $data_count, "")	// Initialize the data array
		);
	}
	$vot_field_names = array_keys($fields);		// Field names in uppercase


	$time_included = isset($fields['TIME']);

	// Read the data for all fields
	if ($time_included) {
		$row = 0;
		foreach($vot_xml->{'RESOURCE'}->{'TABLE'}->{'DATA'}->{'TABLEDATA'}->{'TR'} as $TR) {
			$col = 0;
			foreach ($TR->{'TD'} as $TD)
				$fields[$vot_field_names[$col++]]['data'][$row] = (string) $TD;
			$row++;
		}
	}

	// Write the fields and their metadata. The fields are listed in the header of the interpolated file. It starts with "# x y z"
	$header = trim(fgets($intpolfile));
	$header_array = preg_split("/[\s,]+/", $header);
	$col_ID = 1;
	if ($time_included) {
		fwrite($votfile,'<FIELD ID="col' . $col_ID . '" arraysize="*" datatype="char" name="Time" ucd="time.epoch" xtype="dateTime"/>' . "\n");
		$col_ID++;
	}
	for ($i = 1; $i < count($header_array); $i++) {
		if (isset($hcintpol_names[$header_array[$i]])) {
			fwrite($votfile,'<FIELD ID="col' . $col_ID . '" ');
			fwrite($votfile,'datatype="float" ');
			fwrite($votfile,'name="' . $hcintpol_names[$header_array[$i]] . '" ');
			fwrite($votfile,'ucd="' . $ucd_table[$header_array[$i]] . '" ');
			fwrite($votfile,'unit="' . $Units[$header_array[$i]] . '" ');
			if ($header_array[$i] == "Ebin0")
				fwrite($votfile,'arraysize="' . (count($header_array) - 4) . '"');
// 			if (isset($param_description[$header_array[$i]])) {
// 				fwrite($votfile,'>' . "\n");
// 				fwrite($votfile, "\t" . '<DESCRIPTION>' . $param_description[$header_array[$i]] . '</DESCRIPTION>' . "\n");
// 				fwrite($votfile,'</FIELD>' . "\n");
// 			}
// 			else
				fwrite($votfile,'/>' . "\n");
			$col_ID++;
		}
	}

	// Write the data
	fwrite($votfile,'<DATA>' . "\n");
	fwrite($votfile,'<TABLEDATA>' . "\n");

	$row = 0;
	while (($line = fgets($intpolfile, 4096)) !== false) {
		$data = preg_split("/[\s,]+/", trim($line));
		fwrite($votfile,"\t" . "<TR>" . "\n");

		if ($time_included) {
			fwrite($votfile,"\t\t" . '<TD>' . $fields['TIME']['data'][$row++] . '</TD>' . "\n");
		}

		if (in_array("Ebin0", $header_array)) {		// Spectral channels
			// X,Y and Z
			for ($i=0; $i < 3; $i++) {
				fwrite($votfile,"\t\t" . '<TD>' . $data[$i] . '</TD>' . "\n");
			}
			// Particle counts
			$data_str = "";
			for ($i=3; $i < count($data); $i++) {
				$data_value = $data[$i];
				if ($data_value != "-999")
					$data_str .= $data_value . " ";
				else
					$data_str .= "NaN ";
			}
			$data_str = substr($data_str, 0, -1);	// Remove the " " at the end
			fwrite($votfile,"\t\t" . '<TD>' . $data_str . '</TD>' . "\n");
		}
		else {	// No spectral channels
			for ($i=0; $i < count($data); $i++) {
				$data_value = $data[$i];
				fwrite($votfile,"\t\t" . '<TD>');
				if (($data_value != "-999") && ($data_value != "0"))
					fprintf($votfile,"%e", floatval($data_value));
				else {
					if ($i > 2)
						fwrite($votfile, "NaN");
					else
						fprintf($votfile,"%e", floatval($data_value));
				}
				fwrite($votfile,'</TD>' . "\n");
			}
		}

		fwrite($votfile,"\t" . '</TR>' . "\n");
	}

	fwrite($votfile,'</TABLEDATA>' . "\n");
	fwrite($votfile,'</DATA>' . "\n");
	fwrite($votfile,'</TABLE>' . "\n");
	fwrite($votfile,'</RESOURCE>' . "\n");
	fwrite($votfile,'</VOTABLE>' . "\n");

	fclose($votfile);



	// Copy the file to final destination
	$final_file_name = "hwa_" . random_string(10) . ".vot";
	$command = "cp " . $votable_filename . " " . WWW_DATA_DIR . "/" . $final_file_name;
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Server', ERROR_INTERNAL_TMP_FILE);
	clean_up([$votable_filename]);

	return($final_file_name);
}
?>

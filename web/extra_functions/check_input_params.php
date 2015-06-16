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
//	              			check_input_params.php
//
// ##########################################################################

// ========================================================================
// This php script contains functions for checking various input parameters
// of the web service functions. If an error is found then a soap fault is
// generated.
// ========================================================================


/**
* -----------------------------------------------------------------------------
* Convert a variable to given type.
*
* @param string $type  : Defined type
* @param object $value : User given value of parameter
* @return object       : Variable converted to given type
* -----------------------------------------------------------------------------
*/

function convert_type($type, $value) {
	switch($type) {
		case "vector" :
			$fields = preg_split("/[\s,]+/", trim($value));
			return(array(floatval($fields[0]), floatval($fields[1]), floatval($fields[2])));
			break;
		case "array_6" :
			$fields = preg_split("/[\s,]+/", trim($value));
			return(array(floatval($fields[0]), floatval($fields[1]), floatval($fields[2]), floatval($fields[3]), floatval($fields[4]), floatval($fields[5])));
			break;
		case "float"   : return(floatval($value)); break;
		case "integer" : return(intval($value));   break;
		case "tokenlist" :
			$fields = preg_split("/[\s,]+/", trim($value));
			return($fields);
			break;
		default		   : return($value);
	}
}


/**
* -----------------------------------------------------------------------------
* Check that the type of user given parameter matches the defined type.
*
* @param string $type  : Defined type
* @param string $key   : Name of the parameter. Used in error string.
* @param object $value : User given value of parameter
* @throws SoapFault    : If the user given value does not match the
*                        defined type.
* -----------------------------------------------------------------------------
*/

function check_type($type, $key, $value) {
	switch ($type) {
		case 'string'   : if (!is_string($value)) throw_error('Client', ERROR_INPUT_VAR_FORMAT . $key . ". Should be string"); break;
		case 'integer'  : if (filter_var($value, FILTER_VALIDATE_INT) === FALSE) throw_error('Client', ERROR_INPUT_VAR_FORMAT . $key . ". Should be integer"); break;
		case 'float'    : if (filter_var($value, FILTER_VALIDATE_FLOAT) === FALSE) throw_error('Client', ERROR_INPUT_VAR_FORMAT . $key . ". Should be float"); break;
		case 'date'     : if (strtotime($value) === FALSE) throw_error('Client', ERROR_INPUT_VAR_FORMAT . $key . ". Should be ISO 8601 date"); break;
		case 'interval' :
			try { $dv = new DateInterval($value); }
			catch(Exception $e) { if (!is_numeric($value)) throw_error('Client', ERROR_INPUT_DURATION_FORMAT . $key . ". Should be ISO 8601 time interval"); } break;
		case 'vector'	:
			$fields = preg_split("/[\s,]+/", trim($value));
			if (count($fields) != 3) throw_error('Client', ERROR_INPUT_VAR_FORMAT . $key . ". Should have three components");
			foreach ($fields as $field)
				if (filter_var($field, FILTER_VALIDATE_FLOAT) === FALSE) throw_error('Client', ERROR_INPUT_VAR_FORMAT . $key . ". Should be 3 component vector of floats");
			break;
		case 'array_6'	:
			$fields = preg_split("/[\s,]+/", trim($value));
			if (count($fields) != 6) throw_error('Client', ERROR_INPUT_VAR_FORMAT . $key . ". Should have six components");
			foreach ($fields as $field)
				if (filter_var($field, FILTER_VALIDATE_FLOAT) === FALSE) throw_error('Client', ERROR_INPUT_VAR_FORMAT . $key . ". Should be 6 component array of floats");
			break;
		case 'tokenlist'   : if (!is_string($value)) throw_error('Client', ERROR_INPUT_VAR_FORMAT . $key . ". Should be a list of tokens"); break;
	}
}


/**
* -----------------------------------------------------------------------------
* Check the user given input parameters so that all required parameters
* are given and no illegal parameters are defined. Also if the parameter values
* are enumerated check that the given value matches one of them.
* Return back the $InputParams structure filled with user given values.
*
* @param array $InputParams : Array of all possible input parameters.
* @param array $UserGivenParams : Array of user given parameters.
* @return array             : Return the $InputParams array filled with values
*                             of user given parameters.
* @throws SoapFault         : If required parameter is not defined or if some
*                             unknown parameters are given.
* -----------------------------------------------------------------------------
*/

function check_input_params($InputParams, $UserGivenParams) {

	// Check that all mandatory parameters are defined
	foreach ($InputParams as $key => $value) {
		if (!in_array($key, ["extraParams", "SW_parameters"]) and $value['mandatory'])
			if (!isset($UserGivenParams->{$key})) throw_error('Client', ERROR_INPUT_PARAM_NOT_DEFINED . $key);
	}

	// Check $UserGivenParams
	foreach ($UserGivenParams as $key => $value) {

		// Check that the name of the input parameter is legal
		if (!in_array($key, array_keys($InputParams))) throw_error('Client', ERROR_ILLEGAL_PARAM . $key);

		if ($key == 'extraParams') {
			foreach($UserGivenParams->{'extraParams'} as $key1 => $value1) {
				if (!in_array($key1, array_keys($InputParams['extraParams']))) throw_error('Client', ERROR_ILLEGAL_EXTRA_PARAM . $key1);	// Name defined ?

				check_type($InputParams['extraParams'][$key1]['type'], $key1, $value1);	// Proper type ??

				if (isset($InputParams['extraParams'][$key1]['enum'])) {	// Enumerated ??
					if (!in_array($value1, $InputParams['extraParams'][$key1]['enum'])) throw_error('Client', ERROR_PARAM_VALUE . $value1);
				}

				// OK. Copy value to $InputParams
				$InputParams['extraParams'][$key1]['data'] = convert_type($InputParams['extraParams'][$key1]['type'], $value1);
			}
		}

		else if ($key == 'SW_parameters') {		// SW_parameters in getMostRelevantRun
			foreach($UserGivenParams->{'SW_parameters'} as $key1 => $value1) {
				if (!in_array($key1, array_keys($InputParams['SW_parameters']))) throw_error('Client', ERROR_ILLEGAL_EXTRA_PARAM . $key1);	// Name defined ?
			}
		}

		else {		// Check the normal parameters (not extraParams)

			// check that the type is OK and convert to proper format if necessary
			check_type($InputParams[$key]['type'], $key, $value);

			// Check that the value is ok if it is enumerated
			if (isset($InputParams[$key]['enum'])) {
				if (!in_array($value, $InputParams[$key]['enum'])) throw_error('Client', ERROR_PARAM_VALUE . $value);
			}

			// Value is OK. Copy it to $InputParams
			$InputParams[$key]['data'] = convert_type($InputParams[$key]['type'], $value);
		}
	}

	return $InputParams;
}


/**
* ---------------------------------------------------------------------------
* Check whether the client is using old format for ResourceID (impex://...).
* If so convert to the new format (spase://IMPEX/...).
*
* @param  string $ResourceID : ResourceID of a NumericalOutput element
* @return string             : ResourceID in the new format
* @throws SoapFault          : If ResourceID is not defined. Should never happen
*                              as SOAP should detect this error.
* ---------------------------------------------------------------------------
*/

function check_resourceID($ResourceID) {
	if (!isset($ResourceID)) throw_error('Client', ERROR_INPUT_PARAM_NOT_DEFINED . 'ResourceID');

	if (substr($ResourceID,0,5) == "spase") return $ResourceID;
	if (strpos($ResourceID, "HWA") !== false) return str_replace("impex://FMI/HWA", "spase://IMPEX/NumericalOutput/FMI", $ResourceID);
	if (strpos($ResourceID, "NumericalOutput") !== false) return str_replace("impex://FMI/NumericalOutput", "spase://IMPEX/NumericalOutput/FMI", $ResourceID);
	return $ResourceID;
}


/**
* ----------------------------------------------------------------------------
* Try to locate all <NumericalOutput> elements belonging to given
* simulation run element.
* If no elements are not found boolean false is returned.
*
* @param SimpleXMLElement $tree_xml       : Tree
* @param SimpleXMLElement $simulation_run : SimulationRun element
* @return array of SimpleXMLElement's     : Array of NumericalOutput elements
*                                         : False if none found.
* ----------------------------------------------------------------------------
*/

function locate_all_numerical_elements($tree_xml, $simulation_run ) {
	$num_outputs = array();
	$ResourceID = (string) $simulation_run->{'ResourceID'};
	foreach ($tree_xml->{'NumericalOutput'} as $num_output_elem) {
		if (((string) $num_output_elem->{'InputResourceID'}) == $ResourceID) {
			$num_outputs[] = $num_output_elem;
		}
	}

	if (count($num_outputs) > 0)
		return $num_outputs;
	else
		return false;
}


/**
* ----------------------------------------------------------------------------------------
* Locate the NumericalOutput element with the given ResourceID from the tree.
* If not found then throw an exception.
*
* @param SimpleXMLElement $tree_xml   : Tree
* @param string $ResourceID           : ResourceID of the NumericalOutput element (string)
* @return SimpleXMLElement            : NumericalOutput element
* @throws SoapFault 				  : If NumericalOutput element not found
* -----------------------------------------------------------------------------------------
*/

function get_NumericalOutput($tree_xml, $ResourceID) {
	// The $ResourceID may have extension ?sub-ResourceID. Remove the extension.
	// In tree there are no extensions in ResourceID's.
	$ResID = explode('?', $ResourceID)[0];

	// Go through all NumericalOutput elements in the tree and try to find the proper one
	foreach ($tree_xml->{'NumericalOutput'} as $num_output_elem) {
		if ($num_output_elem->{'ResourceID'} == $ResID) { return ($num_output_elem); }
	}

	// Not found. Throw error
	throw_error('Client', ERROR_NUM_OUTPUT_NOT_FOUND);
}


/**
* ------------------------------------------------------------------------------
* Try to locate the 'SimulationRun' element that is the parent of
* given 'NumericalOutput' element.
*
* @param SimpleXMLElement $tree_xml        : Tree
* @param SimpleXMLElement $num_output_elem : NumericalOutput element
* @return SimpleXMLElement                 : SimulationRun element
* @throws SoapFault 				       : If SimulationRun element not found
* ------------------------------------------------------------------------------
*/

function get_SimulationRun($tree_xml, $num_output_elem) {
	$ResourceID = (string) $num_output_elem->{'InputResourceID'};

	// Go through all SimulationRun elements and try to find the proper one
	foreach ($tree_xml->{'SimulationRun'} as $simulation_run) {
		if ((string) $simulation_run->{'ResourceID'} == $ResourceID) { return ($simulation_run); }
	}

	// Not found. Throw error
	throw_error('Server', ERROR_NUM_OUTPUT_NOT_FOUND);
}


/**
* ------------------------------------------------------------------------------
* Get the tree file (either HYB or GUMICS) as a SimpleXMLElement.
*
* @param string $ResourceID  : ResourceID of the NumericalOutput element (string)
* @return SimpleXMLElement   : The whole tree as a SimpleXMLElement element
* @throws SoapFault          : If tree.xml loading fails
* ------------------------------------------------------------------------------
*/

function get_Tree($ResourceID) {
	if (strpos($ResourceID,"GUMICS") !== false)
		$tree_xml = simplexml_load_file(TREE_GUMICS_FILE);
	else
		$tree_xml = simplexml_load_file(TREE_HYB_FILE);

	if ($tree_xml === false) throw_error('Server', ERROR_TREEXML_LOAD_FAIL);
	return $tree_xml;
}


/**
* --------------------------------------------------------------
* Get the simulation model of the run. Currently GUMICS or HYB.
*
* @return string   : Either 'GUMICS' or 'HYB'
* --------------------------------------------------------------
*/

function get_simu_model() {
	global $metadata;

	return (basename($metadata['SimulationModel']));
}


/**
* -----------------------------------------------------------------
* Copy the file defined in url from remote server into /tmp
* directory in local disk.
*
* @param string $url  : URL of the remote file (string)
* @return string      : Path to the local file
* @throws SoapFault   : If url file not found or couldn't be copied.
* ------------------------------------------------------------------
*/

function get_URL($url) {
	if (!isset($url)) throw_error('Client', ERROR_INPUT_PARAM_NOT_DEFINED . 'url_XYZ');

	// Try to copy the point data file into local /tmp directory
//	$point_file_name = tempnam(TMP_DIR, "hwa_") . "." . format_extension($url);
	$point_file_name = TMP_DIR . "/hwa_" . random_string(10) . "." . format_extension($url);
	$command = "wget -nv -O " . $point_file_name . " '" . $url . "'";
	exec($command, $output, $return_var);
	if ($return_var != 0) throw_error('Client', ERROR_URL_ERROR . $url);
	chmod($point_file_name, 0666);
	return $point_file_name;
}


/**
* -------------------------------------------------------------------------------------
* Construct the url of the final data file.
*
* @param string $final_file_name : Name of the final file. Does not contain full path.
* -------------------------------------------------------------------------------------
*/

function returnURL($final_file_name) {
	if (is_file(WWW_DATA_DIR . '/' . $final_file_name)) {
		chmod(WWW_DATA_DIR . '/' . $final_file_name, 0666);
		return(log_message(URL_DATA_DIR . "/" . $final_file_name));	// URL of the final interpolated data file
	}
	else {
		throw_error('Server', ERROR_EMPTY_FILE);
	}
}


/**
* -----------------------------------------------------------------------
* Get the path to the .hc file containing the simulation data
*
* @param string $ResourceID  : ResourceID of the NumericalOutput element
* @param SimpleXMLElement $num_output_elem : NumericalOutput element
* @return string             : Path to the .hc file
* @throws SoapFault          : If the .hc does not exist
* ------------------------------------------------------------------------
*/

function get_hc_file($ResourceID, $num_output_elem) {
	if (strpos($ResourceID, '?') === false) {
		// No sub-ResourceID's
		$hc_file = $num_output_elem->{'AccessInformation'}->{'AccessURL'}->{'ProductKey'};
		$hc_file = str_replace(array("earth", "EARTH"), array("eclat", "ECLAT"), $hc_file);
	} else {
		// This is one of the GUMICS dynamic runs.
		$dummy = explode('?', $ResourceID);
		$sub_ResID = $dummy[1];
		$hc_file = locate_hc_file($sub_ResID);
	}
	if (!is_file($hc_file)) throw_error('Server', ERROR_NO_HC_FILE);
	return ($hc_file);
}


/**
* -----------------------------------------------------------------------
* Check the given input parameter value
*
* @param string $filename  : Name of file
* @return string           : File format extension
* @throws SoapFault        : Illegal value
* ------------------------------------------------------------------------
*/

function format_extension($filename) {
	$extension = pathinfo($filename, PATHINFO_EXTENSION);
	switch(strtolower($extension)) {
		case "vo"      : return("vot"); break;
		case "vot"     : return("vot"); break;
		case "votable" : return("vot"); break;
		case "xml"     : return("vot"); break;
		case "nc"      : return("nc");  break;
		case "cdf"     : return("nc");  break;
		case "netcdf"  : return("nc");  break;
		case "txt"     : return("txt"); break;
		case "dat"     : return("txt"); break;
		case ""        : return(""); break;
		default        : throw_error('Client', ERROR_INPUT_FORMAT . $extension); break;
	}
}


/**
* -----------------------------------------------------------------------
* Determine file input format from the file extension.
* If file format is not recognized "VOTable" is returned.
*
* @param string $filename  : Name of file
* @return string           : Input Format string
* ------------------------------------------------------------------------
*/

function get_file_format($filename) {
	switch(format_extension($filename)) {
		case "vot" : return("VOTable"); break;
		case "nc"  : return("netCDF");  break;
		case "txt" : return("ASCII");   break;
		default    : return("VOTable"); break;
	}
}


/**
* -----------------------------------------------------------------------
* Get the user defined set of variables and check that they exist in the
* given NumericalOutput element. If user did not provide a variable list
* then return all variables found in the NumericalOutput element.
*
* @param string $user_variables  : User provided list of variables
* @param SimpleXMLElement $num_output_elem : NumericalOutput element
* @return array                  : Array of variables. Key = variable name
*                                  Value = variable description
* @throws SoapFault              : Illegal variable name
* ------------------------------------------------------------------------
*/

function get_variable_list($user_variables, $num_output_elem) {

	// Get all variables in the NumericalOutput element
	$params_all = array();	// Names (key) and description (value) of variables found in <NumericalOutput> element

	foreach ($num_output_elem->{'Parameter'} as $parameter) {	// Check all <Parameter> elements
		// $parameter->{'ParameterKey'} may be a single name (e.g. "Btot") or
		// comma or space separated list of variables, e.g. "Ux,Uy,Uz" or "Ux Uy Uz"
		$pars = preg_split("/[\s,]+/", trim($parameter->{'ParameterKey'}));
		foreach ($pars as $par) {
			$params_all[$par] = (string) $parameter->{'Name'};		// Value is the description
		}
	}

	// Check that all user defined variables ($user_variables) exist in $params_all
	if (($user_variables !== null) and ($user_variables !== "")) {
		$params_given = preg_split("/[\s,]+/", trim($user_variables));
		$param_list = array();
		foreach ($params_given as $param) {
			if (!in_array($param, array_keys($params_all))) return throw_error('Client', ERROR_UNDEFINED_VARIBLE . $param);
			$param_list[$param] = $params_all[$param];
		}
		return $param_list;
	}
	else {
		// $user_variables is not defined so include all variables defined in NumericalOutput
		return $params_all;
	}
}


/**
* ----------------------------------------------------------------------------------
* Convert variable names to those used by hcintpol.
*
* @param array $var_array : Names of variables as in NumericalOutput element
* @return string          : Names of variables in hcintpol format as a list (string)
* -----------------------------------------------------------------------------------
*/

function get_hc_variable_names($var_array) {
	global $hcintpol_variables;

	$hc_var_array = array();
	foreach($var_array as $var)
		$hc_var_array[] = $hcintpol_variables[$var];
	return (implode(",", $hc_var_array));
}


?>

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
// 								      		getFieldLine.php
//
// ######################################################################################################
// ======================================================================================================
// Compute field lines for given vector quantity (currently B or U) which start from given set of points.
//
//	$params = array
//		string 'ResourceID'		 x  Resource ID of the <NumericalOutput> element in tree.xml
//		string 'Variable'			List of individual parameters from the selected
//									NumericalOutput separated by a comma.
//									Identified by Spase/NumericalOutput/Parameter/ParameterKey.
//									By default: all vector parameters in the NumericalOutput
//									Currently supported:
//                                  	Magnetic field lines (Bx,By,Bz or Btot in 'Variable').
//										Velocity lines (Ux,Uy,Uz or Utot in 'Variable').
//		url 'url_XYZ'			x	URL of the VOTable file containing the 3D coordinates of field line
//									start points.
//		array 'extraParams'			Associative array of additional parameters. Different for each SMDB
//			string 'Direction'	   			Enumerated list of Direction parameter
//											('Forward' (default),'Backward' or 'Both')
//			float 'StepSize'	    		Length of one step in the tracing.
//			int 'MaxSteps'	    			The maximum number of steps per field/stream line
//			float 'StopCondition_Radius'	The tracing is stopped if the distance from the center
//											of object is smaller that StopCondition_Radius.
//			list 'StopCondition_Region'	    The tracing is stopped if the point is outside this cube
//											is smaller that StopCondition_Radius. The format of list is
//											"Xmin Xmax Ymin Ymax Zmin Zmax"
//			string 'OutputFileType'			Enumerated list of output file formats.
//											FMI supports only VOTable.
//
//	Parameters indicated by 'x' are mandatory.
// ======================================================================================================

// ======================================================================================================
// Version 2.0  2015-05-31	First release of the new code.
// ======================================================================================================

function getFieldLine($params) {

	// --------------------------------------------
	// Log caller info and method input parameters
	// --------------------------------------------

	log_method("getFieldLine", $params);


	// ----------------------------------------------------------------------------
	// Define a structure to handle Input parameters.
	// The user given values will be stored in the 'data' field.
	// The 'data' field is initially set to default value.
	// ----------------------------------------------------------------------------

	$InputParams = [
		'ResourceID'  => ['type' => 'string', 'mandatory' => true,  'data' => null],
		'Variable'    => ['type' => 'string', 'mandatory' => false, 'data' => ""],
		'url_XYZ'	  => ['type' => 'string', 'mandatory' => true,  'data' => null],
		'extraParams' => [
			'Direction'				=> ['type' => 'string',  'mandatory' => false, 'enum' => ['Forward', 'Backward', 'Both'], 'data' => 'Forward'],
			'StepSize'				=> ['type' => 'float',   'mandatory' => false, 'data' => null],
			'MaxSteps'				=> ['type' => 'integer', 'mandatory' => false, 'data' => 100],
			'StopCondition_Radius'	=> ['type' => 'float',   'mandatory' => false, 'data' => null],
			'StopCondition_Region'	=> ['type' => 'array_6', 'mandatory' => false, 'data' => null],
			'OutputFileType'		=> ['type' => 'string',  'mandatory' => false, 'enum' => ['VOTable'], 'data' => 'VOTable']
		]
	];


	// --------------------------------------------------
	// Handle the user given input parameters ($params).
	// --------------------------------------------------

	// Check that mandatory parameters are included in user given input parameter
	// values ($params) and copy the values of all parameters to $InputParams.
	// Check also that given parameters are of proper type.
	// Set also default values to parameters that the user has not defined.
	$InputParams = check_input_params($InputParams, $params);

	// Check the ResourceID and convert to new format if it is in old format.
	$InputParams['ResourceID']['data'] = check_resourceID($InputParams['ResourceID']['data']);

	// Load the tree file and get the NumericalOutput and SimulationRun elements
	$tree_xml = get_Tree($InputParams['ResourceID']['data']);
	$num_output_elem = get_NumericalOutput($tree_xml, $InputParams['ResourceID']['data']);
	$simulation_run_elem = get_SimulationRun($tree_xml, $num_output_elem);

	// Read the user defined list of variables into $InputParams['Variable']['data']
	// as an array (key = variable name, value = variable description) and check
	// that $num_output_elem contains the given variable names.
	$InputParams['Variable']['data'] = get_variable_list($InputParams['Variable']['data'], $num_output_elem);

	// Check that either magnetic field (Bx,By,Bz or Btot) or velocity (Ux,Uy,Uz or Utot) is defined
	$vars = array_keys($InputParams['Variable']['data']);
	if ((array_intersect($vars, ["Bx","By","Bz","Btot"]) == []) and (array_intersect($vars, ["Ux","Uy","Uz","Utot"]) == []))
		throw_error('Client', ERROR_FIELD_LINE_PARAM);

	// Copy the point file defined in parameter 'url_XYZ' into local disk
	$point_file_name = get_URL($InputParams['url_XYZ']['data']);

	// Read some metadata from $tree_xml into $metadata global variable
	get_metadata($tree_xml, $simulation_run_elem, $num_output_elem, $InputParams);

	// Set the default StepSize to 1/4 of the size of smallest grid cell
	if ($InputParams['extraParams']['StepSize']['data'] == null) {
		$cell_sizes = preg_split("/[\s]+/", $simulation_run_elem->{'SimulationDomain'}->{'GridCellSize'});
		$InputParams['extraParams']['StepSize']['data'] = floatval($cell_sizes[0])/4;
	}


	// -------------------------
	// Compute the field lines.
	// -------------------------

	$final_file_name = handle_field_lines($point_file_name, $num_output_elem, $simulation_run_elem, $InputParams);

	// --------------
	// Final touches
	// --------------

	clean_up([$point_file_name]);
	return(returnURL($final_file_name));

}	// THE END

?>

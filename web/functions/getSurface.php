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
// #####################################################################################################
//
// 								      		getSurface.php
//
// #####################################################################################################
// =====================================================================================================
// Compute interpolated values on a plane surface for parameters defined in $params input variable.
//
//	$params = array
//		string 'ResourceID'		   x  Resource ID of the <NumericalOutput> element in tree.xml
//		string 'Variable'		  	  List of individual parameters from the selected
//									  NumericalOutput separated by a comma.
//									  Identified by Spase/NumericalOutput/Parameter/ParameterKey.
//									  By default: all parameters in the NumericalOutput are sent back.
//		string 'PlaneNormalVector' x  3D components of a normalized vector normal to the requested plane
//                                    = string of 3 floats delimited by " " or ","
//		string 'PlanePoint'        x  A 3D coordinate indicating the position of one point in the
//                                    requested plane.
//                                    = string of 3 floats delimited by " " or ","
//		array 'extraParams'			  Associative array of additional parameters. Different for each SMDB
//			float 'Resolution'	          Spatial resolution of the mesh grid (The unit defined in
//										  <SimulationRun>)
//                                        Default : basic grid size
//			string 'InterpolationMethod'  Enumerated list of interpolation method to be defined
//								          (NearestGridPoint, Linear (default))
//			string 'OutputFiletype'	      Enumerated list of output file formats.
//									      FMI supports ASCII, VOTable (default) and netCDF.
//
//	Parameters indicated by 'x' are mandatory.
// =====================================================================================================

// =====================================================================================================
// Version 2.0 2015-06-01		First release of the new code.
// =====================================================================================================

function getSurface($params) {

	// --------------------------------
	// Log caller info and method name
	// --------------------------------

	log_method("getSurface", $params);


	// ----------------------------------------------------------------------------
	// Define a structure to handle Input parameters.
	// The user given values will be stored in the 'data' field.
	// The 'data' field is initially set to default value.
	// ----------------------------------------------------------------------------

	$InputParams = [
		'ResourceID'  		=> ['type' => 'string',   'mandatory' => true,  'data' => null],
		'Variable'    		=> ['type' => 'string',   'mandatory' => false, 'data' => ""],
		'PlaneNormalVector' => ['type' => 'vector',   'mandatory' => true,  'data' => null],
		'PlanePoint' 		=> ['type' => 'vector',   'mandatory' => true,  'data' => null],
		'extraParams' => [
			'Resolution' 		  => ['type' => 'float',  'mandatory' => false, 'data' => null],
			'InterpolationMethod' => ['type' => 'string', 'mandatory' => false, 'enum' => ['Linear', 'NearestGridPoint'], 'data' => 'Linear'],
			'OutputFileType'      => ['type' => 'string', 'mandatory' => false, 'enum' => ['netCDF', 'VOTable', 'ASCII'], 'data' => 'VOTable']
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

	// Check the ResourceID and convert to new format if it is in old format (FMI only).
	$InputParams['ResourceID']['data'] = check_resourceID($InputParams['ResourceID']['data']);

	// Load the tree file and get the NumericalOutput and SimulationRun elements
	$tree_xml = get_Tree($InputParams['ResourceID']['data']);
	$num_output_elem = get_NumericalOutput($tree_xml, $InputParams['ResourceID']['data']);
	$simulation_run_elem = get_SimulationRun($tree_xml, $num_output_elem);

	// Read the user defined list of variables into $InputParams['Variable']['data']
	// as an array (key = variable name, value = variable description) and check
	// that $num_output_elem contains the given variable names.
	$InputParams['Variable']['data'] = get_variable_list($InputParams['Variable']['data'], $num_output_elem);

	// Read some metadata from $tree_xml into $metadata global variable
	get_metadata($tree_xml, $simulation_run_elem, $num_output_elem, $InputParams);

	// Normalize 'PlaneNormalVector' to unit vector
	$InputParams['PlaneNormalVector']['data'] = unit_vector($InputParams['PlaneNormalVector']['data']);

	// Check the input parameter PlaneNormalVector. In general case it could be pointing to any direction.
	// However, here we accept only cases where PlaneNormalVector is parallel to any three coordinate axis.
	// The general case remains to be written.
	// Vector which is parallel to any of the three coordinate axis will have two zero components.
	$v = $InputParams['PlaneNormalVector']['data'];
	if 	(($v[0] != 0.0) and ($v[1] != 0.0)) throw_error('Client', ERROR_NORMAL_VECTOR_DIR);
	if 	(($v[0] != 0.0) and ($v[2] != 0.0)) throw_error('Client', ERROR_NORMAL_VECTOR_DIR);
	if 	(($v[1] != 0.0) and ($v[2] != 0.0)) throw_error('Client', ERROR_NORMAL_VECTOR_DIR);

	// If Resolution not defined then set to default value
	if ($InputParams['extraParams']['Resolution']['data'] == null) {
		$InputParams['extraParams']['Resolution']['data'] = get_basic_grid_cell_size($simulation_run_elem);
	}


	// -----------------------------------------------------------------------------
	// Generate the plane mesh and store the points in a simple ascii file which is
	// readable by hcintpol.
	// -----------------------------------------------------------------------------

	if ($v[2] != 0.0) 	// XY plane
		$point_file_name = write_plane_mesh($simulation_run_elem, $InputParams['PlanePoint']['data'], "XY", $InputParams['extraParams']['Resolution']['data']);
	if ($v[0] != 0.0)	// YZ plane
		$point_file_name = write_plane_mesh($simulation_run_elem, $InputParams['PlanePoint']['data'], "YZ", $InputParams['extraParams']['Resolution']['data']);
	if ($v[1] != 0.0)	// XZ plane
		$point_file_name = write_plane_mesh($simulation_run_elem, $InputParams['PlanePoint']['data'], "XZ", $InputParams['extraParams']['Resolution']['data']);


	// --------------------------------------------------
	// Handle the computation of interpolated values.
	// --------------------------------------------------

	$final_file_name = handle_interpolation($point_file_name, $num_output_elem, $simulation_run_elem, $InputParams);


	// --------------
	// Final touches
	// --------------

	clean_up([$point_file_name]);
	return(returnURL($final_file_name));

}	// THE END
?>

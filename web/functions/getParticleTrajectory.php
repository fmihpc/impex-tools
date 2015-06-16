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
// 								      		getParticleTrajectory.php
//
// ######################################################################################################
// ======================================================================================================
// Compute particle trajectories for particles with initial position, velocity, charge and mass.
//
//	$params = array
//		string 'ResourceID'		x  Resource ID of the <NumericalOutput> element in tree.xml
//		url 'url_XYZ'			x	URL of the VOTable file containing the initial 3D positions,
//                                  velocities, masses and charges for a set of particles.
//		array 'extraParams'			Associative array of additional parameters. Different for each SMDB
//			string 'Direction'	   			Enumerated list of Direction parameter.
//											('Forward' (default),'Backward' or 'Both').
//			float 'StepSize'	    		Length of one step in the tracing.
//			int 'MaxSteps'	    			The maximum number of steps per field/stream line
//			float 'StopCondition_Radius'	The tracing is stopped if the distance from the center
//											of object is smaller that StopCondition_Radius.
//			list 'StopCondition_Region'	    The tracing is stopped if the point is outside this cube.
//											The format of list is "Xmin Xmax Ymin Ymax Zmin Zmax".
//			string 'InterpolationMethod'	Enumerated list of interpolation method to be defined
//											(NearestGridPoint, Linear (default))
//			string 'OutputFileType'			Enumerated list of output file formats.
//											FMI supports only VOTable.
//
//	Parameters indicated by 'x' are mandatory.
// =====================================================================================================

// =====================================================================================================
// Version 2.0  2015-05-31	First release of the new code.
// =====================================================================================================

function getParticleTrajectory($params) {

	// --------------------------------------------
	// Log caller info and method input parameters
	// --------------------------------------------

	log_method("getParticleTrajectory", $params);


	// ----------------------------------------------------------------------------
	// Define a structure to handle Input parameters.
	// The user given values will be stored in the 'data' field.
	// The 'data' field is initially set to default value.
	// ----------------------------------------------------------------------------

	$InputParams = [
		'ResourceID'  => ['type' => 'string', 'mandatory' => true,  'data' => null],
		'url_XYZ'	  => ['type' => 'string', 'mandatory' => true,  'data' => null],
		'extraParams' => [
			'Direction'				=> ['type' => 'string',  'mandatory' => false, 'enum' => ['Forward', 'Backward', 'Both'], 'data' => 'Forward'],
			'StepSize'				=> ['type' => 'float',   'mandatory' => false, 'data' => null],
			'MaxSteps'				=> ['type' => 'integer', 'mandatory' => false, 'data' => 100],
			'StopCondition_Radius'	=> ['type' => 'float',   'mandatory' => false, 'data' => 0],
			'StopCondition_Region'	=> ['type' => 'array_6', 'mandatory' => false, 'data' => null],
			'InterpolationMethod' 	=> ['type' => 'string',  'mandatory' => false, 'enum' => ['Linear', 'NearestGridPoint'], 'data' => 'Linear'],
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

	// Copy the point file defined in parameter 'url_XYZ' into local disk
	$point_file_name = get_URL($InputParams['url_XYZ']['data']);

	// Read some metadata from $tree_xml into $metadata global variable
	get_metadata($tree_xml, $simulation_run_elem, $num_output_elem, $InputParams);

	// StepSize : If the user has not set this then set to 1/4 of the size of smallest grid cell
	if (!isset($params->{'extraParams'}->{'StepSize'})) {
		$cell_sizes = preg_split("/[\s]+/", $simulation_run_elem->{'SimulationDomain'}->{'GridCellSize'});
		$InputParams['extraParams']['StepSize']['data'] = floatval($cell_sizes[0])/4;
	}

	// StopCondition_Region : If the user has not set this then set to the ValidMin - ValidMax box
	if (!isset($params->{'extraParams'}->{'StopCondition_Region'})) {
		$Valid_min = preg_split("/[\s]+/", $simulation_run_elem->{'SimulationDomain'}->{'ValidMin'});
		$Valid_max = preg_split("/[\s]+/", $simulation_run_elem->{'SimulationDomain'}->{'ValidMax'});
		$InputParams['extraParams']['StopCondition_Region']['data'] = array(floatval($Valid_min[0]), floatval($Valid_max[0]), floatval($Valid_min[1]), floatval($Valid_max[1]), floatval($Valid_min[2]), floatval($Valid_max[2]));
	}


	// ----------------------------------
	// Compute the particle trajectories
	// ----------------------------------

	$final_file_name = handle_particle_trajectories($point_file_name, $num_output_elem, $simulation_run_elem, $tree_xml, $InputParams);


	// --------------
	// Final touches
	// --------------

	clean_up([$point_file_name]);
	return(returnURL($final_file_name));

}	// THE END

?>

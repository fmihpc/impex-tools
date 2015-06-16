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

// #################################################################
//
//	              			errors.php
//
// #################################################################

// #############################################################
// This file defines error message names and the corresponding
// informative error message texts.
// #############################################################

define('ERROR_TREEXML_LOAD_FAIL', 'error: Internal configuration error, tree.xml loading failed.');
define('ERROR_INPUT_PARAM_NOT_DEFINED', 'error: Missing input parameter : ');
define('ERROR_NUM_OUTPUT_NOT_FOUND', 'error: NumericalOutput element not found in tree.xml .');
define('ERROR_NUM_SIM_RUN_FOUND', 'error: SimulationRun element not found in tree.xml .');
define('ERROR_UNDEFINED_VARIBLE', 'error: List of allowed ParameterKeys does not contain variable : ');
define('ERROR_URL_ERROR', 'error: Could not download input data file : ');
define('ERROR_INT_POL_METHOD', 'error: Undefined interpolation method : ');
define('ERROR_INPUT_FORMAT', 'error: Input format not recognized : ');
define('ERROR_OUTPUT_FORMAT', 'error: Undefined output format : ');
define('ERROR_ILLEGAL_PARAM', 'error: Illegal input parameter : ');
define('ERROR_ILLEGAL_EXTRA_PARAM', 'error: Illegal input extraParam : ');
define('ERROR_METHOD_NOT_IMPLEMENTED', 'error : Method not implemented yet.');
define('ERROR_INTERNAL_TIME', 'error : Internal error in checking time dependency.');
define('ERROR_INTERNAL_TMP_FILE', 'error : Internal error in generating tmp file.');
define('ERROR_INTERNAL_HCINTPOL', 'error : Internal error in producing interpolated data.');
define('ERROR_VOTABLE_FILE', 'error : Could not read VOTable file.');
define('ERROR_VOTABLE_FIELDS', 'error : Could not read fields X, Y and Z from VOTable');
define('ERROR_VOTABLE_UNITS', 'error : Units for fields X, Y and Z not defined in VOTable ?');
define('ERROR_MISSING_VOTABLE_FIELD', 'error : The VOTable does not contain required field : ');
define('ERROR_INPUT_VAR_FORMAT', 'error : Type of given input parameter is wrong  : ');
define('ERROR_INPUT_TIME_FORMAT', 'error : Time format is not ISO 8601 : ');
define('ERROR_INPUT_DURATION_FORMAT', 'error : Time duration format is not ISO 8601 : ');
define('ERROR_UNKNOWN_SPACECRAFT', 'error : Unknown spacecraft name : ');
define('ERROR_AMDA', 'error : Could not download spacecraft orbit data from AMDA.');
define('ERROR_AMDA_COORD', 'error : Different coordinate systems used in simulation and AMDA provided ephemeris data');
define('ERROR_NORMAL_VECTOR', 'error : PlaneNormalVector is not 3 component vector : ');
define('ERROR_NORMAL_VECTOR_DIR', 'error : PlaneNormalVector must be parallel to any of the three coordinate axis.');
define('ERROR_NORMAL_VECTOR_LEN', 'error : PlaneNormalVector cannot be zero length.');
define('ERROR_PLANE_POINT', 'error : PlanePoint is not 3 component object : ');
define('ERROR_SW_PARAM_NOT_DEFINED', 'error: Solar wind input parameter not defined : ');
define('ERROR_INPUT_PARAM_EMPTY', 'error: Input parameter empty : ');
define('ERROR_INPUT_MASS_CHARGE', 'error: Mass and charge are optional, but they need to be defined together.');
define('ERROR_FUNCTION_PARSE', 'error: Failed to parse the given function string.');
define('ERROR_ARRAY_LENGTH', 'error: Data arrays are not of same length.');
define('ERROR_INPUT_PARAM', 'error: Illegal value for input parameter : ');
define('ERROR_FIELD_LINE', 'error: The field line tracer program failed ?!');
define('ERROR_FIELD_LINE_PARAM', 'error: The field line tracer requires either Btot or Utot as variable ?!');
define('ERROR_PARAM_VALUE', 'error: Illegal input parameter value : ');
define('ERROR_ARRAY_DIM', 'error: Illegal array dimension : ');
define('ERROR_MASS_CHARGE', 'error: Masses and charges must be same for all particles : ');
define('ERROR_EMPTY_FILE', 'error: No file generated, all datapoints outside simulation region ? ');
define('ERROR_POINTS_OUTSIDE', 'error: All initial points are outside of the simulation box.');
define('ERROR_SCALE_VALUE', 'error: Scale value cannot be zero: ');
define('ERROR_NO_SPECTRA', 'error: NumericalOutput element does not contain spectral information: ');
define('ERROR_NO_HC_FILE', 'error: Error in reading local data file');
define('ERROR_NO_DATETIME', 'error: In dynamical runs the NumericalOutput ResourceID must end with a datetime extension ?YYYYMMDD_HHMMSS');
?>

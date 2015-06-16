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
//	              			    config.php
//
// ##########################################################################

// ===============================================================
// This php script contains some system configuration parameters.
// Fill missing paths with your own.
// ===============================================================

define('MY_WS_DIR', "Put your path here");
define('TREE_HYB_FILE'   , MY_WS_DIR . '/Tree_FMI_HYB.xml');			// Path to the FMI HYB tree.xml file
define('TREE_GUMICS_FILE', MY_WS_DIR . '/Tree_FMI_GUMICS.xml');			// Path to the FMI GUMICS tree.xml file
define('METHODS_FILE'    , MY_WS_DIR . '/Methods_FMI.wsdl');			// Path to the FMI methods.wsdl file
define('METHODS_FILE_URL', 'http://'  . $_SERVER['HTTP_HOST'] . "Put your local path here" . '/Methods_FMI.wsdl');	// URL of the FMI methods.wsdl file

define('BIN'      , MY_WS_DIR . '/bin');						// Directory where essential binaries are located.
define('HCINTPOL' , BIN . '/hcintpol');							// 'hcintpol' is THE program which extracts data from simulation product (.hc) files
define('FTRACER'  , BIN . '/ft');								// Program for computing field lines
define('IONTRACER', BIN . "/iontracer");						// Program for computing ion trajectories
define('STILTS'   , 'java -jar ' . BIN . '/stilts.jar ');		// Stilts command is used to handle some VOTable files.

define('WWW_DATA_DIR', "Local path to data dir");							// Resulting data files are returned to the user in 'data' directory.
define('URL_DATA_DIR', 'http://' . $_SERVER['HTTP_HOST'] . "Path");			// URL of the 'data' directory
define('TMP_DIR'     , '/tmp');												// Place for temporary data files
define('LOG_FILE'    , MY_WS_DIR . '/log.txt');								// All method calls, parameters and returned values are logged in this file
define('DEBUG_FILE'  , MY_WS_DIR . '/debug.txt');							// File for debugging purposes
define('GUMICS_DYN_DIR', "Path to GUMICS dynamical runs");					// Directory where Gumics dynamical runs are located
define('GUMICS_DATETIME', MY_WS_DIR . '/GUMICS_input/GUMICS_datetime.txt');	// File listing times of all Gumics dynamical runs
?>

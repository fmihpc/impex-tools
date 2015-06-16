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

// ########################################################################
//
//	              				Methods_FMI.php
//
// ########################################################################

// =======================================================================
// This php script file contains the code for the web services defined in
// Methods_FMI.wsdl. This is the code that will be executed when a web
// service call is received. The url of the called function (soap address)
// is defined at the end of the file 'Methods_FMI.wsdl' .
// =======================================================================

// -------------------------------
// Some PHP settings
// -------------------------------

ini_set('memory_limit', '512M');			// Try to set the upper limit on memory usage.
ini_set('max_execution_time', 300);			// Some scripts may take a long time to process
ini_set('default_socket_timeout', 300);		// Also this must be set, otherwise the socket will time out
											// Note: These last two limits must be set also in the client side

ini_set('soap.wsdl_cache_enabled', '0');	// Caches are from hell, disable them.
ini_set('soap.wsdl_cache_ttl', '0');


// -------------------------------
// Load some supporting functions
// -------------------------------

require 'config.php';								// System config parameters (path definitions etc.)

require 'extra_functions/globals.php';				// Some global constants
require 'extra_functions/errors.php';				// Definitions of error message texts.
require 'extra_functions/misc_functions.php';		// Load some supporting functions.
require 'extra_functions/format_conversions.php';	// Load some format conversion functions.
require 'extra_functions/tmp_clean.php';			// Clean the /tmp directory = remove files older than one month.
require 'extra_functions/check_input_params.php';	// Check the input parameters of the web service functions.
require 'extra_functions/ucd.php';					// Definitions of UCD's (Unified Content Descriptors).
require 'extra_functions/units.php';				// Definitions of units of various physical quantities.
require 'extra_functions/hcintpol_variables.php';	// Definitions of variable names used by hcintpol.
require 'extra_functions/amda.php';					// Functions for downloading spacecraft orbital data from AMDA


// ==========================================
// Methods defined by FMI IMPEx web services
// ==========================================

$Methods = array(
	'getDataPointValue',
	'getDataPointValueSpacecraft',
	'getSurface',
	'getMostRelevantRun',
	'getVOTableURL',
	'getFieldLine',
	'getParticleTrajectory',
	'getDataPointSpectra',
	'getDataPointSpectraSpacecraft',
	'isAlive'
);


// =============================================
// Each function defining a method is loaded
// from a separate file in 'functions' directory.
// =============================================

foreach($Methods as $Method) {
	require 'functions/' . $Method . '.php';
}


// =======================================
// Initialize the SOAP web service server
// =======================================

$server = new SoapServer(METHODS_FILE, array('cache_wsdl' => WSDL_CACHE_NONE));

foreach($Methods as $Method) {
	$server->addFunction($Method);
}

$server->handle();		// Handle the web service request

?>

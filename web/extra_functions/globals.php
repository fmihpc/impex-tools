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
//	              			    globals.php
//
// ##########################################################################

// ===============================================================
// This php script contains definitions of some global variables.
// ===============================================================

$metadata = [];		// Array containing some metadata about simulation etc.

$mass_p   = 1.67262178e-27;		// Mass of proton (kg)
$charge_e = 1.602177e-19;		// Charge of proton (Coulomb)


// Accepted spacecraft names and the names used in AMDA web services

$Spacecrafts = [
	// Mercury
	'MESSENGER' => 'mes_xyz_orbmso',

	// Venus
	'VEX'       => 'vex_xyz',

	// Earth
	'CLUSTER1'  => 'c1_xyz',
	'CLUSTER2'  => 'c2_xyz',
	'CLUSTER3'  => 'c3_xyz',
	'CLUSTER4'  => 'c4_xyz',
	'GEOTAIL'   => 'gtl_xyz',
	'IMP-8'     => 'imp8_xyz',
	'POLAR'     => 'plr_xyz',

	// Mars
	'MEX'       => 'mex_xyz',
	'MGS'       => 'xyz_mgs_mso',
	'MAVEN'     => 'mav_xyz_mso'
];

?>

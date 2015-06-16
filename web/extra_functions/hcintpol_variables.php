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
//	              			hcintpol_variables.php
//
// ########################################################################

// ============================================================
// hcintpol program is used to interpolate values of physical
// quantities at different points inside the grid cube.
//
// As one of the input parameters hcintpol reads a list of
// of these quantities (a string with comma as delimiter).
// The symbols for these variables that hcintpol uses are
// different from those that VOTable or netCDF use as field
// names. The following array defines the mapping
// hcintpol variable name -> Field name
// ============================================================

global $hcintpol_names;
$hcintpol_names = array(
	"t"   => "Time",
	"x"   => "x",
	"y"   => "y",
	"z"   => "z",
	"rho" => "MassDensity",
	"n"   => "Density",
	"vx"  => "Ux",
	"vy"  => "Uy",
	"vz"  => "Uz",
	"v"   => "Utot",
	"jx"  => "Jx",
	"jy"  => "Jy",
	"jz"  => "Jz",
	"j"   => "Jtot",
	"U"   => "EnergyDensity",
	"P"   => "Pressure",
	"T"   => "Temperature",
	"Bx"  => "Bx",
	"By"  => "By",
	"Bz"  => "Bz",
	"B"   => "Btot",
	"Ex"  => "Ex",
	"Ey"  => "Ey",
	"Ez"  => "Ez",
	"E"   => "Etot",
	"Ebin0" => "ParticleFlux"
);


// ------------------------------------------------------------------------
// Several IMPEx methods require an input parameter named 'Variable' which
// is a list of physical quantities. The possible names of these variables
// (e.g. Bx) are defined in the tree.xml file in
// <NumericalOutput> -> <Parameter> -> <ParameterKey>.
// hcintpol program does not use the same varible names so the names must
// be converted into format which hcintpol understands.
// The following arrar defines the mapping
// ParameterKey variable name -> hcintpol variable name
// ------------------------------------------------------------------------

global $hcintpol_variables;
$hcintpol_variables = array(
	"Density"		=> "n",
	"Ux"			=> "vx",
	"Uy"			=> "vy",
	"Uz"			=> "vz",
	"Utot"			=> "v",
	"Jx"			=> "jx",
	"Jy"			=> "jy",
	"Jz"			=> "jz",
	"Jtot"			=> "j",
	"Pressure"		=> "P",
	"Temperature"	=> "T",
	"Bx"			=> "Bx",
	"By"			=> "By",
	"Bz"			=> "Bz",
	"Btot"			=> "B"
);


/* --------------------------------------------------------------
* The variable names may sometimes be written with all capitals
* or all lowercase letters (e.g. 'BTOT' or 'ux'). The following
* routine converts the given variable name to a form which
* hcintpol understands.
*
* @param string $var  Name of the variable
* @return string      Corresponding hcintpol variable name
* --------------------------------------------------------------
*/

function get_hcintpol_var_name($var) {
	global $hcintpol_variables;

	$var_low = strtolower($var);
	foreach($hcintpol_variables as $key => $hc_var) {
		if ($var_low == strtolower($key)) return $hc_var;
	}
	return $var_low;
}
?>

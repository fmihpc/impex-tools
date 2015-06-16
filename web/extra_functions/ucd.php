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

// #####################################################################
//
//	              			    ucd.php
//
// #####################################################################

// ######################################################################
// This php script defines UCD (Unified Content Descriptors) for some
// physical quantities. UCD's are used in VOTable files where they
// appear as attributes to various field elements and attach physical
// meaning to these fields.
// Source : http://cdsweb.u-strasbg.fr/UCD
// ######################################################################

// ---------------------------------------------------------------
// $ucd_table defines pairs $key => $value where $key is a string
// describing a variable name understandable by hcintpol and $value
// is the corresponding UCD string.
// ---------------------------------------------------------------

global $ucd_table;
$ucd_table = array(
	"time"		=> "time.epoch",
	"x"			=> "pos.cartesian.x",
	"y"			=> "pos.cartesian.y",
	"z"			=> "pos.cartesian.z",
	"rho"		=> "phys.density",
	"n"			=> "phys.density",
	"density"	=> "phys.density",
	"mass"		=> "phys.mass",
	"rhovx"		=> "phys.density",
	"rhovy"		=> "phys.density",
	"rhovz"		=> "phys.density",
	"vx"		=> "phys.veloc",
	"vy"		=> "phys.veloc",
	"vz"		=> "phys.veloc",
	"vr"		=> "phys.veloc",
	"v"			=> "phys.veloc",
	"ux"		=> "phys.veloc",
	"uy"		=> "phys.veloc",
	"uz"		=> "phys.veloc",
	"utot"		=> "phys.veloc",
	"v"			=> "phys.veloc",
	"v2"		=> "phys.veloc",
	"U"			=> "phys.energy.density",
	"U1"		=> "phys.energy.density",
	"P"			=> "phys.pressure",
	"T"			=> "phys.temperature",
	"temperature" => "phys.temperature",
	"Bx"		=> "phys.magField",
	"By"		=> "phys.magField",
	"Bz"		=> "phys.magField",
	"B"			=> "phys.magField",
	"bx"		=> "phys.magField",
	"by"		=> "phys.magField",
	"bz"		=> "phys.magField",
	"btot"		=> "phys.magField",
	"Ex"		=> "phys.electField",
	"Ey"		=> "phys.electField",
	"Ez"		=> "phys.electField",
	"E"			=> "phys.electField",
	"jx"		=> "phys",
	"jy"		=> "phys",
	"jz"		=> "phys",
	"j"			=> "phys",
	"jtot"		=> "phys",
	"Ebin0"		=> "phys.flux.density"
);
?>

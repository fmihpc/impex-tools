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
//	              			    units.php
//
// ########################################################################

// ######################################################################
// This php script defines units of various physical quantities as
// expected by the hcintpol program. It also defines conversion factors
// for some units.
// ######################################################################


// --------------------------------------------------------------------
// Names of physical quantities and their units as defined in hcintpol 
// --------------------------------------------------------------------

global $Units;
$Units = array(
	"x"			=> "m",				// Position X
	"y"			=> "m",				// Position Y
	"z"			=> "m",				// Position Z
	"rho"		=> "kg/m3",			// Mass density
	"mass"		=> "kg",			// Mass
	"n"			=> "1/m^3",			// Number density
	"density"	=> "1/m^3",			// Number density
	"rhovx"		=> "kg/(m^2*s)",	// Momentum flux, X component
	"rhovy"		=> "kg/(m^2*s)",	// Momentum flux, Y component
	"rhovz"		=> "kg/(m^2*s)",	// Momentum flux, Z component
	"vx"		=> "m/s",			// Velocity, X component
	"vy"		=> "m/s",			// Velocity, Y component
	"vz"		=> "m/s",			// Velocity, Z component
	"v"			=> "m/s",			// Velocity, total
	"ux"		=> "m/s",			// Velocity, X component
	"uy"		=> "m/s",			// Velocity, Y component
	"uz"		=> "m/s",			// Velocity, Z component
	"utot"		=> "m/s",			// Velocity, total
	"U" 		=> "J/m^3",			// Total energy density
	"P"			=> "J/m^3",			// Pressure
	"T"			=> "K",				// Temperature
	"temperature" => "K",			// Temperature
	"Bx"		=>  "T",			// Magnetic field, X component
	"By"		=>  "T",			// Magnetic field, Y component
	"Bz"		=>  "T",			// Magnetic field, Z component
	"bx"		=>  "T",			// Magnetic field, X component
	"by"		=>  "T",			// Magnetic field, Y component
	"bz"		=>  "T",			// Magnetic field, Z component
	"B"			=> "T",				// Magnetic field, total
	"btot"		=> "T",				// Magnetic field, total
	"Ex"		=> "V/m^2",			// Electric field, X component
	"Ey"		=> "V/m^2",			// Electric field, Y component
	"Ez"		=> "V/m^2",			// Electric field, Z component
	"E"			=> "V/m^2",			// Electric field, total
	"ex"		=> "V/m^2",			// Electric field, X component
	"ey"		=> "V/m^2",			// Electric field, Y component
	"ez"		=> "V/m^2",			// Electric field, Z component
	"etot"		=> "V/m^2",			// Electric field, total
	"jx"		=> "A/m^2",			// Current density, X component
	"jy"		=> "A/m^2",			// Current density, Y component
	"jz"		=> "A/m^2",			// Current density, Z component
	"j"			=> "A/m^2",			// Current density, total
	"Ebin0"		=> "m-2.s-1.sr-1.eV-1"		// Particle flux
);

// --------------------------------------------------------------------
// Conversion factors from some common units to those used by hcintpol
// --------------------------------------------------------------------

global $Unit_conversion_table;
$Unit_conversion_table = array(
	"km"	 => 1000.0,		// m
	"mi"	 => 1609.344,	// m
	"km/s"	 => 1000.0,		// m/s
	"km/h"	 => 1/3.6,		// m/s
	"g"		 => 0.001,		// kg
	"1/cm^3" => 1.0e6,		// 1/m^3
	"cm^-3"	 => 1.0e6,		// 1/m^3
	"nT"	 => 1.0e-9		// T
);
?>

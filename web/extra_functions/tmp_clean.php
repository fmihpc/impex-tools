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
//	              			    tmp_clean.php
//
// ########################################################################

// ========================================================================
// This php script file removes temporary files from TMP_DIR and WWW_DATA_DIR
// directories which are created by web services (filenames start with
// 'hwa_', 'VOT_' or 'ft_').
// This script is called always whenever a new web service call is made.
// ========================================================================


/** ---------------------------------------------------------------------------------
* Function clean_dir removes specified files from given directory which
* are older than given period.
* @param string $clean_dir  : Path to the Directory (string)
* @param string $file_id    : Files whose name start with this string are removed (string)
* @param integer $seconds    : File is removed if it is older than this period (seconds) (int)
* ---------------------------------------------------------------------------------
*/

function clean_dir($clean_dir, $file_id, $seconds) {
	$clean_time = time() -  $seconds;

	$len = strlen($file_id);
	if ($handle = opendir($clean_dir)) {
		while (false !== ($file = readdir($handle))) {
			if (substr($file,0,$len) == $file_id) {
				if (filemtime($clean_dir . '/' . $file) < $clean_time) {
					unlink($clean_dir . '/' . $file);
				}
			}
		}
	}
}

// --------------------------
// Directories to be cleaned
// --------------------------

clean_dir(TMP_DIR     , 'hwa_', 60*60);				// One hour
clean_dir(WWW_DATA_DIR, 'hwa_', 30*24*60*60);		// One month
clean_dir(WWW_DATA_DIR, 'VOT_', 30*24*60*60);		// One month
clean_dir(WWW_DATA_DIR, 'ft_',  24*60*60);			// One day
?>

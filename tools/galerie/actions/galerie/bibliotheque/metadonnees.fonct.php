<?php
/*vim: set expandtab tabstop=4 shiftwidth=4: */
// +------------------------------------------------------------------------------------------------------+
// | PHP version 5.1                                                                                      |
// +------------------------------------------------------------------------------------------------------+
// | Copyright (C) 1999-2006 Tela Botanica (accueil@tela-botanica.org)                                    |
// +------------------------------------------------------------------------------------------------------+
// | This file is part of v2.pierredupontdugard.com.                                                                         |
// |                                                                                                      |
// | v2.pierredupontdugard.com is free software; you can redistribute it and/or modify                                       |
// | it under the terms of the GNU General Public License as published by                                 |
// | the Free Software Foundation; either version 2 of the License, or                                    |
// | (at your option) any later version.                                                                  |
// |                                                                                                      |
// | v2.pierredupontdugard.com is distributed in the hope that it will be useful,                                            |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of                                       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                                        |
// | GNU General Public License for more details.                                                         |
// |                                                                                                      |
// | You should have received a copy of the GNU General Public License                                    |
// | along with Foobar; if not, write to the Free Software                                                |
// | Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA                            |
// +------------------------------------------------------------------------------------------------------+
// CVS : $Id$
/**
* v2.pierredupontdugard.com - metadonnees.fonct.php
*
* Description :
*
*@package v2.pierredupontdugard.com
//Auteur original :
*@author        Jean-Pascal MILCENT <jpm@tela-botanica.org>
//Autres auteurs :
*@author        Aucun
*@copyright     Tela-Botanica 1999-2008
*@version       $Revision$ $Date$
// +------------------------------------------------------------------------------------------------------+
*/

// +------------------------------------------------------------------------------------------------------+
// |                                            ENTETE du PROGRAMME                                       |
// +------------------------------------------------------------------------------------------------------+


// +------------------------------------------------------------------------------------------------------+
// |                                            CORPS du PROGRAMME                                        |
// +------------------------------------------------------------------------------------------------------+

/**
 * returns informations from IPTC metadata, mapping is done at the beginning
 * of the function
 *
 * @param string $filename
 * @return array
 */
function get_iptc_data($filename, $map)
{
	$result = array();

	$imginfo = array();
	if (false == @getimagesize($filename, $imginfo) ) {
		return $result;
	}

	if (isset($imginfo['APP13'])) {
		$iptc = iptcparse($imginfo['APP13']);
		if (is_array($iptc)) {
			$rmap = array_flip($map);
			foreach (array_keys($rmap) as $iptc_key) {
				if (isset($iptc[$iptc_key][0])) {
					if ($iptc_key == '2#025') {
						$value = implode(',', array_map('clean_iptc_value',$iptc[$iptc_key]));
					} else {
						$value = clean_iptc_value($iptc[$iptc_key][0]);
					}

					foreach (array_keys($map, $iptc_key) as $pwg_key) {
						$result[$pwg_key] = $value;
					}
				}
			}
		}
	}
	return $result;
}

/**
 * return a cleaned IPTC value
 *
 * @param string value
 * @return string
 */
function clean_iptc_value($value)
{
	// strip leading zeros (weird Kodak Scanner software)
	while ( isset($value[0]) and $value[0] == chr(0)) {
		$value = substr($value, 1);
	}
	// remove binary nulls
	$value = str_replace(chr(0x00), ' ', $value);

	return $value;
}

/**
 * returns informations from EXIF metadata, mapping is done at the beginning
 * of the function
 *
 * @param string $filename
 * @return array
 */
function get_exif_data($filename, $map)
{
	$result = array();
 	
	if (!function_exists('read_exif_data')) {
		die('Exif extension not available, admin should disable exif use');
	}

	// Read EXIF data
	if ($exif = @read_exif_data($filename)) {
		foreach ($map as $key => $field) {
			if (strpos($field, ';') === false) {
				if (isset($exif[$field])) {
					if ($field == 'UserComment') {
						$result[$key] = substr($exif[$field], 8);
						// Récupère l'encodage du champ et supprime les caractères NULL de complétion
						$result[$key.'_encodage'] = substr($exif[$field], 0, strpos($exif[$field] , chr(0)));
					} else {
						$result[$key] = $exif[$field];
					}
				}
			} else {
				$tokens = explode(';', $field);
				if (isset($exif[$tokens[0]][$tokens[1]])) {
					$result[$key] = $exif[$tokens[0]][$tokens[1]];
				}
			}
		}
	}

	return $result;
}

/* +--Fin du code ----------------------------------------------------------------------------------------+
*
* $Log$
*
* +-- Fin du code ----------------------------------------------------------------------------------------+
*/
?>

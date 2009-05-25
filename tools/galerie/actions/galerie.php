<?php
/*vim: set expandtab tabstop=4 shiftwidth=4: */
// +------------------------------------------------------------------------------------------------------+
// | PHP version 5.1                                                                                      |
// +------------------------------------------------------------------------------------------------------+
// | Copyright (C) 1999-2006 Tela Botanica (accueil@tela-botanica.org)                                    |
// +------------------------------------------------------------------------------------------------------+
// | This file is part of wikini.                                                                         |
// |                                                                                                      |
// | wikini is free software; you can redistribute it and/or modify                                       |
// | it under the terms of the GNU General Public License as published by                                 |
// | the Free Software Foundation; either version 2 of the License, or                                    |
// | (at your option) any later version.                                                                  |
// |                                                                                                      |
// | wikini is distributed in the hope that it will be useful,                                            |
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
* wikini - galerie.php
*
* Description :
*
*@package wikini
//Auteur original :
*@author        Jean-Pascal MILCENT <jpm@tela-botanica.org>
//Autres auteurs :
*@author        Aucun
*@copyright     Tela-Botanica 1999-2007
*@version       $Revision$ $Date$
// +------------------------------------------------------------------------------------------------------+
*/

// +------------------------------------------------------------------------------------------------------+
// |                                            ENTÊTE du PROGRAMME                                       |
// +------------------------------------------------------------------------------------------------------+
// Définition de constantes
define('DS', DIRECTORY_SEPARATOR);
/** Constante "dynamique" stockant le chemin absolue de base de l'application.*/
define('GAL_CHEMIN_APPLI', dirname(__FILE__).DS);

// Initialisation des variables
$sortie = '';
$GLOBALS['_GALLERIE_']['erreur'] = '';

// Inclusion du fichier de config de l'action
require_once GAL_CHEMIN_APPLI.'galerie'.DS.'configuration'.DS.'gal_config.inc.php';
require_once GAL_CHEMIN_APPLI.'galerie'.DS.'bibliotheque'.DS.'metadonnees.fonct.php';
//+----------------------------------------------------------------------------------------------------------------+
// Récupération des paramêtres et gestion des erreurs de paramètrage
if (!$this->GetParameter('dossier')) {
	$options['dossier'] = null;
	$GLOBALS['_GALERIE_']['erreur'] = "Applette GALERIE : le paramètre 'dossier' est obligatoire !";    
} else {
	$options['dossier'] = rtrim($this->GetParameter('dossier'), DS);
}
if (!$this->GetParameter('id')) {
	$options['id'] = microtime();
	//$GLOBALS['_GALERIE_']['erreur'] = "Applette GALERIE : le paramètre 'id' est obligatoire !";
} else {
	$options['id'] = $this->GetParameter('id');
}
if (!$this->GetParameter('largeur')) {
    $options['largeur'] = 160;
} else {
	$options['largeur'] = $this->GetParameter('largeur');
}
if (!$this->GetParameter('hauteur')) {
    $options['hauteur'] = 160;
} else {
	$options['hauteur'] = $this->GetParameter('hauteur');
}
if (!isset($options['qualite'])) {
    $options['qualite'] = 70;
}
if (!$this->GetParameter('imglargeur')) {
    $options['img_largeur'] = 800;
} else {
	$options['img_largeur'] = $this->GetParameter('imglargeur');
}
if (!$this->GetParameter('imghauteur')) {
    $options['img_hauteur'] = 600;
} else {
	$options['img_hauteur'] = $this->GetParameter('imghauteur');
}
if (!isset($options['img_qualite'])) {
    $options['img_qualite'] = 70;
}
if (!isset($options['squelette'])) {
    $options['squelette'] = GAL_SQUELETTE_LISTE;
}
// +------------------------------------------------------------------------------------------------------+
// |                                            CORPS du PROGRAMME                                        |
// +------------------------------------------------------------------------------------------------------+
//+----------------------------------------------------------------------------------------------------------------+
// Récupération des données	
$noimage = 0;
$GLOBALS['_GALERIE_']['id'] = $options['id'];
$GLOBALS['_GALERIE_']['dossier'] = GAL_CHEMIN_RACINE.$options['dossier'];
if (is_dir($GLOBALS['_GALERIE_']['dossier'])) {
	if ($dh = opendir($GLOBALS['_GALERIE_']['dossier'])) {
		$images = array();
		while (($f = readdir($dh)) !== false) {
			if((substr(strtolower($f),-3) == 'jpg') || (substr(strtolower($f),-3) == 'gif') || (substr(strtolower($f),-3) == 'png')) {
				$noimage++;

				// Gestion des métadonnées
				$iptc = array();
				$exif = array();
				$fichier = $GLOBALS['_GALERIE_']['dossier'].DIRECTORY_SEPARATOR.$f;
				$iptc = get_iptc_data($fichier, array('keywords' => '2#025', 'date_creation' => '2#055', 'author' => '2#122', 'name' => '2#005', 'comment' => '2#120'));
				
				// Nous demandons des entités HTML pour la convertion des champs EXIF stockés en Unicode
				ini_set('exif.encode_unicode', 'HTML-ENTITIES');
				$exif = get_exif_data($fichier, array('date_creation' => 'DateTimeOriginal', 'comment' => 'UserComment'));
				
				// Initialisation et prétraitement des commentaires
				$commentaire = '';
				$exif['comment'] = trim($exif['comment']);
				$iptc['comment'] = trim($iptc['comment']);
				// Comparaison et ajout des commentaires IPTC et EXIF
				if (!empty($iptc['comment']) || !empty($exif['comment'])) {
					if (!empty($exif['comment'])) {
						// Dans le cas d'un champ de commentaire unicode nous supprimons les caractères NULL
						if ($exif['comment_encodage'] == 'UNICODE') {
							$exif['comment'] = str_replace(chr(0), '', $exif['comment']);
						}
						$commentaire .= "\n".trim($exif['comment']);
					}
					
					if (!empty($iptc['comment']) && $iptc['comment'] != $exif['comment']) {
						$commentaire .= $iptc['comment'];
					}
				}
				
				// Récupération des infos
				$images[] = array('filename' => $f, 'titre' => trim($commentaire));
				array_multisort($images, SORT_ASC, SORT_REGULAR); 
			}
		}
		closedir($dh);
	} else {
		$GLOBALS['_GALERIE_']['erreur'] = "Applette GALERIE : le dossier d'images ne peut pas être ouvert : ".$GLOBALS['_GALERIE_']['dossier'];
	}
} else {
	$GLOBALS['_GALERIE_']['erreur'] = "Applette GALERIE : le dossier d'images est introuvable à : ".$GLOBALS['_GALERIE_']['dossier'];
}
//trigger_error('<pre>'.print_r($images, true).'</pre>', E_USER_WARNING);
if($noimage) {
	$GLOBALS['_GALERIE_']['css']['chemin'] = GAL_CHEMIN_STYLES_RELATIF;
	$GLOBALS['_GALERIE_']['css']['largeur'] = $options['largeur'];
	$GLOBALS['_GALERIE_']['script']['chemin'] = GAL_CHEMIN_SCRIPTS_RELATIF;
	$GLOBALS['_GALERIE_']['images'] = array();
	foreach($images as $image) {
		if ($image['filename'] != '') {
			$aso_img['fichier_nom'] = $image['filename'];
			$aso_img['title'] = $image['titre'];
			$aso_img['url_img'] = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).DS.GAL_CHEMIN_SCRIPTS_RELATIF.'showthumb.php?img='.urlencode(GAL_CHEMIN_RACINE.$options['dossier'].DS.$image['filename']).'&amp;width='.$options['img_largeur'].'&amp;height='.$options['img_hauteur'].'&amp;quality='.$options['img_qualite'].'&amp;centrage=0';
			$aso_img['url_img_mini'] = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).DS.GAL_CHEMIN_SCRIPTS_RELATIF.'showthumb.php?img='.urlencode(GAL_CHEMIN_RACINE.$options['dossier'].DS.$image['filename']).'&amp;width='.$options['largeur'].'&amp;height='.$options['hauteur'].'&amp;quality='.$options['qualite'].'&amp;centrage=1';
			$GLOBALS['_GALERIE_']['images'][] = $aso_img;
		}
 	}
} else {
	$GLOBALS['_GALERIE_']['erreur'] = "Applette GALERIE : le dossier d'images ne contient rien : ".$GLOBALS['_GALERIE_']['dossier'];
}

//+----------------------------------------------------------------------------------------------------------------+
// Gestion des squelettes
// Extrait les variables et les ajoutes à l'espace de noms local
extract($GLOBALS['_GALERIE_']);
// Démarre le buffer
ob_start();
// Inclusion du fichier
include GAL_CHEMIN_SQUELETTE.$options['squelette'];
// Récupérer le  contenu du buffer
$sortie = ob_get_contents();
// Arrête et détruit le buffer
ob_end_clean();

//+----------------------------------------------------------------------------------------------------------------+
// Sortie
echo $sortie;

/* +--Fin du code ----------------------------------------------------------------------------------------+
*
* $Log$
*
* +-- Fin du code ----------------------------------------------------------------------------------------+
*/
?>
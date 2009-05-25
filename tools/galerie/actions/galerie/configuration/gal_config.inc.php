<?php
/*vim: set expandtab tabstop=4 shiftwidth=4: */
// +------------------------------------------------------------------------------------------------------+
// | PHP version 5.1                                                                                      |
// +------------------------------------------------------------------------------------------------------+
// | Copyright (C) 1999-2006 Tela Botanica (accueil@tela-botanica.org)                                    |
// +------------------------------------------------------------------------------------------------------+
// | This file is part of papyrus_bp.                                                                         |
// |                                                                                                      |
// | Foobar is free software; you can redistribute it and/or modify                                       |
// | it under the terms of the GNU General Public License as published by                                 |
// | the Free Software Foundation; either version 2 of the License, or                                    |
// | (at your option) any later version.                                                                  |
// |                                                                                                      |
// | Foobar is distributed in the hope that it will be useful,                                            |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of                                       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                                        |
// | GNU General Public License for more details.                                                         |
// |                                                                                                      |
// | You should have received a copy of the GNU General Public License                                    |
// | along with Foobar; if not, write to the Free Software                                                |
// | Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA                            |
// +------------------------------------------------------------------------------------------------------+
// CVS : $Id: gall_config.inc.php,v 1.1 2006-12-07 17:29:20 jp_milcent Exp $
/**
* papyrus_bp - gall_config.inc.php
*
* Description :
*
*@package papyrus_bp
//Auteur original :
*@author        Jean-Pascal MILCENT <jpm@tela-botanica.org>
//Autres auteurs :
*@author        Aucun
*@copyright     Tela-Botanica 1999-2006
*@version       $Revision: 1.1 $ $Date: 2006-12-07 17:29:20 $
// +------------------------------------------------------------------------------------------------------+
*/

// +------------------------------------------------------------------------------------------------------+
// |                                            ENTÊTE du PROGRAMME                                       |
// +------------------------------------------------------------------------------------------------------+
$GLOBALS['_GALERIE_'] = array();

// +------------------------------------------------------------------------------------------------------+
// |                                            CORPS du PROGRAMME                                        |
// +------------------------------------------------------------------------------------------------------+
/** Constante stockant la valeur de la langue par défaut pour l'applette GALL.*/
define('GAL_I18N_DEFAUT', 'fr');

/** L'url à partir de laquelle l'applette est appelée.*/
define('GAL_URL', $this->GetConfigValue('base_url'));

// Chemin vers les dossiers de l'applette
/** Chemin vers la racine du site.*/
define('GAL_CHEMIN_RACINE', $_SERVER['DOCUMENT_ROOT'].DS);
/** Constante "dynamique" stockant le chemin relatif de base de l'application.*/
define('GAL_CHEMIN_APPLI_RELATIF', 'tools'.DS.'galerie'.DS.'actions'.DS);
/** Chemin vers l'applette Gallerie de Papyrus.*/
define('GAL_CHEMIN_APPLETTE', GAL_CHEMIN_APPLI.'galerie'.DS);
/** Chemin relatif vers l'applette Gallerie de Papyrus.*/
define('GAL_CHEMIN_APPLETTE_RELATIF', GAL_CHEMIN_APPLI_RELATIF.'galerie'.DS);
/** Chemin vers le dossier de Présentation.*/
define('GAL_CHEMIN_PRESENTATION', GAL_CHEMIN_APPLETTE.'presentation'.DS);
/** Chemin relatif vers le dossier de Présentation.*/
define('GAL_CHEMIN_PRESENTATION_RELATIF', GAL_CHEMIN_APPLETTE_RELATIF.'presentation'.DS);
/** Chemin vers les fichiers js de l'applette Gallerie de Papyrus.*/
define('GAL_CHEMIN_SCRIPTS', GAL_CHEMIN_PRESENTATION.'scripts'.DS);
/** Chemin relatif vers les fichiers js de l'applette Gallerie de Papyrus.*/
define('GAL_CHEMIN_SCRIPTS_RELATIF', GAL_CHEMIN_PRESENTATION_RELATIF.'scripts'.DS);
/** Chemin vers les fichiers css de l'applette Gallerie de Papyrus.*/
define('GAL_CHEMIN_STYLES', GAL_CHEMIN_PRESENTATION.'styles'.DS);
/** Chemin relatif vers les fichiers css de l'applette Gallerie de Papyrus.*/
define('GAL_CHEMIN_STYLES_RELATIF', GAL_CHEMIN_PRESENTATION_RELATIF.'styles'.DS);
/** Chemin vers les fichiers images de l'applette Gallerie de Papyrus.*/
define('GAL_CHEMIN_IMAGES', GAL_CHEMIN_PRESENTATION.'images'.DS);
/** Chemin vers les fichiers squelettes de l'applette Gallerie de Papyrus.*/
define('GAL_CHEMIN_SQUELETTE', GAL_CHEMIN_PRESENTATION.'squelettes'.DS);

// Configuration du rendu
/** Nom du fichier de squelette à utiliser pour la liste des pages.*/
define('GAL_SQUELETTE_LISTE', 'gal_liste_ss_inclusion.tpl.html');


/* +--Fin du code ----------------------------------------------------------------------------------------+
*
* $Log: gall_config.inc.php,v $
* Revision 1.1  2006-12-07 17:29:20  jp_milcent
* Ajout de l'applette Gallerie dans Client car elle n'a pas un rapport direct avec Papyrus.
*
* Revision 1.2  2006/12/07 16:25:23  jp_milcent
* Ajout de la gestion de messages d'erreur.
* Ajout de la gestion des squelettes.
*
* Revision 1.1  2006/12/07 15:39:47  jp_milcent
* Ajout de l'applette Gallerie.
*
*
* +-- Fin du code ----------------------------------------------------------------------------------------+
*/
?>

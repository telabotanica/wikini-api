<?php
// declare(encoding='UTF-8');
/**
 * Handler renvoyant au navigateur le contenu HTML de la page wiki.
 * 
 * @category	PHP 5.2
 * @package		Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2 
 * @version		$Id$
 */
//Vérification de sécurité
if (!eregi("wakka.php", $_SERVER['PHP_SELF'])) {
    die ("Acc&eacute;s direct interdit");
}
$chemin = dirname(__FILE__).DIRECTORY_SEPARATOR.'../../rest/index.php';
if (!file_exists($chemin)) {
    $e = "Veuillez paramétrer l'emplacement de l'api : $chemin";
} else {
	include $chemin;
}
?>
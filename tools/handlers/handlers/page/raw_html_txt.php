<?php
// declare(encoding='ISO-8859-1');
/**
 * Handler renvoyant au navigateur le contenu HTML de la page wiki avec un type mime text/plain.
 *
 * @category	PHP 5.2
 * @package		Framework
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2009, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL-v3
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL-v2
 * @version		$Id$
 */


//VÃ©rification de sécurité
if (!eregi("wakka.php", $_SERVER['PHP_SELF'])) {
    die ("Acc&eacute;s direct interdit");
}

if ($this->HasAccess('read')) {
	if (!$this->page)	{
		return;
	} else {
		header("Content-type: text/plain");
		echo $this->Format($this->page["body"], "wakka");
	}
} else {
	return;
}
?>
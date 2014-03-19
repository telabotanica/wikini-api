<?php
// declare(encoding='ISO-8859-1');
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


//VÃ©rification de sécurité
if (!eregi("wakka.php", $_SERVER['PHP_SELF'])) {
    die ("Acc&eacute;s direct interdit");
}
if ($this->HasAccess("read")) {
	if (!$this->page) {
		return;
	} else {
		header("Content-type: text/html");
		// Affichage de la page HTML
		$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
		$html .= '<html>'."\n";
		$html .= '	<head>'."\n";
		$html .= '		<title>'.$this->GetPageTag().'</title>'."\n";
		$html .= '	</head>'."\n";
		$html .= '	<body>'."\n";
		$html .= $this->Format($this->page["body"], "wakka");
		$html .= '	</body>'."\n";
		$html .= '</html>';
		echo $html;
	}
} else {
	return;
}
?>
<?php
/*
raw_html.php
Copyright 2010  Jean-Pascal MILCENT
Copyright 2002  David DELON
Copyright 2003  Eric FELDSTEIN
Copyright 2003  Charles NEPOTE
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//vérification de sécurité
if (!eregi("wakka.php", $_SERVER['PHP_SELF'])) {
    die ("Accés direct interdit");
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
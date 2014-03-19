<?php
/*
recentchangesrss.php

Copyright 2003  David DELON
Copyright 2005-2007  Didier LOISEAU
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

if ($this->GetMethod() != 'xml') {
	echo 'Pour obtenir le fil RSS des derniers changements, utilisez l\'adresse suivante: ';
	echo $this->Link($this->Href('xml'));
	return;
}

if (isset($_GET['max']) && is_numeric($_GET['max'])) {
	$max = ($_GET['max'] < 1000) ? $_GET['max'] : 1000;
} else if ($user = $this->GetUser()) {
	$max = $user["changescount"];
} else {
	$max = 50;
}
$pages = $this->LoadRecentlyChanged($max);
$last_users = $this->LoadAll('SELECT name, signuptime, motto FROM '.$this->GetConfigValue('table_prefix').'users ORDER BY signuptime DESC LIMIT '.$max);
if ($pages || $last_users)
{
	if (!($link = $this->GetParameter("link"))) $link=$this->GetConfigValue("root_page");
	$output = '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n";
	$output .= "<channel>\n";
	$output .= "<title> Derniers changements sur ". $this->GetConfigValue("wakka_name")  . "</title>\n";
	$output .= "<link>" . $this->Href(false, $link) . "</link>\n";
	$output .= "<description> $max derniers changements sur " . htmlspecialchars($this->GetConfigValue("wakka_name")) . " </description>\n";
	$output .= "<language>fr</language>\n";
	$output .= '<generator>WikiNi ' . WIKINI_VERSION . "</generator>\n";
	
	$items = array();
	if ($pages)
	{
		foreach ($pages as $page)
		{
			$items[strtotime($page['time'])] = array('type' => 'page', 'content' => $page);
		}
	}
	
	if ($last_users)
	{
		foreach ($last_users as $user) {
			$items[strtotime($user['signuptime'])] = array('type' => 'user', 'content' => $user);
		}
	}
	krsort($items);
	
	foreach ($items as $item) {
		$type = $item['type'];
		if ($type == 'page')
		{
			$page = $item['content'];
			
			$output .= "<item>\n";
			$output .= "<title>" . htmlspecialchars($page["tag"]) . "</title>\n";
			$output .= '<dc:creator>' . htmlspecialchars($page["user"]) . "</dc:creator>\n";
			$output .= '<pubDate>' . gmdate('D, d M Y H:i:s \G\M\T', strtotime($page['time'])) . "</pubDate>\n";
			$output .= "<description>" . htmlspecialchars(
					'Modification de ' . $this->ComposeLinkToPage($page["tag"])
					. ' (' . $this->ComposeLinkToPage($page["tag"], 'revisions', 'historique') . ')'
					. " --- par " .$page["user"])
					. "</description>\n";
			$itemurl = $this->href(false, $page["tag"], "time=" . htmlspecialchars(rawurlencode($page["time"])));
			$output .= '<guid>' . $itemurl . "</guid>\n";
			$output .= "</item>\n";
		}
		else if ($type == 'user')
		{
			$user = $item['content'];
			$itemurl = $this->Href('', $user['name']);
			
			$output .= '<item>'."\n";
			$output .= '<title>'.'Utilisateur '.htmlspecialchars($user['name']).' - inscription le '.$user['signuptime'].'</title>'."\n";
			$output .= '<link>'.$itemurl.'</link>'."\n";
			$output .= '<pubDate>' . gmdate('D, d M Y H:i:s \G\M\T', strtotime($user['signuptime'])) . "</pubDate>\n";
			$output .= '<description>'.'L\'utilisateur '.htmlspecialchars($user['name']).' s\'est inscrit le '.$user['signuptime'];
			if (!empty($user['motto']))
			{
				$output .= ' avec pour devise  "'.htmlspecialchars($user['motto']).'"';
			}
			$output .= '</description>'."\n";
			$output .= '<guid>'.$itemurl.'</guid>'."\n";
			$output .= '</item>'."\n";
		}
	}
	$output .= "</channel>\n";
	$output .= "</rss>\n";
	echo $output ;
}
?>

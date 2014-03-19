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
if (!defined("WIKINI_VERSION"))
{
    die ("acc&egrave;s direct interdit");
}

if ($this->GetMethod() != 'xml') {
	echo 'Pour obtenir le fil RSS des derniers changements, utilisez l\'adresse suivante: ';
	echo $this->Link($this->Href('xml'));
	return;
}


if (!function_exists("rssdiff")) {

    function rssdiff($tag,$idfirst,$idlast) {

        require_once 'includes/diff/side.class.php';
        require_once 'includes/diff/diff.class.php';
        require_once 'includes/diff/diffformatter.class.php';

        $output='';
        global $wiki;
        // TODO : cache ?

        if ($idfirst==$idlast) {
            $previousdiff=$wiki->LoadSingle("select id from ".$wiki->config["table_prefix"]."pages where tag = '".mysql_escape_string($tag)."' and id < $idfirst order by time desc limit 1");
            if ($previousdiff) {
                $idlast=$previousdiff['id'];
            }
            else {
                return;
            }

        }

        $pageA = $wiki->LoadPageById($idfirst);
        $pageB = $wiki->LoadPageById($idlast);


        $bodyA = explode("\n", $pageA["body"]);
        $bodyB = explode("\n", $pageB["body"]);

        $added = array_diff($bodyA, $bodyB);
        $deleted = array_diff($bodyB, $bodyA);
        if (!isset($output)) $output = '';

        $output .= "<br />\n";
        $output .= "<br />\n";
        $output .= "<b>Comparaison de <a href=\"".$wiki->href("", "", "time=".urlencode($pageA["time"]))."\">".$pageA["time"]."</a> &agrave; <a href=\"".$wiki->href("", "", "time=".urlencode($pageB["time"]))."\">".$pageB["time"]."</a></b><br />\n";

        $wiki->RegisterInclusion($tag);
        if ($added)
        {
            // remove blank lines
            $output .= "<br />\n<b>Ajouts:</b><br />\n";
            $output .= "<div class=\"additions\">".(implode("\n", $added))."</div>";
        }

        if ($deleted)
        {
            $output .= "<br />\n<b>Suppressions:</b><br />\n";
            $output .= "<div class=\"deletions\">".(implode("\n", $deleted))."</div>";
        }
        $wiki->UnregisterLastInclusion();

        if (!$added && !$deleted)
        {
            $output .= "<br />\nPas de diff&eacute;rences.";
        }
        return $output;

    }
}

if (isset($_GET['max']) && is_numeric($_GET['max'])) {
	$max = ($_GET['max'] < 1000) ? $_GET['max'] : 1000;
} else if ($user = $this->GetUser()) {
	$max = $user["changescount"];
} else {
	$max = 50;
}
$pages = $this->LoadAll("SELECT id, tag, time, user, owner FROM ".$this->config["table_prefix"]."pages WHERE comment_on = '' ORDER BY time DESC LIMIT $max");
$last_users = $this->LoadAll('SELECT name, signuptime, motto FROM '.$this->GetConfigValue('table_prefix').'users ORDER BY signuptime DESC LIMIT '.$max);
if ($pages || $last_users) {

	if (!($link = $this->GetParameter("link"))) $link=$this->GetConfigValue("root_page");
	$output = '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
	$output .= "<channel>\n";
	$output .= "<atom:link href=\"".$this->Href("xml")."\" rel=\"self\" type=\"application/rss+xml\" />\n";
	$output .= "<title> ". htmlspecialchars($this->GetConfigValue("wakka_name"), ENT_COMPAT, TEMPLATES_DEFAULT_CHARSET) . "</title>\n";
	$output .= "<link>" . $this->Href(false, $link) . "</link>\n";
	$output .= "<description>$max derniers changements sur " . htmlspecialchars($this->GetConfigValue("wakka_name"), ENT_COMPAT, TEMPLATES_DEFAULT_CHARSET) . "</description>\n";
	$output .= "<language>fr</language>\n";
	$output .= '<generator>WikiNi ' . WIKINI_VERSION . "</generator>\n";

	$items = array();
	if ($pages) {
		foreach ($pages as $page) {
			$page['diff'] = rssdiff($page["tag"], $page["id"], $page["id"]);

			$items[strtotime($page['time'])] = array('type' => 'page', 'content' => $page);
		}
	}

	if ($last_users) {
		foreach ($last_users as $user) {
			$items[strtotime($user['signuptime'])] = array('type' => 'user', 'content' => $user);
		}
	}
	krsort($items);

	foreach ($items as $item) {
		$type = $item['type'];
		if ($type == 'page') {
			$page = $item['content'];

			$output .= "<item>\n";
			$output .= "<title>" . htmlspecialchars($page["tag"], ENT_COMPAT, TEMPLATES_DEFAULT_CHARSET) . "</title>\n";
			$output .= '<dc:creator>' . htmlspecialchars($page["user"], ENT_COMPAT, TEMPLATES_DEFAULT_CHARSET) . "</dc:creator>\n";
			$output .= '<pubDate>' . gmdate('D, d M Y H:i:s \G\M\T', strtotime($page['time'])) . "</pubDate>\n";
			$output .= "<description>" . htmlspecialchars(
				'Modification de ' . $this->ComposeLinkToPage($page["tag"]).
				' (' . $this->ComposeLinkToPage($page["tag"], 'revisions', 'historique') . ')'.
				' --- par ' . $page["user"].
				$page['diff']).
				"</description>\n";
			$output .= "<dc:format>text/html</dc:format>";

			$itemurl = $this->href(false, $page["tag"], "time=" . htmlspecialchars(rawurlencode($page["time"]), ENT_COMPAT, TEMPLATES_DEFAULT_CHARSET));
			$output .= '<guid>' . $itemurl . "</guid>\n";
			$output .= "</item>\n";
		} else if ($type == 'user') {
			$user = $item['content'];
			$itemurl = $this->Href('', $user['name']);

			$output .= '<item>'."\n";
			$output .= '<title>'.'Utilisateur '.htmlspecialchars($user['name']).' - inscription le '.$user['signuptime'].'</title>'."\n";
			$output .= '<link>'.$itemurl.'</link>'."\n";
			$output .= '<pubDate>' . gmdate('D, d M Y H:i:s \G\M\T', strtotime($user['signuptime'])) . "</pubDate>\n";
			$output .= '<description>'.'L\'utilisateur '.htmlspecialchars($user['name']).' s\'est inscrit le '.$user['signuptime'];
			if (!empty($user['motto'])) {
				$output .= ' avec pour devise  "'.htmlspecialchars($user['motto']).'"';
			}
			$output .= '</description>'."\n";
			$output .= '<guid>'.$itemurl.'</guid>'."\n";
			$output .= '</item>'."\n";
		}
	}
	$output .= "</channel>\n";
	$output .= "</rss>\n";
	echo $output;
}
?>
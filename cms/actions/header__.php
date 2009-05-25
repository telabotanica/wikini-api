<?php

if (!defined("WIKINI_VERSION"))
{
            die ("acc&egrave;s direct interdit");
}

$user = $this->GetUser();
$root_page = $this->ComposeLinkToPage($this->config["root_page"]);
$navigation_links = $this->config["navigation_links"] ? $this->Format($this->config["navigation_links"]) : "";
$user_name = $this->Format($this->GetUserName());
$disconnect_link = $this->GetUser() ? '(<a href="' . $this->href('', 'Identification', 'action=logout') . "\">D&eacute;connexion</a>)\n" : '';

if ($user) {
	$plugin_output_new = preg_replace ('/<!-- ADMIN -->/',
		'<div class="header">'.
			$root_page.' :: '.$navigation_links.' :: '.'Vous &ecirc;tes '.$user_name.' '.$disconnect_link.
		'</div>
		<div class="page"',
		$plugin_output_new);
}

?>

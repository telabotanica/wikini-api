<?php

if (!defined("WIKINI_VERSION")) {
	die ("acc&egrave;s direct interdit");
}

if (version_compare(phpversion(), '5.0') < 0) {
    eval('
    if (!function_exists("clone")) {
        function clone($object) {
                return $object;
        }
    }
    ');
}

$user = $this->GetUser();
$menu_page = $this->config["menu_page"];
$menu_page_admin = $this->config["menu_page_admin"];
if (isset($menu_page) and ($menu_page!="")) {
    // Ajout Menu de Navigation
	$wikiMenu = clone($this);
    $wikiMenu->tag = $menu_page;
    $wikiMenu->tag_admin = $menu_page_admin;
    $wikiMenu->SetPage($wikiMenu->LoadPage($wikiMenu->tag));
    $menu_normal = $wikiMenu->Format($wikiMenu->page['body'], 'wakka');
    $menu_admin = '';
    if ($user) {
        $wikiMenu->tag = $menu_page_admin;
        $wikiMenu->SetPage($wikiMenu->LoadPage($wikiMenu->tag));
        $menu_admin = $wikiMenu->Format($wikiMenu->page['body'], 'wakka');
    }
	
    $plugin_output_new = preg_replace ('/<!-- NAVIGATION -->/',
		'<div id="navigation">'.
    	'  <div id="menu">'."\n".
    	'		<h2 id="titre_menu"><span id="menu_debut"> Menu </span></h2>'."\n".
		$menu_normal.$menu_admin.
		"\n".'		<h2 id="titre_contenu"><span id="menu_fin"> Contenu </span></h2>'.
		"\n".'  </div>'."\n".
		"\n".'</div>'."\n".
		'<div id="corps">'."\n",
		$plugin_output_new);
}
?>
<?php

if (!defined("WIKINI_VERSION"))
{
            die ("acc&egrave;s direct interdit");
}
$plugin_output_new = preg_replace ('/<link.*style>/ms',
	'<!-- STYLE_DEBUT -->
		<link rel="stylesheet" type="text/css" media="screen" href="tools/ppdg_style/wakka.basic.css" />
		<style type="text/css" media="all">
			<!--
			@import url(tools/ppdg_style/'.$imported_style.'.css);
			-->
		</style>
		<!-- STYLE_FIN -->',
	$plugin_output_new);

$user = $this->GetUser();
if ($user) {
   	$plugin_output_new = preg_replace ('/<!-- STYLE_FIN -->/',
	'<style type="text/css" media="all"> @import "tools/ppdg_style/wakka.admin.css";</style>
		<!-- STYLE_FIN -->',
	$plugin_output_new);
}

$plugin_output_new = preg_replace ('/<!-- STYLE_FIN -->/',
	'<link rel="stylesheet" type="text/css" media="print" href="tools/ppdg_style/wakka.print.css" /> 
		<!-- STYLE_FIN -->',
	$plugin_output_new);

$plugin_output_new = preg_replace('/<body (onload="[^"]"|).*$/ms',
	'<body $1'.$body_attr.'>
	<!-- ENTETE_DEBUT -->
	<div id="entete">
		<div id="logo">
			<a href="/"><img src="bibliotheque/images/graphisme/logo.jpg" alt="Logo de la Carrière la Pierre du Pont du Gard Authentique."/></a>
		</div>
		<div style="display: none;"><a href="'.$page_addr.'/resetstyle" accesskey="7"></a></div>
		<div id="site_titre">
			<h1>La pierre du pont du gard authentique</h1>
		</div>
		<!-- PAGE_NOM -->
		<!-- RECHERCHE -->
		<!-- COMMUN -->
		<!-- ENTETE_FIN -->
		<!-- NAVIGATION -->
		<!-- ADMIN -->',
	$plugin_output_new);

if ($user) {
	$plugin_output_new = preg_replace ('/<!-- PAGE_NOM -->/',
		'<h1 class="page_name">
			<a href="'.$page_search.'">'.$page_name.'</a>
		</h1>',
		$plugin_output_new);
}

$plugin_output_new = preg_replace ('/<!-- RECHERCHE -->/',
	$this->FormOpen('', 'RechercheTexte', 'get').
	'<p id="moteur_recherche">
		<input id="phrase" name="phrase" type="text" size="15" value="Rechercher" onclick="javascrip:this.value=\'\';" />
		<input id="ok" name="ok" type="submit" value="OK" />
	</p>'.
	$this->FormClose(),
	$plugin_output_new);

$plugin_output_new = preg_replace ('/<!-- COMMUN -->/',
	'<div id="commun">
    	<p>
        	<a href="wakka.php?wiki=PlanDuSite">Plan du Site</a>
        	<a id="aller_menu" href="#menu">Aller au menu</a>
        	<a id="aller_contenu" href="#corps">Aller au contenu</a>
    	</p>
	</div>',
	$plugin_output_new);

$plugin_output_new = preg_replace ('/<!-- ENTETE_FIN -->/',
	"</div>\n<!-- ENTETE_FIN -->\n",
	$plugin_output_new);
?>
<?php

if (!defined("WIKINI_VERSION"))
{
            die ("acc&egrave;s direct interdit");
}

$plugin_output_new=preg_replace ('/<\/head>/',
	'
	<link rel="stylesheet" type="text/css" href="tools/galerie/actions/galerie/presentation/styles/jquery.galerie-0.1.css" media="screen" />
	<script type="text/javascript" src="tools/galerie/actions/galerie/presentation/scripts/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="tools/galerie/actions/galerie/presentation/scripts/jquery.galerie-0.1.js"></script>
	</head>
	',
	$plugin_output_new);
?>

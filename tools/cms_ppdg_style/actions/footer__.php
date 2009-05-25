<? 
if (!defined("WIKINI_VERSION")) {
	die ("acc&egrave;s direct interdit");
}
$user = $this->GetUser();
if (!$user) {
	$plugin_output_new = preg_replace ('/^.*<\/body>/ms',
		'</body>', 
		$plugin_output_new);
}

$plugin_output_new = preg_replace ('/<\/body>/',
		'<div class="copyright">
    	S.A.R.L. La Pierre du Pont du Gard Authentique - N° SIRET: 339 291 015 00016 - 
    	Fonctionne avec '.$wikini_site_url."\n".
    	'-'.$this->Link("Identification", "", "Administration")."\n".
		'</div>
		</body>', 
		$plugin_output_new);

?> 
<?php
class WikiApi {
	
	private $page = null;
	public $wikiObjet = null;
	private $cheminWiki = null;
	private $cheminApi = null;
	
	public function __construct($cheminWiki, $cheminApi) {
		$this->cheminWiki = $cheminWiki;
		$this->cheminApi = $cheminApi;
		$this->initialiser();
	}
	
	private function initialiser() {
		if ($this->page != null) {
        	$_REQUEST['wiki'] = $this->page;
        }
		
		ini_set('include_path',ini_get('include_path').':'.$this->cheminWiki.':');
		chdir($this->cheminWiki);
        include 'api.php';
        $this->wikiObjet = $wiki;
        chdir($this->cheminApi);
	}
	
	public function setPageCourante($page) {
		$this->page = $page;
	}
	
	public function chargerClasseWiki($classe) {
		$chemins = array($this->cheminWiki.'/tools/', $this->cheminWiki.'/formatters/');
		foreach ($chemins as $chemin) {
			$chemin = $chemin.$classe.'.php';
			if (file_exists($chemin)) {
				require_once $chemin;
				$classeTrouvee = true;
			}
		}
	}
	
	public function __call($methodeNom, $arguments) {

        if ($this->wikiObjet == null) {
        	$this->initialiser();
        }
        	
        chdir($this->cheminWiki);
        $retour = call_user_func_array(array($this->wikiObjet, $methodeNom), $arguments);
        chdir($this->cheminApi);
        return $retour;
    }
}
?>
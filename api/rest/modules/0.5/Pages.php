<?php
// declare(encoding='UTF-8');
/**
 * Web service de consultation d'un page wiki
 *
 * @category	php 5.2
 * @package		wapi
 * @author		Aurélien Peronnet < aurelien@tela-botanica.org>
 * @author		Jean-Pascal Milcent < jpm@tela-botanica.org>
 * @copyright	Copyright (c) 2011, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version		$Id$
 */
class Pages extends Service {
	
	private $wiki = null;
	private $pageNom = null;
	private $section = null;
	private $creerPage = false;
	private $templateDefaut = null;
	
	private $manipulationPage = null;
	
	private $retour = 'txt';
	private $formats_retour = array('text/plain','text/html');
	private $format_texte;
	
	const MIME_JSON = 'application/json';
	const MIME_HTML = 'text/html';
	const MIME_TEXT = 'text/plain';
	
	public function __construct($config) {
		parent::__construct($config);
		
		$this->wiki = Registre::get('wikiApi');
		// La variable globale wiki est déclarée par les wiki et leurs plugins
		// un bug lié à certains plugin impose de la redéclarer et la réaffecter
		global $wiki;
		$wiki = $this->wiki;
		
		// C'est moche mais normalement déterministe comme chemin de fichier
		require_once realpath(dirname(__FILE__).'/../../../../tools/login/libs/identificationsso.class.php');
		// Attention la classe de sso s'instancie avec le vrai objet wiki contenu dans wikiApi
		$identification = new IdentificationSso($this->wiki->wikiObjet);
		$identification->recupererIdentiteConnecteePourApi();
		
		require_once realpath(dirname(__FILE__).'/../../../bibliotheque/ManipulationPage.php');
		$this->manipulationPage = new ManipulationPage($this->wiki, $this->pageNom);
	}
	
	public function consulter($ressources, $parametres) {
		
		try {
			$this->definirValeurParDefautDesParametres();
			$this->verifierParametres($parametres);
			$this->analyserParametres($ressources, $parametres);
			
			$page = $this->manipulationPage->consulterPage($this->pageNom, $this->section);

			if($page == null && $this->creerPage) {
				$this->manipulationPage->creerPageAPartirTemplate($this->pageNom, $this->templateDefaut);
				$page = $this->manipulationPage->consulterPage($this->pageNom, $this->section);
			}
			
			$retour = $this->formaterRetour($page);
			
			$this->envoyerContenuJson($retour);
		} catch (Exception $e) {
			$this->envoyerErreur($e);
		}
	}
	
	private function definirValeurParDefautDesParametres() {
		if (isset($this->parametres['txt_format']) == false) {
			$this->parametres['txt_format'] = 'text/plain';
		}
	}
	
	private function verifierParametres($parametres) {
		$erreurs = array();
		
		if (isset($parametres['txt_format'])) {
			if(!in_array($parametres['txt_format'], $this->formats_retour)) {
				$message = "La valeur du paramètre 'txt.format' peut seulement prendre les valeurs : text/plain et text/html.";
				$erreurs[] = $message;
			}
		}
		
		if (isset($parametres['txt_section_position']) && !is_numeric($parametres['txt_section_position'])) {
			$message = "La valeur du paramètre 'txt.section.position' peut seulement prendre des valeurs numeriques";
			$erreurs[] = $message;
		}
		
		if (isset($parametres['txt_section_titre']) && trim($parametres['txt_section_titre']) == '') {
			$message = "La valeur du paramètre 'txt.section.titre' ne peut pas être vide si celui-ci est présent";
			$erreurs[] = $message;
		}
		
		if (isset($parametres['txt_section_titre']) && trim($parametres['txt_section_titre']) == '') {
			$message = "La valeur du paramètre 'txt.section.titre' ne peut pas être vide si celui-ci est présent";
			$erreurs[] = $message;
		}
		
		if (isset($parametres['txt_template']) && trim($parametres['txt_template']) == '') {
			$message = "La valeur du paramètre 'txt_template' ne peut pas être vide si celui-ci est présent";
			$erreurs[] = $message;
		}
				
		if (count($erreurs) > 0) {
			$message = implode('<br />', $erreurs);
			$code = RestServeur::HTTP_CODE_MAUVAISE_REQUETE;
			throw new Exception($message, $code);
		}
	}
	
	private function analyserParametres($ressources, $parametres) {	
		$this->pageNom = $ressources[0];
		if (isset($parametres['txt_section_titre'])) {
			$this->section = $parametres['txt_section_titre'];
		}
		if (isset($parametres['txt_section_position'])) {
			$this->section = $parametres['txt_section_position'];
		}
		if (isset($parametres['txt_format'])) {
			$this->retour = $parametres['txt_format'];
		}
		if (isset($parametres['txt_template'])) {
			$this->creerPage = true;
			$this->templateDefaut = $parametres['txt_template'];
		}
	}
	
	private function formaterRetour($page) {

		$mime = null;
		$texte = '';
		
		switch ($this->retour) {
			case self::MIME_HTML:
				$texte = $this->wiki->Format($page["body"], "wakka");
				if(!empty($page["sections"])) {
					foreach($page["sections"] as &$page_section) {
						$page_section = $this->wiki->Format($page_section, "wakka");
					}
				}
				$mime = self::MIME_HTML;
				break;
			default:
				$texte = $page["body"];
				$mime = self::MIME_TEXT;
		}
		
		$url = $this->wiki->Href("", $this->pageNom);
		
		$retour = array('id' => $this->pageNom,
				'titre' => $this->pageNom,
				'mime' => $mime,
				'texte' => $texte,
				'sections' => $page["sections"],
				'href' => $url);
		
		return $retour;
	}
	
	public function ajouter($ressources, $requeteDonnees) {
		return $this->modifier($ressources, $requeteDonnees);
	}
	
	public function modifier($ressources, $requeteDonnees) {

		$requeteDonnees['pageTag'] = $ressources[0];
		$this->verifierParametresEcriture($requeteDonnees);
		$this->analyserParametresEcriture($requeteDonnees);
		$this->wiki = Registre::get('wikiApi');
		$this->wiki->setPageCourante($this->pageNom);
		
		$texte = $requeteDonnees['pageContenu'];
		$page = $this->manipulationPage->consulterPage($this->pageNom);
				
		if ($page != null) {
			$corps = ($this->section != null) ? $this->manipulationPage->remplacerSection($this->section, $texte, $page['body']) : $texte;	
		} else {
			$corps = $texte;
		}	
		
		$ecriture = $this->manipulationPage->ecrirePage($this->pageNom, $corps);
		
		if ($ecriture) {
			$this->envoyerCreationEffectuee();
			exit;
		} else {
			$message = 'Impossible de créer ou modifier la page';
			$code = RestServeur::HTTP_CODE_ERREUR;
			throw new Exception($message, $code);
		}
		
		return $ecriture;
	}
	
	private function analyserParametresEcriture($parametres) {
		$this->pageNom = $parametres['pageTag'];
		$this->section = isset($parametres['pageSectionTitre']) ? $parametres['pageSectionTitre'] : null;
	}
	
	private function verifierParametresEcriture($parametres) {
			
		$erreurs = array();
		
		if (!isset($parametres['pageContenu'])) {
			$message = "Le paramètre pageContenu est obligatoire";
			$erreurs[] = $message;
		}
		
		if (!isset($parametres['pageTag']) || trim($parametres['pageTag']) == '') {
			$message = "Le paramètre pageTag est obligatoire";
			$erreurs[] = $message;
		}
		
		if (isset($parametres['pageSectionTitre']) && $parametres['pageSectionTitre'] == '') {
			$message = "Le paramètre pageSectionTitre ne doit pas être vide s'il est présent";
			$erreurs[] = $message;
		}
		
		if (count($erreurs) > 0) {
			$message = implode('<br />', $erreurs);
			$code = RestServeur::HTTP_CODE_MAUVAISE_REQUETE;
			throw new Exception($message, $code);
		}
	}
}	
?>
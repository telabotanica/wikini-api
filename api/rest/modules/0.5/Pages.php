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
	
	private $retour = 'txt';
	private $formats_retour = array('text/plain','text/html');
	private $format_texte;
	
	const MIME_JSON = 'application/json';
	const MIME_HTML = 'text/html';
	const MIME_TEXT = 'text/plain';
	
	public function consulter($ressources, $parametres) {
		
		try {
			$this->definirValeurParDefautDesParametres();
			$this->verifierParametres($parametres);
			$this->analyserParametres($ressources, $parametres);
			
			$page = $this->consulterPage($this->pageNom, $this->section);
			$retour = $this->formaterRetour($page);
			
			$this->envoyerContenuJson($retour);
		} catch (Exception $e) {
			$this->envoyerErreur($e);
		}
	}
	
	private function definirValeurParDefautDesParametres() {
		if (isset($this->parametres['txt_format']) == false) {
			$this->parametres['txt_format'] = 'txt';
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
		
		if(isset($parametres['txt_section_position']) && !is_numeric($parametres['txt_section_position'])) {
			$message = "La valeur du paramètre 'txt.section.position' peut seulement prendre des valeurs numeriques";
			$erreurs[] = $message;
		}
		
		if(isset($parametres['txt_section_titre']) && trim($parametres['txt_section_titre']) == '') {
			$message = "La valeur du paramètre 'txt.section.titre' ne peut pas être vide si celui-ci est présent";
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
		if(isset($parametres['txt_section_titre'])) {
			$this->section = $parametres['txt_section_titre'];
		}
		if(isset($parametres['txt_section_position'])) {
			$this->section = $parametres['txt_section_position'];
		}
		if (isset($parametres['txt_format'])) {
			$this->retour = $parametres['txt_format'];
		}
	}
	
	private function consulterPage($page, $section = null) {
		$this->wiki = Registre::get('wikiApi');
		$this->wiki->setPageCourante($this->pageNom);
		$page = $this->wiki->LoadPage($page);
		// attention les wikis sont en ISO !
		$page["body"] = $this->convertirTexteWikiVersEncodageAppli($page['body']);
	
		if($section != null) {
			$page["body"] = $this->decouperPageSection($page["body"], $section);
		}
	
		return $page;
	}
	
	private function decouperPageSection($contenu_page, $section) {
	
		$section_retour = '';
	
		if(is_numeric($section)) {
			$section_retour =  $this->getSectionParNumero($contenu_page, $section);
		} else {
			$section_retour =  $this->getSectionParTitre($contenu_page, $section);
		}
	
		return $section_retour;
	}
	
	public function getSectionParNumero($page, $num) {
		preg_match_all('/(=[=]+[ ]*)(.[.^=]*)+[ ]*=[=]+[.]*/i', $page, $sections, PREG_OFFSET_CAPTURE);
		$sectionTxt = '';
		$debut_section = 0;
		$lg_page = strlen($page);
		$fin_section = $lg_page;
		
		if($num <= count($sections[1]) && $num > 0) {	
						
			$debut_section = $sections[1][$num - 1][1];
			$separateur = trim($sections[1][$num - 1][0]);
			$separateur_trouve = false;
						
			for($i = $num; $i < count($sections[1]); $i++) {
				$fin_section = $sections[1][$i][1];
				if($separateur == trim($sections[1][$i][0])) {
					$separateur_trouve = true;
					break;
				}
			}
			
			$fin_section = $separateur_trouve ? $fin_section : $lg_page;
			$sectionTxt = substr($page, $debut_section, $fin_section - $debut_section);
		} else {
			$sectionTxt = '';
		}	

		return $sectionTxt;
	}
	
	public function getSectionParTitre($page, $titre) {
		$section = '';
		$reg_exp = '/((=[=]+)[ ]*'.preg_quote(trim($titre), '/').'[ ]*=[=]+)[.]*/i';
		$match = preg_split($reg_exp, $page, 2, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		
		if(count($match) > 3) {
			$section = explode(trim($match[2]), $match[3], 2);
			$section = $match[1].$section[0];
		} elseif(count($match) == 2) {
			$section = explode(trim($match[1]), $match[2], 2);
			$section = $match[0].$section[0];
		} else {
			$section = "";
		}
		
		return $section;
	}
	
	private function formaterRetour($page) {

		$mime = null;
		$texte = '';
		
		switch($this->retour) {
			case self::MIME_HTML:
				$texte = $this->wiki->Format($page["body"], "wakka");
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
				'href' => $url);
		
		return $retour;
	}
	
	public function ajouter($ressources, $requeteDonnees) {
		return $this->modifier($ressources, $requeteDonnees);
	}
	
	public function modifier($ressources, $requeteDonnees) {
	
		$this->pageNom = $ressources[0];
		$this->wiki = Registre::get('wikiApi');
		$this->wiki->setPageCourante($this->pageNom);
		
		$section = (isset($requeteDonnees['txt.section.titre']) && trim($requeteDonnees['txt.section.titre']) != "") ? $requeteDonnees['txt.section.titre'] : null;
		$texte = $requeteDonnees['texte'];
		
		$page = $this->consulterPage($this->pageNom);
		$corps = $page['body'];
		$section_page_originale = $corps;
		
		if($section != null) {
			$section_page_originale = $this->decouperPageSection($corps, $section);
		}	
		
		$page_remplacee = str_replace($section_page_originale, $texte, $corps);
		
		$texte_encode = $this->convertirTexteAppliVersEncodageWiki($page_remplacee);
		$ecriture = $this->wiki->SavePage($this->pageNom, $texte_encode, "", true);
	
		if($ecriture) {
			$this->envoyerCreationEffectuee();
		} else {
			$this->envoyerErreurServeur();
		}
		
		return $ecriture;
	}
	
	private function convertirTexteWikiVersEncodageAppli($texte) {
		if(Config::get('encodage_appli') != Config::get('encodage_wiki')) {
			$texte = mb_convert_encoding($texte,Config::get('encodage_appli'),Config::get('encodage_wiki'));
		}
		return $texte;
	}
	
	private function convertirTexteAppliVersEncodageWiki($texte) {
		if(Config::get('encodage_appli') != Config::get('encodage_wiki')) {
			$texte = mb_convert_encoding($texte,Config::get('encodage_wiki'),Config::get('encodage_appli'));
		}
		return $texte;
	}
}	
?>
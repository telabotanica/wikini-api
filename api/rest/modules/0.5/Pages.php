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
	private $formats_retour = array('txt','html');
	private $format_texte;
	
	const MIME_JSON = 'application/json';
	const MIME_HTML = 'text/html';
	const MIME_TEXT = 'text/plain';
	
	public function consulter($ressources, $parametres) {
		
		try {
			$this->definirValeurParDefautDesParametres();
			$this->verifierParametres($parametres);
			$this->analyserParametres($ressources, $parametres);
			
			$page = $this->consulterPage($this->pageNom);
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
				$message = "La valeur du paramètre 'txt.format' peut seulement prendre les valeurs : txt et html.";
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
	
	private function consulterPage($page) {
		$this->wiki = Registre::get('wikiApi');
		$this->wiki->setPageCourante($this->pageNom);
		$page = $this->wiki->LoadPage($page);
		
		// attention les wikis sont en ISO !
		if(Config::get('encodage_appli') != Config::get('encodage_wiki')) {
			$page["body"] = mb_convert_encoding($page['body'],Config::get('encodage_appli'),Config::get('encodage_wiki'));
		}
	
		if($this->section != null) {
			$page["body"] = $this->découperPageSection($page["body"], $this->section);
		}
	
		return $page;
	}
	
	private function découperPageSection($contenu_page, $section) {
	
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
			$section = $match[1].' '.$section[0];
		} elseif(count($match) == 2) {
			$section = explode(trim($match[1]), $match[2], 2);
			$section = $match[0].' '.$section[0];
		} else {
			$section = "";
		}
		
		return $section;
	}
	
	private function formaterRetour($page) {

		$mime = null;
		$texte = '';
		
		switch($this->retour) {
			case 'html':
				$texte = $this->wiki->Format($page["body"], "wakka");
				$mime = self::MIME_HTML;
				break;
			default:
				$texte = $page["body"];
				$mime = self::MIME_TEXT;
		}
		
		$retour = array('id' => $this->pageNom,
				'titre' => $this->pageNom,
				'mime' => $mime,
				'texte' => $texte,
				'href' => '');
		
		return $retour;
	}
}	
?>
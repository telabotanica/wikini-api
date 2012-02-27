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
	
	public function consulter($ressources, $parametres) {
		header('Content-type: text/plain');
		$verifOk = $this->verifierParametres($parametres);
		if ($verifOk) {
			$this->pageNom = $ressources[0];
			$page = $this->consulterPage($ressources[0]);
			return $this->formaterRetour($page);
		} else {
			RestServeur::envoyerEnteteStatutHttp(RestServeur::HTTP_CODE_MAUVAISE_REQUETE);		
		}
	}
	
	private function definirValeurParDefautDesParametres() {
		if (isset($this->parametres['retour']) == false) {
			$this->parametres['retour'] = self::MIME_JSON;
		}
		if (isset($this->parametres['txt_format']) == false) {
			$this->parametres['txt_format'] = 'txt';
		}
	}
	
	private function verifierParametres($parametres) {
		$ok = true;
		if (isset($parametres['txt_format'])) {
			if(!in_array($parametres['txt_format'], $this->formats_retour)) {
				$message = "La valeur du paramètre 'txt.format' peut seulement prendre les valeurs : txt et html.";
				$this->ajouterMessage($message);
				$ok = false;
			} else {
				$this->retour = $parametres['txt_format'];
			}
		}
		
		if(isset($parametres['txt_section_position'])) {
			$this->section = $parametres['txt_section_position'];
		}
		
		if(isset($parametres['txt_section_titre'])) {
			$this->section = $parametres['txt_section_titre'];
		}
		
		return $ok;
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

		switch($this->retour) {
			case 'html':
				$retour = $this->wiki->Format($page["body"], "wakka");
				break;
			default:
				$retour = $page["body"];
		}
		return $retour;
	}
	
	private function formaterRetourHtml($retour) {
	
	}
}	
?>
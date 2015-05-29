<?php
// declare(encoding='UTF-8');
/**
 * Librairie de consultation d'une page wiki
 *
 * @category	php 5.2
 * @package		wapi
 * @author		Aurélien Peronnet < aurelien@tela-botanica.org>
 * @copyright	Copyright (c) 2015, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version		$Id$
 */
class ManipulationPage {
	
	// C'est dommage cette classe fait doublon avec la classe contenue dans le dossier rest
	// il faudrait faire une factorisation de tout ça 
	private $wiki = null;
	private $pageNom = null;
	private $section = null;
	private $creerPage = false;
	private $templateDefaut = null;
	
	public function __construct($wiki, $pageNom) {
		// Cette construction bizarre sert à éviter des bugs issus du comportement de wikini
		$this->wiki = $wiki;
		global $wiki;
		$wiki = $this->wiki;
		
		$this->pageNom = $pageNom;
		$this->wiki->setPageCourante($this->pageNom);
	}
	
	public function consulterPage($page, $section = null) {
		$page = $this->wiki->LoadPage($page);
				
		if ($page != null) {
			$this->consulterPageSectionsFormatees($page, $section);
		}
	
		return $page;
	}
	
	public function consulterPageSectionsFormatees(&$page, $section) {
		// attention les wikis sont souvent en ISO !
		$page["body"] = $this->convertirTexteWikiVersEncodageAppli($page['body']);
		if($section != null) {
			$sections_tab = explode(',', $section);
			if(count($sections_tab) > 1) {
				foreach($sections_tab as $section_t) {
					$page["sections"][$section_t] = $this->decouperPageSection($page["body"], $section_t);
				}
			} else {
				$page["body"] = $this->decouperPageSection($page["body"], $section);
			}
		}
	}
	
	public function decouperPageSection($contenu_page, $section) {
		$section_retour = '';
		if (is_numeric($section)) {
			$section_retour =  $this->getSectionParNumero($contenu_page, $section);
		} else {
			$section_retour =  $this->getSectionParTitre($contenu_page, $section, false);
		}
		return $section_retour;
	}
	
	public function getSectionParNumero($page, $num) {
		preg_match_all('/(=[=]+[ ]*)(.[.^=]*)+[ ]*=[=]+[.]*/i', $page, $sections, PREG_OFFSET_CAPTURE);
		$sectionTxt = '';
		$debut_section = 0;
		$lg_page = strlen($page);
		$fin_section = $lg_page;
		
		if ($num <= count($sections[1]) && $num > 0) {	
						
			$debut_section = $sections[1][$num - 1][1];
			$separateur = trim($sections[1][$num - 1][0]);
			$separateur_trouve = false;
						
			for ($i = $num; $i < count($sections[1]); $i++) {
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
	
	public function getSectionParTitre($page, $titre, $inclure_titre = false) {
		$section = '';
		$reg_exp = '/((=[=]+)[ ]*'. trim($titre) .'[ ]*=[=]+)[.]*/i';
		$match = preg_split($reg_exp, $page, 2, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		if (count($match) > 3) {
			$section = explode(trim($match[2]), $match[3], 2);
			$section = $section[0];
			$section = ($inclure_titre) ? $match[1].$section : $section;
		} elseif (count($match) == 3) {
			$section = explode(trim($match[1]), $match[2], 2);
			$section = $section[0];
			$section = ($inclure_titre) ? $match[0].$section : $section;
		} else {
			$section = "";
		}
		
		return $section;
	}
	
	private function creerPageAPartirTemplate($tag_page_a_creer, $tag_template) {
		$page_template = $this->consulterPage($tag_template);
		$corps_nouvelle_page = ($page_template != null) ? $page_template['body'] : '';
		// si le template n'existe pas, la page créée sera vide
		$ecriture = $this->ecrirePage($tag_page_a_creer, $corps_nouvelle_page);	
		
		return $ecriture;
	}
	
	/**
	 * 
	 * Si la section demandée existe dans la page, renvoie un tableau contenant le numéro de caractère de 
	 * début de la section, après son titre, ainsi que la longeur du titre
	 * @param string $titre de la section
	 * @param string $page contenu de la page wiki
	 * @return tableau associatif tel que décrit ici
	 */
	private function getInformationsPositionSection($titre, $page) {
		
		preg_match_all('/(=[=]+[ ]*'.preg_quote(trim($titre), '/').'[ ]*=[=]+[.]*)/i', $page, $sections, PREG_OFFSET_CAPTURE);
		$longueur_titre = 0;
		$debut_section_apres_titre = 0;
		
		if (count($sections) > 0 && is_array($sections[0]) && count($sections[0][0]) >= 2) {
			$longueur_titre = mb_strlen($sections[0][0][0]);
			$debut_section_apres_titre = $sections[0][0][1] + $longueur_titre;
		}
		
		// ATTENTION : début contient le numéro du caractere de début de la section, après le titre
		$infos = array('debut' => $debut_section_apres_titre,
						'longueur_titre' => $longueur_titre
				);
		
		return $infos;
	}
	
	private function remplacerSection($titre_section, $section_remplacement, $corps) {
				
		// insertion d'un saut de ligne pour empêcher de casser le titre, lorsque le titre
		// suivant vient directement après la section, sans saut de ligne ni espace
		$section_remplacement = "\n".$section_remplacement."\n";
		$section_page_originale = $this->getSectionParTitre($corps, $titre_section, true);
		$infos_section = $this->getInformationsPositionSection($titre_section, $corps);
		$nb_caracteres_a_remplacer = mb_strlen($section_page_originale) - $infos_section['longueur_titre'];
		
		$nouveau_contenu = substr_replace($corps, $section_remplacement, $infos_section['debut'], $nb_caracteres_a_remplacer);
			
		return $nouveau_contenu;
	}
	
	public function ecrirePage($nom_page, $contenu) {
		
		$texte_encode = $this->convertirTexteAppliVersEncodageWiki($contenu);
		$ecriture = $this->wiki->SavePage($nom_page, $texte_encode, "", true);
		
		return $ecriture;
	}
	
	public static function convertirTexteWikiVersEncodageAppli($texte) {
		if (Config::get('encodage_appli') != Config::get('encodage_wiki')) {
			$texte = mb_convert_encoding($texte,Config::get('encodage_appli'),Config::get('encodage_wiki'));
		}
		return $texte;
	}
	
	public static function convertirTexteAppliVersEncodageWiki($texte) {
		if (Config::get('encodage_appli') != Config::get('encodage_wiki')) {
			$texte = mb_convert_encoding($texte,Config::get('encodage_wiki'),Config::get('encodage_appli'));
		}
		return $texte;
	}
}	
?>
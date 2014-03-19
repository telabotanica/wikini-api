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

	private $retour = null;
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

			if($page == null && $this->creerPage) {
				$this->creerPageAPartirTemplate($this->pageNom, $this->templateDefaut);
				$page = $this->consulterPage($this->pageNom, $this->section);
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

	private function consulterPage($page, $section = null) {

		$this->wiki = Registre::get('wikiApi');
		// La variable globale wiki est déclarée par les wiki et leurs plugins
		// un bug lié à certains plugin impose de la redéclarer et la réaffecter
		global $wiki;
		$wiki = $this->wiki;
		$this->wiki->setPageCourante($this->pageNom);
		$page = $this->wiki->LoadPage($page);

		if ($page != null) {
			$page["body"] = $this->convertirTexteWikiVersEncodageAppli($page['body']);
			if($section != null) {
				$page["body"] = $this->decouperPageSection($page["body"], $section);
			}
		}

		return $page;
	}

	private function decouperPageSection($contenu_page, $section) {
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
		$reg_exp = '/((=[=]+)[ ]*'.preg_quote(trim($titre), '/').'[ ]*=[=]+)[.]*/i';
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

	private function formaterRetour($page) {

		$mime = null;
		$texte = '';

		switch ($this->retour) {
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

	private function creerPageAPartirTemplate($tag_page_a_creer, $tag_template) {
		$page_template = $this->consulterPage($tag_template);
		$corps_nouvelle_page = ($page_template != null) ? $page_template['body'] : '';
		// si le template n'existe pas, la page créée sera vide
		$ecriture = $this->ecrirePage($tag_page_a_creer, $corps_nouvelle_page);

		return $ecriture;
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
		$page = $this->consulterPage($this->pageNom);

		if ($page != null) {
			$corps = ($this->section != null) ? $this->remplacerSection($this->section, $texte, $page['body']) : $texte;
		} else {
			$corps = $texte;
		}

		$ecriture = $this->ecrirePage($this->pageNom, $corps);

		if ($ecriture) {
			$this->envoyerCreationEffectuee();
		} else {
			$message = 'Impossible de créer ou modifier la page';
			$code = RestServeur::HTTP_CODE_ERREUR;
			throw new Exception($message, $code);
		}

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

	private function ecrirePage($nom_page, $contenu) {

		$texte_encode = $this->convertirTexteAppliVersEncodageWiki($contenu);
		$ecriture = $this->wiki->SavePage($nom_page, $texte_encode, "", true);

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

	private function convertirTexteWikiVersEncodageAppli($texte) {
		if (Config::get('encodage_appli') != Config::get('encodage_wiki')) {
			$texte = mb_convert_encoding($texte,Config::get('encodage_appli'),Config::get('encodage_wiki'));
		}
		return $texte;
	}

	private function convertirTexteAppliVersEncodageWiki($texte) {
		if (Config::get('encodage_appli') != Config::get('encodage_wiki')) {
			$texte = mb_convert_encoding($texte,Config::get('encodage_wiki'),Config::get('encodage_appli'));
		}
		return $texte;
	}
}
?>
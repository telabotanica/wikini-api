<?php
// declare(encoding='UTF-8');
/**
 *
 * @category   wiki/smart'Flore
 * @package    Scripts
 * @author     Aurelien PERONNET <aurelien@tela-botanica.org>
 * @license    GPL v3 <http://www.gnu.org/licenses/gpl.txt>
 * @license    CECILL v2 <http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt>
 * @copyright  1999-2015 Tela Botanica <accueil@tela-botanica.org>
 */

class MigrationSmartFlore extends Script {

	protected $mode_verbeux = false;
	
	// Paramêtres autorisées lors de l'appel au script en ligne de commande
	protected $parametres_autorises = array(
			'-n' => array(true, true, 'Nom du fichier ou du dossier à traiter'));
	
	public function executer() {
		// L'obligation de mettre un paramètre -a donnée par le framework
		// n'a pas de sens, ça ne doit pas être obligatoire !!!
		$cmd = $this->getParametre('a');
		$this->mode_verbeux = $this->getParametre('v');
		
		switch($cmd) {
			case 'tous' :
				$this->migrerFormatSmartFlore();
			break;
			
			default:
		}
	}
	
	protected function migrerFormatSmartFlore() {	

		// sections "souples" - attention, ne seront pas "quotées" mais interprétées comme morceaux de regexp directement !
		$sections = array("Fiche simplifi.+e Smart.+flore", "Introduction","Comment la reconna.+tre.+","Son histoire","Ses usages",".+cologie.+habitat","Ce qu.+il faut savoir.+","Sources");
		$nouvelles_sections = array(
			"Description" => array("Introduction","Comment la reconna.+tre.+","Son histoire"),
			"Usages" => array("Ses usages", "Ce qu.+il faut savoir.+"),
			"Écologie & habitat" => array(".+cologie.+habitat"),
			"Sources" => array("Sources")
		);
		
		$where_section = 'body NOT LIKE "';
		$nouvelles_sections_k = array_keys($nouvelles_sections);
		foreach($nouvelles_sections_k as $nouvelle_section_k) {
			// Encore et toujours de l'iso (d'ailleurs si on ne fait pas de conversion la requete se comporte
			// très bizarrement et renvoie des résultats en trop une fois le script déjà exécuté)
			$where_section .= '%'.addslashes(ManipulationPage::convertirTexteAppliVersEncodageWiki($nouvelle_section_k)).'%';
		}
		$where_section = $where_section.'"';
			
		$this->wiki = Registre::get('wikiApi');
		$requete = 'SELECT * FROM '.$this->wiki->GetConfigValue('table_prefix').'pages WHERE latest = "Y" '.
						'AND tag LIKE "SmartFlore%nt%" '.
						'AND '.$where_section;

		$pages = $this->wiki->LoadAll($requete);
		$pages_fmt = array();
		echo "Nombre de pages à migrer : ".count($pages)."\n";

		if(!empty($pages)) {
			$manipulation = new ManipulationPage($this->wiki, $pages[0]);
			echo "Migration en cours... \n";
			
			foreach($pages as &$page) {
				
				$page_fmt = array();
				
				// On capte l'entête de la page situé avant la première section pour le recopier
				// dans les nouvelles pages (il contient les backlinks et les noms)
				$delim_entete = strpos($page["body"], "==== Introduction ====");
				if($delim_entete === false) {
					$delim_entete = strpos($page["body"], "====Introduction====");
				}
				// Attention l'entete est en iso, il faut le convertir manuellement
				$entete = $manipulation->convertirTexteWikiVersEncodageAppli(substr($page["body"], 0, $delim_entete));
				
				// Par contre ici consulterPageSectionsFormatees est gentil et fait la conversion vers l'encodage de l'appli pour nous
				$manipulation->consulterPageSectionsFormatees($page, implode(',', $sections));
				
				// Fusion des anciennes sections dans les nouvelles
				foreach($nouvelles_sections as $nom_nouvelle_section => $sections_a_fusionner) {
					$page_fmt[$nom_nouvelle_section] = '===='.$nom_nouvelle_section.'====';
					foreach($sections_a_fusionner as $section_a_fusionner) {
						if(isset($page['sections'][$section_a_fusionner])) {
							$page_fmt[$nom_nouvelle_section] .= $page['sections'][$section_a_fusionner];
						}
					}
				}
				
				$corps = $entete."\n".implode("\n", $page_fmt);
				$manipulation->ecrirePage($page["tag"], $corps);
			}
		}
		echo "Migration effectuée \n";
		// Le exit est là pour empecher l'affichage d'être pollué par les erreurs 
		// dûes à certaines antédiluviennités de wikini
		exit;
	}
	
	// http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
	protected function endsWith($haystack, $needle) {
		// search forward starting from end minus needle length characters
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}
}
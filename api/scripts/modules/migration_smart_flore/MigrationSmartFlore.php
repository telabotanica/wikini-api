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
	
	public function executer() {
		$cmd = $this->getParametre('a');
		$this->mode_verbeux = $this->getParametre('v');
		
		switch($cmd) {
			case 'migrerFormatSmartFlore' :
				$this->migrerFormatSmartFlore();
			break;
			
			case 'migrerSentiersSmartFlore' :
				$this->migrerSentiersSmartFlore();
			break;
			
			default:
		}
	}
	
	protected function migrerSentiersSmartFlore() {
		$this->wiki = Registre::get('wikiApi');
		$requete = 'SELECT * FROM '.$this->wiki->GetConfigValue('table_prefix').'pages WHERE latest = "Y" '.
				'AND tag = "AccesProjet" ';
				
		$page_sentiers = $this->wiki->LoadSingle($requete);
		
		preg_match_all("|\[\[([^\]\]]*)\]\]|", $page_sentiers['body'], $sentiers, PREG_PATTERN_ORDER);
		$sentiers = $sentiers[1];
		
		echo "Nombre de sentiers à migrer : ".count($sentiers)."\n";
		
		$sentiers_a_inserer = array();
		$valeurs_sentiers_a_inserer = array();
		$fiches_a_associer = array();
		
		$proprietaires_sentiers = array();
		// Chargement du fichier contenant les propriétaires à associer aux sentiers
		$fichier = file(realpath(dirname(__FILE__)).'proprietaires_sentiers.csv');
		
		foreach ($fichier as $ligne) {
			$data = str_getcsv($ligne);
			if($data[2] != "") {
				$proprietaires_sentiers[trim($data[0])] = trim($data[2]);
			}		
		}
		
		$courriel_proprietaires = array_values(array_unique($proprietaires_sentiers));	
		$url_infos_courriels = 	Config::get('annuaire_infos_courriels_url').implode(',', $courriel_proprietaires);

		$infos_proprietaires = json_decode(file_get_contents($url_infos_courriels), true);
		
		$infos_proprietaires_a_sentier = array();
		
		foreach($proprietaires_sentiers as $nom_sentier => $proprietaire_sentier) {
			if(isset($infos_proprietaires[$proprietaire_sentier])) {
				$infos_proprietaires_a_sentier[$nom_sentier] = $infos_proprietaires[$proprietaire_sentier]['nomWiki'];
			} else {
				// les sentiers sans propriétaires sont affectés au compte accueil
				$infos_proprietaires_a_sentier[$nom_sentier] = "AssociationTelaBotanica";
			}
		}
		
		$requete_insertion = 'INSERT INTO '.$this->wiki->GetConfigValue('table_prefix').'triples '.
					'(resource, property, value) VALUES ';
		
		foreach($sentiers as $sentier) {
			
			list($tag, $titre) = explode(' ', $sentier, 2);
			
			$requete = 'SELECT * FROM '.$this->wiki->GetConfigValue('table_prefix').'pages WHERE latest = "Y" '.
					'AND tag = "'.$tag.'" ';
			
			$infos_sentier = $this->wiki->LoadSingle($requete);
			
			$titre = trim($titre);
			$proprietaire = !empty($infos_proprietaires_a_sentier[$titre]) ? $infos_proprietaires_a_sentier[$titre] : "AssociationTelaBotanica";
			
			$sentiers_a_inserer[] = array(
										'resource' => $titre,
										'property' => 'smartFlore.sentiers',
										'value' => $proprietaire
									);
			
			$valeurs_sentiers_a_inserer[] = "('".addslashes(trim($titre))."', 'smartFlore.sentiers', '".$proprietaire."')";
			
			preg_match_all("|\[\[(SmartFlore[^(?:nt)]*nt[0-9]*)|", $infos_sentier['body'], $fiches_du_sentier, PREG_PATTERN_ORDER);
			
			if(!empty($fiches_du_sentier[0])) {
				foreach($fiches_du_sentier[1] as $fiche_du_sentier) {
					$fiches_a_associer[] = array(
							'resource' => $fiche_du_sentier,
							'property' => 'smartFlore.sentiers.fiche',
							'value' => $titre
					);
					
					$valeurs_fiches_a_associer[] = "('".$fiche_du_sentier."', 'smartFlore.sentiers.fiche', '".addslashes(trim($titre))."')";
				}
			}
		}
		
		$valeurs_a_inserer = $valeurs_sentiers_a_inserer + $valeurs_fiches_a_associer;
		$requete_insertion .= implode(', '."\n", $valeurs_a_inserer);
		// Tout est contenu dans la table triple du wiki, donc une seule requête suffit pour tout insérer
		$this->wiki->Query($requete_insertion);
		
		echo 'Migration des sentiers effectuée'."\n";
		exit;
	}
	
	protected function migrerFormatSmartFlore() {	

		// sections "souples" - attention, ne seront pas "quotées" mais interprétées comme morceaux de regexp directement !
		$sections = array("Fiche simplifi.+e Smart.+flore", "Introduction","Comment la reconna.+tre.+","Son histoire","Ses usages",".+(?:cologie|habitat).+","Ce qu.+il faut savoir.+","Sources");
		$nouvelles_sections = array(
			"Description" => array("Introduction","Comment la reconna.+tre.+","Son histoire"),
			"Usages" => array("Ses usages", "Ce qu.+il faut savoir.+"),
			"Écologie & habitat" => array(".+(?:cologie|habitat).+"), // groupe non-capturant avec (?:a|b)
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
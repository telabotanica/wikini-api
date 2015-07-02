<?php
// declare(encoding='UTF-8');
/**
 * Classe d'exemple de service web du projet eFlore
 * Source des données : {NOM_DU_PROJET} {ADRESSE_WEB_DONNEES_DU_PROJET}
 * Paramètres du service :
 *  - param1 : explication de l'utilisation du param1
 *  - param2 : explication de l'utilisation du param2
 * Exemple :
 * http://localhost/{CODE_DU_PROJET}/services/0.1/Exemple?param1=val1&param2=val2
 *
 * @category	php 5.2
 * @package		lion1906
 * @author		{PRENOM} {NOM}<{PRENOM}@tela-botanica.org>
 * @copyright	Copyright (c) 2011, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 * @version		$Id$
 */
class Exemple extends Service {
		
	public function consulter($ressources, $parametres) {
		$verifOk = $this->verifierParametres($parametres);
		if ($verifOk) {
			// Débuter ici le code du service
			$wiki = Registre::get('wikiApi');
			$wiki->setPageCourrante($ressources[0]);
			$page = $wiki->LoadPage($ressources[0]);
			$retour = $wiki->Format($page["body"], "wakka");
			return $retour;
		} else {
			RestServeur::envoyerEnteteStatutHttp(RestServeur::HTTP_CODE_MAUVAISE_REQUETE);
			
		}
	}
	
	private function verifierParametres($parametres) {
		$ok = true;
		extract($parametres);
		if (isset($param1) && !preg_match('/^val1$/', $param1)) {
			$message = "La valeur du param1 n'est pas bonne. Seul 'val1' est accepté.";
			$this->ajouterMessage($message);
			$ok = false;
		}
		if (isset($param1) && !preg_match('/^val1$/', $param2)) {
			$message = "La valeur du param2 n'est pas bonne Seul 'val2' est accepté.";
			$this->ajouterMessage($message);
			$ok = false;
		}
		return $ok;
	}
}	
?>
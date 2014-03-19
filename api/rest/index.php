<?php

// Permet d'afficher le temps d'execution du service
$temps_debut = (isset($_GET['chrono']) && $_GET['chrono'] == 1) ? microtime(true) : '';
// +-------------------------------------------------------------------------------------------------------------------+
/**
 * Serveur REST Wikini
 *
 * Initialise le chargement et l'exécution des services web.
 * Encodage : UTF-8
 * @author		Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @copyright	Tela-Botanica 1999-2008
 * @licence		GPL v3 & CeCILL v2
 */

// Le fichier autoload.inc.php du Framework de Tela Botanica doit être appelée avant tout autre chose dans l'application.
// Sinon, rien ne sera chargé.
// Chemin du fichier chargeant le framework requis
$framework = dirname(__FILE__).DIRECTORY_SEPARATOR.'framework.php';

if (!file_exists($framework)) {
	$e = "Veuillez paramétrer l'emplacement et la version du Framework dans le fichier $framework";
	trigger_error($e, E_USER_ERROR);
} else {
	// Inclusion du Framework
	require_once $framework;
	// Ajout d'information concernant cette application
	Framework::setCheminAppli(__FILE__);// Obligatoire
	Framework::setInfoAppli(Config::get('info'));

	// Transformation de l'url du handler wikini en url pour le serveur REST
	// TODO : améliorer la gestion de l'url entre le wikini et le serveur REST
	//$_SERVER['REQUEST_URI'] = Config::get('serveur.baseURL').$_GET['api'];
	//$_SERVER['QUERY_STRING'] = $_GET['params'] ? $_GET['params'] : '';

	// Création de l'objet Wiki qui sera transmis au service via le Registre
	Registre::set('cheminApi', getcwd());
	Registre::set('cheminWiki', realpath(dirname(__FILE__).DS.'..'.DS.'..'.DS).DS);
	$wikiApi = new WikiApi(Registre::get('cheminWiki'), Registre::get('cheminApi'));
	Registre::set('wikiApi', $wikiApi);

	// Initialisation et lancement du serveur
	$Serveur = new RestServeur();
	$Serveur->executer();

	// Affiche le temps d'execution du service
	if (isset($_GET['chrono']) && $_GET['chrono'] == 1) {
		$temps_fin = microtime(true);
		echo 'Temps d\'execution : '.round($temps_fin - $temps_debut, 4);
	}
}
?>
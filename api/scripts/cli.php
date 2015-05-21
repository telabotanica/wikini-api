<?php
// Encodage : UTF-8
// +-------------------------------------------------------------------------------------------------------------------+
/**
* Initialise le chargement et l'exécution des scripts
*
* Lancer ce fichier en ligne de commande avec :
* <code>/opt/lampp/bin/php cli.php mon_script -a test</code>
*
 * @category   CEL
 * @package    Scripts
 * @author     Mathias CHOUET <mathias@tela-botanica.org>
 * @author     Jean-Pascal MILCENT <jpm@tela-botanica.org>
 * @author     Aurelien PERONNET <aurelien@tela-botanica.org>
 * @license    GPL v3 <http://www.gnu.org/licenses/gpl.txt>
 * @license    CECILL v2 <http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt>
 * @copyright  1999-2014 Tela Botanica <accueil@tela-botanica.org>
 */

// Le fichier Framework.php du Framework de Tela Botanica doit être appelée avant tout autre chose dans l'application.
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
	
	// Création de l'objet Wiki qui sera transmis au service via le Registre
	Registre::set('cheminApi', getcwd());
	Registre::set('cheminWiki', realpath(dirname(__FILE__).DS.'..'.DS.'..'.DS).DS);
	//require_once(getcwd().DS.'bibliotheque'.DS.'WikiApi.php');
	$wikiApi = new WikiApi(Registre::get('cheminWiki'), Registre::get('cheminApi'));
	Registre::set('wikiApi', $wikiApi);

	// Initialisation et lancement du script appelé en ligne de commande
	Cli::executer();
}
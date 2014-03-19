<?php
/*
usersdeleting.php

Copyright 2002 Patrick PAUL
Copyright 2003 David DELON
* Copyright 2013 Jean-Pascal MILCENT
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Data managment
$origineLink = $this->Href();
$messages = array();
if ($this->UserIsAdmin() === false) {
	$msg = "Il est nécessaire d'être administrateur de ce wikini pour accéder à la gestion des utilisateurs.";
	$messages[] = array('type' => 'warning', 'txt' => $msg); 
} else {
	$msg_accueil = "Zone de sécurité renforcé : identifiez vous avec l'identifiant et le mot de passe de connexion à la base de données de ce Wikini";
	$msg_echec = "Accès limité aux administrateurs de ce Wikini ayant accès aux paramètres d'installation (identifiant et mot de passe de la base de données).\n".
			"Votre tentative d'identification a échoué.\n".
			"Actualiser la page pour essayer à nouveau si vous avez en votre possession les paramètres nécessaires.";
	$wikiAuthHttp = new WikiAuthHttp($msg_accueil, $msg_echec, $this->config['mysql_user'], $this->config['mysql_password']);
	$wikiAuthHttp->authentifier();
	
	$prefix = $this->config['table_prefix'];
	
	// Data update, insert or delete
	if (isset($_POST['action'])) {
		if ($_POST['action'] == 'deleteUser') {
			if (isset($_POST['users']) === false) {
				$messages[] = array('type' => 'error', 'txt' => 'Veuillez sélectionner au moins un utilisateur.');
			} else {
				$usersDeleted = array();
				$usersEscaped = array();
				foreach ($_POST['users'] as $user_name) {
					$usersDeleted[] = $user_name;
					$usersEscaped[] = "'".mysql_real_escape_string($user_name)."'";
				}
				$sqlUsersToDelete = implode(',', $usersEscaped);
			}
			$queryDeleteUser = "DELETE FROM {$prefix}users ".
				"WHERE name IN ($sqlUsersToDelete) ";
			$this->Query($queryDeleteUser);
			
			$usersDeleted = implode(', ', $usersDeleted);
			if (empty($usersDeleted) == false) {
				$msg = "Les utilisateurs suivant ont été supprimés : $usersDeleted.";
				$messages[] = array('type' => 'success', 'txt' => $msg); 
			}
		} else if ($_POST['action'] == 'deletePage') {
			$deletedPages = array();
			if (!empty($_POST['suppr'])) {
				foreach ($_POST['suppr'] as $page) {
					// Effacement de la page en utilisant la méthode DeleteOrphanedPage
					$this->DeleteOrphanedPage($page);
					$deletedPages[] = $page;
				}
				
				$deletedPages = implode(', ', $deletedPages);
				if (empty($deletedPages) == false) {
					$msg = "Les pages suivantes ont été supprimées : $deletedPages.";
					$messages[] = array('type' => 'success', 'txt' => $msg);
				}
			}
			
			$restoredPages = array();
			if (!empty($_POST['rev'])) {
				foreach ($_POST['rev'] as $rev_id) {
					// Sélectionne la révision
					$id = "'".mysql_real_escape_string($rev_id)."'";
					$query = 'SELECT * '.
						"FROM {$prefix}pages ".
						"WHERE id = $id ".
						'LIMIT 1';
					$revision = $this->LoadSingle($query); 

					// Fait de la dernière version de cette révision une version archivée
					$tag = "'".mysql_real_escape_string($revision['tag'])."'";
					$queryUpdate =
						"UPDATE {$prefix}pages " .
						"SET latest = 'N' ".
						"WHERE latest = 'Y' " .
						"AND tag = $tag " .
						"LIMIT 1";
					$this->Query($queryUpdate);
					$restoredPages[] = $revision['tag'];
					
					// add new revision
					$owner = "'".mysql_real_escape_string($revision['owner'] )."'";
					$user = "'".mysql_real_escape_string('WikiAdmin')."'";
					$body = "'".mysql_real_escape_string(chop($revision['body']))."'";
					$queryInsert = "INSERT INTO {$prefix}pages SET ".
						"tag = $tag, ".
						"time = NOW(), ".
						"owner = $owner, ".
						"user = $user, ".
						"latest = 'Y', ".
						"body = $body";
					$this->Query($queryInsert);
				}
			}
			
			$restoredPages = implode(', ', $restoredPages);
			if (empty($restoredPages) == false) {
				$msg = "Les pages suivantes ont été restaurées à une version antérieure: $restoredPages.";
				$messages[] = array('type' => 'success', 'txt' => $msg);
			}
		}
	}
	
	// Data loading
	$queryAllUsers = 'SELECT name, email, motto, revisioncount, changescount, doubleclickedit, signuptime, show_comments '.
		"FROM {$prefix}users ".
		'ORDER BY signuptime DESC';
	$users = $this->LoadAll($queryAllUsers);
	$users_infos = array();
	foreach($users as $user) {
		$user_name = mysql_real_escape_string($user['name']);
		$result = $this->LoadSingle("SELECT COUNT(*) AS pages FROM {$prefix}pages WHERE user = '$user_name'");
		$user['pages_user'] = $result['pages'];
		
		$result = $this->LoadSingle("SELECT COUNT(*) AS pages FROM {$prefix}pages WHERE latest = 'Y' AND owner = '$user_name'");
		$user['pages_owner'] = $result['pages'];
		
		$user['has_pages'] = ($user['pages_user'] != 0 || $user['pages_owner'] != 0) ? true : false;
		
		$user['pages_link'] = $this->Href('', '', "action=seeUserPage&user={$user['name']}");
		
		$users_infos[] = $user;
	}
	if (count($users_infos) == 0) {
		$msg = "Ce Wikini ne possède pas d'utilisateur";
		$messages[] = array('type' => 'info', 'txt' => $msg); 
	}
	
	if (isset($_GET['action'])) {
		if ($_GET['action'] == 'seeUserPage' && $_GET['user'] != '') {
			$actionLink = $this->Href('', '', "action=seeUserPage&user={$_GET['user']}");
			
			$userPages['name'] = $_GET['user'];
			$userName = "'".mysql_real_escape_string($_GET['user'])."'";
			$queryAllUserPages = "SELECT tag ".
				"FROM {$prefix}pages ".
				"WHERE owner = $userName OR ".
				" user = $userName ".
				"ORDER BY time DESC ";
			$pages = $this->LoadAll($queryAllUserPages);
			if (count($pages) == 0) {
				$msg = "Cet utilisateur ne possède plus aucune page.";
				$messages[] = array('type' => 'error', 'txt' => $msg); 
			} else { 
				foreach ($pages as $page) {
					if (! isset($userPages['pages'][$page['tag']])) {
						$pageTag = "'".mysql_real_escape_string($page['tag'])."'";
						$infos = $this->LoadSingle("SELECT * FROM {$prefix}pages WHERE tag = $pageTag AND latest = 'Y'");
						$infos['link'] = $this->Href('', $page['tag']);
						$infos['revisions'] = $this->LoadAll("SELECT * FROM {$prefix}pages WHERE tag = $pageTag ORDER BY time DESC"); 
						$userPages['pages'][$page['tag']] = $infos;
					}
				}
			}
		}
	}
}

// Template HTML
$html = '';
if ($_GET['action'] == 'seeUserPage') {
	$html .= '<ul class="breadcrumb">
			<li>Retour&nbsp;:&nbsp;</li>
			<li><a href="'.$origineLink.'">Gestion des utilisateurs</a> <span class="divider">&gt;</span></li>
			<li class="active">Pages utilisateur '.$userPages['name'].'</li>
		</ul>';
}
$html .= '<h2>Gestion des utilisateurs</h2>';

if (count($messages) != 0) {
	foreach ($messages as $msg) {
		$html .= '<p class="alert alert-'.$msg['type'].'">';
		$html .= $msg['txt'].'<br />';
		$html .= '</p>';
	}
}
if ($this->UserIsAdmin()) {
	if ($_GET['action'] == 'seeUserPage' && isset($userPages)) {
		if (count($userPages['pages']) > 0) {
			$html .= '<h3 id="user-pages-modal-label">'."Pages de l'utilisateur {$userPages['name']}".'</h3>'.
				'<p class="alert alert-info">Sélectionnez une ou plusieurs pages à supprimer et/ou versions à restaurer puis cliquer sur le bouton en bas de page.</p>';
		
			$html .= '<form action="'.$actionLink.'" method="post">';
			$html .= '<table class="table table-bordered">
				<thead>
					<tr>
						<th>[supprimer] Page</th>
						<th>Propriétaire</th>
						<th>[restaurer] Version du</th>
						<th>Auteur modification</th>
					</tr>
				</thead>';
			
			foreach ($userPages['pages'] as $page) {
				$html .= '<tr class="success">'.
						'<td>'.
							'<span class="has-tooltip" title="Supprimer la page '.$page['tag'].' et toutes ses versions !">'.
								'<input name="suppr[]" value="'.$page['tag'].'" type="checkbox"/>'.
							'</span> '.
							'<a href="'.$page['link'].'">'.
								$page['tag'].
							'</a>'.
						'</td>'.
						'<td>'.$page['owner'].'</td>'.
						'<td>'.$page['time'].'</td>'.
						'<td>'.$page['user'].'</td>'.
					'</tr>';
				$revisionsNbre = count($page['revisions']);
				if ($revisionsNbre != 0) {
					for ($i = 1; $i < $revisionsNbre; $i++) {
						$revision = $page['revisions'][$i];
						$html .= '<tr>'.
								'<td>&nbsp;</td>'.
								'<td>'.$revision['owner'].'</td>'.
								'<td>'.
									'<span class="has-tooltip"  title="Restaurer la version du '.$revision['time'].' de la page '.$page["tag"].'">'.
										'<input name="rev[]" value="'.$revision['id'].'" type="checkbox"/> '.
									'</span>'.
									$revision['time'].
								'</td>'.
								'<td>'.$revision['user'].'</td>'.
							'</tr>';
					}
				}
			}
			$html .= '</table>
					<div class="form-actions">
						<button class="btn btn-danger has-tooltip" type="submit" name="action" value="deletePage" title="Supprime les pages sélectionnés ou restaure des anciennes versions">
							Supprimer des pages et/ou restaurer des versions
						</button>
					</div>
				</form>';
		}
	} else {
		$html .= '<form action="'.$origineLink.'" method="post">';
		
		$html .= '<p class="alert alert-info">Sélectionnez un ou plusieurs utilisateurs à supprimer puis cliquer sur le bouton supprimer en bas de page.</p>
		<table class="table table-striped table-hover table-bordered table-condensed">
			<thead>
				<tr>
					<th>&nbsp;</th>
					<th>Nom & Devise</th>
					<th>Courriel</th>
					<th>Inscription</th>
					<th>
						<span class="has-tooltip" title="Nombre de pages (hors archive) dont l\'utilisateur est le propriétaire.">Pages proprio.</span>
						 / 
						<span class="has-tooltip" title="Nombre de pages toutes versions confondues modifiées par l\'utilisateur.">Modif.</span>
					</th>
					<th>
						<span class="has-tooltip" title="Nombre de révisions visible par l\'utilisateur">révisions</span> / 
						<span class="has-tooltip" title="Nombre de changements visible par l\'utilisateur">changements</span> /
						<span class="has-tooltip" title="L\'utilisateur peut éditer les page en réalisant un double clic.">clic</span> / 
						<span class="has-tooltip" title="L\'utilisateur veut voir les commentaires sur la page.">commentaire</span>
					</th>
				</tr>
			</thead>
			<tbody>';
		if (isset($users_infos)) {
			foreach ($users_infos as $user) {
				$html .= '<tr>
						<td><input type="checkbox" name="users[]" value="'.$user['name'].'"/></td>
						<td>'.
							$user['name'].
							(empty($user['motto']) ? '' : '<br /><em><cite>'.$user['motto'].'</cite></em>').
						'</td>
						<td><a href="mailto:'.$user['email'].'">'.$user['email'].'</a></td>
						<td>'.$user['signuptime'].'</td>
						<td>'.
							($user['has_pages'] ? '<a href="'.$user['pages_link'].'">' : '').
								$user['pages_owner'].' / '.$user['pages_user'].
							($user['has_pages'] ? '</a>' : '').
						'</td>
						<td>'.$user['revisioncount'].' / '.$user['changescount'].' / '.$user['doubleclickedit'].' / '.$user['show_comments'].'</td>
					</tr>';
			}
		}
		$html .= '</tbody>
				</table>
				<div class="form-actions">
					<button class="btn btn-danger has-tooltip" type="submit" name="action" value="deleteUser" title="Supprime les utilisateurs sélectionnés de la base de données">
						Supprimer des utilisateurs
					</button>
				</div>
			</form>';
	}
}

// Sortie
echo isset($this->config['encoding']) ? mb_convert_encoding($html, $this->config['encoding'], 'iso-8859-15') : $html;

// Functions & class
class WikiAuthHttp {
	private $message_accueil;
	private $message_echec;
	private $identifiant;
	private $mot_de_passe;
	
	public function __construct($message_accueil, $message_echec, $identifiant, $mot_de_passe) {
		$this->message_accueil = $message_accueil;
		$this->message_echec = $message_echec;
		$this->identifiant = $identifiant;
		$this->mot_de_passe = $mot_de_passe;
	}
	
	public function authentifier() {
		$authentifie = $this->etreAutorise();
		if ($authentifie === false) {
			$this->envoyerAuth();
		}
		return $authentifie;
	}

	private function envoyerAuth() {
		header('HTTP/1.0 401 Unauthorized');
		header('WWW-Authenticate: Basic realm="'.$this->message_accueil.'"');
		header('Content-type: text/plain; charset=ISO-8859-15');
		print $this->message_echec;
		exit(0);
	}

	private function etreAutorise() {
		$identifiant = $this->getAuthIdentifiant();
		$mdp = $this->getAuthMotDePasse();
		$autorisation = ($identifiant == $this->identifiant && $mdp == $this->mot_de_passe) ? true : false;
		return $autorisation;
	}

	private function getAuthIdentifiant() {
		$id = (isset($_SERVER['PHP_AUTH_USER'])) ? $_SERVER['PHP_AUTH_USER'] : null;
		return $id;
	}

	private function getAuthMotDePasse() {
		$mdp = (isset($_SERVER['PHP_AUTH_PW'])) ? $_SERVER['PHP_AUTH_PW'] : null;
		return $mdp;
	}
}
?>

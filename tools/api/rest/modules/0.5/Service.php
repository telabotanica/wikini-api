<?php
abstract class Service extends RestService {
	
	//+----------------------------------------------------------------------------------------------------------------+
	// GESTION de l'ENVOIE au NAVIGATEUR

	protected function formaterEnJsonp($donnees = null, $encodage = 'utf-8') {
		$contenu = $_GET['callback'].'('.json_encode($donnees).');';
		return $this->preparerEnvoie($contenu, 'text/html', $encodage);
	}
	
	protected function formaterEnJson($donnees = null, $encodage = 'utf-8') {
		$contenu = json_encode($donnees);
		return $this->preparerEnvoie($contenu, 'application/json', $encodage);
	}
	
	private function preparerEnvoie($sortie = 'OK', $mime = 'text/html', $encodage = 'utf-8') {
		$this->envoyerEnteteContenu($encodage, $mime);
		return $sortie;
	}

	private function envoyerEnteteContenu($encodage, $mime) {
		if (!is_null($mime) && !is_null($encodage)) {
			header("Content-Type: $mime; charset=$encodage");
		} else if (!is_null($mime) && is_null($encodage)) {
			header("Content-Type: $mime");
		}
	}
		
	protected function envoyerAuth($message_accueil, $message_echec) {
		header('HTTP/1.0 401 Unauthorized');
		header('WWW-Authenticate: Basic realm="'.mb_convert_encoding($message_accueil, 'ISO-8859-1', 'UTF-8').'"');
		header('Content-type: text/plain; charset=UTF-8');
		print $message_echec;
		exit(0);
	}
}
?>
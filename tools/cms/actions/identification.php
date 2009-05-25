<?php
if ($_REQUEST["action"] == "logout") {
	echo 'ici';
	$this->LogoutUser();
	$this->SetMessage("Vous êtes maintenant déconnecté !");
	$this->Redirect($this->href());
} else if ($user = $this->GetUser()) {
	
	// is user trying to update?
	if ($_REQUEST["action"] == "update")
	{
		$this->Query("update ".$this->config["table_prefix"]."users set ".
			"email = '".mysql_escape_string($_POST["email"])."', ".
			"doubleclickedit = '".mysql_escape_string($_POST["doubleclickedit"])."', ".
			"show_comments = '".mysql_escape_string($_POST["show_comments"])."', ".
			"revisioncount = '".mysql_escape_string($_POST["revisioncount"])."', ".
			"changescount = '".mysql_escape_string($_POST["changescount"])."', ".
			"motto = '".mysql_escape_string($_POST["motto"])."' ".
			"where name = '".$user["name"]."' limit 1");
		
		$this->SetUser($this->LoadUser($user["name"]));
		
		// forward
		$this->SetMessage("Paramètres sauvegardés !");
		$this->Redirect($this->href());
	}
	
	if ($_REQUEST["action"] == "changepass")
	{
			// check password
			$password = $_POST["password"];			
                        if (preg_match("/ /", $password)) $error = "Les espaces ne sont pas permis dans les mots de passe.";
			else if (strlen($password) < 5) $error = "Password too short.";
			else if ($user["password"] != md5($_POST["oldpass"])) $error = "Mauvais mot de passe."; 
			else  
			{
				$this->Query("update ".$this->config["table_prefix"]."users set "."password = md5('".mysql_escape_string($password)."') "."where name = '".$user["name"]."'");				
				$this->SetMessage("Mot de passe changé !");
				$user["password"]=md5($password);
				$this->SetUser($user);
				$this->Redirect($this->href());
			}
	}
	// user is logged in; display config form
	print($this->FormOpen());
	?>
	<input type="hidden" name="action" value="update" />
	<table>
		<tr>
			<td align="right"></td>
			<td>Hello, <?php echo  $this->Link($user["name"]) ?>!</td>
		</tr>
		<tr>
			<td align="right">Votre adresse e-mail :</td>
			<td><input name="email" value="<?php echo  htmlentities($user["email"]) ?>" size="40" /></td>
		</tr>
		<tr>
			<td align="right">Edition en Doublecliquant :</td>
			<td><input type="hidden" name="doubleclickedit" value="N" /><input type="checkbox" name="doubleclickedit" value="Y" <?php echo  $user["doubleclickedit"] == "Y" ? "checked=\"checked\"" : "" ?> /></td>
		</tr>
		<tr>
			<td align="right">Montrer les commentaires par default :</td>
			<td><input type="hidden" name="show_comments" value="N" /><input type="checkbox" name="show_comments" value="Y" <?php echo  $user["show_comments"] == "Y" ? "checked\"checked\"" : "" ?> /></td>
		</tr>
		<tr>
			<td align="right">Nombre maximum de derniers commentaires :</td>
			<td><input name="changescount" value="<?php echo  htmlentities($user["changescount"]) ?>" size="40" /></td>
		</tr>
		<tr>
			<td align="right">Nombre maximum de versions :</td>
			<td><input name="revisioncount" value="<?php echo  htmlentities($user["revisioncount"]) ?>" size="40" /></td>
		</tr>
		<tr>
			<td align="right">Votre devise :</td>
			<td><input name="motto" value="<?php echo  htmlentities($user["motto"]) ?>" size="40" /></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" value="Mise à jour" /> <input type="button" value="Déconnection" onClick="document.location='<?php echo  $this->href("", "", "action=logout"); ?>'" /></td>
		</tr>

	<?php
	print($this->FormClose());

	print($this->FormOpen());
	?>
	<input type="hidden" name="action" value="changepass" />

		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right"></td>
			<td><?php echo  $this->Format("Changement de mot de passe"); ?></td>
		</tr>
		<?php
		if ($error)
		{
			print("<tr><td></td><td><div class=\"error\">".$this->Format($error)."</div></td></tr>\n");
		}
		?>
		<tr>
			<td align="right">Votre ancien mot de passe :</td>
			<td><input type="password" name="oldpass" size="40" /></td>
		</tr>
		<tr>
			<td align="right">Nouveau mot de passe :</td>
			<td><input type="password" name="password" size="40" /></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" value="Changer" size="40" /></td>
		</tr>
	</table>
	<?php
	print($this->FormClose());

} else {
	// user is not logged in
	
	// is user trying to log in or register?
	if ($_REQUEST["action"] == "login")
	{
		// if user name already exists, check password
		if ($existingUser = $this->LoadUser($_POST["name"]))
		{
			// check password
			if ($existingUser["password"] == md5($_POST["password"]))
			{
				$this->SetUser($existingUser, $_POST["remember"]);
				$this->Redirect($this->href());
			}
			else
			{
				$error = "Mauvais mot de passe !";
			}
		}
		// otherwise, create new account
		else
		{
			$name = trim($_POST["name"]);
			$email = trim($_POST["email"]);
			$password = $_POST["password"];
			$confpassword = $_POST["confpassword"];

			// check if name is WikkiName style
			if (!$this->IsWikiName($name)) $error = "Votre nom d'utilisateur dois être formaté en NomWiki.";
			else if (!$email) $error = "Vous devez spécifier une adresse e-mail.";
			else if (!preg_match("/^.+?\@.+?\..+$/", $email)) $error = "Ceci ne ressemble pas à une adresse e-mail.";
			else if ($confpassword != $password) $error = "Les mots de passe n'étaient pas identiques";
			else if (preg_match("/ /", $password)) $error = "Les espaces ne sont pas permis dans un mot de passe.";
			else if (strlen($password) < 5) $error = "Mot de passe trop court. Un mot de passe doit contenir au minimum 5 caractères alphanumériques.";
			else
			{
				$this->Query("insert into ".$this->config["table_prefix"]."users set ".
					"signuptime = now(), ".
					"name = '".mysql_escape_string($name)."', ".
					"email = '".mysql_escape_string($email)."', ".
					"password = md5('".mysql_escape_string($_POST["password"])."')");

				// log in
				$this->SetUser($this->LoadUser($name));

				// forward
				$this->Redirect($this->href());
			}
		}
	}
	
	print($this->FormOpen());
	?>
	<input type="hidden" name="action" value="login" />
	<table>
		<tr>
			<td align="right"></td>
			<td><?php echo  $this->Format("Si vous êtes déjà enregistré, identifiez-vous ici"); ?></td>
		</tr>
		<?php
		if ($error)
		{
			print("<tr><td></td><td><div class=\"error\">".$this->Format($error)."</div></td></tr>\n");
		}
		?>
		<tr>
			<td align="right">Votre NomWiki :</td>
			<td><input name="name" size="40" value="<?php echo  $name ?>" /></td>
		</tr>
		<tr>
			<td align="right">Mot de passe (5 caractères minimum) :</td>
			<td><input type="password" name="password" size="40" />
			    <input type="hidden" name="remember" value="0" /><input type="checkbox" name="remember" value="1" /> <?php echo $this->Format("Se souvenir de moi.") ?> </td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" value="Identification" size="40" /></td>
		</tr>
	</table>
	<?php
	print($this->FormClose());
}
?>


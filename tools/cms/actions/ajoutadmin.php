<?php

// is user trying to log in or register?
if ($_REQUEST["action"] == "ajout") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confpassword = $_POST["confpassword"];
    
    // check if name is WikkiName style
    if (! $this->IsWikiName($name) ) $error = "Votre nom d'utilisateur dois être formaté en NomWiki.";
    else if (!$email) $error = "Vous devez spécifier une adresse e-mail.";
    else if (!preg_match("/^.+?\@.+?\..+$/", $email)) $error = "Ceci ne ressemble pas à une adresse e-mail.";
    else if ($confpassword != $password) $error = "Les mots de passe n'étaient pas identiques";
    else if (preg_match("/ /", $password)) $error = "Les espaces ne sont pas permis dans un mot de passe.";
    else if (strlen($password) < 5) $error = "Mot de passe trop court. Un mot de passe doit contenir au minimum 5 caractères alphanumériques.";
    else {
        $reussite = "L'administrateur a bien été ajouté !";
        $this->Query(   "insert into ".$this->config["table_prefix"]."users set ".
                        "signuptime = now(), ".
                        "name = '".mysql_escape_string($name)."', ".
                        "email = '".mysql_escape_string($email)."', ".
                        "password = md5('".mysql_escape_string($_POST["password"])."')");
        // log in
        //$this->SetUser($this->LoadUser($name));
        // forward
        //$this->Redirect($this->href());
    }
}

print($this->FormOpen());
?>
<input type="hidden" name="action" value="ajout" />
  <table>
    <tr>
      <td align="left" colspan="2"><?php echo  $this->Format("Pour ajouter un administrateur renseigner les champs ci-dessous:"); ?></td>
    </tr>
      <?php
        if ($error) {
            print('<tr><td></td><td><div class="error">'.$this->Format($error)."</div></td></tr>\n");
		}
        if ($reussite) {
            echo('<tr><td></td><td><div class="reussite">'.$this->Format($reussite)."</div></td></tr>\n");
        }
      ?>
    <tr>
      <td align="right">Votre NomWiki :</td>
      <td><input name="name" size="40" value="<?php echo  $name ?>" /></td>
    </tr>
    <tr>
      <td align="right">Mot de passe (5 caractères minimum) :</td>
      <td><input type="password" name="password" size="40" /></td>
    </tr>
    <tr>
      <td align="right">Confirmation du mot de passe :</td>
      <td><input type="password" name="confpassword" size="40" /></td>
    </tr>
    <tr>
      <td align="right">Adresse e-mail :</td>
      <td><input name="email" size="40" value="<?php echo  $email ?>" /></td>
    </tr>
    <tr>
      <td></td>
      <td><input type="submit" value="Nouveau compte" size="40" /></td>
    </tr>
</table>
<?php
print($this->FormClose());
?>


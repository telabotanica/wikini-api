<?php
/*
$Id: deletepage.php 858 2007-11-22 00:46:30Z nepote $
Copyright 2002  David DELON
Copyright 2003  Eric FELDSTEIN
Copyright 2004  Jean Christophe ANDRÉ
Copyright 2006  Didier Loiseau
Copyright 2007  Charles NÉPOTE
Copyright 2013  Jean-Pascal MILCENT
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

// Vérification de sécurité
if (!defined('WIKINI_VERSION'))
{
	die ('Accès direct interdit');
}
print_r($_POST);
if ($this->UserIsOwner() || $this->UserIsAdmin())
{
	$deletePage = false;
	$tag = $this->GetPageTag();
	if ($this->IsOrphanedPage($this->GetPageTag()))
	{
		if ($_POST['confirme'] == 'Oui') {
			$deletePage = true;
		}
		else
		{
			$pageLink = $this->Link($tag);
			$actionLink = $this->Href('deletepage');
			$cancelLink = $this->Href();

			$msg .= '<form action="'.$actionLink.'" method="post" style="display: inline">'."\n".
				'<p class="alert alert-warning">Voulez-vous supprimer définitivement la page '.$pageLink.' &nbsp;?<br/>'."\n".
				'<button class="btn btn-danger" name="confirme" value="Oui" type="submit">Oui</button>'."\n".
				'<a class="btn" href="'.$cancelLink.'">Non</a>'."\n".
				'</p>'."\n".
				'</form>'."\n";
		}
	}
	else
	{
		if ($_POST['confirme'] == 'Oui') {
			$deletePage = true;
		}
		else
		{
			$pageTag = $this->GetPageTag();
			$tablePrefix = $this->config["table_prefix"];
			$query = "SELECT DISTINCT from_tag ".
				"FROM {$tablePrefix}links ".
				"WHERE to_tag = '$pageTag'";
			$linkedFrom = $this->LoadAll($query);
			$pageLink = $this->ComposeLinkToPage($this->tag, "", "", 0);

			$msg = '<p><em>Cette page n\'est pas orpheline.</em></p>'."\n".
				'<p>Pages ayant un lien vers '.$pageLink.' :</p>'."\n".
				'<ul>'."\n";
			foreach ($linkedFrom as $page)
			{
				$currentPageLink = $this->ComposeLinkToPage($page['from_tag'], '', '', 0);
				$msg .= '<li>'.$currentPageLink.'</li>'."\n";
			}
			$msg .= '</ul>'."\n";

			if ($this->UserIsAdmin()) {
				$actionLink = $this->Href('deletepage');
				$cancelLink = $this->Href();
				$msg .= '<form action="'.$actionLink.'" method="post" style="display: inline">'."\n".
					'<p class="alert alert-warning">En tant qu\'administrateur, vous pouvez supprimer malgré tout la page.<br/>'.
					'Cela laissera des liens de type "page à créer" dans les pages listées ci-dessus.<br/>'.
					'Voulez-vous supprimer définitivement la page '.$pageLink.' &nbsp;?<br/>'."\n".
					'<button class="btn btn-danger" name="confirme" value="Oui" type="submit">Oui</button>'."\n".
					'<a class="btn" href="'.$cancelLink.'">Non</a>'."\n".
					'</p>'."\n".
					'</form>'."\n";
			}
		}
	}

	if ($deletePage == true) {
		$this->DeleteOrphanedPage($tag);
		$this->LogAdministrativeAction($this->GetUserName(), "Suppression de la page ->\"\"" . $tag . "\"\"");
		$msg = '<p class="alert alert-info">'."La page ${tag} a été définitivement supprimée.".'</p>';
	}
}
else
{
	$msg = '<p class="alert alert-warning"><em>'."Vous n'êtes pas le propriétaire de cette page.".'</em></p>'."\n";
}

echo $this->Header();
echo '<div class="page">'."\n";
echo $msg;
echo '</div>'."\n";
echo $this->Footer();
?>
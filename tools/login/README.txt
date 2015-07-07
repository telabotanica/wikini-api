# Utilisation du sso avec wikini

Afin de pouvoir utiliser le sso, en plus du remplacement de ce tool
il faut ajouter deux variables de configuration au wiki	:

sso_url => 'https://localhost/annuaire/jrest/auth/'
use_sso => '1'

Eventuellement si le serveur ne supporte pas la variable Authorization
dans un header, le nom du header peut être changé en ajoutant ce paramètre : 
sso_auth_header => 'Auth'
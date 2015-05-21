## Pour installer l'api wiki
1/ copiez le dossier api de ce projet dans le répertoire monwikini/tools/api de wikini (ou bien monwikini/api)

2/ configurez l'emplacement du framework en renommant le fichier framework.defaut.php en framework.php (attention, framework
version 0.3 nécessaire)

3/ renommez le fichier config.defaut.ini du dossier api/rest/configurations en config.ini et en modifiant les variables
de configuration décrites dans le fichier.

4/ copier le fichier wakka.php et renommez la copie en api.php à la racine du wiki. Prenez soin de commenter la dernière
ligne appelant la méthode run du wiki.


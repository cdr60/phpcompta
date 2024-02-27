# phpcompta
Comptabilité basique en php

Ceci est un site internet permettant la gestion comptable de particuliers ou d'une association
Les fonctionnalités comptables sont basiques :
- identification par email/mot de passe
- multi-utilisateur et multi-rôle
- Rôle adminiostrateur : donne tous les droits
- Rôle trésorier : donne tous les droits sauf la gestion des utilisateurs
- Rôle stardard : consultation uniquement ,seulement de la partie compta et personnalisation de son profil utilisateur
- Possibilité de gérer plusieurs comptes en banques
- Possibilité de saisir des transferts de compte à compte
- Gestion des recettes et dépenses par postes, et d'attribue rà chaque poste, un budget prévisionnel
- Affichage de la répartition des dépenses et des recettes par postes
- Pointage des écritures
- Affichage du solde du relevé (toutes écritures) et du dolde banque (écritures pointées seulement)
- Choix de la période de sélection
- Affichage des soldes antérieures à la période et en fin de période

# partie technique
- scripts en php (compatibles php 8.2) et en javascript
- base de données en sqlite 3 via PDO

# installation :
- copier simplement tous les fichiers dans un dossier de votre site
- assurez vous au moins que les modules php-mbstring, php-pdo et php-sqlite soient installés.

# première connexion :
- à la 1ère connexion, le compte administrateur email : Admin, mot de passe admin@admin.admin est créé,
- Créez alors immédiatement !! un autre compte administrateur, déconnectez-vous du compte Admin, connectez-vous avec votre nouveau compte et supprimez immédiatement !!! le compte Admin !!!


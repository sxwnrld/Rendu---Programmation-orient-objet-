# Compléments du projet

## Ajouts

- `Client` : identité, téléphone facultatif, email validé et affichage.
- `Location` : propriétés promues, date par défaut, getters et récapitulatif.
- `Agence` : parc, locations, contrôles de disponibilité et d'entretien, retours, chiffre d'affaires, sérialisation et sauvegarde/relecture.
- Étapes 4 à 9 dans `index.php`, avec création de l'agence dès l'étape 1.
- 26 tests complémentaires, soit 52 tests au total.
- README de lancement et affichage adapté aux petits écrans.

## Ajustements sur la base existante

- Tarifs et volumes infinis ou non numériques refusés.
- Utilisation explicite de `__CLASS__` dans le trait d'historique, tout en conservant le nom concret des véhicules.

## Vérifications effectuées

- PHP 8.1.2 : syntaxe des 16 fichiers PHP vérifiée.
- 52 tests réussis, aucun échec.
- Page et CSS servis en HTTP 200.
- Rendu des neuf étapes contrôlé sur ordinateur et mobile.
- Scénario : 3 locations, chiffre d'affaires de 417,60 € TTC.
- Sauvegarde dans `data/agence.txt` et restauration vérifiées.

## Avant de rendre

- Compléter le nom de famille de Sawwan dans le README.
- Relancer les tests sur le poste de rendu et vérifier les droits d'écriture de `data/`.
- Déposer le projet sur GitHub et vérifier l'accès du formateur. Aucun dépôt distant n'a été modifié par cette archive.

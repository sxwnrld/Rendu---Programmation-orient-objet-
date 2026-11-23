# Agence de location de véhicules

Projet PHP orienté objet réalisé en binôme.

## Auteurs

- Ruben Sitbon
- Sawwan Akrour

## Installation

PHP 8.1 ou supérieur suffit. Il n'y a ni dépendance Composer, ni base de données à installer.

Depuis le dossier du projet :

```bash
php -S localhost:8000
```

Ouvrir ensuite http://localhost:8000 dans un navigateur.

Pour lancer les tests :

```bash
php tests/run.php
```

Le résultat attendu est `52 test(s) réussi(s), 0 échec(s).` Un échec donne un code de sortie égal à 1.

## Scénario

La page `index.php` exécute et affiche les neuf étapes du sujet :

1. Création d'une agence, de deux voitures dont une de sept places, d'un utilitaire et d'un vélo électrique.
2. Affichage du compteur de véhicules.
3. Affichage des cartes du parc avec `echo $vehicule`.
4. Création de deux clients et refus d'un troisième avec un email invalide.
5. Location de la voiture sept places pour trois jours, du vélo pour huit jours et refus d'une double location.
6. Retour de la voiture avec 16 000 km et refus de location avant entretien.
7. Entretien, puis nouvelle location de la voiture pour trois jours.
8. Affichage des locations, du chiffre d'affaires et de l'historique.
9. Sauvegarde de l'agence dans `data/agence.txt`, puis relecture de contrôle.

Chaque chargement de la page rejoue une démonstration neuve et remplace cette sauvegarde. Il ne s'agit pas d'un formulaire de gestion persistant.

### Montants attendus

| Location | Calcul TTC | Montant |
| --- | --- | --- |
| Renault Espace, 3 jours | (32 + 10) × 3 × 1,20 | 151,20 € |
| Vélo électrique, 8 jours | 15 × 8 × 0,80 × 1,20 | 115,20 € |
| Renault Espace après entretien, 3 jours | (32 + 10) × 3 × 1,20 | 151,20 € |
| Total | | 417,60 € |

Les tentatives refusées ne créent aucune location et ne changent pas le chiffre d'affaires.

## Organisation

```text
.
├── index.php
├── autoload.php
├── assets/style.css
├── data/
│   └── agence.txt
├── tests/
│   ├── run.php
│   └── complement.php
└── src/
    ├── Agence.php
    ├── Clients/Client.php
    ├── Locations/Location.php
    ├── Interfaces/
    │   ├── Louable.php
    │   └── Entretenable.php
    ├── Traits/
    │   ├── HistoriqueTrait.php
    │   └── EntretienTrait.php
    ├── Utils/Format.php
    └── Vehicules/
        ├── Vehicule.php
        ├── Voiture.php
        ├── Utilitaire.php
        └── VeloElectrique.php
```

## Choix de conception

- L'autoloader associe le namespace `App\` au dossier `src/`.
- `Vehicule::louer()` est finale. Les enfants définissent seulement le calcul HT et le type de véhicule.
- Les propriétés sont privées, sauf le tarif journalier, protégé pour les calculs des enfants. Le setter de disponibilité reste protégé pour passer par les méthodes de location et de retour.
- Le trait `EntretienTrait` regroupe la logique d'entretien des voitures et utilitaires. Les seuils restent propres à chaque classe.
- `HistoriqueTrait` utilise `date()` pour l'horodatage. `__CLASS__` identifie la classe qui utilise le trait ; `static::class` permet d'afficher le type concret lorsque le trait est hérité.
- `Client` valide son email avec `filter_var()`. `Location` conserve le montant TTC facturé et une date au format `AAAA-MM-JJ`.
- L'agence refuse les immatriculations en double, les durées invalides, les véhicules loués et ceux qui nécessitent un entretien.
- `getVehiculesDisponibles()` retourne les véhicules réellement louables : libres et sans entretien en attente.
- Les retours répétés ne comptent pas deux fois les kilomètres. Les kilomètres négatifs sont refusés avant de modifier le véhicule.
- `getChiffreAffaires()` additionne toutes les locations enregistrées, y compris celles déjà rendues.
- La sérialisation conserve les relations entre clients, véhicules et locations, ainsi que l'historique. Le compteur statique compte les constructions pendant l'exécution courante ; un chargement ne rappelle pas les constructeurs.
- Les valeurs affichées en HTML sont échappées avec `Format::html()`.

## Sauvegarde

Le chemin `/data/agence.txt` du sujet est interprété comme le dossier `data` à la racine du projet, et non à la racine du système. Le chemin utilisé est `__DIR__ . '/data/agence.txt'`.

Le dossier `data/` doit être accessible en écriture par PHP. Une erreur lisible est affichée si la sauvegarde est impossible. Pour charger une sauvegarde depuis un autre script :

```php
require __DIR__ . '/autoload.php';

$agence = App\Agence::charger(__DIR__ . '/data/agence.txt');
```

Ne charger que les fichiers locaux produits par l'application. `unserialize()` n'est pas un format d'import de fichiers provenant de tiers. Une liste de classes autorisées et des contrôles de structure sont appliqués au chargement.

## Vérifications

Les tests couvrent les fondations, les prix, les seuils d'entretien, les clients, les dates, les refus de location, les retours, le chiffre d'affaires et la sauvegarde/relecture. Ils vérifient aussi les erreurs de fichier et la conservation des références après restauration.

Le projet a été exécuté avec PHP 8.1.2, sans dépendance, avec les erreurs PHP activées. Le rendu des neuf étapes a été vérifié dans Chrome en largeur ordinateur et mobile.

## Avant le rendu GitHub

- Rejouer `php tests/run.php` et ouvrir la page dans l'environnement de rendu.
- Vérifier les droits d'écriture du dossier `data/`.
- Déposer les fichiers dans le dépôt du binôme et donner l'accès au formateur `yoanncoualan`, conformément au sujet. Cette archive ne modifie aucun dépôt à distance.

Consignes : https://docs.yoanncoualan.com/php-pdo-mvc/evaluations/a2-cdi

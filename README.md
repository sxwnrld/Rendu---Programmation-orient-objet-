# Agence de location de véhicules — PHP Orienté Objet

Évaluation **PHP OO, PDO & MVC** : cœur applicatif d'une agence qui loue des
voitures, des utilitaires et des vélos électriques.

## Auteurs
| _Sawwan_ et _Ruben_|


## Prérequis

- PHP **8.1 ou supérieur** (promotion des propriétés du constructeur,
  type de retour `static`, `final` sur les classes et méthodes).
- Aucune dépendance externe : un autoloader PSR-4 maison est fourni
  ([autoload.php](autoload.php)).

## Lancer le projet

```bash
# Serveur de développement intégré à PHP
php -S localhost:8000

# puis ouvrir http://localhost:8000 dans le navigateur
```

```bash
# Tests unitaires (code de sortie 0 si tout passe)
php tests/run.php
```

## Choix de conception

- **Encapsulation** : les propriétés sont `private` sauf `tarifJournalier`
  (`protected`, utilisé par les enfants pour calculer le prix). La
  disponibilité ne peut changer que via `louer()` / `rendre()` (setter
  `protected`), et l'historique ne peut être alimenté que par l'objet
  lui-même (`ajouterHistorique()` est `protected`).
- **`louer()` est `final`** : la règle commune (TVA, arrondi, passage en
  indisponible, historique) est figée ; chaque véhicule ne personnalise que
  `calculerPrixHT()` (patron _méthode modèle_).
- **`EntretienTrait`** factorise l'implémentation de `Entretenable`, identique
  pour `Voiture` et `Utilitaire` à l'exception du seuil
  (`static::SEUIL_ENTRETIEN`).
- **Validation** : chaque setter vérifie sa donnée et lève une
  `InvalidArgumentException` explicite. Les propriétés propres aux enfants
  sont validées **avant** l'appel au constructeur parent, afin qu'un véhicule
  invalide ne soit jamais comptabilisé dans `nbVehicules`.
- **Historique** : `__CLASS__` dans un trait vaut la classe qui l'utilise
  (`Vehicule`) ; on utilise donc `static::class` pour afficher la classe
  réelle (`Voiture`, `Utilitaire`...).
- **Sécurité** : toutes les valeurs insérées dans du HTML sont échappées
  (`Format::html()`).

## Organisation du projet

```text
.
├── index.php                  # Scénario de démonstration
├── autoload.php               # Autoloader PSR-4 (App\ => src/) + fuseau horaire
├── assets/style.css           # Mise en forme des cartes et de la page
├── data/                      # Fichier de sauvegarde de l'agence
├── tests/run.php              # Tests unitaires légers (sans PHPUnit)
└── src/
    ├── Interfaces/
    │   ├── Louable.php        # louer() / rendre() / estDisponible()
    │   └── Entretenable.php   # ajouterKilometres() / besoinEntretien() / entretenir()
    ├── Traits/
    │   ├── HistoriqueTrait.php  # Journal horodaté des évènements
    │   └── EntretienTrait.php   # Implémentation commune de Entretenable
    ├── Utils/
    │   └── Format.php         # Formatage des prix et échappement HTML
    └── Vehicules/
        ├── Vehicule.php       # Classe abstraite (TVA, compteur, location)
        ├── Voiture.php        # Supplément > 5 places, entretien à 15 000 km
        ├── Utilitaire.php     # Frais de nettoyage, entretien à 10 000 km
        └── VeloElectrique.php # Classe finale, -20 % dès 7 jours
```

## Avancement

- [x] Fondations : `Louable`, `Entretenable`, `HistoriqueTrait`, `Vehicule`
- [x] Véhicules : `Voiture`, `Utilitaire`, `VeloElectrique`
- [ ] Clients et locations : `Client`, `Location`
- [ ] Agence : `Agence` (location, retour, chiffre d'affaires, sauvegarde)
- [ ] Scénario complet dans `index.php`

<?php

declare(strict_types=1);

/**
 * Scénario de démonstration de l'agence de location.
 *
 * Chaque étape du sujet est affichée dans sa propre section.
 * Lancement : php -S localhost:8000 puis http://localhost:8000
 */

require __DIR__ . '/autoload.php';

use App\Agence;
use App\Clients\Client;
use App\Utils\Format;
use App\Vehicules\Utilitaire;
use App\Vehicules\VeloElectrique;
use App\Vehicules\Vehicule;
use App\Vehicules\Voiture;

/* --------------------------------------------------------------------------
 * Fonctions d'affichage
 * -------------------------------------------------------------------------- */

/**
 * Ouvre la section d'une étape du scénario.
 */
function ouvrirEtape(int $numero, string $titre): void
{
    echo '<section class="etape">'
        . '<h2><span class="etape__numero">' . $numero . '</span>' . Format::html($titre) . '</h2>';
}

/**
 * Ferme la section ouverte par ouvrirEtape().
 */
function fermerEtape(): void
{
    echo '</section>';
}

/**
 * Affiche un message de résultat.
 *
 * @param string $type "info", "succes" ou "echec".
 */
function message(string $texte, string $type = 'info'): void
{
    echo '<p class="message message--' . Format::html($type) . '">' . Format::html($texte) . '</p>';
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agence de location</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="page">
    <header class="page__entete">
        <h1>Agence de location de véhicules</h1>
        <p>Scénario de démonstration — PHP orienté objet</p>
    </header>

<?php

/* --------------------------------------------------------------------------
 * Étape 1 : création du parc
 * -------------------------------------------------------------------------- */

ouvrirEtape(1, 'Création de l’agence et des véhicules');

$agence = new Agence('Agence du centre');
message('Agence créée : ' . $agence->getNom(), 'succes');

$clio = new Voiture('AB-123-CD', 'Renault', 'Clio', 35.0);
$espace = new Voiture('EF-456-GH', 'Renault', 'Espace', 32.0, 7);
$master = new Utilitaire('IJ-789-KL', 'Renault', 'Master', 60.0, 12.0);
$velo = new VeloElectrique('MN-012-OP', 'Moustache', 'Samedi 28', 15.0, 80);

/** @var list<Vehicule> $parc */
$parc = [$clio, $espace, $master, $velo];

foreach ($parc as $vehicule) {
    $agence->ajouterVehicule($vehicule);
    message(sprintf(
        '%s créé(e) : %s %s (%s)',
        $vehicule->getType(),
        $vehicule->getMarque(),
        $vehicule->getModele(),
        $vehicule->getImmatriculation()
    ), 'succes');
}

fermerEtape();

/* --------------------------------------------------------------------------
 * Étape 2 : nombre de véhicules créés (propriété statique)
 * -------------------------------------------------------------------------- */

ouvrirEtape(2, 'Nombre de véhicules créés');
echo '<p><span class="chiffre-cle">' . Vehicule::getNbVehicules() . '</span> véhicules créés.</p>';
fermerEtape();

/* --------------------------------------------------------------------------
 * Étape 3 : affichage du parc (un simple echo sur chaque véhicule)
 * -------------------------------------------------------------------------- */

ouvrirEtape(3, 'Parc de véhicules');
echo '<div class="parc">';

foreach ($parc as $vehicule) {
    echo $vehicule;
}

echo '</div>';
fermerEtape();

/* --------------------------------------------------------------------------
 * Étape 4 : clients et validation de l’email
 * -------------------------------------------------------------------------- */

ouvrirEtape(4, 'Création des clients');
$jean = new Client('Dupont', 'Jean', 'jean.dupont@example.com', '0601020304');
$marie = new Client('Martin', 'Marie', 'marie.martin@example.com');
echo '<p>' . $jean . '</p><p>' . $marie . '</p>';
try {
    new Client('Durand', 'Paul', 'email-invalide');
    message('Le client invalide a été accepté à tort.', 'echec');
} catch (InvalidArgumentException $e) {
    message('Email invalide refusé : ' . $e->getMessage(), 'succes');
}
fermerEtape();

/* --------------------------------------------------------------------------
 * Étape 5 : locations et refus d’une double location
 * -------------------------------------------------------------------------- */

ouvrirEtape(5, 'Locations et contrôle de disponibilité');
$locationVoiture = $agence->louerVehicule($jean, 'EF-456-GH', 3);
$locationVelo = $agence->louerVehicule($marie, 'MN-012-OP', 8);
if ($locationVoiture !== null) {
    echo '<p class="message message--succes">' . $locationVoiture . '</p>';
    message('7 places : (32 + 10) × 3 × 1,20 = 151.20 € TTC.');
}
if ($locationVelo !== null) {
    echo '<p class="message message--succes">' . $locationVelo . '</p>';
    message('Vélo : 15 × 8 × 0,80 × 1,20 = 115.20 € TTC (réduction de 20 %).');
}
$doubleLocation = $agence->louerVehicule($marie, 'EF-456-GH', 2);
message(
    $doubleLocation === null ? 'Deuxième location refusée : la voiture est déjà louée.' : 'Erreur : double location acceptée.',
    $doubleLocation === null ? 'succes' : 'echec'
);
fermerEtape();

ouvrirEtape(6, 'Retour après 16 000 km et entretien nécessaire');
$agence->rendreVehicule('EF-456-GH', 16000);
message('Voiture rendue : ' . Format::entier($espace->getKmDepuisEntretien()) . ' km depuis le dernier entretien.');
$locationAvantEntretien = $agence->louerVehicule($jean, 'EF-456-GH', 3);
message(
    $locationAvantEntretien === null ? 'Location refusée : le seuil d’entretien de 15 000 km est dépassé.' : 'Erreur : entretien ignoré.',
    $locationAvantEntretien === null ? 'succes' : 'echec'
);
fermerEtape();

ouvrirEtape(7, 'Entretien et nouvelle location');
$espace->entretenir();
message('Entretien effectué, compteur remis à ' . $espace->getKmDepuisEntretien() . ' km.', 'succes');
$nouvelleLocation = $agence->louerVehicule($jean, 'EF-456-GH', 3);
if ($nouvelleLocation !== null) {
    echo '<p class="message message--succes">Nouvelle location acceptée : ' . $nouvelleLocation . '</p>';
} else {
    message('Erreur : la voiture reste impossible à louer après entretien.', 'echec');
}
fermerEtape();

ouvrirEtape(8, 'Locations, chiffre d’affaires et historique');
echo '<h3>Locations enregistrées</h3><ul class="locations">';
foreach ($agence->getLocations() as $location) {
    echo '<li>' . $location . ' <span class="date-location">(' . Format::html($location->getDateDebut()) . ')</span></li>';
}
echo '</ul><p>Chiffre d’affaires : <strong class="chiffre-cle">'
    . Format::prix($agence->getChiffreAffaires()) . '</strong> TTC</p>';
echo '<h3>Historique de la Renault Espace</h3>' . $espace->afficherHistorique();
message(count($agence->getVehiculesDisponibles()) . ' véhicules disponibles à la location en fin de scénario.');
fermerEtape();

ouvrirEtape(9, 'Sauvegarde et vérification de la restauration');
try {
    $fichier = __DIR__ . '/data/agence.txt';
    $agence->sauvegarder($fichier);
    message('Agence sauvegardée dans data/agence.txt.', 'succes');
    $agenceRechargee = Agence::charger($fichier);
    message(sprintf(
        'Relecture réussie : %s, %d véhicules, %d locations, chiffre d’affaires de %s TTC.',
        $agenceRechargee->getNom(),
        count($agenceRechargee->getVehicules()),
        count($agenceRechargee->getLocations()),
        Format::prix($agenceRechargee->getChiffreAffaires())
    ), 'succes');
} catch (RuntimeException $e) {
    message($e->getMessage() . ' Vérifiez les droits d’écriture du dossier data.', 'echec');
}
fermerEtape();
?>
</main>
</body>
</html>

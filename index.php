<?php

declare(strict_types=1);

/**
 * Scénario de démonstration de l'agence de location.
 *
 * Chaque étape du sujet est affichée dans sa propre section.
 * Lancement : php -S localhost:8000 puis http://localhost:8000
 */

require __DIR__ . '/autoload.php';

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

ouvrirEtape(1, 'Création des véhicules');

$clio = new Voiture('AB-123-CD', 'Renault', 'Clio', 35.0);
$espace = new Voiture('EF-456-GH', 'Renault', 'Espace', 32.0, 7);
$master = new Utilitaire('IJ-789-KL', 'Renault', 'Master', 60.0, 12.0);
$velo = new VeloElectrique('MN-012-OP', 'Moustache', 'Samedi 28', 15.0, 80);

/** @var list<Vehicule> $parc */
$parc = [$clio, $espace, $master, $velo];

foreach ($parc as $vehicule) {
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

?>
    <section class="etape etape--a-venir">
        <h2>Suite du scénario</h2>
        <p>Clients, locations, agence et sauvegarde : en cours de développement.</p>
    </section>
</main>
</body>
</html>

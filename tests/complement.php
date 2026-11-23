<?php

declare(strict_types=1);

use App\Agence;
use App\Clients\Client;
use App\Locations\Location;

// Ce fichier est chargé par run.php : il réutilise tester(), leve() et $bilan.
echo PHP_EOL . '== Client ==' . PHP_EOL;

tester('Client : nom complet, getters et téléphone facultatif', function (): bool {
    $client = new Client(' Dupont ', ' Jean ', ' jean@example.com ');
    return $client->getNomComplet() === 'Jean Dupont'
        && $client->getNom() === 'Dupont' && $client->getPrenom() === 'Jean'
        && $client->getEmail() === 'jean@example.com' && $client->getTelephone() === null;
}, $bilan);

tester('Client : email invalide refusé au constructeur et au setter', function (): bool {
    $client = new Client('Dupont', 'Jean', 'jean@example.com');
    return leve(fn () => new Client('Dupont', 'Jean', 'invalide'))
        && leve(fn () => $client->setEmail('invalide'))
        && $client->getEmail() === 'jean@example.com';
}, $bilan);

tester('Client : noms vides refusés', fn (): bool =>
    leve(fn () => new Client('', 'Jean', 'jean@example.com'))
    && leve(fn () => new Client('Dupont', '  ', 'jean@example.com')), $bilan);

tester('Client : setters chaînables et téléphone vide normalisé', function (): bool {
    $client = new Client('Dupont', 'Jean', 'jean@example.com');
    return $client->setNom('Martin')->setPrenom('Marie')->setEmail('marie@example.com')->setTelephone('0601020304') === $client
        && $client->getTelephone() === '0601020304'
        && $client->setTelephone(' ')->getTelephone() === null;
}, $bilan);

tester('Client : affichage HTML échappé', fn (): bool =>
    str_contains((string) new Client('<b>Dupont</b>', 'Jean', 'jean@example.com'), '&lt;b&gt;Dupont&lt;/b&gt;'), $bilan);

$client = new Client('Dupont', 'Jean', 'jean@example.com');
$creerVoiture = static fn (): App\Vehicules\Voiture => new App\Vehicules\Voiture('EF-456-GH', 'Renault', 'Espace', 32, 7);

echo PHP_EOL . '== Location ==' . PHP_EOL;

tester('Location : getters, date fournie et format attendu', function () use ($client, $creerVoiture): bool {
    $voiture = $creerVoiture();
    $location = new Location($client, $voiture, 3, 151.2, '2026-11-16');
    return $location->getClient() === $client && $location->getVehicule() === $voiture
        && $location->getJours() === 3 && $location->getMontantTTC() === 151.2
        && $location->getDateDebut() === '2026-11-16'
        && (string) $location === 'Jean Dupont - Renault Espace (EF-456-GH) - 3 jours - 151.20 €';
}, $bilan);

tester('Location : date du jour par défaut et singulier', function () use ($client, $creerVoiture): bool {
    $location = new Location($client, $creerVoiture(), 1, 50.4);
    return $location->getDateDebut() === date('Y-m-d') && str_contains((string) $location, '1 jour -');
}, $bilan);

tester('Location : durée, montant et date invalides refusés', fn (): bool =>
    leve(fn () => new Location($client, $creerVoiture(), 0, 151.2))
    && leve(fn () => new Location($client, $creerVoiture(), 3, -1))
    && leve(fn () => new Location($client, $creerVoiture(), 3, INF))
    && leve(fn () => new Location($client, $creerVoiture(), 3, 151.2, '2026-02-30'))
    && leve(fn () => new Location($client, $creerVoiture(), 3, 151.2, '16/11/2026')), $bilan);

echo PHP_EOL . '== Agence ==' . PHP_EOL;

tester('Agence : nom vide refusé', fn (): bool => leve(fn () => new Agence(' ')), $bilan);

tester('Agence : ajout chaînable et getters', function () use ($creerVoiture): bool {
    $agence = new Agence('Centre');
    $voiture = $creerVoiture();
    return $agence->ajouterVehicule($voiture) === $agence && $agence->getNom() === 'Centre'
        && $agence->getVehicules() === [$voiture] && $agence->getLocations() === []
        && $agence->getChiffreAffaires() === 0.0;
}, $bilan);

tester('Agence : immatriculation en double refusée', function () use ($creerVoiture): bool {
    $agence = (new Agence('Centre'))->ajouterVehicule($creerVoiture());
    return leve(fn () => $agence->ajouterVehicule($creerVoiture())) && count($agence->getVehicules()) === 1;
}, $bilan);

tester('Agence : véhicule inconnu ou durée invalide, sans effet', function () use ($client, $creerVoiture): bool {
    $voiture = $creerVoiture();
    $agence = (new Agence('Centre'))->ajouterVehicule($voiture);
    return $agence->louerVehicule($client, 'ZZ-999-ZZ', 3) === null
        && $agence->louerVehicule($client, 'EF-456-GH', 0) === null
        && $agence->louerVehicule($client, 'EF-456-GH', -3) === null
        && $voiture->estDisponible() && $agence->getLocations() === [];
}, $bilan);

tester('Agence : location enregistrée, double location refusée', function () use ($client, $creerVoiture): bool {
    $voiture = $creerVoiture();
    $agence = (new Agence('Centre'))->ajouterVehicule($voiture);
    $location = $agence->louerVehicule($client, ' ef-456-gh ', 3);
    return $location instanceof Location && $location->getMontantTTC() === 151.2
        && $agence->getLocations() === [$location] && !$voiture->estDisponible()
        && $agence->getVehiculesDisponibles() === []
        && $agence->louerVehicule($client, 'EF-456-GH', 2) === null
        && $agence->getChiffreAffaires() === 151.2;
}, $bilan);

tester('Agence : retour, entretien bloquant, puis nouvelle location', function () use ($client, $creerVoiture): bool {
    $voiture = $creerVoiture();
    $agence = (new Agence('Centre'))->ajouterVehicule($voiture);
    $agence->louerVehicule($client, 'EF-456-GH', 3);
    $agence->rendreVehicule('EF-456-GH', 16000);
    if (!$voiture->estDisponible() || !$voiture->besoinEntretien()
        || $agence->louerVehicule($client, 'EF-456-GH', 3) !== null
        || $agence->getVehiculesDisponibles() !== []) {
        return false;
    }
    $voiture->entretenir();
    return $agence->getVehiculesDisponibles() === [$voiture]
        && $agence->louerVehicule($client, 'EF-456-GH', 3) instanceof Location
        && $agence->getChiffreAffaires() === 302.4;
}, $bilan);

tester('Agence : retour négatif refusé avant modification', function () use ($client, $creerVoiture): bool {
    $voiture = $creerVoiture();
    $agence = (new Agence('Centre'))->ajouterVehicule($voiture);
    $agence->louerVehicule($client, 'EF-456-GH', 1);
    return leve(fn () => $agence->rendreVehicule('EF-456-GH', -1))
        && !$voiture->estDisponible() && $voiture->getKmDepuisEntretien() === 0;
}, $bilan);

tester('Agence : double retour sans double ajout de kilomètres', function () use ($client, $creerVoiture): bool {
    $voiture = $creerVoiture();
    $agence = (new Agence('Centre'))->ajouterVehicule($voiture);
    $agence->louerVehicule($client, 'EF-456-GH', 1);
    $agence->rendreVehicule('EF-456-GH', 200);
    $agence->rendreVehicule('EF-456-GH', 200);
    $agence->rendreVehicule('ZZ-999-ZZ');
    return $voiture->getKmDepuisEntretien() === 200 && $voiture->estDisponible();
}, $bilan);

tester('Agence : utilitaire bloqué à 10 000 km', function () use ($client): bool {
    $utilitaire = new App\Vehicules\Utilitaire('IJ-789-KL', 'Renault', 'Master', 60, 12);
    $utilitaire->ajouterKilometres(10000);
    $agence = (new Agence('Centre'))->ajouterVehicule($utilitaire);
    return $agence->louerVehicule($client, 'IJ-789-KL', 2) === null;
}, $bilan);

tester('Agence : retour d’un vélo non entretenable', function () use ($client): bool {
    $velo = new App\Vehicules\VeloElectrique('MN-012-OP', 'Moustache', 'Samedi', 15, 80);
    $agence = (new Agence('Centre'))->ajouterVehicule($velo);
    $agence->louerVehicule($client, 'MN-012-OP', 8);
    $agence->rendreVehicule('MN-012-OP', 60);
    return $velo->estDisponible() && $agence->getChiffreAffaires() === 115.2;
}, $bilan);

tester('Agence : scénario complet = 3 locations et 417.60 € TTC', function () use ($client, $creerVoiture): bool {
    $voiture = $creerVoiture();
    $velo = new App\Vehicules\VeloElectrique('MN-012-OP', 'Moustache', 'Samedi', 15, 80);
    $agence = (new Agence('Centre'))->ajouterVehicule($voiture)->ajouterVehicule($velo);
    $agence->louerVehicule($client, 'EF-456-GH', 3);
    $agence->louerVehicule($client, 'MN-012-OP', 8);
    $agence->louerVehicule($client, 'EF-456-GH', 3);
    $agence->rendreVehicule('EF-456-GH', 16000);
    $agence->louerVehicule($client, 'EF-456-GH', 3);
    $voiture->entretenir();
    $agence->louerVehicule($client, 'EF-456-GH', 3);
    return count($agence->getLocations()) === 3 && $agence->getChiffreAffaires() === 417.6;
}, $bilan);

echo PHP_EOL . '== Sauvegarde ==' . PHP_EOL;

tester('Sauvegarde : état, historique et références préservés', function () use ($client, $creerVoiture): bool {
    $voiture = $creerVoiture();
    $agence = (new Agence('Centre'))->ajouterVehicule($voiture);
    $agence->louerVehicule($client, 'EF-456-GH', 3);
    $fichier = tempnam(sys_get_temp_dir(), 'agence-test-');
    try {
        $agence->sauvegarder($fichier);
        $copie = Agence::charger($fichier);
        $vehicule = $copie->getVehicules()[0];
        return $copie !== $agence && $copie->getNom() === 'Centre'
            && $copie->getChiffreAffaires() === 151.2
            && !$vehicule->estDisponible()
            && $copie->getLocations()[0]->getVehicule() === $vehicule
            && $vehicule->getHistorique() === $voiture->getHistorique();
    } finally {
        unlink($fichier);
    }
}, $bilan);

tester('Sauvegarde : rechargement puis retour et nouvelle location', function () use ($client, $creerVoiture): bool {
    $agence = (new Agence('Centre'))->ajouterVehicule($creerVoiture());
    $agence->louerVehicule($client, 'EF-456-GH', 3);
    $copie = unserialize(serialize($agence));
    $copie->rendreVehicule('EF-456-GH', 100);
    return $copie->getVehicules()[0]->getKmDepuisEntretien() === 100
        && $copie->louerVehicule($client, 'EF-456-GH', 3) instanceof Location
        && $copie->getChiffreAffaires() === 302.4;
}, $bilan);

tester('Sauvegarde : fichier absent et écriture impossible signalés', function (): bool {
    $absent = sys_get_temp_dir() . '/agence-absente-' . bin2hex(random_bytes(8));
    return leve(fn () => Agence::charger($absent), RuntimeException::class)
        && leve(fn () => (new Agence('Centre'))->sauvegarder($absent . '/fichier.txt'), RuntimeException::class);
}, $bilan);

tester('Sauvegarde : fichier vide, corrompu ou mauvais objet refusé', function (): bool {
    $fichier = tempnam(sys_get_temp_dir(), 'agence-test-');
    try {
        $vide = leve(fn () => Agence::charger($fichier), RuntimeException::class);
        file_put_contents($fichier, 'contenu incorrect');
        $corrompu = leve(fn () => Agence::charger($fichier), RuntimeException::class);
        file_put_contents($fichier, serialize(new stdClass()));
        return $vide && $corrompu && leve(fn () => Agence::charger($fichier), RuntimeException::class);
    } finally {
        unlink($fichier);
    }
}, $bilan);

tester('Sauvegarde : structure invalide refusée', function (): bool {
    $agence = new Agence('Centre');
    return leve(fn () => $agence->__unserialize(['nom' => 'Centre', 'vehicules' => ['incorrect'], 'locations' => []]), UnexpectedValueException::class)
        && leve(fn () => $agence->__unserialize([]), UnexpectedValueException::class);
}, $bilan);

echo PHP_EOL . '== Cas limites ==' . PHP_EOL;

tester('Tarif et volume non finis refusés', fn (): bool =>
    leve(fn () => new App\Vehicules\Voiture('AB-123-CD', 'Renault', 'Clio', INF))
    && leve(fn () => new App\Vehicules\Voiture('AB-123-CD', 'Renault', 'Clio', NAN))
    && leve(fn () => new App\Vehicules\Utilitaire('AB-123-CD', 'Renault', 'Master', 60, INF)), $bilan);

tester('Vélo : réduction appliquée exactement dès 7 jours', fn (): bool =>
    (new App\Vehicules\VeloElectrique('AB-123-CD', 'Moustache', 'Samedi', 15, 80))->louer(7) === 100.8, $bilan);

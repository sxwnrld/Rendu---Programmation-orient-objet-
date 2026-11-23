<?php

declare(strict_types=1);

/**
 * Tests unitaires légers, sans dépendance (PHPUnit non requis).
 *
 * Lancement : php tests/run.php
 * Code de sortie 0 si tout passe, 1 sinon (utilisable en intégration continue).
 */

require __DIR__ . '/../autoload.php';

use App\Interfaces\Entretenable;
use App\Vehicules\Utilitaire;
use App\Vehicules\VeloElectrique;
use App\Vehicules\Vehicule;
use App\Vehicules\Voiture;

/** @var array{ok: int, ko: int} $bilan */
$bilan = ['ok' => 0, 'ko' => 0];

/**
 * Exécute un test et affiche son résultat.
 *
 * @param string   $nom   Description du comportement testé.
 * @param callable $test  Fonction qui retourne true si le test passe.
 * @param array{ok: int, ko: int} $bilan Compteurs mis à jour par référence.
 */
function tester(string $nom, callable $test, array &$bilan): void
{
    try {
        $reussi = $test() === true;
    } catch (Throwable $e) {
        $reussi = false;
        $nom .= ' (exception : ' . $e->getMessage() . ')';
    }

    $bilan[$reussi ? 'ok' : 'ko']++;
    echo ($reussi ? "\u{2714} " : "\u{2718} ") . $nom . PHP_EOL;
}

/**
 * Vrai si la fonction lève une exception de la classe attendue.
 *
 * @param class-string<Throwable> $classe
 */
function leve(callable $fonction, string $classe = InvalidArgumentException::class): bool
{
    try {
        $fonction();
    } catch (Throwable $e) {
        return $e instanceof $classe;
    }

    return false;
}

echo PHP_EOL . '== Vehicule ==' . PHP_EOL;

tester('Le compteur de véhicules s\'incrémente à chaque création', function (): bool {
    $avant = Vehicule::getNbVehicules();
    new Voiture('AA-111-AA', 'Peugeot', '208', 30);
    new VeloElectrique('BB-222-BB', 'Moustache', 'Samedi', 15, 80);

    return Vehicule::getNbVehicules() === $avant + 2;
}, $bilan);

tester('Un véhicule invalide n\'est pas comptabilisé', function (): bool {
    $avant = Vehicule::getNbVehicules();
    leve(fn () => new Voiture('AA-111-AA', 'Peugeot', '208', 30, 0));
    leve(fn () => new Voiture('mauvaise', 'Peugeot', '208', 30));

    return Vehicule::getNbVehicules() === $avant;
}, $bilan);

tester('Immatriculation : format AB-123-CD exigé', fn (): bool =>
    leve(fn () => new Voiture('AB123CD', 'Peugeot', '208', 30))
    && leve(fn () => new Voiture('A1-123-CD', 'Peugeot', '208', 30))
    && leve(fn () => new Voiture('AB-12-CD', 'Peugeot', '208', 30)), $bilan);

tester('Immatriculation : minuscules et espaces normalisés', fn (): bool =>
    (new Voiture(' ab-123-cd ', 'Peugeot', '208', 30))->getImmatriculation() === 'AB-123-CD', $bilan);

tester('Tarif journalier <= 0 refusé', fn (): bool =>
    leve(fn () => new Voiture('AB-123-CD', 'Peugeot', '208', 0))
    && leve(fn () => new Voiture('AB-123-CD', 'Peugeot', '208', -5)), $bilan);

tester('Setters chaînables', function (): bool {
    $voiture = new Voiture('AB-123-CD', 'Peugeot', '208', 30);
    $retour = $voiture->setMarque('Renault')->setModele('Clio')->setTarifJournalier(40);

    return $retour === $voiture && $voiture->getModele() === 'Clio';
}, $bilan);

tester('louer() est finale (non redéfinissable)', fn (): bool =>
    (new ReflectionMethod(Vehicule::class, 'louer'))->isFinal(), $bilan);

tester('nbVehicules n\'est pas accessible depuis l\'extérieur', fn (): bool =>
    (new ReflectionProperty(Vehicule::class, 'nbVehicules'))->isPrivate(), $bilan);

tester('louer() : 0 si durée < 1, sans changer la disponibilité', function (): bool {
    $voiture = new Voiture('AB-123-CD', 'Peugeot', '208', 30);

    return $voiture->louer(0) === 0.0 && $voiture->estDisponible();
}, $bilan);

tester('louer() : prix TTC, véhicule indisponible, puis 0 si reloué', function (): bool {
    $voiture = new Voiture('AB-123-CD', 'Peugeot', '208', 30);

    return $voiture->louer(2) === 72.0
        && !$voiture->estDisponible()
        && $voiture->louer(2) === 0.0;
}, $bilan);

tester('rendre() : le véhicule redevient disponible', function (): bool {
    $voiture = new Voiture('AB-123-CD', 'Peugeot', '208', 30);
    $voiture->louer(1);
    $voiture->rendre();

    return $voiture->estDisponible();
}, $bilan);

tester('Historique : format [jj/mm/aaaa hh:mm] Classe : message', function (): bool {
    $voiture = new Voiture('AB-123-CD', 'Peugeot', '208', 30);
    $voiture->louer(1);
    $ligne = $voiture->getHistorique()[0] ?? '';

    return preg_match('#^\[\d{2}/\d{2}/\d{4} \d{2}:\d{2}\] Voiture : #', $ligne) === 1;
}, $bilan);

tester('afficherHistorique() : liste HTML échappée', function (): bool {
    $voiture = new Voiture('AB-123-CD', 'Peugeot', '208', 30);
    $voiture->louer(1);
    $voiture->rendre();
    $html = $voiture->afficherHistorique();

    return str_starts_with($html, '<ul')
        && substr_count($html, '<li>') === 2
        && str_contains($html, 'Retour à l&#039;agence');
}, $bilan);

tester('__toString() : carte HTML avec les informations clés', function (): bool {
    $html = (string) new Voiture('AB-123-CD', 'Renault', 'Clio', 30);

    return str_contains($html, 'Voiture')
        && str_contains($html, 'Renault Clio')
        && str_contains($html, 'AB-123-CD')
        && str_contains($html, '36.00 € TTC')
        && str_contains($html, 'Disponible');
}, $bilan);

echo PHP_EOL . '== Voiture ==' . PHP_EOL;

tester('Prix HT : tarif × jours pour 5 places', fn (): bool =>
    (new Voiture('AB-123-CD', 'Renault', 'Clio', 30, 5))->calculerPrixHT(3) === 90.0, $bilan);

tester('Prix HT : +10 €/jour au-delà de 5 places', fn (): bool =>
    (new Voiture('AB-123-CD', 'Renault', 'Espace', 32, 7))->calculerPrixHT(3) === 126.0, $bilan);

tester('Prix TTC 7 places, 3 jours à 32 € = 151.20 €', fn (): bool =>
    (new Voiture('AB-123-CD', 'Renault', 'Espace', 32, 7))->louer(3) === 151.2, $bilan);

tester('Entretien : nécessaire à partir de 15 000 km', function (): bool {
    $voiture = new Voiture('AB-123-CD', 'Renault', 'Clio', 30);
    $voiture->ajouterKilometres(14999);
    $avant = $voiture->besoinEntretien();
    $voiture->ajouterKilometres(1);

    return !$avant && $voiture->besoinEntretien();
}, $bilan);

tester('entretenir() : remise à zéro + ligne d\'historique', function (): bool {
    $voiture = new Voiture('AB-123-CD', 'Renault', 'Clio', 30);
    $voiture->ajouterKilometres(16000)->entretenir();
    $historique = $voiture->getHistorique();

    return $voiture->getKmDepuisEntretien() === 0
        && !$voiture->besoinEntretien()
        && str_contains((string) end($historique), 'Entretien effectué');
}, $bilan);

tester('ajouterKilometres() : valeur négative refusée', fn (): bool =>
    leve(fn () => (new Voiture('AB-123-CD', 'Renault', 'Clio', 30))->ajouterKilometres(-10)), $bilan);

echo PHP_EOL . '== Utilitaire ==' . PHP_EOL;

tester('Prix HT : tarif × jours + 50 € de nettoyage (une fois)', fn (): bool =>
    (new Utilitaire('AB-123-CD', 'Renault', 'Master', 60, 12))->calculerPrixHT(3) === 230.0, $bilan);

tester('Entretien : nécessaire à partir de 10 000 km', fn (): bool =>
    (new Utilitaire('AB-123-CD', 'Renault', 'Master', 60, 12))->ajouterKilometres(10000)->besoinEntretien(), $bilan);

tester('__toString() : réutilise la carte parente et ajoute le volume', function (): bool {
    $html = (string) new Utilitaire('AB-123-CD', 'Renault', 'Master', 60, 12);

    return str_contains($html, 'AB-123-CD') && str_contains($html, '12,0 m³');
}, $bilan);

echo PHP_EOL . '== VeloElectrique ==' . PHP_EOL;

tester('Classe finale et non entretenable', function (): bool {
    $velo = new VeloElectrique('AB-123-CD', 'Moustache', 'Samedi', 15, 80);

    return (new ReflectionClass(VeloElectrique::class))->isFinal()
        && !$velo instanceof Entretenable;
}, $bilan);

tester('Prix HT : sans réduction sous 7 jours', fn (): bool =>
    (new VeloElectrique('AB-123-CD', 'Moustache', 'Samedi', 15, 80))->calculerPrixHT(6) === 90.0, $bilan);

tester('Prix HT : -20 % à partir de 7 jours', fn (): bool =>
    (new VeloElectrique('AB-123-CD', 'Moustache', 'Samedi', 15, 80))->calculerPrixHT(8) === 96.0, $bilan);

require __DIR__ . '/complement.php';

echo PHP_EOL . sprintf('%d test(s) réussi(s), %d échec(s).', $bilan['ok'], $bilan['ko']) . PHP_EOL;

exit($bilan['ko'] === 0 ? 0 : 1);

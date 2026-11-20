<?php

declare(strict_types=1);

/**
 * Point d'amorçage de l'application.
 *
 * - Fixe le fuseau horaire utilisé par date() (historique, date de location).
 * - Enregistre un autoloader PSR-4 minimal : le namespace racine "App\" est
 *   associé au dossier "src/". Ainsi App\Vehicules\Voiture est chargé depuis
 *   src/Vehicules/Voiture.php sans aucun require manuel.
 */

date_default_timezone_set('Europe/Paris');

spl_autoload_register(static function (string $classe): void {
    $prefixe = 'App\\';
    $dossierBase = __DIR__ . '/src/';

    // On ignore les classes qui n'appartiennent pas à l'application.
    if (strncmp($classe, $prefixe, strlen($prefixe)) !== 0) {
        return;
    }

    $cheminRelatif = str_replace('\\', '/', substr($classe, strlen($prefixe)));
    $fichier = $dossierBase . $cheminRelatif . '.php';

    if (is_file($fichier)) {
        require_once $fichier;
    }
});

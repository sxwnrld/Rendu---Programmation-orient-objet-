<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Contrat de tout élément que l'agence peut mettre en location.
 *
 * Un élément louable passe successivement par les états
 * « disponible » → louer() → « loué » → rendre() → « disponible ».
 */
interface Louable
{
    /**
     * Loue l'élément pour une durée donnée.
     *
     * @param int $jours Durée de la location, en jours (au moins 1).
     *
     * @return float Prix TTC de la location, ou 0 si la location est impossible
     *               (élément indisponible ou durée invalide).
     */
    public function louer(int $jours): float;

    /**
     * Rend l'élément à l'agence : il redevient disponible à la location.
     */
    public function rendre(): void;

    /**
     * Indique si l'élément peut actuellement être loué.
     */
    public function estDisponible(): bool;
}

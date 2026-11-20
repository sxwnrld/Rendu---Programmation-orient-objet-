<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Contrat de tout élément soumis à un entretien périodique,
 * déclenché par le kilométrage parcouru depuis le dernier entretien.
 */
interface Entretenable
{
    /**
     * Ajoute des kilomètres parcourus depuis le dernier entretien.
     *
     * @param int $km Kilomètres parcourus (positif ou nul).
     *
     * @return self L'instance courante, pour permettre le chaînage.
     */
    public function ajouterKilometres(int $km): self;

    /**
     * Indique si l'élément doit passer à l'entretien
     * (seuil de kilométrage atteint ou dépassé).
     */
    public function besoinEntretien(): bool;

    /**
     * Effectue l'entretien : remet à zéro les kilomètres
     * parcourus depuis le dernier entretien.
     */
    public function entretenir(): void;
}

<?php

declare(strict_types=1);

namespace App\Vehicules;

use App\Utils\Format;
use InvalidArgumentException;

/**
 * Vélo à assistance électrique.
 *
 * - Classe finale : elle ne peut pas avoir d'enfants.
 * - N'est pas entretenable (n'implémente pas Entretenable).
 * - 20 % de réduction pour une location d'au moins 7 jours.
 */
final class VeloElectrique extends Vehicule
{
    /** Durée (en jours) à partir de laquelle la réduction s'applique. */
    public const JOURS_MIN_REDUCTION = 7;

    /** Taux de réduction pour les locations longues (20 %). */
    public const TAUX_REDUCTION = 0.20;

    /**
     * @param string $immatriculation Immatriculation au format AB-123-CD.
     * @param string $marque          Marque du vélo.
     * @param string $modele          Modèle du vélo.
     * @param float  $tarifJournalier Tarif HT par jour.
     * @param int    $autonomie       Autonomie de la batterie, en km.
     *
     * @throws InvalidArgumentException Si l'une des valeurs est invalide.
     */
    public function __construct(
        string $immatriculation,
        string $marque,
        string $modele,
        float $tarifJournalier,
        private int $autonomie,
    ) {
        $this->setAutonomie($autonomie);

        parent::__construct($immatriculation, $marque, $modele, $tarifJournalier);
    }

    /**
     * Tarif journalier × jours, avec 20 % de réduction à partir de 7 jours.
     */
    public function calculerPrixHT(int $jours): float
    {
        if ($jours < 1) {
            return 0.0;
        }

        $prixHT = $this->tarifJournalier * $jours;

        if ($jours >= self::JOURS_MIN_REDUCTION) {
            $prixHT *= 1 - self::TAUX_REDUCTION;
        }

        return $prixHT;
    }

    public function getType(): string
    {
        return 'Vélo électrique';
    }

    /**
     * Autonomie de la batterie, en km.
     */
    public function getAutonomie(): int
    {
        return $this->autonomie;
    }

    /**
     * @throws InvalidArgumentException Si l'autonomie est inférieure ou égale à 0.
     */
    public function setAutonomie(int $autonomie): static
    {
        if ($autonomie <= 0) {
            throw new InvalidArgumentException('L\'autonomie d\'un vélo électrique doit être strictement positive.');
        }

        $this->autonomie = $autonomie;

        return $this;
    }

    /**
     * Carte du parent enrichie de l'autonomie.
     */
    public function __toString(): string
    {
        return $this->completerCarte(
            parent::__toString(),
            $this->ligneCarte('Autonomie', Format::entier($this->autonomie) . ' km')
        );
    }
}

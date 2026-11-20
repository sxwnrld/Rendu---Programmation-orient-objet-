<?php

declare(strict_types=1);

namespace App\Traits;

use App\Utils\Format;
use InvalidArgumentException;

/**
 * Implémentation commune de l'interface Entretenable.
 *
 * Voiture et Utilitaire partagent exactement la même logique d'entretien ;
 * seul le seuil change. Ce trait évite donc de dupliquer le code.
 *
 * Prérequis pour la classe qui l'utilise :
 *  - déclarer une propriété  int $kmDepuisEntretien ;
 *  - déclarer une constante  SEUIL_ENTRETIEN (en km) ;
 *  - utiliser HistoriqueTrait (pour ajouterHistorique()).
 */
trait EntretienTrait
{
    /**
     * Ajoute des kilomètres parcourus depuis le dernier entretien.
     *
     * @param int $km Kilomètres parcourus (positif ou nul).
     *
     * @throws InvalidArgumentException Si $km est négatif.
     */
    public function ajouterKilometres(int $km): static
    {
        if ($km < 0) {
            throw new InvalidArgumentException('Le nombre de kilomètres ajoutés ne peut pas être négatif.');
        }

        if ($km > 0) {
            $this->kmDepuisEntretien += $km;
            $this->ajouterHistorique(sprintf(
                '+%s km (%s km depuis le dernier entretien)',
                Format::entier($km),
                Format::entier($this->kmDepuisEntretien)
            ));
        }

        return $this;
    }

    /**
     * Vrai si le seuil de kilométrage propre au véhicule est atteint.
     */
    public function besoinEntretien(): bool
    {
        return $this->kmDepuisEntretien >= static::SEUIL_ENTRETIEN;
    }

    /**
     * Remet à zéro le compteur depuis le dernier entretien et le trace dans l'historique.
     */
    public function entretenir(): void
    {
        $kmAvantEntretien = $this->kmDepuisEntretien;
        $this->kmDepuisEntretien = 0;

        $this->ajouterHistorique(sprintf(
            'Entretien effectué (%s km depuis le précédent)',
            Format::entier($kmAvantEntretien)
        ));
    }

    /**
     * Kilomètres parcourus depuis le dernier entretien.
     */
    public function getKmDepuisEntretien(): int
    {
        return $this->kmDepuisEntretien;
    }

    /**
     * Kilomètres restants avant le prochain entretien (0 si le seuil est dépassé).
     */
    public function getKmAvantEntretien(): int
    {
        return max(0, static::SEUIL_ENTRETIEN - $this->kmDepuisEntretien);
    }

    /**
     * Définit le kilométrage depuis le dernier entretien.
     *
     * Protégé : en dehors de la construction, le compteur n'évolue que via
     * ajouterKilometres() et entretenir(), qui laissent une trace.
     *
     * @throws InvalidArgumentException Si $km est négatif.
     */
    protected function setKmDepuisEntretien(int $km): static
    {
        if ($km < 0) {
            throw new InvalidArgumentException('Le kilométrage depuis le dernier entretien ne peut pas être négatif.');
        }

        $this->kmDepuisEntretien = $km;

        return $this;
    }
}

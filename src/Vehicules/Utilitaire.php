<?php

declare(strict_types=1);

namespace App\Vehicules;

use App\Interfaces\Entretenable;
use App\Traits\EntretienTrait;
use App\Utils\Format;
use InvalidArgumentException;

/**
 * Véhicule utilitaire (camionnette, fourgon...).
 *
 * - Frais de nettoyage de 50 € HT facturés une seule fois par location.
 * - Entretien obligatoire tous les 10 000 km.
 */
class Utilitaire extends Vehicule implements Entretenable
{
    // ajouterKilometres(), besoinEntretien(), entretenir()
    use EntretienTrait;

    /** Kilométrage à partir duquel un entretien est nécessaire. */
    public const SEUIL_ENTRETIEN = 10000;

    /** Frais de nettoyage HT, ajoutés une fois par location. */
    public const FRAIS_NETTOYAGE = 50.0;

    /**
     * @param string $immatriculation   Immatriculation au format AB-123-CD.
     * @param string $marque            Marque du véhicule.
     * @param string $modele            Modèle du véhicule.
     * @param float  $tarifJournalier   Tarif HT par jour.
     * @param float  $volume            Volume de chargement, en m³.
     * @param int    $kmDepuisEntretien Kilomètres depuis le dernier entretien.
     *
     * @throws InvalidArgumentException Si l'une des valeurs est invalide.
     */
    public function __construct(
        string $immatriculation,
        string $marque,
        string $modele,
        float $tarifJournalier,
        private float $volume,
        private int $kmDepuisEntretien = 0,
    ) {
        $this->setVolume($volume)
            ->setKmDepuisEntretien($kmDepuisEntretien);

        parent::__construct($immatriculation, $marque, $modele, $tarifJournalier);
    }

    /**
     * Tarif journalier × jours + frais de nettoyage (une seule fois).
     */
    public function calculerPrixHT(int $jours): float
    {
        if ($jours < 1) {
            return 0.0;
        }

        return $this->tarifJournalier * $jours + self::FRAIS_NETTOYAGE;
    }

    public function getType(): string
    {
        return 'Utilitaire';
    }

    /**
     * Volume de chargement, en m³.
     */
    public function getVolume(): float
    {
        return $this->volume;
    }

    /**
     * @param float $volume Volume de chargement, en m³.
     *
     * @throws InvalidArgumentException Si le volume est inférieur ou égal à 0.
     */
    public function setVolume(float $volume): static
    {
        if (!is_finite($volume) || $volume <= 0) {
            throw new InvalidArgumentException('Le volume d\'un utilitaire doit être strictement positif.');
        }

        $this->volume = $volume;

        return $this;
    }

    /**
     * Réutilise la carte du parent et y ajoute le volume (et l'état d'entretien).
     */
    public function __toString(): string
    {
        return $this->completerCarte(
            parent::__toString(),
            $this->ligneCarte('Volume', number_format($this->volume, 1, ',', ' ') . ' m³'),
            $this->ligneCarte(
                'Entretien',
                $this->besoinEntretien()
                    ? 'Nécessaire'
                    : 'Dans ' . Format::entier($this->getKmAvantEntretien()) . ' km'
            )
        );
    }
}

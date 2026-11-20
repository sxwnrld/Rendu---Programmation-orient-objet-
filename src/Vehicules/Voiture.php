<?php

declare(strict_types=1);

namespace App\Vehicules;

use App\Interfaces\Entretenable;
use App\Traits\EntretienTrait;
use App\Utils\Format;
use InvalidArgumentException;

/**
 * Voiture de tourisme.
 *
 * - Supplément de 10 € HT par jour au-delà de 5 places.
 * - Entretien obligatoire tous les 15 000 km.
 */
class Voiture extends Vehicule implements Entretenable
{
    // ajouterKilometres(), besoinEntretien(), entretenir()
    use EntretienTrait;

    /** Kilométrage à partir duquel un entretien est nécessaire. */
    public const SEUIL_ENTRETIEN = 15000;

    /** Nombre de places au-delà duquel le supplément s'applique. */
    public const PLACES_SANS_SUPPLEMENT = 5;

    /** Supplément HT par jour pour les voitures de plus de 5 places. */
    public const SUPPLEMENT_GRANDE_CAPACITE = 10.0;

    /**
     * nbPlaces et kmDepuisEntretien sont déclarées par promotion des
     * propriétés du constructeur.
     *
     * @param string $immatriculation   Immatriculation au format AB-123-CD.
     * @param string $marque            Marque du véhicule.
     * @param string $modele            Modèle du véhicule.
     * @param float  $tarifJournalier   Tarif HT par jour.
     * @param int    $nbPlaces          Nombre de places (au moins 1).
     * @param int    $kmDepuisEntretien Kilomètres depuis le dernier entretien.
     *
     * @throws InvalidArgumentException Si l'une des valeurs est invalide.
     */
    public function __construct(
        string $immatriculation,
        string $marque,
        string $modele,
        float $tarifJournalier,
        private int $nbPlaces = 5,
        private int $kmDepuisEntretien = 0,
    ) {
        // Validation des propriétés propres AVANT l'appel au parent,
        // pour ne pas comptabiliser un véhicule finalement invalide.
        $this->setNbPlaces($nbPlaces)
            ->setKmDepuisEntretien($kmDepuisEntretien);

        parent::__construct($immatriculation, $marque, $modele, $tarifJournalier);
    }

    /**
     * Tarif journalier × jours, + 10 € HT par jour au-delà de 5 places.
     */
    public function calculerPrixHT(int $jours): float
    {
        if ($jours < 1) {
            return 0.0;
        }

        $prixHT = $this->tarifJournalier * $jours;

        if ($this->nbPlaces > self::PLACES_SANS_SUPPLEMENT) {
            $prixHT += self::SUPPLEMENT_GRANDE_CAPACITE * $jours;
        }

        return $prixHT;
    }

    public function getType(): string
    {
        return 'Voiture';
    }

    public function getNbPlaces(): int
    {
        return $this->nbPlaces;
    }

    /**
     * @throws InvalidArgumentException Si le nombre de places est inférieur à 1.
     */
    public function setNbPlaces(int $nbPlaces): static
    {
        if ($nbPlaces < 1) {
            throw new InvalidArgumentException('Une voiture doit avoir au moins 1 place.');
        }

        $this->nbPlaces = $nbPlaces;

        return $this;
    }

    /**
     * Carte du parent enrichie du nombre de places et de l'état d'entretien.
     */
    public function __toString(): string
    {
        return $this->completerCarte(
            parent::__toString(),
            $this->ligneCarte('Places', (string) $this->nbPlaces),
            $this->ligneCarte(
                'Entretien',
                $this->besoinEntretien()
                    ? 'Nécessaire'
                    : 'Dans ' . Format::entier($this->getKmAvantEntretien()) . ' km'
            )
        );
    }
}

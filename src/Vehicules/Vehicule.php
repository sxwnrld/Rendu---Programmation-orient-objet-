<?php

declare(strict_types=1);

namespace App\Vehicules;

use App\Interfaces\Louable;
use App\Traits\HistoriqueTrait;
use App\Utils\Format;
use InvalidArgumentException;

/**
 * Base commune à tous les véhicules de l'agence.
 *
 * Elle gère l'identité du véhicule, sa disponibilité, le calcul du prix TTC
 * et l'historique. Chaque type concret (Voiture, Utilitaire, VeloElectrique)
 * fournit uniquement son mode de calcul du prix HT et son libellé.
 */
abstract class Vehicule implements Louable
{
    use HistoriqueTrait;

    /** Coefficient de TVA appliqué au prix HT (20 %). */
    public const TVA = 1.20;

    /** Format d'immatriculation SIV : 2 lettres, 3 chiffres, 2 lettres (AB-123-CD). */
    public const FORMAT_IMMATRICULATION = '/^[A-Z]{2}-\d{3}-[A-Z]{2}$/';

    /** Nombre total de véhicules créés (lecture via getNbVehicules()). */
    private static int $nbVehicules = 0;

    /** Immatriculation au format AB-123-CD. */
    private string $immatriculation;

    /** Marque du véhicule (ex. : Renault). */
    private string $marque;

    /** Modèle du véhicule (ex. : Clio). */
    private string $modele;

    /** Tarif HT par jour, accessible aux classes enfants pour le calcul du prix. */
    protected float $tarifJournalier;

    /** Vrai tant que le véhicule n'est pas en cours de location. */
    private bool $disponible = true;

    /**
     * @param string $immatriculation Immatriculation au format AB-123-CD.
     * @param string $marque          Marque du véhicule.
     * @param string $modele          Modèle du véhicule.
     * @param float  $tarifJournalier Tarif HT par jour (strictement positif).
     *
     * @throws InvalidArgumentException Si l'une des valeurs est invalide.
     */
    public function __construct(string $immatriculation, string $marque, string $modele, float $tarifJournalier)
    {
        $this->setImmatriculation($immatriculation)
            ->setMarque($marque)
            ->setModele($modele)
            ->setTarifJournalier($tarifJournalier);

        // Incrémenté en dernier : un véhicule invalide (exception levée
        // par un setter) n'est jamais comptabilisé.
        self::$nbVehicules++;
    }

    /* ------------------------------------------------------------------
     * Méthodes abstraites : propres à chaque type de véhicule
     * ------------------------------------------------------------------ */

    /**
     * Calcule le prix HT d'une location selon les règles du type de véhicule.
     *
     * @param int $jours Durée de la location, en jours.
     *
     * @return float Prix HT, ou 0 si la durée est inférieure à 1 jour.
     */
    abstract public function calculerPrixHT(int $jours): float;

    /**
     * Libellé du type de véhicule (ex. : "Voiture").
     */
    abstract public function getType(): string;

    /* ------------------------------------------------------------------
     * Compteur de véhicules
     * ------------------------------------------------------------------ */

    /**
     * Nombre total de véhicules créés depuis le début du script.
     */
    public static function getNbVehicules(): int
    {
        return self::$nbVehicules;
    }

    /* ------------------------------------------------------------------
     * Implémentation de Louable
     * ------------------------------------------------------------------ */

    /**
     * Loue le véhicule et retourne le prix TTC.
     *
     * Méthode finale : la règle de location (TVA, arrondi, passage en
     * indisponible, historique) est identique pour tous les véhicules ;
     * seuls les enfants personnalisent le prix via calculerPrixHT().
     *
     * @param int $jours Durée de la location, en jours (au moins 1).
     *
     * @return float Prix TTC arrondi à 2 décimales, ou 0 si la location est impossible.
     */
    final public function louer(int $jours): float
    {
        if (!$this->estDisponible() || $jours < 1) {
            return 0.0;
        }

        $prixTTC = round($this->calculerPrixHT($jours) * self::TVA, 2);

        $this->setDisponible(false);
        $this->ajouterHistorique(sprintf(
            'Location de %d jour%s pour %s TTC',
            $jours,
            $jours > 1 ? 's' : '',
            Format::prix($prixTTC)
        ));

        return $prixTTC;
    }

    /**
     * Rend le véhicule à l'agence : il redevient disponible.
     *
     * Sans effet si le véhicule n'était pas loué (évite un faux
     * évènement « retour » dans l'historique).
     */
    public function rendre(): void
    {
        if ($this->estDisponible()) {
            return;
        }

        $this->setDisponible(true);
        $this->ajouterHistorique('Retour à l\'agence');
    }

    /**
     * Indique si le véhicule peut être loué.
     */
    public function estDisponible(): bool
    {
        return $this->disponible;
    }

    /* ------------------------------------------------------------------
     * Getters
     * ------------------------------------------------------------------ */

    public function getImmatriculation(): string
    {
        return $this->immatriculation;
    }

    public function getMarque(): string
    {
        return $this->marque;
    }

    public function getModele(): string
    {
        return $this->modele;
    }

    /**
     * Tarif journalier HT.
     */
    public function getTarifJournalier(): float
    {
        return $this->tarifJournalier;
    }

    /**
     * Tarif journalier TTC, arrondi à 2 décimales.
     */
    public function getTarifJournalierTTC(): float
    {
        return round($this->tarifJournalier * self::TVA, 2);
    }

    /**
     * Alias de estDisponible(), pour respecter la convention getter/setter.
     */
    public function getDisponible(): bool
    {
        return $this->disponible;
    }

    /* ------------------------------------------------------------------
     * Setters (chaînables : ils retournent l'instance courante)
     * ------------------------------------------------------------------ */

    /**
     * @param string $immatriculation Immatriculation au format AB-123-CD
     *                                (les minuscules et espaces autour sont tolérés).
     *
     * @throws InvalidArgumentException Si le format est invalide.
     */
    public function setImmatriculation(string $immatriculation): static
    {
        $immatriculation = strtoupper(trim($immatriculation));

        if (preg_match(self::FORMAT_IMMATRICULATION, $immatriculation) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'Immatriculation « %s » invalide : le format attendu est AB-123-CD.',
                $immatriculation
            ));
        }

        $this->immatriculation = $immatriculation;

        return $this;
    }

    /**
     * @throws InvalidArgumentException Si la marque est vide.
     */
    public function setMarque(string $marque): static
    {
        $this->marque = self::verifierTexteNonVide($marque, 'La marque');

        return $this;
    }

    /**
     * @throws InvalidArgumentException Si le modèle est vide.
     */
    public function setModele(string $modele): static
    {
        $this->modele = self::verifierTexteNonVide($modele, 'Le modèle');

        return $this;
    }

    /**
     * @param float $tarifJournalier Tarif HT par jour.
     *
     * @throws InvalidArgumentException Si le tarif est inférieur ou égal à 0.
     */
    public function setTarifJournalier(float $tarifJournalier): static
    {
        if ($tarifJournalier <= 0) {
            throw new InvalidArgumentException(sprintf(
                'Tarif journalier invalide (%s) : il doit être strictement positif.',
                $tarifJournalier
            ));
        }

        $this->tarifJournalier = $tarifJournalier;

        return $this;
    }

    /**
     * Setter protégé : la disponibilité ne doit évoluer que par louer()
     * et rendre(), sinon on pourrait « libérer » un véhicule loué sans
     * passer par le retour (et sans trace dans l'historique).
     */
    protected function setDisponible(bool $disponible): static
    {
        $this->disponible = $disponible;

        return $this;
    }

    /* ------------------------------------------------------------------
     * Affichage
     * ------------------------------------------------------------------ */

    /**
     * Carte HTML : type, marque, modèle, immatriculation,
     * tarif journalier TTC et disponibilité.
     */
    public function __toString(): string
    {
        $etat = $this->estDisponible() ? 'disponible' : 'loue';
        $libelleEtat = $this->estDisponible() ? 'Disponible' : 'En location';

        return '<article class="vehicule vehicule--' . $etat . '">'
            . '<header class="vehicule__entete">'
            . '<span class="vehicule__type">' . Format::html($this->getType()) . '</span>'
            . '<span class="badge badge--' . $etat . '">' . $libelleEtat . '</span>'
            . '</header>'
            . '<h3 class="vehicule__nom">' . Format::html($this->marque . ' ' . $this->modele) . '</h3>'
            . '<ul class="vehicule__details">'
            . $this->ligneCarte('Immatriculation', $this->immatriculation)
            . $this->ligneCarte('Tarif journalier', Format::prix($this->getTarifJournalierTTC()) . ' TTC')
            . $this->ligneCarte('Disponibilité', $libelleEtat)
            . '</ul>'
            . '</article>';
    }

    /**
     * Génère une ligne "libellé : valeur" de la carte HTML (valeurs échappées).
     */
    protected function ligneCarte(string $libelle, string $valeur): string
    {
        return '<li><span>' . Format::html($libelle) . '</span>'
            . '<strong>' . Format::html($valeur) . '</strong></li>';
    }

    /**
     * Insère des lignes supplémentaires à la fin de la liste de détails
     * d'une carte générée par __toString(). Permet aux enfants d'enrichir
     * la carte du parent sans dupliquer sa structure HTML.
     *
     * @param string $carte  Carte HTML produite par parent::__toString().
     * @param string ...$lignes Lignes produites par ligneCarte().
     */
    protected function completerCarte(string $carte, string ...$lignes): string
    {
        $position = strrpos($carte, '</ul>');

        if ($position === false) {
            return $carte . implode('', $lignes);
        }

        return substr_replace($carte, implode('', $lignes), $position, 0);
    }

    /* ------------------------------------------------------------------
     * Outils internes
     * ------------------------------------------------------------------ */

    /**
     * Supprime les espaces superflus et refuse une chaîne vide.
     *
     * @param string $valeur  Valeur à contrôler.
     * @param string $libelle Nom du champ, pour le message d'erreur.
     *
     * @throws InvalidArgumentException Si la valeur est vide.
     */
    private static function verifierTexteNonVide(string $valeur, string $libelle): string
    {
        $valeur = trim($valeur);

        if ($valeur === '') {
            throw new InvalidArgumentException($libelle . ' ne peut pas être vide.');
        }

        return $valeur;
    }
}

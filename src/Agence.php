<?php

declare(strict_types=1);

namespace App;

use App\Clients\Client;
use App\Interfaces\Entretenable;
use App\Locations\Location;
use App\Vehicules\Utilitaire;
use App\Vehicules\Vehicule;
use App\Vehicules\VeloElectrique;
use App\Vehicules\Voiture;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class Agence
{
    private string $nom;
    /** @var list<Vehicule> */
    private array $vehicules = [];
    /** @var list<Location> */
    private array $locations = [];

    public function __construct(string $nom)
    {
        $nom = trim($nom);
        if ($nom === '') {
            throw new InvalidArgumentException('Le nom de l’agence ne peut pas être vide.');
        }
        $this->nom = $nom;
    }

    public function getNom(): string
    {
        return $this->nom;
    }
    /** @return list<Vehicule> */
    public function getVehicules(): array
    {
        return $this->vehicules;
    }
    /** @return list<Location> */
    public function getLocations(): array
    {
        return $this->locations;
    }

    public function ajouterVehicule(Vehicule $vehicule): self
    {
        if ($this->trouverVehicule($vehicule->getImmatriculation()) !== null) {
            throw new InvalidArgumentException('Cette immatriculation existe déjà dans le parc.');
        }
        $this->vehicules[] = $vehicule;
        return $this;
    }

    /** @return list<Vehicule> Véhicules disponibles et en état de partir. */
    public function getVehiculesDisponibles(): array
    {
        return array_values(array_filter(
            $this->vehicules,
            static fn (Vehicule $vehicule): bool => $vehicule->estDisponible()
                && !($vehicule instanceof Entretenable && $vehicule->besoinEntretien())
        ));
    }

    public function louerVehicule(Client $client, string $immatriculation, int $jours): ?Location
    {
        $vehicule = $this->trouverVehicule($immatriculation);
        if ($jours < 1 || $vehicule === null || !$vehicule->estDisponible()
            || ($vehicule instanceof Entretenable && $vehicule->besoinEntretien())) {
            return null;
        }

        $montantTTC = $vehicule->louer($jours);
        if ($montantTTC <= 0) {
            return null;
        }
        $location = new Location($client, $vehicule, $jours, $montantTTC);
        $this->locations[] = $location;
        return $location;
    }

    public function rendreVehicule(string $immatriculation, int $kmParcourus = 0): void
    {
        // Valider avant toute modification pour éviter un retour partiel.
        if ($kmParcourus < 0) {
            throw new InvalidArgumentException('Les kilomètres parcourus ne peuvent pas être négatifs.');
        }
        $vehicule = $this->trouverVehicule($immatriculation);
        if ($vehicule === null || $vehicule->estDisponible()) {
            return;
        }
        $vehicule->rendre();
        if ($vehicule instanceof Entretenable) {
            $vehicule->ajouterKilometres($kmParcourus);
        }
    }

    public function getChiffreAffaires(): float
    {
        $total = 0.0;
        foreach ($this->locations as $location) {
            $total += $location->getMontantTTC();
        }
        return round($total, 2);
    }

    /** @return array{nom: string, vehicules: list<Vehicule>, locations: list<Location>} */
    public function __serialize(): array
    {
        return ['nom' => $this->nom, 'vehicules' => $this->vehicules, 'locations' => $this->locations];
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        if (!isset($data['nom'], $data['vehicules'], $data['locations'])
            || !is_string($data['nom']) || trim($data['nom']) === ''
            || !is_array($data['vehicules']) || !is_array($data['locations'])) {
            throw new UnexpectedValueException('Structure de sauvegarde invalide.');
        }
        $immatriculations = [];
        foreach ($data['vehicules'] as $vehicule) {
            if (!$vehicule instanceof Vehicule) {
                throw new UnexpectedValueException('Véhicule invalide dans la sauvegarde.');
            }
            $immatriculation = $vehicule->getImmatriculation();
            if (isset($immatriculations[$immatriculation])) {
                throw new UnexpectedValueException('Immatriculation en double dans la sauvegarde.');
            }
            $immatriculations[$immatriculation] = true;
        }
        foreach ($data['locations'] as $location) {
            if (!$location instanceof Location
                || !in_array($location->getVehicule(), $data['vehicules'], true)) {
                throw new UnexpectedValueException('Location invalide dans la sauvegarde.');
            }
        }
        $this->nom = $data['nom'];
        $this->vehicules = array_values($data['vehicules']);
        $this->locations = array_values($data['locations']);
    }

    public function sauvegarder(string $fichier): void
    {
        $contenu = serialize($this);
        $octets = @file_put_contents($fichier, $contenu, LOCK_EX);
        if ($octets === false || $octets !== strlen($contenu)) {
            throw new RuntimeException('Impossible d’écrire la sauvegarde : ' . $fichier);
        }
    }

    public static function charger(string $fichier): Agence
    {
        if (!is_file($fichier) || !is_readable($fichier)) {
            throw new RuntimeException('Sauvegarde introuvable ou illisible : ' . $fichier);
        }
        $contenu = @file_get_contents($fichier);
        if ($contenu === false || $contenu === '') {
            throw new RuntimeException('La sauvegarde est vide ou illisible.');
        }
        try {
            // Ne charger que nos propres sauvegardes locales, jamais un fichier reçu.
            $agence = @unserialize($contenu, ['allowed_classes' => [
                self::class, Voiture::class, Utilitaire::class, VeloElectrique::class,
                Client::class, Location::class,
            ]]);
            if (!$agence instanceof self) {
                throw new UnexpectedValueException('Le fichier ne contient pas une agence.');
            }
            return $agence;
        } catch (Throwable $e) {
            throw new RuntimeException('Sauvegarde invalide ou corrompue.', 0, $e);
        }
    }

    private function trouverVehicule(string $immatriculation): ?Vehicule
    {
        $immatriculation = strtoupper(trim($immatriculation));
        foreach ($this->vehicules as $vehicule) {
            if ($vehicule->getImmatriculation() === $immatriculation) {
                return $vehicule;
            }
        }
        return null;
    }
}

<?php

declare(strict_types=1);

namespace App\Locations;

use App\Clients\Client;
use App\Utils\Format;
use App\Vehicules\Vehicule;
use DateTimeImmutable;
use InvalidArgumentException;

class Location
{
    public function __construct(
        private Client $client,
        private Vehicule $vehicule,
        private int $jours,
        private float $montantTTC,
        private ?string $dateDebut = null,
    ) {
        if ($jours < 1) {
            throw new InvalidArgumentException('Une location dure au moins un jour.');
        }
        if (!is_finite($montantTTC) || $montantTTC <= 0) {
            throw new InvalidArgumentException('Le montant TTC doit être strictement positif et fini.');
        }

        $this->dateDebut ??= date('Y-m-d');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $this->dateDebut);
        if ($date === false || $date->format('Y-m-d') !== $this->dateDebut) {
            throw new InvalidArgumentException('La date doit être valide et au format AAAA-MM-JJ.');
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }
    public function getVehicule(): Vehicule
    {
        return $this->vehicule;
    }
    public function getJours(): int
    {
        return $this->jours;
    }
    public function getMontantTTC(): float
    {
        return $this->montantTTC;
    }
    public function getDateDebut(): string
    {
        return $this->dateDebut;
    }

    public function __toString(): string
    {
        return Format::html(sprintf(
            '%s - %s %s (%s) - %d jour%s - %s',
            $this->client->getNomComplet(),
            $this->vehicule->getMarque(),
            $this->vehicule->getModele(),
            $this->vehicule->getImmatriculation(),
            $this->jours,
            $this->jours > 1 ? 's' : '',
            Format::prix($this->montantTTC)
        ));
    }
}

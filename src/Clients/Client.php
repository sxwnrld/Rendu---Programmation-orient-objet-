<?php

declare(strict_types=1);

namespace App\Clients;

use App\Utils\Format;
use InvalidArgumentException;

class Client
{
    private string $nom;
    private string $prenom;
    private string $email;
    private ?string $telephone;

    public function __construct(string $nom, string $prenom, string $email, ?string $telephone = null)
    {
        $this->setNom($nom)->setPrenom($prenom)->setEmail($email)->setTelephone($telephone);
    }

    public function getNom(): string
    {
        return $this->nom;
    }
    public function getPrenom(): string
    {
        return $this->prenom;
    }
    public function getEmail(): string
    {
        return $this->email;
    }
    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setNom(string $nom): self
    {
        $nom = trim($nom);
        if ($nom === '') {
            throw new InvalidArgumentException('Le nom ne peut pas être vide.');
        }
        $this->nom = $nom;
        return $this;
    }

    public function setPrenom(string $prenom): self
    {
        $prenom = trim($prenom);
        if ($prenom === '') {
            throw new InvalidArgumentException('Le prénom ne peut pas être vide.');
        }
        $this->prenom = $prenom;
        return $this;
    }

    public function setEmail(string $email): self
    {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Adresse email invalide : ' . $email);
        }
        $this->email = $email;
        return $this;
    }

    public function setTelephone(?string $telephone): self
    {
        $telephone = $telephone === null ? null : trim($telephone);
        $this->telephone = $telephone === '' ? null : $telephone;
        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function __toString(): string
    {
        return Format::html($this->getNomComplet() . ' (' . $this->email . ')');
    }
}

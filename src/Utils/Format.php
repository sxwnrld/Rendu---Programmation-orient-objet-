<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Fonctions de formatage partagées par toute l'application.
 *
 * Classe purement statique : elle ne peut être ni instanciée ni étendue.
 */
final class Format
{
    /** Symbole monétaire affiché après les montants. */
    private const DEVISE = '€';

    /**
     * Constructeur privé : la classe ne sert qu'à regrouper des fonctions.
     */
    private function __construct()
    {
    }

    /**
     * Formate un montant avec deux décimales, ex. : 151.2 → "151.20 €".
     *
     * @param float $montant Montant à formater.
     */
    public static function prix(float $montant): string
    {
        return number_format($montant, 2, '.', ' ') . ' ' . self::DEVISE;
    }

    /**
     * Formate un nombre entier avec séparateur de milliers, ex. : 16000 → "16 000".
     *
     * @param int $nombre Nombre à formater.
     */
    public static function entier(int $nombre): string
    {
        return number_format($nombre, 0, ',', ' ');
    }

    /**
     * Échappe une chaîne avant de l'insérer dans du HTML (protection XSS).
     *
     * @param string $texte Texte brut.
     */
    public static function html(string $texte): string
    {
        return htmlspecialchars($texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

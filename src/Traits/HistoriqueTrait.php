<?php

declare(strict_types=1);

namespace App\Traits;

use App\Utils\Format;

/**
 * Ajoute à une classe un journal horodaté de ses évènements.
 *
 * Chaque ligne est au format : [16/11/2026 14:30] Voiture : message
 */
trait HistoriqueTrait
{
    /**
     * Évènements enregistrés, du plus ancien au plus récent.
     *
     * @var list<string>
     */
    private array $historique = [];

    /**
     * Ajoute une ligne horodatée à l'historique.
     *
     * La méthode est protégée : seul l'objet lui-même (ou ses enfants)
     * peut écrire dans son historique, qui reflète donc uniquement
     * des évènements réellement survenus (location, retour, entretien...).
     *
     * @param string $message Description de l'évènement.
     */
    protected function ajouterHistorique(string $message): void
    {
        $this->historique[] = sprintf(
            '[%s] %s : %s',
            date('d/m/Y H:i'),
            $this->getNomClasseHistorique(),
            $message
        );
    }

    /**
     * Retourne l'historique sous forme de liste HTML (<ul><li>...</li></ul>).
     */
    public function afficherHistorique(): string
    {
        if ($this->historique === []) {
            return '<ul class="historique"><li class="historique__vide">Aucun évènement.</li></ul>';
        }

        $lignes = array_map(
            static fn (string $ligne): string => '<li>' . Format::html($ligne) . '</li>',
            $this->historique
        );

        return '<ul class="historique">' . implode('', $lignes) . '</ul>';
    }

    /**
     * Retourne l'historique brut (copie du tableau : il reste non modifiable de l'extérieur).
     *
     * @return list<string>
     */
    public function getHistorique(): array
    {
        return $this->historique;
    }

    /**
     * __CLASS__ donne la classe qui utilise le trait (Vehicule).
     * Pour un enfant, static::class conserve son nom concret dans le journal.
     */
    private function getNomClasseHistorique(): string
    {
        $classe = __CLASS__;
        if (static::class !== $classe) {
            $classe = static::class;
        }
        $position = strrpos($classe, '\\');

        return $position === false ? $classe : substr($classe, $position + 1);
    }
}

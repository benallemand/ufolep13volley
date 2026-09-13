<?php

require_once __DIR__ . '/Generic.php';

class Indicator extends Generic
{

    private string $fieldLabel;
    private string $sql;
    private string $type;
    private ?string $target;
    private ?string $idColumn;

    public function getType(): string
    {
        return $this->type;
    }

    public function getFieldLabel(): string
    {
        return $this->fieldLabel;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @param $fieldLabel
     * @param $sql
     * @param string $type
     * @param string|null $target Écran d'administration où corriger l'anomalie,
     *                            sous la forme de sa route sans le `#/` :
     *                            'players', 'teams'… Null quand l'indicateur
     *                            n'a pas de cible évidente — c'est le cas de la
     *                            plupart, qui croisent plusieurs entités.
     * @param string|null $idColumn Colonne de la requête portant l'identifiant
     *                              de la ligne à corriger. Voir `getResult()`.
     */
    function __construct($fieldLabel, $sql, $type = 'info', ?string $target = null, ?string $idColumn = null)
    {
        parent::__construct();
        $this->fieldLabel = $fieldLabel;
        $this->sql = $sql;
        $this->type = $type;
        $this->target = $target;
        $this->idColumn = $idColumn;
    }

    function execSqlGetDetails()
    {
        return $this->sql_manager->execute($this->sql);
    }

    /**
     * Le tableau de bord dit CE QUI ne va pas ; `target` et `ids` disent OÙ
     * aller le corriger (issue #312). L'écran visé s'ouvre alors filtré sur ces
     * seules lignes, au lieu qu'on aille les retrouver à la main.
     *
     * L'identifiant est **retiré du détail affiché** : la requête doit le
     * sélectionner pour qu'on puisse l'extraire, mais une colonne d'identifiants
     * bruts n'apprend rien à personne dans le tableau du détail. Les valeurs
     * sont dédoublonnées — une jointure sur `joueur_equipe` ramène le même
     * joueur autant de fois qu'il a d'équipes.
     */
    function getResult()
    {
        $results = $this->execSqlGetDetails();
        $ids = array();
        if ($this->idColumn !== null) {
            $ids = array_values(array_unique(array_filter(
                array_column($results, $this->idColumn),
                static function ($id) {
                    return $id !== null && $id !== '';
                }
            )));
            foreach ($results as $index => $row) {
                unset($results[$index][$this->idColumn]);
            }
        }
        return array(
            'fieldLabel' => $this->fieldLabel,
            'type' => $this->type,
            'value' => count($results),
            'details' => $results,
            'target' => $this->target,
            'ids' => $ids,
        );
    }

}

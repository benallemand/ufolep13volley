<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/Indicator.php';

/**
 * Sections du tableau de bord des indicateurs (issue #396).
 *
 * `ajax/indicators.php` ne se teste pas en l'exécutant (garde admin, `exit`) :
 * on lit les déclarations dans le source.
 */
class IndicatorCategoriesTest extends UfolepTestCase
{
    /**
     * Une entrée par `new Indicator(` du tableau de bord : le texte qui le
     * suit, jusqu'au suivant. Ses propres arguments viennent donc en premier.
     *
     * @return array<int, string>
     */
    private function declarations(): array
    {
        $parts = explode('new Indicator(', file_get_contents(__DIR__ . '/../ajax/indicators.php'));
        array_shift($parts);
        return $parts;
    }

    public function test_chaque_indicateur_declare_sa_section(): void
    {
        $declarations = $this->declarations();
        $this->assertCount(44, $declarations);
        foreach ($declarations as $declaration) {
            // Sans section explicite, la valeur par défaut rangerait en silence
            // une alerte dans les statistiques.
            $this->assertRegExp('/category:\s*Indicator::[A-Z_]+/', $declaration,
                "Indicateur sans section : " . strtok($declaration, "\n"));
        }
    }

    public function test_les_sections_utilisees_existent(): void
    {
        $constants = (new ReflectionClass(Indicator::class))->getConstants();
        foreach ($this->declarations() as $declaration) {
            preg_match('/category:\s*Indicator::([A-Z_]+)/', $declaration, $match);
            $this->assertArrayHasKey($match[1], $constants);
            $this->assertArrayHasKey($constants[$match[1]], Indicator::CATEGORIES);
        }
    }

    public function test_les_requetes_declarees_existent(): void
    {
        foreach ($this->declarations() as $declaration) {
            preg_match("/indicator_sql\('([^']+)'\)/", $declaration, $match);
            $this->assertFileExists(__DIR__ . '/../sql/' . $match[1]);
        }
    }

    /**
     * Toute alerte a son bouton « Corriger » (#409) : un écran cible, routé
     * dans l'administration, et une requête qui sélectionne `indicator_id`.
     * Une alerte sans cible dit ce qui ne va pas sans dire où le corriger.
     */
    public function test_chaque_alerte_a_un_bouton_corriger(): void
    {
        $layout = file_get_contents(__DIR__ . '/../admin/components/layout/AdminLayout.js');
        foreach ($this->declarations() as $declaration) {
            if (!preg_match("/indicator_sql\('([^']+)'\),\s*'alert'/", $declaration, $sql)) {
                continue;
            }
            $label = strtok($declaration, "\n");
            $this->assertRegExp("/'alert',\s*'([a-z-]+)',\s*'indicator_id'/", $declaration,
                "Alerte sans « Corriger » : $label");
            preg_match("/'alert',\s*'([a-z-]+)',\s*'indicator_id'/", $declaration, $target);
            $this->assertStringContainsString("path: '/" . $target[1] . "'", $layout,
                "Écran cible inconnu de l'administration : " . $target[1]);
            $this->assertRegExp('/\bAS\s+indicator_id\b/i', file_get_contents(__DIR__ . '/../sql/' . $sql[1]),
                "La requête ne sélectionne pas indicator_id : " . $sql[1]);
        }
    }

    public function test_une_ligne_peut_designer_plusieurs_lignes_a_corriger(): void
    {
        $result = (new Indicator('test',
            "SELECT '3,4' AS indicator_id, 'a' AS libelle UNION ALL SELECT '4', 'b' UNION ALL SELECT NULL, 'c'",
            'alert', 'matches', 'indicator_id', category: Indicator::SEASON))->getResult();
        $this->assertSame(array('3', '4'), $result['ids']);
        $this->assertArrayNotHasKey('indicator_id', $result['details'][0]);
    }

    public function test_une_section_inconnue_est_refusee(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Indicator('test', 'SELECT 1', category: 'nimportequoi');
    }

    public function test_la_section_est_rendue_avec_le_detail(): void
    {
        $result = (new Indicator('test', 'SELECT 1 AS un', category: Indicator::CALENDAR))->getResult();
        $this->assertSame(Indicator::CALENDAR, $result['category']);
    }
}

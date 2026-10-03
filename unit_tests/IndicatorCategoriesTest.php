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

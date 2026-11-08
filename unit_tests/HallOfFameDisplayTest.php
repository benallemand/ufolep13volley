<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/HallOfFame.php';

/**
 * Palmarès public (`sql/get_hall_of_fame.sql`) : un podium s'affiche dès qu'il a
 * un premier, même sans second. Les archives d'avant 2015-2016 n'ont souvent
 * pas de vice-champion, et la mi-saison féminine 2009-2010 s'est jouée en
 * poules : avant, ces podiums disparaissaient de la page.
 */
class HallOfFameDisplayTest extends UfolepTestCase
{
    private string $league;

    protected function setUp(): void
    {
        parent::setUp();
        $this->league = 'TEST HOF ' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->sql->execute("DELETE FROM hall_of_fame WHERE league = ?",
            [['type' => 's', 'value' => $this->league]]);
        parent::tearDown();
    }

    private function ajouter(string $title, string $team, string $period = '1999-2000'): void
    {
        $this->sql->execute(
            "INSERT INTO hall_of_fame (title, team_name, period, league) VALUES (?, ?, ?, ?)",
            [
                ['type' => 's', 'value' => $title],
                ['type' => 's', 'value' => $team],
                ['type' => 's', 'value' => $period],
                ['type' => 's', 'value' => $this->league],
            ]);
    }

    /** @return array<string, array> podiums de la ligue de test, par clé division|demi-saison */
    private function podiums(): array
    {
        $rows = array_filter((new HallOfFame())->getHallOfFameDisplay(),
            fn($r) => $r['league'] === $this->league);
        $out = array();
        foreach ($rows as $r) {
            $out[$r['period'] . '|' . $r['division'] . '|' . $r['demi_saison']] = $r;
        }
        return $out;
    }

    public function test_podium_complet(): void
    {
        $this->ajouter('Championne Dept. de Division 2', 'Premier');
        $this->ajouter('Vice-championne Dept. de Division 2', 'Second');
        $p = $this->podiums();
        self::assertCount(1, $p);
        self::assertSame('Premier', $p['1999-2000|2|2']['champion']);
        self::assertSame('Second', $p['1999-2000|2|2']['vice_champion']);
        self::assertRegExp('/^\d+,\d+$/', $p['1999-2000|2|2']['ids']);
    }

    public function test_podium_sans_second(): void
    {
        $this->ajouter('Championne mi-saison de Division 3', 'Seul');
        $this->ajouter('Vainqueur', 'Vainqueur seul', '2000');
        $p = $this->podiums();
        self::assertCount(2, $p);
        self::assertSame('Seul', $p['1999-2000|3|1']['champion']);
        self::assertNull($p['1999-2000|3|1']['vice_champion']);
        self::assertRegExp('/^\d+$/', $p['1999-2000|3|1']['ids']);
        self::assertSame('Vainqueur seul', $p['2000||2']['champion']);
    }

    public function test_poules(): void
    {
        $this->ajouter('Championne mi-saison de Poule 1', 'Poule un');
        $this->ajouter('Championne mi-saison de Poule 4', 'Poule quatre');
        $p = $this->podiums();
        self::assertSame('Poule un', $p['1999-2000|Poule 1|1']['champion']);
        self::assertSame('Poule quatre', $p['1999-2000|Poule 4|1']['champion']);
    }
}

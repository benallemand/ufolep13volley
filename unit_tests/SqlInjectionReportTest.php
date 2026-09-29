<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . "/../classes/MatchMgr.php";

/**
 * Injection SQL par `code_match` dans le workflow de report de match.
 *
 * `isTeamDomForMatch()`, `getTeamsEmailsFromMatch*()`,
 * `check_team_allowed_to_ask_report()` et les UPDATE de `askForReport`,
 * `acceptReport` et `refuseReport` concaténaient le code reçu du client.
 *
 * Même parti pris que les lots #270 : des assertions **discriminantes**. La
 * charge vise un match réel (la « victime ») à partir d'un code inexistant :
 * concaténée, elle atteint la victime ; liée, elle ne correspond à rien.
 *
 * Aucune donnée n'est modifiée par le code corrigé ; l'état de la victime est
 * malgré tout restauré, pour qu'un retour de la faille ne salisse pas la base.
 */
class SqlInjectionReportTest extends UfolepTestCase
{
    private MatchMgr $matchMgr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matchMgr = new MatchMgr();
    }

    /**
     * Un match confirmé, visible dans `matchs_view` (donc résolu par
     * `get_match_by_code_match()`).
     */
    private function get_victim(): array
    {
        $matches = $this->matchMgr->get_matches(
            "m.match_status = 'CONFIRMED' AND m.code_match IS NOT NULL AND m.code_match <> ''");
        if (empty($matches)) {
            self::markTestSkipped("Pas de match confirmé en base");
        }
        return $matches[0];
    }

    /**
     * Code inexistant qui, concaténé dans `code_match = '...'`, ajoute
     * `OR code_match = '<victime>'`.
     */
    private static function payload_for(array $victim): string
    {
        return "UT_INEXISTANT' OR code_match = '" . $victim['code_match'];
    }

    private function report_state(array $victim): array
    {
        $status = $this->sql->execute(
            "SELECT report_status FROM matches WHERE code_match = ?",
            array(array('type' => 's', 'value' => $victim['code_match'])));
        $counts = $this->sql->execute(
            "SELECT id_equipe, report_count FROM classements
             WHERE code_competition = ? AND id_equipe IN (?, ?)
             ORDER BY id_equipe",
            array(
                array('type' => 's', 'value' => $victim['code_competition']),
                array('type' => 'i', 'value' => $victim['id_equipe_dom']),
                array('type' => 'i', 'value' => $victim['id_equipe_ext']),
            ));
        return array('report_status' => $status[0]['report_status'], 'report_counts' => $counts);
    }

    private function restore_report_state(array $victim, array $state): void
    {
        $this->sql->execute(
            "UPDATE matches SET report_status = ? WHERE code_match = ?",
            array(
                array('type' => 's', 'value' => $state['report_status']),
                array('type' => 's', 'value' => $victim['code_match']),
            ));
        foreach ($state['report_counts'] as $row) {
            $this->sql->execute(
                "UPDATE classements SET report_count = ? WHERE code_competition = ? AND id_equipe = ?",
                array(
                    array('type' => 'i', 'value' => $row['report_count']),
                    array('type' => 's', 'value' => $victim['code_competition']),
                    array('type' => 'i', 'value' => $row['id_equipe']),
                ));
        }
    }

    /**
     * Concaténée, la charge rendait la condition vraie grâce au `OR` ; liée,
     * elle ne correspond à aucun match.
     */
    public function test_is_team_dom_for_match_lie_le_code_match(): void
    {
        $victim = $this->get_victim();
        self::assertFalse(
            $this->matchMgr->isTeamDomForMatch($victim['id_equipe_dom'], self::payload_for($victim)),
            "La charge a atteint un autre match : le code n'est pas lié");
    }

    public function test_is_team_dom_for_match_fonctionne_toujours(): void
    {
        $victim = $this->get_victim();
        self::assertTrue($this->matchMgr->isTeamDomForMatch($victim['id_equipe_dom'], $victim['code_match']));
        self::assertFalse($this->matchMgr->isTeamDomForMatch($victim['id_equipe_ext'], $victim['code_match']));
    }

    /**
     * Concaténée, la charge ramenait exactement la victime : les emails de ses
     * équipes étaient renvoyés au lieu de l'erreur « match introuvable ».
     */
    public function test_emails_des_equipes_lient_le_code_match(): void
    {
        $victim = $this->get_victim();
        foreach (array('getTeamsEmailsFromMatch', 'getTeamsEmailsFromMatchReport') as $method) {
            try {
                $this->matchMgr->$method(self::payload_for($victim));
                self::fail("$method : la charge a atteint un autre match");
            } catch (Exception $e) {
                self::assertStringContainsString(
                    'Impossible de récupérer le match', $e->getMessage(),
                    "$method : erreur inattendue, le code n'est pas lié");
            }
        }
    }

    public function test_check_team_allowed_to_ask_report_echappe_le_code_match(): void
    {
        $victim = $this->get_victim();
        try {
            $this->matchMgr->check_team_allowed_to_ask_report($victim['id_equipe_dom'], self::payload_for($victim));
            self::fail("La charge a atteint un autre match");
        } catch (Exception $e) {
            self::assertStringContainsString('0 match', $e->getMessage());
        }
    }

    /**
     * Les actions d'écriture du workflow : un code inconnu doit échouer avant
     * toute écriture, et la victime ne doit pas bouger — ni son statut de
     * report, ni les compteurs de reports de ses équipes (`acceptReport`
     * incrémentait celui de l'adversaire).
     *
     * Pas de @dataProvider (incompatible avec le constructeur de
     * UfolepTestCase) : une boucle.
     */
    public function test_les_actions_de_report_ne_touchent_pas_un_autre_match(): void
    {
        $victim = $this->get_victim();
        $payload = self::payload_for($victim);
        $cases = array(
            'askForReport (responsable)' => array('leader', fn() => $this->matchMgr->askForReport($payload, 'ut')),
            'acceptReport (responsable)' => array('leader', fn() => $this->matchMgr->acceptReport($payload)),
            'refuseReport (responsable)' => array('leader', fn() => $this->matchMgr->refuseReport($payload, 'ut')),
            'refuseReport (admin)' => array('admin', fn() => $this->matchMgr->refuseReport($payload, 'ut')),
        );
        $before = $this->report_state($victim);
        try {
            foreach ($cases as $label => [$role, $action]) {
                if ($role === 'admin') {
                    $this->connect_as_admin();
                } else {
                    $this->connect_as_team_leader($victim['id_equipe_dom']);
                }
                try {
                    $action();
                    self::fail("$label : un code inconnu doit être refusé");
                } catch (Exception $e) {
                    self::assertStringContainsString('0 match', $e->getMessage(), "$label : " . $e->getMessage());
                }
                self::assertSame($before, $this->report_state($victim), "$label a modifié la victime");
            }
        } finally {
            $this->restore_report_state($victim, $before);
        }
    }
}

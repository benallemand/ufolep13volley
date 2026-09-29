<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Competition.php';
require_once __DIR__ . '/../classes/HallOfFame.php';
require_once __DIR__ . '/../classes/MatchMgr.php';
require_once __DIR__ . '/../classes/Players.php';
require_once __DIR__ . '/../classes/Team.php';
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../rest/error_response.php';

/**
 * Issue #355 — injections SQL atteignables par un compte connecté (et, en
 * priorité basse, par un admin), et messages MySQL renvoyés au client.
 *
 * Les failles passaient surtout par des helpers internes (`getTeamName`,
 * `getPlayersIdClub`, `getIdClubFromIdTeam`, `getUserLogin`…) appelés avec une
 * valeur du client : ils lient désormais leurs paramètres eux-mêmes.
 *
 * Comme pour le lot 2, les assertions sont discriminantes : la version
 * vulnérable levait aussi une exception — une erreur SQL, après avoir exécuté
 * la charge. On exige donc l'erreur de validation.
 *
 * Tous les tests sont en lecture seule.
 */
class SqlInjectionLot3Test extends UfolepTestCase
{
    private const CHARGE = '1 OR 1=1';

    private function assert_validation_error(callable $call, string $expected): void
    {
        try {
            $call();
            self::fail("La charge devait être refusée ($expected)");
        } catch (Exception $e) {
            self::assertStringContainsString($expected, $e->getMessage(),
                "Erreur inattendue (SQL ?) : la valeur n'est pas validée");
        }
    }

    // --- Generic::parse_id

    public function test_parse_id_accepte_un_entier(): void
    {
        self::assertSame(12, Generic::parse_id(12));
        self::assertSame(12, Generic::parse_id('12'));
        self::assertSame(12, Generic::parse_id(' 12 '));
    }

    public function test_parse_id_refuse_tout_le_reste(): void
    {
        foreach (array('12 OR 1=1', '12)', '', '-1', '1e3', '0x1', null, array(1), 1.5) as $value) {
            try {
                Generic::parse_id($value, 'identifiant de test');
                self::fail('parse_id devait refuser ' . json_encode($value));
            } catch (Exception $e) {
                self::assertSame('Identifiant de test invalide !', $e->getMessage());
            }
        }
    }

    // --- helpers appelés avec une valeur du client

    public function test_get_team_name_lie_l_identifiant(): void
    {
        $team = new Team();
        $this->assert_validation_error(fn() => $team->getTeamName(self::CHARGE), "Identifiant d'équipe invalide");
        self::assertSame('Non renseigné', $team->getTeamName(0));
        self::assertSame('Non renseigné', $team->getTeamName('999999999'));
    }

    public function test_get_id_club_from_id_team_lie_l_identifiant(): void
    {
        $team = new Team();
        $this->assert_validation_error(fn() => $team->getIdClubFromIdTeam(self::CHARGE), "Identifiant d'équipe invalide");
        self::assertNull($team->getIdClubFromIdTeam('999999999'));
    }

    public function test_get_user_login_lie_l_identifiant(): void
    {
        $this->assert_validation_error(fn() => (new UserManager())->getUserLogin(self::CHARGE), 'Identifiant de compte invalide');
    }

    public function test_get_players_id_club_lie_l_identifiant(): void
    {
        $this->assert_validation_error(fn() => (new Players())->getPlayersIdClub(self::CHARGE), 'Identifiant de joueur invalide');
    }

    public function test_get_players_from_team_lie_l_identifiant(): void
    {
        $players = new Players();
        $this->assert_validation_error(fn() => $players->getLivePlayersFromTeam(self::CHARGE), "Identifiant d'équipe invalide");
        self::assertSame(array(), $players->getLivePlayersFromTeam('999999999'));
    }

    public function test_get_players_par_match_valide_l_identifiant(): void
    {
        $this->assert_validation_error(fn() => (new Players())->getPlayers(null, self::CHARGE), 'Identifiant de match invalide');
    }

    public function test_joueurs_d_un_match_lient_l_identifiant(): void
    {
        $matchMgr = new MatchMgr();
        $this->assert_validation_error(fn() => $matchMgr->getMatchPlayers(self::CHARGE), 'Identifiant de match invalide');
        $this->assert_validation_error(fn() => $matchMgr->getNotMatchPlayers(self::CHARGE), 'Identifiant de match invalide');
        self::assertSame(array(), $matchMgr->getMatchPlayers('999999999'));
        self::assertSame(array(), $matchMgr->getNotMatchPlayers('999999999'));
    }

    public function test_hall_of_fame_lie_le_code_de_competition(): void
    {
        // Concaténée, la charge trouvait toutes les compétitions et poursuivait.
        $this->assert_validation_error(
            fn() => (new HallOfFame())->generateHallOfFameFromMatches("zz' OR '1'='1", '2025-01-01', '2025-06-30', 'p', 't'),
            'Compétition non trouvée');
    }

    public function test_reset_competition_ignore_les_identifiants_non_numeriques(): void
    {
        // `parse_id_list` écarte la charge : aucune compétition n'est touchée.
        (new Competition())->resetCompetition('999999999 OR 1=1');
        $this->addToAssertionCount(1);
    }

    // --- réponse d'erreur au client

    public function test_une_erreur_sql_n_est_plus_renvoyee_au_client(): void
    {
        try {
            $this->sql->execute("SELECT * FROM table_qui_n_existe_pas_355");
            self::fail('La requête devait échouer');
        } catch (Exception $e) {
            [$code, $message] = client_error_response($e);
            self::assertSame(500, $code);
            self::assertStringStartsWith('Erreur de base de données (réf. ', $message);
            self::assertStringNotContainsString('table_qui_n_existe_pas_355', $message);
        }
    }

    public function test_un_doublon_est_reformule(): void
    {
        $e = new mysqli_sql_exception("Duplicate entry 'Les sardines' for key 'register.new_team_name'", 1062);
        [$code, $message] = client_error_response($e);
        self::assertSame(409, $code);
        self::assertSame('La valeur « Les sardines » existe déjà !', $message);
        self::assertStringNotContainsString('register', $message);
    }

    public function test_une_contrainte_d_integrite_est_reformulee(): void
    {
        $e = new mysqli_sql_exception('Cannot delete or update a parent row: a foreign key constraint fails (`x`.`y`)', 1451);
        self::assertSame(array(409, "Opération impossible : des données liées s'y opposent !"), client_error_response($e));
    }

    public function test_les_messages_metier_et_codes_http_sont_conserves(): void
    {
        self::assertSame(array(403, 'Action réservée aux administrateurs !'),
            client_error_response(new Exception('Action réservée aux administrateurs !', 403)));
        self::assertSame(array(500, 'Erreur métier'), client_error_response(new Exception('Erreur métier')));
        // un errno MySQL n'est pas un code HTTP
        self::assertSame(500, client_error_response(new Exception('Autre', 1062))[0]);
    }
}

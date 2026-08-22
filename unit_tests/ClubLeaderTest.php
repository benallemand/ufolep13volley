<?php

require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/Club.php';
require_once __DIR__ . '/../classes/TimeSlot.php';
require_once __DIR__ . '/../classes/BlackListCourt.php';
require_once __DIR__ . '/../classes/BlackListTeam.php';
require_once __DIR__ . '/../classes/MatchMgr.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

/**
 * Issue #101 — socle du rôle responsable de club (issue #245 : le rôle est
 * dérivé de users_clubs, flag de session is_club_leader, cumulable).
 *
 * Un responsable de club gère toutes les équipes de son club. Le socle :
 *  - UserManager::isClubLeader() / isTeamManager() (responsable d'équipe OU de club)
 *  - Club::getMyClubId() résout le club depuis la session (posée au login depuis users_clubs)
 *  - Club::getMyClubTeams() liste les équipes du club
 *  - UserManager::switchCurrentUserTeam() autorise le club leader à basculer
 *    sur n'importe quelle équipe de SON club, et refuse les autres
 *  - les créneaux (TimeSlot) deviennent gérables par le club leader sur l'équipe
 *    sélectionnée
 *
 * Complété ensuite pour le multi-club et la gestion des équipes sans compte :
 *  - la session porte club_ids (tous les clubs) et id_club (le club courant),
 *    que UserManager::switchCurrentUserClub() fait varier
 *  - switchCurrentUserTeam() accepte une équipe du club SANS ligne users_teams
 *  - UserManager::getMyManageableTeams() liste les équipes sélectionnables
 */
class ClubLeaderTest extends UfolepTestCase
{
    private ?int $id_club = null;
    private array $club_team_ids = [];
    private ?int $foreign_team_id = null;
    private array $club_gymnasium_ids = [];
    private ?int $foreign_gymnasium_id = null;

    public function __construct()
    {
        parent::__construct();
        // Choisit dynamiquement un club ayant au moins 2 équipes ET des créneaux
        // (donc des gymnases), pour couvrir aussi la gestion des fermetures.
        $rows = $this->sql->execute(
            "SELECT e.id_club, COUNT(DISTINCT e.id_equipe) AS nb
             FROM equipes e
             JOIN clubs c ON c.id = e.id_club
             JOIN creneau cr ON cr.id_equipe = e.id_equipe
             GROUP BY e.id_club
             HAVING nb >= 2
             ORDER BY nb DESC
             LIMIT 1"
        );
        if (count($rows) > 0) {
            $this->id_club = (int)$rows[0]['id_club'];
            $teamRows = $this->sql->execute(
                "SELECT id_equipe FROM equipes WHERE id_club = " . $this->id_club . " ORDER BY id_equipe"
            );
            $this->club_team_ids = array_map('intval', array_column($teamRows, 'id_equipe'));
            $foreignRows = $this->sql->execute(
                "SELECT id_equipe FROM equipes WHERE id_club <> " . $this->id_club . " LIMIT 1"
            );
            if (count($foreignRows) > 0) {
                $this->foreign_team_id = (int)$foreignRows[0]['id_equipe'];
            }
            $gymRows = $this->sql->execute(
                "SELECT DISTINCT cr.id_gymnase
                 FROM creneau cr
                 JOIN equipes e ON e.id_equipe = cr.id_equipe
                 WHERE e.id_club = " . $this->id_club
            );
            $this->club_gymnasium_ids = array_map('intval', array_column($gymRows, 'id_gymnase'));
            if (count($this->club_gymnasium_ids) > 0) {
                $idsCsv = implode(',', $this->club_gymnasium_ids);
                $foreignGym = $this->sql->execute(
                    "SELECT id FROM gymnase WHERE id NOT IN ($idsCsv) LIMIT 1"
                );
                if (count($foreignGym) > 0) {
                    $this->foreign_gymnasium_id = (int)$foreignGym[0]['id'];
                }
            }
        }
    }

    public function test_isClubLeader_true_when_club_leader_connected()
    {
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $this->assertTrue(UserManager::isClubLeader());
        $this->assertFalse(UserManager::isTeamLeader());
    }

    public function test_getMyClubTeams_returns_only_teams_of_the_club()
    {
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $club = new Club();
        $teams = $club->getMyClubTeams();
        $this->assertNotEmpty($teams);
        // indicateur d'engagement (compétitions de la saison)
        $this->assertArrayHasKey('nb_competitions', $teams[0]);
        $this->assertArrayHasKey('competitions', $teams[0]);
        $returnedIds = array_map('intval', array_column($teams, 'id_equipe'));
        sort($returnedIds);
        $expected = $this->club_team_ids;
        sort($expected);
        $this->assertEquals($expected, $returnedIds);
    }

    public function test_getMyClubId_reads_session_club()
    {
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $club = new Club();
        $this->assertEquals($this->id_club, $club->getMyClubId());
    }

    // ---- Phase b : fermetures des gymnases du club -------------------------

    public function test_getMyClubGymnasiums_returns_gyms_used_by_club()
    {
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $blacklist = new BlackListCourt();
        $gyms = $blacklist->getMyClubGymnasiums();
        $this->assertNotEmpty($gyms);
        $returnedIds = array_map('intval', array_column($gyms, 'id'));
        sort($returnedIds);
        $expected = $this->club_gymnasium_ids;
        sort($expected);
        $this->assertEquals($expected, $returnedIds);
    }

    public function test_saveBlacklistGymnase_refuses_gym_outside_club()
    {
        if ($this->foreign_gymnasium_id === null) {
            $this->markTestSkipped("Pas de gymnase hors club disponible");
        }
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $blacklist = new BlackListCourt();
        $this->expectException(Exception::class);
        $blacklist->saveBlacklistGymnase($this->foreign_gymnasium_id, '01/01/2099');
    }

    public function test_club_leader_blacklist_gymnase_roundtrip()
    {
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $blacklist = new BlackListCourt();
        $gymId = $this->club_gymnasium_ids[0];
        $closedDate = '02/01/2099';
        $blacklist->saveBlacklistGymnase($gymId, $closedDate);
        // la fermeture apparaît dans la vue club
        $rows = $blacklist->getMyClubBlacklistGymnase();
        $match = array_filter($rows, static function ($r) use ($gymId, $closedDate) {
            return (int)$r['id_gymnase'] === $gymId && $r['closed_date'] === $closedDate;
        });
        $this->assertNotEmpty($match, "La fermeture créée doit apparaître dans la vue club");
        // nettoyage : suppression par le club leader
        $created = array_values($match)[0];
        $blacklist->removeBlacklistGymnase($created['id']);
        $rowsAfter = $blacklist->getMyClubBlacklistGymnase();
        $stillThere = array_filter($rowsAfter, static function ($r) use ($created) {
            return $r['id'] === $created['id'];
        });
        $this->assertEmpty($stillThere, "La fermeture doit avoir été supprimée");
    }

    // ---- Phase c : indisponibilités des équipes du club -------------------

    public function test_saveBlacklistTeam_refuses_team_outside_club()
    {
        if ($this->foreign_team_id === null) {
            $this->markTestSkipped("Pas d'équipe hors club disponible");
        }
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $blacklist = new BlackListTeam();
        $this->expectException(Exception::class);
        $blacklist->saveBlacklistTeam($this->foreign_team_id, '03/01/2099');
    }

    public function test_club_leader_blacklist_team_roundtrip()
    {
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $blacklist = new BlackListTeam();
        $teamId = $this->club_team_ids[0];
        $closedDate = '04/01/2099';
        $blacklist->saveBlacklistTeam($teamId, $closedDate);
        $rows = $blacklist->getMyClubBlacklistTeam();
        $match = array_filter($rows, static function ($r) use ($teamId, $closedDate) {
            return (int)$r['id_team'] === $teamId && $r['closed_date'] === $closedDate;
        });
        $this->assertNotEmpty($match, "L'indisponibilité créée doit apparaître dans la vue club");
        $created = array_values($match)[0];
        $blacklist->removeBlacklistTeam($created['id']);
        $rowsAfter = $blacklist->getMyClubBlacklistTeam();
        $stillThere = array_filter($rowsAfter, static function ($r) use ($created) {
            return $r['id'] === $created['id'];
        });
        $this->assertEmpty($stillThere, "L'indisponibilité doit avoir été supprimée");
    }

    // ---- Phase e : attribution des comptes RESPONSABLE_EQUIPE -------------

    public function test_getMyClubTeamLeaders_returns_only_club_teams()
    {
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $userManager = new UserManager();
        $rows = $userManager->getMyClubTeamLeaders();
        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertContains((int)$row['id_equipe'], $this->club_team_ids);
        }
    }

    public function test_attachClubTeamLeader_refuses_team_outside_club()
    {
        if ($this->foreign_team_id === null) {
            $this->markTestSkipped("Pas d'équipe hors club disponible");
        }
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $userManager = new UserManager();
        $this->expectException(Exception::class);
        $userManager->attachClubTeamLeader('ne.doit.pas.etre.cree@ufolep13.test', $this->foreign_team_id);
    }

    public function test_detachClubTeamLeader_refuses_team_outside_club()
    {
        if ($this->foreign_team_id === null) {
            $this->markTestSkipped("Pas d'équipe hors club disponible");
        }
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $userManager = new UserManager();
        $this->expectException(Exception::class);
        $userManager->detachClubTeamLeader(1, $this->foreign_team_id);
    }

    // ---- Phase d : agir en tant qu'un responsable d'équipe du club --------

    public function test_switch_to_club_team_leader_succeeds_and_switch_back_restores_club()
    {
        $row = $this->sql->execute(
            "SELECT e.id_club, ut.user_id, ut.team_id
             FROM users_teams ut
             JOIN equipes e ON e.id_equipe = ut.team_id
             LIMIT 1"
        );
        if (count($row) === 0) {
            $this->markTestSkipped("Aucun compte responsable d'équipe disponible");
        }
        $clubId = (int)$row[0]['id_club'];
        $targetUserId = (int)$row[0]['user_id'];

        $this->connect_as_club_leader($clubId);
        $userManager = new UserManager();
        $userManager->switch_to_club_team_leader($targetUserId);

        $this->assertTrue(UserManager::is_acting_as());
        $this->assertEquals($targetUserId, $_SESSION['id_user']);
        $this->assertTrue(UserManager::isTeamLeader());
        // l'équipe ciblée appartient au club
        $clubTeamIds = array_map('intval', array_column(
            $this->sql->execute("SELECT id_equipe FROM equipes WHERE id_club = $clubId"), 'id_equipe'));
        $this->assertContains((int)$_SESSION['id_equipe'], $clubTeamIds);

        // retour : on retrouve le compte club
        $userManager->switch_back_to_admin();
        $this->assertFalse(UserManager::is_acting_as());
        $this->assertTrue(UserManager::isClubLeader());
        $this->assertEquals($clubId, $_SESSION['id_club']);
    }

    public function test_switch_to_club_team_leader_refuses_account_outside_club()
    {
        $clubRow = $this->sql->execute(
            "SELECT e.id_club
             FROM users_teams ut
             JOIN equipes e ON e.id_equipe = ut.team_id
             LIMIT 1"
        );
        if (count($clubRow) === 0) {
            $this->markTestSkipped("Aucun compte responsable d'équipe disponible");
        }
        $clubId = (int)$clubRow[0]['id_club'];
        $foreign = $this->sql->execute(
            "SELECT ut.user_id
             FROM users_teams ut
             JOIN equipes e ON e.id_equipe = ut.team_id
             WHERE e.id_club <> $clubId
               AND ut.user_id NOT IN (
                   SELECT ut2.user_id FROM users_teams ut2
                   JOIN equipes e2 ON e2.id_equipe = ut2.team_id
                   WHERE e2.id_club = $clubId)
             LIMIT 1"
        );
        if (count($foreign) === 0) {
            $this->markTestSkipped("Aucun compte responsable hors club disponible");
        }
        $this->connect_as_club_leader($clubId);
        $userManager = new UserManager();
        $this->expectException(Exception::class);
        $userManager->switch_to_club_team_leader((int)$foreign[0]['user_id']);
    }

    // ---- Sélection directe d'une équipe du club (avec ou sans compte) ------

    /**
     * Une équipe du club sans aucun compte responsable rattaché doit rester
     * gérable par le responsable de club, sous sa propre identité.
     */
    public function test_switchCurrentUserTeam_allows_club_team_without_leader_account()
    {
        $row = $this->sql->execute(
            "SELECT e.id_club, e.id_equipe
             FROM equipes e
             WHERE e.id_club IS NOT NULL
               AND NOT EXISTS (SELECT 1 FROM users_teams ut WHERE ut.team_id = e.id_equipe)
             LIMIT 1"
        );
        if (count($row) === 0) {
            $this->markTestSkipped("Aucune équipe sans compte responsable disponible");
        }
        $id_club = (int)$row[0]['id_club'];
        $id_equipe = (int)$row[0]['id_equipe'];
        // le compte de session n'est rattaché à aucune équipe (users_teams)
        $this->connect_as_club_leader($id_club);
        $this->assertFalse(UserManager::isTeamLeader());

        (new UserManager())->switchCurrentUserTeam($id_equipe);

        $this->assertEquals($id_equipe, $_SESSION['id_equipe']);
        $this->assertTrue(UserManager::isTeamLeader());
        $this->assertEquals($id_club, $_SESSION['id_club']);
    }

    /**
     * Équipe d'un autre club, non rattachée au compte de session : sert de
     * contre-exemple sans que la voie « équipes du compte » ne s'en mêle.
     */
    private function pick_team_of_another_club(): ?array
    {
        $rows = $this->sql->execute(
            "SELECT e.id_equipe, e.id_club
             FROM equipes e
             WHERE e.id_club IS NOT NULL
               AND e.id_club <> " . $this->id_club . "
               AND e.id_equipe NOT IN (
                   SELECT ut.team_id FROM users_teams ut WHERE ut.user_id = " . (int)$_SESSION['id_user'] . ")
             LIMIT 1"
        );
        return count($rows) === 0 ? null : array(
            'id_equipe' => (int)$rows[0]['id_equipe'],
            'id_club' => (int)$rows[0]['id_club'],
        );
    }

    public function test_switchCurrentUserTeam_refuses_team_outside_my_clubs()
    {
        $this->connect_as_club_leader($this->id_club);
        $other = $this->pick_team_of_another_club();
        if ($other === null) {
            $this->markTestSkipped("Pas d'équipe hors club disponible");
        }
        $this->expectException(Exception::class);
        (new UserManager())->switchCurrentUserTeam($other['id_equipe']);
    }

    /**
     * Compte rattaché à 2 clubs : choisir une équipe du second club bascule
     * aussi le club courant, pour que les écrans club restent cohérents.
     */
    public function test_switchCurrentUserTeam_moves_current_club_to_the_team_club()
    {
        $this->connect_as_club_leader($this->id_club);
        $other = $this->pick_team_of_another_club();
        if ($other === null) {
            $this->markTestSkipped("Pas d'équipe d'un second club disponible");
        }
        $this->connect_as_club_leader(array($this->id_club, $other['id_club']));
        $this->assertEquals($this->id_club, $_SESSION['id_club']);

        (new UserManager())->switchCurrentUserTeam($other['id_equipe']);

        $this->assertEquals($other['id_equipe'], $_SESSION['id_equipe']);
        $this->assertEquals($other['id_club'], $_SESSION['id_club']);
    }

    /**
     * L'accès aux matchs se résout sur les équipes du compte ET sur l'équipe
     * courante : une équipe de club sélectionnée sans compte responsable doit
     * ouvrir l'accès à ses matchs.
     */
    public function test_selected_club_team_without_account_can_read_its_matches()
    {
        $row = $this->sql->execute(
            "SELECT m.id_match, e.id_club, e.id_equipe
             FROM matchs_view m
             JOIN equipes e ON e.id_equipe = m.id_equipe_dom
             WHERE e.id_club IS NOT NULL
               AND NOT EXISTS (SELECT 1 FROM users_teams ut WHERE ut.team_id = e.id_equipe)
             LIMIT 1"
        );
        if (count($row) === 0) {
            $this->markTestSkipped("Aucun match d'une équipe sans compte responsable disponible");
        }
        $this->connect_as_club_leader((int)$row[0]['id_club']);
        (new UserManager())->switchCurrentUserTeam((int)$row[0]['id_equipe']);
        $access = (new MatchMgr())->getMatchReadAccess($row[0]['id_match']);
        $this->assertTrue($access['allowed']);
    }

    public function test_getMyManageableTeams_contains_club_teams_without_account()
    {
        $this->connect_as_club_leader($this->id_club);
        $teams = (new UserManager())->getMyManageableTeams();
        $returnedIds = array_map('intval', array_column($teams, 'id_equipe'));
        $namedTeamIds = array_map('intval', array_column($this->sql->execute(
            "SELECT id_equipe FROM equipes
             WHERE id_club = " . $this->id_club . "
               AND NULLIF(nom_equipe, '') IS NOT NULL"), 'id_equipe'));
        foreach ($namedTeamIds as $id_equipe) {
            $this->assertContains($id_equipe, $returnedIds);
        }
        $this->assertArrayHasKey('has_leader_account', $teams[0]);
        $this->assertArrayHasKey('is_my_team', $teams[0]);
        $this->assertArrayHasKey('club_name', $teams[0]);
    }

    /**
     * Les lignes d'equipes sans nom sont des créations avortées : elles ne
     * doivent pas polluer le sélecteur d'équipe.
     */
    public function test_getMyManageableTeams_excludes_nameless_teams()
    {
        $nameless = $this->sql->execute(
            "SELECT id_equipe, id_club FROM equipes
             WHERE id_club IS NOT NULL AND NULLIF(nom_equipe, '') IS NULL
             LIMIT 1");
        if (count($nameless) === 0) {
            $this->markTestSkipped("Aucune équipe sans nom en base");
        }
        $this->connect_as_club_leader((int)$nameless[0]['id_club']);
        $returnedIds = array_map('intval', array_column(
            (new UserManager())->getMyManageableTeams(), 'id_equipe'));
        $this->assertNotContains((int)$nameless[0]['id_equipe'], $returnedIds);
    }

    // ---- Multi-club : liste des clubs et bascule du club courant -----------

    public function test_getMyClubIds_returns_every_managed_club()
    {
        $this->connect_as_club_leader($this->id_club);
        $other = $this->pick_team_of_another_club();
        if ($other === null) {
            $this->markTestSkipped("Pas d'équipe d'un second club disponible");
        }
        $this->connect_as_club_leader(array($this->id_club, $other['id_club']));
        $club = new Club();
        $this->assertEquals(array($this->id_club, $other['id_club']), $club->getMyClubIds());
        // le club courant reste le premier tant qu'on ne bascule pas
        $this->assertEquals($this->id_club, $club->getMyClubId());
        $returnedIds = array_map('intval', array_column($club->getMyClubs(), 'id'));
        sort($returnedIds);
        $expected = array($this->id_club, $other['id_club']);
        sort($expected);
        $this->assertEquals($expected, $returnedIds);
    }

    public function test_switchCurrentUserClub_switches_and_scopes_club_screens()
    {
        $this->connect_as_club_leader($this->id_club);
        $other = $this->pick_team_of_another_club();
        if ($other === null) {
            $this->markTestSkipped("Pas d'équipe d'un second club disponible");
        }
        $this->connect_as_club_leader(array($this->id_club, $other['id_club']));

        (new UserManager())->switchCurrentUserClub($other['id_club']);

        $this->assertEquals($other['id_club'], $_SESSION['id_club']);
        // les écrans club suivent le club courant
        $teamIds = array_map('intval', array_column((new Club())->getMyClubTeams(), 'id_equipe'));
        $this->assertContains($other['id_equipe'], $teamIds);
    }

    public function test_switchCurrentUserClub_refuses_unmanaged_club()
    {
        $foreignClub = $this->sql->execute(
            "SELECT id FROM clubs WHERE id <> " . $this->id_club . " LIMIT 1"
        );
        if (count($foreignClub) === 0) {
            $this->markTestSkipped("Pas de club hors périmètre disponible");
        }
        $this->connect_as_club_leader($this->id_club);
        $this->expectException(Exception::class);
        (new UserManager())->switchCurrentUserClub((int)$foreignClub[0]['id']);
    }

    /**
     * Bascule de club : une équipe sélectionnée qui n'appartient pas au nouveau
     * club est relâchée, pour ne pas gérer une équipe hors du club affiché.
     */
    public function test_switchCurrentUserClub_releases_team_of_another_club()
    {
        $this->connect_as_club_leader($this->id_club);
        $other = $this->pick_team_of_another_club();
        if ($other === null) {
            $this->markTestSkipped("Pas d'équipe d'un second club disponible");
        }
        $ownClubTeam = $this->sql->execute(
            "SELECT e.id_equipe
             FROM equipes e
             WHERE e.id_club = " . $this->id_club . "
               AND e.id_equipe NOT IN (
                   SELECT ut.team_id FROM users_teams ut WHERE ut.user_id = " . (int)$_SESSION['id_user'] . ")
             LIMIT 1"
        );
        if (count($ownClubTeam) === 0) {
            $this->markTestSkipped("Pas d'équipe du club détachée du compte de session");
        }
        $this->connect_as_club_leader(
            array($this->id_club, $other['id_club']), (int)$ownClubTeam[0]['id_equipe']);
        $_SESSION['is_team_leader'] = true;

        (new UserManager())->switchCurrentUserClub($other['id_club']);

        $this->assertEmpty($_SESSION['id_equipe']);
        $this->assertFalse(UserManager::isTeamLeader());
    }
}

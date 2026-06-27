<?php

require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/Club.php';
require_once __DIR__ . '/../classes/TimeSlot.php';
require_once __DIR__ . '/../classes/BlackListCourt.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

/**
 * Issue #101 — socle du profil RESPONSABLE_CLUB.
 *
 * Un RESPONSABLE_CLUB gère toutes les équipes de son club. Le socle :
 *  - UserManager::isClubLeader() / isTeamManager() (responsable d'équipe OU de club)
 *  - Club::getMyClubId() résout le club depuis la session (posée au login depuis users_clubs)
 *  - Club::getMyClubTeams() liste les équipes du club
 *  - UserManager::switchCurrentUserTeam() autorise le club leader à basculer
 *    sur n'importe quelle équipe de SON club, et refuse les autres
 *  - les créneaux (TimeSlot) deviennent gérables par le club leader sur l'équipe
 *    sélectionnée
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

    public function test_isTeamManager_true_for_both_team_and_club_leaders()
    {
        $this->connect_as_team_leader($this->club_team_ids[0]);
        $this->assertTrue(UserManager::isTeamManager());

        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $this->assertTrue(UserManager::isTeamManager());

        $this->connect_as_admin();
        $this->assertFalse(UserManager::isTeamManager());
    }

    public function test_getMyClubTeams_returns_only_teams_of_the_club()
    {
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $club = new Club();
        $teams = $club->getMyClubTeams();
        $this->assertNotEmpty($teams);
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

    public function test_switchCurrentUserTeam_allows_team_of_own_club()
    {
        $target = $this->club_team_ids[1];
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $userManager = new UserManager();
        $userManager->switchCurrentUserTeam($target);
        $this->assertEquals($target, $_SESSION['id_equipe']);
    }

    public function test_switchCurrentUserTeam_refuses_foreign_team()
    {
        if ($this->foreign_team_id === null) {
            $this->markTestSkipped("Pas d'équipe hors club disponible");
        }
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $userManager = new UserManager();
        $this->expectException(Exception::class);
        $userManager->switchCurrentUserTeam($this->foreign_team_id);
    }

    public function test_club_leader_can_read_timeslots_of_selected_team()
    {
        $this->connect_as_club_leader($this->id_club, $this->club_team_ids[0]);
        $timeSlot = new TimeSlot();
        // ne doit pas lever d'exception d'autorisation
        $result = $timeSlot->get_my_timeslots();
        $this->assertIsArray($result);
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
}

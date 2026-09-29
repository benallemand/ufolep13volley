<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/MatchMgr.php';
require_once __DIR__ . '/../classes/Players.php';

/**
 * Issue #343 — pas de photo, pas de match.
 *
 * Une équipe de deux joueurs : l'un avec une photo enregistrée, l'autre sans.
 * La photo « compte » dès qu'elle est enregistrée en base, que le fichier soit
 * ou non présent sur ce serveur (la CI n'a aucun fichier photo).
 */
class PlayerPhotoRequiredTest extends UfolepTestCase
{
    private MatchMgr $match_manager;
    private int $team;
    private int $id_match;
    private int $with_photo;
    private int $without_photo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->match_manager = new MatchMgr();
        $this->delete_test_data();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'uw', libelle = 'issue343 tests', id_compet_maitre = 'uw'");
        $teams = array();
        foreach (array('dom', 'ext') as $side) {
            $id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'issue343 club $side'");
            $teams[$side] = $this->sql->execute(
                "INSERT INTO equipes SET code_competition = 'uw', nom_equipe = 'issue343 $side', id_club = $id_club");
        }
        $this->team = $teams['dom'];
        $id_photo = $this->sql->execute("INSERT INTO photos SET path_photo = 'players_pics/issue343_absent_du_disque.jpg'");
        $this->with_photo = $this->sql->execute(
            "INSERT INTO joueurs SET nom = 'Issue343', prenom = 'Avec', sexe = 'M', id_photo = ?",
            array(array('type' => 'i', 'value' => $id_photo)));
        $this->without_photo = $this->sql->execute("INSERT INTO joueurs SET nom = 'Issue343', prenom = 'Sans', sexe = 'M'");
        foreach (array($this->with_photo, $this->without_photo) as $id_player) {
            $this->sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?", array(
                array('type' => 'i', 'value' => $id_player),
                array('type' => 'i', 'value' => $this->team),
            ));
        }
        $id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'issue343 court'");
        $this->id_match = $this->sql->execute(
            "INSERT INTO matches SET code_match = 'ISS343', code_competition = 'uw', division = '1',
                id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
                date_reception = CURRENT_DATE, date_original = CURRENT_DATE, match_status = 'CONFIRMED'",
            array(
                array('type' => 'i', 'value' => $teams['dom']),
                array('type' => 'i', 'value' => $teams['ext']),
                array('type' => 'i', 'value' => $id_court),
            ));
        $this->connect_as_team_leader($this->team);
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM match_player WHERE id_match IN (SELECT id_match FROM matches WHERE code_match = 'ISS343')");
        $this->sql->execute("DELETE FROM matches WHERE code_match = 'ISS343'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%ISS343%'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_joueur IN (SELECT id FROM joueurs WHERE nom = 'Issue343')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'Issue343'");
        $this->sql->execute("DELETE FROM photos WHERE path_photo = 'players_pics/issue343_absent_du_disque.jpg'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue343 %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'issue343 club %'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'issue343 court'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'uw'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function presents(): array
    {
        return array_map('intval', array_column($this->sql->execute(
            "SELECT id_player FROM match_player WHERE id_match = ? ORDER BY id_player",
            array(array('type' => 'i', 'value' => $this->id_match))), 'id_player'));
    }

    public function test_un_joueur_sans_photo_est_refuse_et_la_fiche_reste_intacte(): void
    {
        $this->match_manager->manage_match_players($this->id_match, array($this->with_photo));
        try {
            $this->match_manager->manage_match_players($this->id_match, array($this->with_photo, $this->without_photo));
            self::fail('Un joueur sans photo devait être refusé');
        } catch (Exception $e) {
            self::assertSame(409, $e->getCode());
            self::assertStringContainsString('ISSUE343 Sans', $e->getMessage());
        }
        self::assertSame(array($this->with_photo), $this->presents(), "Le refus ne doit pas vider la fiche");
    }

    public function test_un_renfort_sans_photo_est_refuse(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(409);
        $this->match_manager->manage_match_players($this->id_match, array($this->with_photo), $this->without_photo);
    }

    public function test_une_photo_enregistree_suffit_meme_sans_fichier(): void
    {
        $this->match_manager->manage_match_players($this->id_match, array($this->with_photo));
        self::assertSame(array($this->with_photo), $this->presents());
    }

    public function test_l_admin_peut_corriger_une_fiche(): void
    {
        $this->connect_as_admin();
        $this->match_manager->manage_match_players($this->id_match, array($this->with_photo, $this->without_photo));
        self::assertCount(2, $this->presents());
    }

    public function test_les_listes_de_joueurs_signalent_la_photo_manquante(): void
    {
        $by_id = array();
        foreach ($this->match_manager->getNotMatchPlayers($this->id_match) as $player) {
            $by_id[(int)$player['id']] = (int)$player['has_photo'];
        }
        self::assertSame(1, $by_id[$this->with_photo]);
        self::assertSame(0, $by_id[$this->without_photo]);
    }
}

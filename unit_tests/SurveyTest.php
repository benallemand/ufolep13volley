<?php
require_once __DIR__ . '/../classes/MatchMgr.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

class SurveyTest extends UfolepTestCase
{
    private MatchMgr $match_manager;
    private int $test_user_id;
    private int $test_match_id;

    private function create_test_data(): void
    {
        $this->delete_test_data();
        // `save_survey` relit le match par `matchs_view`, qui exige sa competition (#351)
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'us', libelle = 'survey tests', id_compet_maitre = 'us'");
        $this->test_user_id = $this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'survey_test_user', email = 'survey_test@test.fr', password_hash = MD5('test'), is_admin = 1");
        $id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'survey test club 1'");
        $id_club2 = $this->sql->execute("INSERT INTO clubs SET nom = 'survey test club 2'");
        $id_team1 = $this->sql->execute(
            "INSERT INTO equipes SET code_competition = 'us', nom_equipe = 'survey test team 1', id_club = $id_club");
        $id_team2 = $this->sql->execute(
            "INSERT INTO equipes SET code_competition = 'us', nom_equipe = 'survey test team 2', id_club = $id_club2");
        $this->sql->execute(
            "INSERT INTO users_teams SET user_id = ?, team_id = ?",
            array(
                array('type' => 'i', 'value' => $this->test_user_id),
                array('type' => 'i', 'value' => $id_team1),
            ));
        $id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'survey test court'");
        $this->test_match_id = $this->sql->execute(
            "INSERT INTO matches SET
                code_match = 'SURVEY_UT001',
                code_competition = 'us',
                division = '1',
                id_equipe_dom = $id_team1,
                id_equipe_ext = $id_team2,
                date_reception = CURRENT_DATE,
                id_gymnasium = $id_court,
                date_original = CURRENT_DATE,
                match_status = 'CONFIRMED'");
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM survey WHERE id_match IN (SELECT id FROM matches WHERE code_match = 'SURVEY_UT001')");
        $this->sql->execute("DELETE FROM matches WHERE code_match = 'SURVEY_UT001'");
        $this->sql->execute("DELETE FROM users_teams WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = 'survey_test_user')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login = 'survey_test_user'");
        $this->sql->execute("DELETE FROM classements WHERE code_competition = 'us'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'survey test team %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'survey test club %'");
        $this->sql->execute("DELETE FROM creneau WHERE id_gymnase IN (SELECT id FROM gymnase WHERE nom = 'survey test court')");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'survey test court'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'us'");
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->match_manager = new MatchMgr();
        $this->create_test_data();
        $this->connect_as_admin();
        $_SESSION['login'] = 'survey_test_user';
        $_SESSION['id_user'] = $this->test_user_id;
    }

    // --- Échelle -- - = + ++ stockée en -2..+2 (issue #350)

    private function save(array $ratings, ?string $comment = null, $id = null)
    {
        $ratings += array('on_time' => 0, 'spirit' => 0, 'referee' => 0, 'catering' => 0, 'global' => 0);
        return $this->match_manager->save_survey(
            id_match: $this->test_match_id,
            on_time: $ratings['on_time'],
            spirit: $ratings['spirit'],
            referee: $ratings['referee'],
            catering: $ratings['catering'],
            global: $ratings['global'],
            comment: $comment,
            id: $id
        );
    }

    /**
     * En boucle et non en `@dataProvider` : UfolepTestCase redéfinit le
     * constructeur sans transmettre les données du fournisseur.
     */
    public function test_save_survey_refuse_une_note_hors_echelle()
    {
        $cases = array(
            'ponctualité 3' => array('on_time', 3),
            'état d\'esprit -3' => array('spirit', -3),
            'arbitrage 10 (ancienne échelle)' => array('referee', 10),
            'apéro non entier' => array('catering', '1.5'),
            'global texte' => array('global', 'abc'),
        );
        foreach ($cases as $label => [$field, $value]) {
            try {
                $this->save(array($field => $value));
                self::fail("$label : la note devait être refusée");
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString("'$field'", $e->getMessage(), $label);
            }
        }
    }

    public function test_save_survey_accepte_les_bornes_de_l_echelle()
    {
        self::assertNotNull($this->save(array('on_time' => 2, 'spirit' => -1, 'global' => 1)));
        self::assertNotNull($this->save(array('referee' => -2), 'arbitre absent, match arbitré par un joueur'));
    }

    public function test_une_note_tres_insatisfaisante_exige_un_commentaire()
    {
        try {
            $this->save(array('spirit' => -2), '   ');
            self::fail('Une note à -- sans commentaire doit être refusée');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('commentaire', $e->getMessage());
        }
    }

    public function test_le_formulaire_neuf_est_prerempli_a_egal()
    {
        $survey = $this->match_manager->get_survey($this->test_match_id);
        self::assertNull($survey['id']);
        self::assertSame(2, $survey['scale_version']);
        foreach (array('on_time', 'spirit', 'referee', 'catering', 'global') as $field) {
            self::assertSame(0, $survey[$field], "$field doit être prérempli à =");
        }
    }

    public function test_un_sondage_enregistre_porte_l_echelle_courante()
    {
        $this->save(array('global' => 1));
        $survey = $this->match_manager->get_survey($this->test_match_id);
        self::assertNotNull($survey['id']);
        self::assertSame(2, (int)$survey['scale_version']);
        self::assertSame(1, (int)$survey['global']);
    }

    public function test_un_sondage_de_l_ancienne_echelle_n_est_pas_repris()
    {
        // un sondage 0..10 d'une saison passée : le formulaire repart à `=`
        $this->sql->execute(
            "INSERT INTO survey SET user_id = ?, id_match = ?, on_time = 8, spirit = 8, referee = 8, catering = 8, global = 8, scale_version = 1",
            array(
                array('type' => 'i', 'value' => $this->test_user_id),
                array('type' => 'i', 'value' => $this->test_match_id),
            ));
        $survey = $this->match_manager->get_survey($this->test_match_id);
        self::assertNull($survey['id']);
        self::assertSame(0, $survey['global']);
    }

    public function test_un_sondage_tout_a_egal_compte_pour_le_fair_play()
    {
        // L'ancien filtre « somme > 0 » de survey_view_raw écartait ce sondage.
        $this->sql->execute("INSERT INTO classements (code_competition, division, id_equipe)
                             SELECT 'us', '1', id_equipe FROM equipes WHERE nom_equipe = 'survey test team 2'");
        $this->save(array());
        $rows = $this->sql->execute("SELECT on_time, global FROM survey_view_raw WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $this->test_match_id)));
        self::assertCount(1, $rows);
        self::assertSame(0, (int)$rows[0]['global']);
    }

    public function test_l_ancienne_echelle_est_exclue_du_fair_play()
    {
        $this->sql->execute("INSERT INTO classements (code_competition, division, id_equipe)
                             SELECT 'us', '1', id_equipe FROM equipes WHERE nom_equipe = 'survey test team 2'");
        $this->sql->execute(
            "INSERT INTO survey SET user_id = ?, id_match = ?, on_time = 8, spirit = 8, referee = 8, catering = 8, global = 8, scale_version = 1",
            array(
                array('type' => 'i', 'value' => $this->test_user_id),
                array('type' => 'i', 'value' => $this->test_match_id),
            ));
        self::assertCount(0, $this->sql->execute("SELECT id FROM survey_view_raw WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $this->test_match_id))));
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }
}

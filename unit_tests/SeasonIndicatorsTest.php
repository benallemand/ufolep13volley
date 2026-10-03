<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';

/**
 * Indicateurs de saison corrigés par l'issue #397.
 *
 * Les équipes de test portent un nom en « IR ». Elles vivent dans une
 * compétition de test « ir », sauf pour « Joueurs dans plusieurs équipes »,
 * dont les seuils dépendent du championnat (m, f, mo).
 */
class SeasonIndicatorsTest extends UfolepTestCase
{
    private int $id_club;
    private int $id_gymnase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'ir', libelle = 'IR test', id_compet_maitre = 'ir'");
        $this->id_club = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'IR club'");
        $this->id_gymnase = (int)$this->sql->execute("INSERT INTO gymnase SET nom = 'IR gymnase', ville = 'Irville', nb_terrain = 1");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $teams = "SELECT id_equipe FROM equipes WHERE nom_equipe LIKE 'IR %'";
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'IRT%'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_equipe IN ($teams)");
        $this->sql->execute("DELETE FROM classements WHERE id_equipe IN ($teams)");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'IR %'");
        $this->sql->execute("DELETE FROM joueurs WHERE nom LIKE 'IRTEST%'");
        $this->sql->execute("DELETE FROM emails WHERE subject = 'IR test'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'IR gymnase'");
        $this->sql->execute("DELETE FROM clubs WHERE nom = 'IR club'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'ir'");
    }

    /** Début de la saison en cours : 1er juillet, comme `CalendarEvents::getCurrentSeason()`. */
    private function season_start(): string
    {
        $year = (int)date('n') <= 6 ? (int)date('Y') - 1 : (int)date('Y');
        return "$year-07-01";
    }

    private function team(string $name, string $competition = 'ir', bool $ranked = true, bool $with_slot = true,
                          ?int $id_club = -1): int
    {
        $id = (int)$this->sql->execute(
            "INSERT INTO equipes SET nom_equipe = ?, code_competition = ?, id_club = ?",
            array(
                array('type' => 's', 'value' => $name),
                array('type' => 's', 'value' => $competition),
                array('type' => 'i', 'value' => $id_club === -1 ? $this->id_club : $id_club),
            ));
        if ($ranked) {
            $this->sql->execute("INSERT INTO classements SET code_competition = ?, division = '1', id_equipe = ?",
                array(array('type' => 's', 'value' => $competition), array('type' => 'i', 'value' => $id)));
        }
        if ($with_slot) {
            $this->sql->execute("INSERT INTO creneau SET id_gymnase = ?, jour = 'Lundi', heure = '20:00', id_equipe = ?",
                array(array('type' => 'i', 'value' => $this->id_gymnase), array('type' => 'i', 'value' => $id)));
        }
        return $id;
    }

    private function match(string $code, int $dom, int $ext, string $date, string $status = 'ARCHIVED',
                           string $competition = 'ir'): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = ?, division = '1',
                                     id_equipe_dom = ?, id_equipe_ext = ?, date_reception = ?, match_status = ?",
            array(
                array('type' => 's', 'value' => $code),
                array('type' => 's', 'value' => $competition),
                array('type' => 'i', 'value' => $dom),
                array('type' => 'i', 'value' => $ext),
                array('type' => 's', 'value' => $date),
                array('type' => 's', 'value' => $status),
            ));
    }

    private function player(string $suffix, array $teams = array(), string $first_name = 'Test'): int
    {
        $id = (int)$this->sql->execute(
            "INSERT INTO joueurs SET nom = ?, prenom = ?, sexe = 'M', id_club = ?",
            array(
                array('type' => 's', 'value' => 'IRTEST' . $suffix),
                array('type' => 's', 'value' => $first_name),
                array('type' => 'i', 'value' => $this->id_club),
            ));
        foreach ($teams as $id_team) {
            $this->sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?",
                array(array('type' => 'i', 'value' => $id), array('type' => 'i', 'value' => $id_team)));
        }
        return $id;
    }

    private function rows(string $file): array
    {
        return $this->sql->execute(file_get_contents(__DIR__ . '/../sql/' . $file));
    }

    public function test_meme_reception_compare_les_deux_dernieres_rencontres_par_date(): void
    {
        $a = $this->team('IR Recoit');
        $b = $this->team('IR Voyage');
        $this->match('IRT1', $b, $a, date('Y-m-d', strtotime('-100 days')));
        $this->match('IRT2', $a, $b, date('Y-m-d', strtotime('-60 days')));
        $this->match('IRT3', $a, $b, date('Y-m-d', strtotime('-20 days')));
        // Même paire, autre compétition : ne compte pas dans la série.
        $this->match('IRT4', $b, $a, date('Y-m-d', strtotime('-10 days')), 'ARCHIVED', 'm');

        $rows = array_values(array_filter($this->rows('same_reception.sql'),
            static fn($r) => $r['recoit_deux_fois'] === 'IR Recoit'));

        $this->assertCount(1, $rows);
        $this->assertSame('IRT2', $rows[0]['avant_dernier_match']);
        $this->assertSame('IRT3', $rows[0]['dernier_match']);
        $this->assertSame('IR Voyage', $rows[0]['se_deplace_deux_fois']);
    }

    public function test_meme_reception_alternee_n_est_pas_signalee(): void
    {
        $a = $this->team('IR Recoit');
        $b = $this->team('IR Voyage');
        $this->match('IRT1', $a, $b, date('Y-m-d', strtotime('-60 days')));
        $this->match('IRT2', $b, $a, date('Y-m-d', strtotime('-20 days')));

        $this->assertSame(array(), array_filter($this->rows('same_reception.sql'),
            static fn($r) => str_starts_with($r['recoit_deux_fois'], 'IR ')));
    }

    public function test_equilibre_annuel_ignore_la_saison_passee(): void
    {
        $passee = $this->team('IR Saison passee');
        $courante = $this->team('IR Saison courante');
        $adverse = $this->team('IR Adverse');
        $avant = date('Y-m-d', strtotime($this->season_start() . ' -10 days'));
        $pendant = date('Y-m-d', strtotime($this->season_start() . ' +1 day'));
        for ($i = 1; $i <= 3; $i++) {
            $this->match("IRTP$i", $passee, $adverse, $avant);
            $this->match("IRTC$i", $courante, $adverse, $pendant);
        }

        $equipes = array_column(array_filter($this->rows('overall_equity_home_away.sql'),
            static fn($r) => str_starts_with($r['equipe'], 'IR ')), 'equipe');
        sort($equipes);

        $this->assertSame(array('IR Adverse', 'IR Saison courante'), $equipes);
    }

    public function test_plusieurs_equipes_le_seuil_suit_la_competition_pas_le_nom(): void
    {
        // Trois joueurs partagés entre deux équipes masculines : sous le seuil
        // (plus de 3). Le « f » de « Fafa » faisait croire à du féminin.
        $m1 = $this->team('IR Fafa 1', 'm');
        $m2 = $this->team('IR Fafa 2', 'm');
        // Trois joueurs partagés entre une équipe masculine et une féminine :
        // au-dessus du seuil (plus de 2).
        $m3 = $this->team('IR Mixe 1', 'm');
        $f1 = $this->team('IR Mixe 2', 'f');
        for ($i = 1; $i <= 3; $i++) {
            $this->player("M$i", array($m1, $m2));
            $this->player("F$i", array($m3, $f1));
        }

        $groupes = array_column(array_filter($this->rows('players_in_many_teams.sql'),
            static fn($r) => str_contains($r['equipes'], 'IR ')), 'equipes');

        $this->assertSame(array('IR Mixe 1 (m), IR Mixe 2 (f)'), $groupes);
    }

    public function test_plusieurs_equipes_deux_homonymes_ne_font_pas_un(): void
    {
        $f1 = $this->team('IR Homo 1', 'f');
        $f2 = $this->team('IR Homo 2', 'f');
        // Deux personnes distinctes, même nom et prénom, chacune dans une
        // seule équipe : personne n'est dans deux équipes.
        for ($i = 1; $i <= 3; $i++) {
            $this->player("H$i", array($f1), 'Homonyme');
            $this->player("H$i", array($f2), 'Homonyme');
        }

        $this->assertSame(array(), array_filter($this->rows('players_in_many_teams.sql'),
            static fn($r) => str_contains($r['equipes'], 'IR ')));
    }

    public function test_renforts_de_la_saison_avec_l_equipe_renforcee(): void
    {
        $dom = $this->team('IR Renforcee');
        $ext = $this->team('IR Adverse');
        $autre = $this->team('IR Autre');
        $renfort = $this->player('R', array($autre));
        $ancien = $this->player('A', array($autre));
        $pendant = date('Y-m-d', strtotime($this->season_start() . ' +1 day'));
        $avant = date('Y-m-d', strtotime($this->season_start() . ' -10 days'));
        $courant = $this->match('IRTR1', $dom, $ext, $pendant);
        $passe = $this->match('IRTR2', $dom, $ext, $avant);
        $this->sql->execute("INSERT INTO match_player SET id_match = ?, id_player = ?, id_team_reinforced = ?", array(
            array('type' => 'i', 'value' => $courant),
            array('type' => 'i', 'value' => $renfort),
            array('type' => 'i', 'value' => $dom),
        ));
        // Saison passée : hors du décompte.
        $this->sql->execute("INSERT INTO match_player SET id_match = ?, id_player = ?", array(
            array('type' => 'i', 'value' => $passe),
            array('type' => 'i', 'value' => $ancien),
        ));

        $rows = array_values(array_filter($this->rows('matchs_with_reinforcement.sql'),
            static fn($r) => str_starts_with((string)$r['matchs'], 'IRT')));

        $this->assertCount(1, $rows);
        $this->assertSame('Test IRTESTR', $rows[0]['joueur']);
        $this->assertSame('IR Renforcee', $rows[0]['equipes_renforcees']);
        $this->assertSame('IR Autre (ir1)', $rows[0]['ses_equipes']);
    }

    public function test_doublons_une_ligne_par_joueur_avec_son_identifiant(): void
    {
        $premier = $this->player('DOUBLON', array(), 'Jean Marc');
        $second = $this->player('DOUBLON', array(), 'JeanMarc');

        $ids = array_map('intval', array_column(array_filter($this->rows('player_duplicates.sql'),
            static fn($r) => str_starts_with($r['nom'], 'IRTEST')), 'indicator_id'));
        sort($ids);

        $this->assertSame(array($premier, $second), $ids);
    }

    public function test_club_non_renseigne_couvre_le_mixte(): void
    {
        $this->team('IR Mixte sans club', 'mo', true, false, null);

        $equipes = array_column($this->rows('teams_without_club.sql'), 'nom_equipe');

        $this->assertContains('IR Mixte sans club', $equipes);
    }

    public function test_emails_en_erreur_sans_corps_avec_identifiant(): void
    {
        $id = (int)$this->sql->execute(
            "INSERT INTO emails SET from_email = 'a@ufolep.test', to_email = 'b@ufolep.test', cc = '', bcc = '',
                                    subject = 'IR test', body = '<p>corps</p>', sending_status = 'ERROR'");

        $rows = array_values(array_filter($this->rows('email_errors.sql'),
            static fn($r) => $r['sujet'] === 'IR test'));

        $this->assertCount(1, $rows);
        $this->assertEquals($id, $rows[0]['indicator_id']);
        $this->assertArrayNotHasKey('body', $rows[0]);
    }
}

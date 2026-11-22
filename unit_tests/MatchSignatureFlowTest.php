<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/MatchMgr.php';

/**
 * Issue #490 — page de match unique : les deux équipes saisissent sur la même
 * page, sans pouvoir effacer la saisie de l'autre.
 *
 * - chaque enregistrement porte la version du match : périmée, il est refusé ;
 * - la première signature de la fiche équipes fige les présents ;
 * - chaque équipe annule sa propre signature, aux conditions de la page ;
 * - la commission annule les deux.
 */
class MatchSignatureFlowTest extends UfolepTestCase
{
    private MatchMgr $match_manager;
    private array $team = array();
    private array $player = array();
    private int $id_match;

    protected function setUp(): void
    {
        parent::setUp();
        $this->match_manager = new MatchMgr();
        $this->delete_test_data();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'uv', libelle = 'issue490 tests', id_compet_maitre = 'uv'");
        $id_photo = $this->sql->execute("INSERT INTO photos SET path_photo = 'players_pics/issue490_absent_du_disque.jpg'");
        foreach (array('dom', 'ext') as $side) {
            $id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'issue490 club $side'");
            $this->team[$side] = (int)$this->sql->execute(
                "INSERT INTO equipes SET code_competition = 'uv', nom_equipe = 'issue490 $side', id_club = $id_club");
            // 3 présents par équipe au moins pour pouvoir signer (match_players_count_view)
            foreach (array(1, 2, 3, 4) as $n) {
                $id = (int)$this->sql->execute(
                    "INSERT INTO joueurs SET nom = 'Issue490', prenom = ?, sexe = 'M', id_photo = ?",
                    array(array('type' => 's', 'value' => "$side$n"), array('type' => 'i', 'value' => $id_photo)));
                $this->sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?", array(
                    array('type' => 'i', 'value' => $id),
                    array('type' => 'i', 'value' => $this->team[$side]),
                ));
                $this->player["$side$n"] = $id;
            }
        }
        // responsable de l'équipe extérieure, avec email : destinataire des avis
        $this->sql->execute("UPDATE joueurs SET email = 'issue490_ext@test.fr' WHERE id = ?",
            array(array('type' => 'i', 'value' => $this->player['ext1'])));
        $this->sql->execute("UPDATE joueur_equipe SET is_leader = 1 WHERE id_joueur = ?",
            array(array('type' => 'i', 'value' => $this->player['ext1'])));
        $id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'issue490 court'");
        $this->id_match = (int)$this->sql->execute(
            "INSERT INTO matches SET code_match = 'ISS490', code_competition = 'uv', division = '1',
                id_equipe_dom = ?, id_equipe_ext = ?, id_gymnasium = ?,
                date_reception = CURRENT_DATE, date_original = CURRENT_DATE, match_status = 'CONFIRMED'",
            array(
                array('type' => 'i', 'value' => $this->team['dom']),
                array('type' => 'i', 'value' => $this->team['ext']),
                array('type' => 'i', 'value' => $id_court),
            ));
        $this->as_leader('dom');
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM match_player WHERE id_match IN (SELECT id_match FROM matches WHERE code_match = 'ISS490')");
        $this->sql->execute("DELETE FROM matches WHERE code_match = 'ISS490'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%ISS490%'");
        $this->sql->execute("DELETE FROM emails WHERE subject LIKE '%ISS490%'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_joueur IN (SELECT id FROM joueurs WHERE nom = 'Issue490')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'Issue490'");
        $this->sql->execute("DELETE FROM photos WHERE path_photo = 'players_pics/issue490_absent_du_disque.jpg'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue490 %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'issue490 club %'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'issue490 court'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'uv'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function as_leader(string $side): void
    {
        $this->connect_as_team_leader($this->team[$side]);
    }

    private function presents(): array
    {
        return array_map('intval', array_column($this->sql->execute(
            "SELECT id_player FROM match_player WHERE id_match = ? ORDER BY id_player",
            array(array('type' => 'i', 'value' => $this->id_match))), 'id_player'));
    }

    private function flags(): array
    {
        return array_map('intval', $this->sql->execute(
            "SELECT is_sign_team_dom + 0 AS td, is_sign_team_ext + 0 AS te,
                    is_sign_match_dom + 0 AS md, is_sign_match_ext + 0 AS me
             FROM matches WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $this->id_match)))[0]);
    }

    private function set_flags(array $flags): void
    {
        foreach ($flags as $column => $value) {
            $this->sql->execute("UPDATE matches SET $column = ? WHERE id_match = ?", array(
                array('type' => 'i', 'value' => $value),
                array('type' => 'i', 'value' => $this->id_match),
            ));
        }
    }

    private function version(): string
    {
        return $this->match_manager->get_match_version($this->id_match)['version'];
    }

    /** Signer / annuler finit par une exception 200 « … » : on la consomme. */
    private function call(callable $action): void
    {
        try {
            $action();
        } catch (Exception $e) {
            if ($e->getCode() !== 200) {
                throw $e;
            }
        }
    }

    private function players_dom_ext(): array
    {
        return array($this->player['dom1'], $this->player['dom2'], $this->player['dom3'],
            $this->player['ext1'], $this->player['ext2'], $this->player['ext3']);
    }

    // --- Version : rien n'écrase la saisie de l'autre équipe

    public function test_un_responsable_saisit_les_presents_des_deux_equipes(): void
    {
        $this->match_manager->manage_match_players($this->id_match, $this->players_dom_ext(), null, null, $this->version());
        self::assertCount(6, $this->presents());
    }

    public function test_une_liste_perimee_n_efface_pas_la_saisie_de_l_autre_equipe(): void
    {
        $version_dom = $this->version();
        // l'équipe extérieure enregistre pendant que la page de l'équipe à domicile est ouverte
        $this->as_leader('ext');
        $this->match_manager->manage_match_players($this->id_match, $this->players_dom_ext(), null, null, $this->version());
        $this->as_leader('dom');
        try {
            $this->match_manager->manage_match_players($this->id_match, array($this->player['dom1']), null, null, $version_dom);
            self::fail('Une liste périmée devait être refusée');
        } catch (Exception $e) {
            self::assertSame(409, $e->getCode());
            self::assertStringContainsString("modifié entre-temps", $e->getMessage());
        }
        self::assertCount(6, $this->presents(), "La saisie de l'autre équipe est intacte");
    }

    public function test_on_ne_signe_pas_une_liste_changee_entre_temps(): void
    {
        $this->match_manager->manage_match_players($this->id_match, $this->players_dom_ext(), null, null, $this->version());
        $seen = $this->version();
        $this->as_leader('ext');
        $this->match_manager->manage_match_players($this->id_match,
            array_merge($this->players_dom_ext(), array($this->player['dom4'])), null, null, $this->version());
        $this->as_leader('dom');
        try {
            $this->match_manager->sign_team_sheet($this->id_match, $seen);
            self::fail('La signature devait être refusée');
        } catch (Exception $e) {
            self::assertSame(409, $e->getCode());
        }
        self::assertSame(0, $this->flags()['td']);
    }

    public function test_la_version_change_avec_les_presents_le_score_et_les_signatures(): void
    {
        $v0 = $this->version();
        $this->match_manager->manage_match_players($this->id_match, $this->players_dom_ext(), null, null, $v0);
        $v1 = $this->version();
        self::assertNotSame($v0, $v1);
        $this->set_flags(array('is_sign_team_ext' => 1));
        self::assertNotSame($v1, $this->version());
    }

    public function test_sans_version_pas_de_controle(): void
    {
        // anciens appels et administration : comportement inchangé
        $this->match_manager->manage_match_players($this->id_match, $this->players_dom_ext());
        self::assertCount(6, $this->presents());
    }

    public function test_tous_les_presents_peuvent_etre_retires(): void
    {
        $this->match_manager->manage_match_players($this->id_match, $this->players_dom_ext());
        $this->match_manager->manage_match_players($this->id_match, null, null, null, $this->version());
        self::assertSame(array(), $this->presents());
    }

    // --- La première signature fige les présents

    public function test_la_premiere_signature_fige_les_presents(): void
    {
        $this->match_manager->manage_match_players($this->id_match, $this->players_dom_ext());
        $this->call(fn() => $this->match_manager->sign_team_sheet($this->id_match, $this->version()));
        $this->as_leader('ext');
        $this->expectExceptionMessage('Déjà signé');
        $this->match_manager->manage_match_players($this->id_match, array($this->player['ext1']));
    }

    // --- Annulation de sa signature

    public function test_annuler_sa_signature_de_fiche_equipes(): void
    {
        $this->set_flags(array('is_sign_team_dom' => 1));
        $this->call(fn() => $this->match_manager->unsign_team_sheet($this->id_match, $this->version()));
        self::assertSame(0, $this->flags()['td']);
        $log = $this->sql->execute("SELECT id_team FROM activity WHERE comment LIKE 'Signature annulée : Fiche équipes de issue490 dom%ISS490'");
        self::assertCount(1, $log);
        self::assertSame($this->team['dom'], (int)$log[0]['id_team']);
        self::assertCount(1, $this->sql->execute("SELECT id FROM emails WHERE subject LIKE '%ISS490 : signature annulée par issue490 dom%'"),
            "l'autre équipe est prévenue");
    }

    public function test_les_presents_restent_figes_tant_que_l_autre_n_a_pas_annule(): void
    {
        $this->match_manager->manage_match_players($this->id_match, $this->players_dom_ext());
        $this->set_flags(array('is_sign_team_dom' => 1, 'is_sign_team_ext' => 1));
        $this->call(fn() => $this->match_manager->unsign_team_sheet($this->id_match));
        try {
            $this->match_manager->manage_match_players($this->id_match, array($this->player['dom1']));
            self::fail('Les présents restent figés par la signature de l\'autre équipe');
        } catch (Exception $e) {
            self::assertStringContainsString('Déjà signé', $e->getMessage());
        }
        $this->as_leader('ext');
        $this->call(fn() => $this->match_manager->unsign_team_sheet($this->id_match));
        // plus aucune signature : les présents redeviennent modifiables, puis on signe de nouveau
        $corrected = array_merge($this->players_dom_ext(), array($this->player['dom4']));
        $this->match_manager->manage_match_players($this->id_match, $corrected, null, null, $this->version());
        self::assertCount(7, $this->presents());
        $this->call(fn() => $this->match_manager->sign_team_sheet($this->id_match, $this->version()));
        self::assertSame(1, $this->flags()['te']);
    }

    public function test_pas_d_annulation_de_fiche_apres_la_feuille_de_match(): void
    {
        $this->set_flags(array('is_sign_team_dom' => 1, 'is_sign_match_ext' => 1));
        try {
            $this->match_manager->unsign_team_sheet($this->id_match);
            self::fail("L'annulation devait être refusée");
        } catch (Exception $e) {
            self::assertStringContainsString('la feuille de match est déjà signée', $e->getMessage());
        }
        self::assertSame(1, $this->flags()['td']);
    }

    public function test_annuler_sa_feuille_de_match_tant_que_l_autre_n_a_pas_signe(): void
    {
        $this->set_flags(array('is_sign_team_dom' => 1, 'is_sign_match_dom' => 1));
        $this->call(fn() => $this->match_manager->unsign_match_sheet($this->id_match));
        self::assertSame(0, $this->flags()['md']);
        self::assertSame(1, $this->flags()['td'], 'la fiche équipes reste signée');
    }

    public function test_match_clos_quand_les_deux_ont_signe_la_feuille(): void
    {
        $this->set_flags(array('is_sign_match_dom' => 1, 'is_sign_match_ext' => 1));
        try {
            $this->match_manager->unsign_match_sheet($this->id_match);
            self::fail("L'annulation devait être refusée");
        } catch (Exception $e) {
            self::assertStringContainsString('le match est clos', $e->getMessage());
        }
        self::assertSame(1, $this->flags()['md']);
    }

    public function test_on_n_annule_que_sa_propre_signature(): void
    {
        $this->set_flags(array('is_sign_team_ext' => 1));
        try {
            $this->match_manager->unsign_team_sheet($this->id_match);
            self::fail("La signature de l'autre équipe ne s'annule pas");
        } catch (Exception $e) {
            self::assertStringContainsString("n'a pas signé", $e->getMessage());
        }
        self::assertSame(1, $this->flags()['te']);
    }

    public function test_pas_d_annulation_sur_un_match_certifie(): void
    {
        $this->set_flags(array('is_sign_team_dom' => 1, 'certif' => 1));
        $this->expectExceptionMessage('certifié');
        $this->match_manager->unsign_team_sheet($this->id_match);
    }

    public function test_la_commission_annule_les_deux_signatures(): void
    {
        $this->set_flags(array('is_sign_match_dom' => 1, 'is_sign_match_ext' => 1));
        $this->connect_as_admin();
        $this->call(fn() => $this->match_manager->unsign_match_sheet($this->id_match));
        $flags = $this->flags();
        self::assertSame(array(0, 0), array($flags['md'], $flags['me']));
        self::assertCount(2, $this->sql->execute("SELECT id FROM activity WHERE comment LIKE '%(par la commission)%ISS490'"));
    }

    public function test_une_annulation_perimee_est_refusee(): void
    {
        $this->set_flags(array('is_sign_team_dom' => 1));
        $seen = $this->version();
        $this->set_flags(array('is_sign_team_ext' => 1));
        $this->expectExceptionCode(409);
        $this->match_manager->unsign_team_sheet($this->id_match, $seen);
    }

    // --- Qui ajoute ou retire qui (issue #492)

    private function activity_of(string $side): array
    {
        return array_column($this->sql->execute(
            "SELECT comment FROM activity WHERE id_team = ? AND comment LIKE '%ISS490' ORDER BY id",
            array(array('type' => 'i', 'value' => $this->team[$side]))), 'comment');
    }

    private function emails_to_ext(): int
    {
        return count($this->sql->execute(
            "SELECT id FROM emails WHERE subject LIKE '%ISS490 : présents de issue490 ext modifiés par%'"));
    }

    /** Cas de Meyrargues : l'adversaire complète nos présents, on le sait et on est prévenu. */
    public function test_l_adversaire_qui_modifie_nos_presents_est_trace_et_nous_previent(): void
    {
        $this->match_manager->manage_match_players($this->id_match, $this->players_dom_ext());
        self::assertSame(array('Présents de issue490 ext : issue490 dom ajoute ext1 Issue490, ext2 Issue490, ext3 Issue490 — match ISS490'),
            $this->activity_of('ext'));
        self::assertSame(array('Présents de issue490 dom : issue490 dom ajoute dom1 Issue490, dom2 Issue490, dom3 Issue490 — match ISS490'),
            $this->activity_of('dom'));
        self::assertSame(1, $this->emails_to_ext(), "l'équipe extérieure est prévenue");
        $body = $this->sql->execute("SELECT body FROM emails WHERE subject LIKE '%ISS490 : présents de issue490 ext%'")[0]['body'];
        self::assertStringContainsString('joueurs ajoutés : ext1 Issue490, ext2 Issue490, ext3 Issue490', $body);
        self::assertStringContainsString('match.html?id_match=' . $this->id_match . '#presents', $body);

        // l'adversaire retire un joueur et en ajoute un autre
        $this->match_manager->manage_match_players($this->id_match, array($this->player['dom1'], $this->player['dom2'],
            $this->player['dom3'], $this->player['ext1'], $this->player['ext2'], $this->player['ext4']));
        self::assertSame('Présents de issue490 ext : issue490 dom ajoute ext4 Issue490 ; retire ext3 Issue490 — match ISS490',
            $this->activity_of('ext')[1]);
        self::assertSame(2, $this->emails_to_ext());
    }

    public function test_ses_propres_presents_sont_traces_sans_email(): void
    {
        $this->as_leader('ext');
        $this->match_manager->manage_match_players($this->id_match, array($this->player['ext1'], $this->player['ext2']));
        self::assertSame(array('Présents de issue490 ext : issue490 ext ajoute ext1 Issue490, ext2 Issue490 — match ISS490'),
            $this->activity_of('ext'));
        self::assertSame(0, $this->emails_to_ext(), 'pas d\'email pour ses propres présents');
    }

    public function test_un_enregistrement_sans_changement_est_trace(): void
    {
        $this->match_manager->manage_match_players($this->id_match, array($this->player['dom1']));
        $this->match_manager->manage_match_players($this->id_match, array($this->player['dom1']));
        self::assertSame('Présents enregistrés sans changement — match ISS490', $this->activity_of('dom')[1]);
    }

    public function test_la_commission_qui_corrige_previent_l_equipe(): void
    {
        $this->connect_as_admin();
        $this->match_manager->manage_match_players($this->id_match, array($this->player['ext1']));
        self::assertSame(array('Présents de issue490 ext : la commission ajoute ext1 Issue490 — match ISS490'),
            $this->activity_of('ext'));
        self::assertSame(1, $this->emails_to_ext());
    }
}

<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . "/../classes/MatchMgr.php";
require_once __DIR__ . "/../classes/Players.php";
require_once __DIR__ . "/../classes/Team.php";

/**
 * Issue #270, lot 1 — injections SQL atteignables sans rôle administrateur.
 *
 * Le critère de périmètre est le niveau déclaré dans `rest/access.php` : un
 * endpoint `public` ou `user` est joignable par un responsable d'équipe, un
 * endpoint `admin` ne l'est pas. Les méthodes couvertes ici étaient atteintes
 * depuis des entrées `public` ou `user`, avec une valeur fournie par le client.
 *
 * Tous les tests sont en lecture seule : aucune donnée créée ni supprimée.
 */
class SqlInjectionLot1Test extends UfolepTestCase
{
    private MatchMgr $matchMgr;
    private Players $players;
    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matchMgr = new MatchMgr();
        $this->players = new Players();
        $this->team = new Team();
    }

    /**
     * `Team::download_calendar()` compose une clause WHERE a la main. L'endpoint
     * est `public` : avec `?id=695 OR 1=1`, il renvoyait le calendrier de TOUTES
     * les equipes au lieu d'une seule.
     */
    public function test_download_calendar_refuse_un_identifiant_non_numerique(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Identifiant d'équipe invalide");
        $this->team->download_calendar('695 OR 1=1');
    }

    /**
     * `MatchMgr::get_match()` : meme motif, atteint par 8 endpoints dont un public.
     */
    public function test_get_match_refuse_un_identifiant_non_numerique(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Identifiant de match invalide");
        $this->matchMgr->get_match('1 OR 1=1');
    }

    /**
     * `get_match_by_code_match()` interpole dans une chaine quotee : la charge
     * doit etre echappee, donc ne correspondre a aucun match — et surtout pas
     * les faire tous remonter.
     */
    public function test_get_match_by_code_match_echappe_la_charge(): void
    {
        try {
            $this->matchMgr->get_match_by_code_match("' OR '1'='1");
            self::fail("Une charge d'injection ne doit correspondre à aucun match");
        } catch (Exception $e) {
            // Le message compte le nombre de matchs trouves : 0 prouve que la
            // charge n'a pas ete interpretee comme du SQL.
            self::assertStringContainsString('0 match', $e->getMessage());
        }
    }

    /**
     * `Players::isPlayerInTeam()` est appele avec l'id fourni par le client
     * depuis les actions du responsable d'equipe (set_leader, set_captain…).
     *
     * On part d'un couple (joueur, equipe) qui n'existe PAS, puis on y greffe
     * `OR 1=1`. Concatenee, la charge rendait la condition vraie ; liee, elle est
     * castee en entier et le resultat ne change pas.
     *
     * Le couple est cherche en base a l'execution : asserter une valeur en dur
     * dependrait du jeu de donnees (la premiere version de ce test passait en
     * local et echouait sur le seed de la CI).
     */
    public function test_is_player_in_team_neutralise_une_charge(): void
    {
        $rows = $this->sql->execute(
            "SELECT j.id AS id_joueur, e.id_equipe
             FROM joueurs j
             CROSS JOIN equipes e
             WHERE NOT EXISTS (
                 SELECT 1 FROM joueur_equipe je
                 WHERE je.id_joueur = j.id AND je.id_equipe = e.id_equipe
             )
             LIMIT 1"
        );
        self::assertNotEmpty($rows, 'Il faut un couple joueur/équipe non lié pour ce test');
        $id_joueur = (int)$rows[0]['id_joueur'];
        $id_equipe = (int)$rows[0]['id_equipe'];

        // Reference : le couple n'est pas lie
        self::assertFalse($this->players->isPlayerInTeam($id_joueur, $id_equipe));

        // Meme couple, avec une charge greffee : le resultat doit etre identique
        self::assertFalse(
            $this->players->isPlayerInTeam("$id_joueur OR 1=1", $id_equipe),
            "La charge d'injection a modifié le résultat : la valeur n'est pas liée"
        );
    }

    /**
     * `Players::get_player()` est protege par son typage `int` : PHP 8 leve une
     * TypeError sur une chaine non numerique. Test de non-regression au cas ou
     * la signature serait relachee.
     */
    public function test_get_player_est_protege_par_son_typage(): void
    {
        $this->expectException(TypeError::class);
        /** @noinspection PhpParamsInspection */
        $this->players->get_player('1 OR 1=1');
    }

    /**
     * Le chemin nominal doit continuer de fonctionner : une charge bloquee ne
     * vaut rien si elle bloque aussi les appels legitimes.
     */
    public function test_les_appels_legitimes_fonctionnent_toujours(): void
    {
        $rows = $this->sql->execute(
            "SELECT id_equipe FROM equipes ORDER BY id_equipe LIMIT 1"
        );
        self::assertNotEmpty($rows, 'La base de test doit contenir au moins une équipe');
        $id_equipe = (int)$rows[0]['id_equipe'];

        // Ne doit pas lever : l'identifiant est valide
        $this->players->isPlayerInTeam(0, $id_equipe);
        self::assertTrue(true);
    }
}

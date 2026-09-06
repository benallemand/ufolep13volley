<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . "/../classes/Competition.php";
require_once __DIR__ . "/../classes/LimitDate.php";
require_once __DIR__ . "/../classes/Players.php";
require_once __DIR__ . "/../classes/Rank.php";

/**
 * Issue #270, lot 2 — injections SQL sur les chemins réservés aux administrateurs.
 *
 * Sévérité moindre que le lot 1 (il faut un compte admin), même nature.
 *
 * Les assertions cherchent à être **discriminantes** : constater qu'une charge
 * lève une exception ne prouve rien, puisque la version vulnérable en lève une
 * aussi (mais après avoir exécuté du SQL arbitraire). On distingue donc l'erreur
 * métier attendue d'une erreur SQL, ou on compte les lignes remontées.
 *
 * Tous les tests sont en lecture seule.
 */
class SqlInjectionLot2Test extends UfolepTestCase
{
    private Rank $rank;
    private Players $players;
    private Competition $competition;
    private LimitDate $limitDate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rank = new Rank();
        $this->players = new Players();
        $this->competition = new Competition();
        $this->limitDate = new LimitDate();
    }

    /**
     * `Players::getPlayers($query)` compose `LIKE '%$query%'` dans une clause
     * passee a `get_players()`. Test discriminant : concatenee, la charge fait
     * remonter TOUS les joueurs ; echappee, elle n'en trouve aucun.
     */
    public function test_recherche_joueurs_echappe_la_charge(): void
    {
        $total = (int)$this->sql->execute("SELECT COUNT(*) AS cnt FROM joueurs")[0]['cnt'];
        self::assertGreaterThan(0, $total, 'La base de test doit contenir des joueurs');

        $results = $this->players->getPlayers("' OR '1'='1");

        self::assertLessThan(
            $total,
            count($results),
            "La charge a fait remonter tous les joueurs : le terme n'est pas échappé"
        );
    }

    /**
     * `Competition::isCompetitionStarted($id)` : le parametre est desormais lie
     * en entier. Une charge non numerique est castee en 0, donc aucune
     * competition ne correspond et l'erreur metier est levee. Concatenee, la
     * requete `WHERE id = abc` aurait produit une erreur SQL.
     */
    public function test_is_competition_started_lie_l_identifiant(): void
    {
        try {
            $this->competition->isCompetitionStarted('abc');
            self::fail('Une compétition inexistante doit lever une exception');
        } catch (Exception $e) {
            self::assertStringContainsString(
                'date de début',
                $e->getMessage(),
                'Erreur SQL au lieu de l\'erreur métier : la valeur n\'est pas liée'
            );
        }
    }

    /**
     * Meme raisonnement pour `Rank::addPenalty()` : `$id_equipe` est lie en
     * entier. Une charge non numerique donne 0, donc aucune ligne, donc l'erreur
     * metier. Concatenee, `id_equipe = abc` aurait casse la requete.
     *
     * Aucune penalite n'est infligee : la methode leve avant l'UPDATE.
     */
    public function test_add_penalty_lie_l_identifiant_d_equipe(): void
    {
        try {
            $this->rank->addPenalty('m', 'abc');
            self::fail('Une équipe inexistante doit lever une exception');
        } catch (Exception $e) {
            self::assertStringContainsString(
                'pénalités',
                $e->getMessage(),
                'Erreur SQL au lieu de l\'erreur métier : la valeur n\'est pas liée'
            );
        }
    }

    /**
     * `LimitDate::getLimitDate($compet)` : contexte de chaine quotee, desormais
     * lie. La charge ne correspond a aucune competition.
     */
    public function test_get_limit_date_lie_le_code_competition(): void
    {
        try {
            $this->limitDate->getLimitDate("' OR '1'='1");
            self::fail('Un code de compétition inexistant doit lever une exception');
        } catch (Exception $e) {
            self::assertStringContainsString(
                'Impossible de récupérer la date limite',
                $e->getMessage(),
                'Erreur SQL au lieu de l\'erreur métier : la valeur n\'est pas liée'
            );
        }
    }

    /**
     * Le chemin nominal doit continuer de fonctionner.
     */
    public function test_les_appels_legitimes_fonctionnent_toujours(): void
    {
        $rows = $this->sql->execute(
            "SELECT code_competition FROM dates_limite LIMIT 1"
        );
        if (empty($rows)) {
            self::markTestSkipped("Pas de date limite en base");
        }
        $date = $this->limitDate->getLimitDate($rows[0]['code_competition']);
        self::assertNotEmpty($date);
    }
}

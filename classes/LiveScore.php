<?php

require_once __DIR__ . '/Generic.php';

class LiveScore extends Generic
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'live_scores';
        $this->id_name = 'id';
    }

    /**
     * Get live score for a match
     * @param int $id_match
     * @return array|null
     * @throws Exception
     */
    public function getLiveScore(string|int $id_match): ?array
    {
        $sql = "SELECT * FROM live_scores WHERE id_match = ? AND is_active = 1";
        $bindings = array(
            array('type' => 's', 'value' => $id_match)
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return empty($results) ? null : $results[0];
    }

    /**
     * Effectifs des deux équipes d'un match, pour l'écran arbitre (issue #332).
     *
     * **Réservé au scoreur du match** : l'autorisation est faite par
     * `ajax/live_score.php`, seul appelant, avec le `canModifyLiveScore()` qui
     * garde déjà les POST. Les compositions ne concernent que le mode arbitre —
     * la page publique ne les affiche pas.
     *
     * C'est pourquoi les vignettes ne sont PAS ajoutées à
     * `player/getLivePlayersFromTeam` : cet endpoint est en niveau `user`, donc
     * tout compte connecté pourrait lister les photos de n'importe quelle
     * équipe. Il reste sans PII (issue #228).
     *
     * `nom_court` est le prénom suivi de l'initiale du nom : un nom de famille
     * entier ne tient pas dans une case de terrain de 55 px, et c'est la
     * vignette qui fait reconnaître le joueur.
     *
     * Les membres **non jouants** (issue #325) sont exclus : ils sont rattachés
     * à l'équipe pour la piloter, pas pour y jouer.
     *
     * @return array{dom: array, ext: array}
     * @throws Exception
     */
    public function getScorerRosters(string|int $id_match): array
    {
        require_once __DIR__ . '/MatchMgr.php';
        require_once __DIR__ . '/Players.php';

        $match = (new MatchMgr())->get_match_by_code_match($id_match);
        if (empty($match)) {
            throw new Exception("Match introuvable !");
        }
        return array(
            'dom' => $this->getRosterForTeam($match['id_equipe_dom']),
            'ext' => $this->getRosterForTeam($match['id_equipe_ext']),
        );
    }

    /**
     * @throws Exception
     */
    private function getRosterForTeam($id_equipe): array
    {
        $sql = "SELECT j.id,
                       j.prenom,
                       j.nom,
                       CONCAT(j.prenom, ' ', UPPER(LEFT(j.nom, 1)), '.') AS nom_court,
                       j.full_name,
                       -- `adjust_photo_path_from_results` lit `path_photo` et
                       -- `sexe` pour choisir l'image de repli : les deux doivent
                       -- etre selectionnees meme si seule la vignette est affichee.
                       j.path_photo,
                       j.path_photo_low,
                       j.sexe,
                       j.est_actif
                FROM players_view j
                         JOIN joueur_equipe je ON je.id_joueur = j.id
                WHERE je.id_equipe = ?
                  AND je.est_jouant + 0 > 0
                ORDER BY j.nom, j.prenom";
        $bindings = array(array('type' => 'i', 'value' => (int)$id_equipe));
        $players = $this->sql_manager->execute($sql, $bindings);
        // Garantit une image de repli quand le fichier manque (issue #295).
        return array_values(Players::adjust_photo_path_from_results($players));
    }

    /**
     * Start a new live score session for a match
     * @param int $id_match
     * @return int|string Insert ID
     * @throws Exception
     */
    public function startLiveScore(string|int $id_match): int|string
    {
        $existing = $this->getLiveScore($id_match);
        if ($existing) {
            return $existing['id'];
        }

        $sql = "INSERT INTO live_scores (id_match, set_en_cours, score_dom, score_ext, sets_dom, sets_ext, is_active) 
                VALUES (?, 1, 0, 0, 0, 0, 1)";
        $bindings = array(
            array('type' => 's', 'value' => $id_match)
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Increment score for a team
     * @param int $id_match
     * @param string $team 'dom' or 'ext'
     * @throws Exception
     */
    public function incrementScore(string|int $id_match, string $team): void
    {
        if (!in_array($team, ['dom', 'ext'])) {
            throw new Exception("Invalid team: must be 'dom' or 'ext'");
        }

        $column = "score_$team";
        $sql = "UPDATE live_scores SET $column = $column + 1 WHERE id_match = ? AND is_active = 1";
        $bindings = array(
            array('type' => 's', 'value' => $id_match)
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Decrement score for a team (cannot go below 0)
     * @param int $id_match
     * @param string $team 'dom' or 'ext'
     * @throws Exception
     */
    public function decrementScore(string|int $id_match, string $team): void
    {
        if (!in_array($team, ['dom', 'ext'])) {
            throw new Exception("Invalid team: must be 'dom' or 'ext'");
        }

        $column = "score_$team";
        $sql = "UPDATE live_scores SET $column = GREATEST(0, $column - 1) WHERE id_match = ? AND is_active = 1";
        $bindings = array(
            array('type' => 's', 'value' => $id_match)
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Move to next set, save current set scores, reset point scores, increment set for winner
     * @param string|int $id_match
     * @param string|null $setWinner 'dom' or 'ext' - who won the set
     * @throws Exception
     */
    public function nextSet(string|int $id_match, ?string $setWinner = null): void
    {
        $current = $this->getLiveScore($id_match);
        if (!$current) {
            throw new Exception("No active live score for this match");
        }

        $setNum = $current['set_en_cours'];
        if ($setNum > 5) {
            throw new Exception("Cannot exceed 5 sets");
        }

        // Save current scores to set columns
        $setDomCol = "set_{$setNum}_dom";
        $setExtCol = "set_{$setNum}_ext";
        
        $updates = "$setDomCol = ?, $setExtCol = ?, set_en_cours = set_en_cours + 1, score_dom = 0, score_ext = 0";
        
        if ($setWinner === 'dom') {
            $updates .= ", sets_dom = sets_dom + 1";
        } elseif ($setWinner === 'ext') {
            $updates .= ", sets_ext = sets_ext + 1";
        }

        $sql = "UPDATE live_scores SET $updates WHERE id_match = ? AND is_active = 1";
        $bindings = array(
            array('type' => 'i', 'value' => $current['score_dom']),
            array('type' => 'i', 'value' => $current['score_ext']),
            array('type' => 's', 'value' => $id_match)
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Delete live score for a match
     * @param int $id_match
     * @throws Exception
     */
    public function deleteLiveScore(string|int $id_match): void
    {
        $sql = "DELETE FROM live_scores WHERE id_match = ?";
        $bindings = array(
            array('type' => 's', 'value' => $id_match)
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * End live score session (mark as inactive)
     * @param string|int $id_match
     * @throws Exception
     */
    public function endLiveScore(string|int $id_match): void
    {
        $sql = "UPDATE live_scores SET is_active = 0 WHERE id_match = ?";
        $bindings = array(
            array('type' => 's', 'value' => $id_match)
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Save live score data to the matches table
     * @param string|int $id_match code_match
     * @throws Exception
     */
    public function saveToMatch(string|int $id_match): void
    {
        $liveScore = $this->getLiveScore($id_match);
        if (!$liveScore) {
            throw new Exception("No live score found for this match");
        }

        $sql = "UPDATE matches SET 
                    set_1_dom = ?, set_1_ext = ?,
                    set_2_dom = ?, set_2_ext = ?,
                    set_3_dom = ?, set_3_ext = ?,
                    set_4_dom = ?, set_4_ext = ?,
                    set_5_dom = ?, set_5_ext = ?
                WHERE code_match = ?";
        
        $bindings = array(
            array('type' => 'i', 'value' => $liveScore['set_1_dom']),
            array('type' => 'i', 'value' => $liveScore['set_1_ext']),
            array('type' => 'i', 'value' => $liveScore['set_2_dom']),
            array('type' => 'i', 'value' => $liveScore['set_2_ext']),
            array('type' => 'i', 'value' => $liveScore['set_3_dom']),
            array('type' => 'i', 'value' => $liveScore['set_3_ext']),
            array('type' => 'i', 'value' => $liveScore['set_4_dom']),
            array('type' => 'i', 'value' => $liveScore['set_4_ext']),
            array('type' => 'i', 'value' => $liveScore['set_5_dom']),
            array('type' => 'i', 'value' => $liveScore['set_5_ext']),
            array('type' => 's', 'value' => $id_match)
        );
        
        $this->sql_manager->execute($sql, $bindings);
        
        // End the live score session after saving
        $this->endLiveScore($id_match);
    }

    /**
     * Idempotent upsert: save the full score state with optimistic concurrency control.
     * Rejects the update if the provided version doesn't match the server version.
     * @param string|int $id_match
     * @param array $scoreData Full score state (score_dom, score_ext, sets_dom, sets_ext, set_en_cours, set_X_dom, set_X_ext)
     * @param int $expectedVersion Version the client last saw
     * @return array ['success' => bool, 'version' => int, 'data' => array|null, 'error' => string|null]
     * @throws Exception
     */
    public function upsertScore(string|int $id_match, array $scoreData, int $expectedVersion): array
    {
        $current = $this->getLiveScore($id_match);
        if (!$current) {
            throw new Exception("No active live score for this match");
        }

        $serverVersion = (int)$current['version'];
        if ($serverVersion !== $expectedVersion) {
            return [
                'success' => false,
                'error' => 'version_conflict',
                'version' => $serverVersion,
                'data' => $current
            ];
        }

        $allowedFields = [
            'score_dom', 'score_ext', 'sets_dom', 'sets_ext', 'set_en_cours',
            'set_1_dom', 'set_1_ext', 'set_2_dom', 'set_2_ext', 'set_3_dom',
            'set_3_ext', 'set_4_dom', 'set_4_ext', 'set_5_dom', 'set_5_ext'
        ];

        $setClauses = [];
        $bindings = [];
        foreach ($scoreData as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $setClauses[] = "$field = ?";
                $bindings[] = array('type' => 'i', 'value' => (int)$value);
            }
        }

        if (empty($setClauses)) {
            throw new Exception("No valid fields to update");
        }

        $setClauses[] = "version = version + 1";
        $setString = implode(', ', $setClauses);

        $sql = "UPDATE live_scores SET $setString WHERE id_match = ? AND is_active = 1 AND version = ?";
        $bindings[] = array('type' => 's', 'value' => $id_match);
        $bindings[] = array('type' => 'i', 'value' => $expectedVersion);

        $this->sql_manager->execute($sql, $bindings);

        $updated = $this->getLiveScore($id_match);
        return [
            'success' => true,
            'version' => (int)$updated['version'],
            'data' => $updated
        ];
    }

    /**
     * Get all active live scores with match details
     * @return array
     * @throws Exception
     */
    public function getActiveLiveScores(): array
    {
        $sql = "SELECT 
                    ls.*,
                    m.code_match,
                    m.equipe_dom,
                    m.equipe_ext,
                    m.code_competition,
                    m.division
                FROM live_scores ls
                JOIN matchs_view m ON m.id_match = ls.id_match
                WHERE ls.is_active = 1
                ORDER BY ls.updated_at DESC";
        return $this->sql_manager->execute($sql);
    }
}

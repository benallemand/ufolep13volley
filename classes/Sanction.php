<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/UserManager.php';

/**
 * Sanctions (issue #475).
 *
 * Une sanction est un MANQUEMENT constaté (motif, match éventuel), avec la
 * sanction envisagée. Elle n'a aucun effet tant que la commission ne l'a pas
 * APPLIQUÉE, en réunion de fin de demi-saison (règlement, article 27) ; elle
 * peut aussi la LEVER (clémence). Une décision se défait (« remettre à
 * décider ») : l'effet appliqué est alors annulé.
 *
 * Effets d'une sanction appliquée :
 *  - avertissement    : aucun ;
 *  - points           : `classements.penalite` = somme des points des
 *                       sanctions appliquées de l'équipe dans la compétition ;
 *  - defaite_penalite,
 *    forfait          : le match est marqué perdu 0-25 ×3 par l'équipe
 *                       sanctionnée, feuille signée des deux côtés. Le
 *                       classement le compte comme un forfait (article 12 :
 *                       3 pts à l'adversaire, -1 pt) ;
 *  - annulation_match : le match passe ARCHIVED, que le classement ignore ;
 *  - forfait_general  : tous les matchs non archivés de l'équipe dans la
 *                       compétition passent ARCHIVED, et l'équipe sort du
 *                       classement (`classements.withdrawn_at`).
 * L'état d'origine (scores, signatures, statuts) est gardé dans
 * `effect_backup` et restauré si la décision est défaite.
 */
class Sanction extends Generic
{
    public const STATUS_CONSTATEE = 'CONSTATEE';
    public const STATUS_APPLIQUEE = 'APPLIQUEE';
    public const STATUS_LEVEE = 'LEVEE';

    /** Motifs de manquement (règlement général). */
    public const MOTIFS = array(
        'feuille_non_signee' => 'Feuille de match non signée à 7 jours (art. 8)',
        'score_non_saisi' => 'Score non saisi à 7 jours (art. 6)',
        'feuille_mal_remplie' => 'Feuille de match mal remplie',
        'joueur_non_licencie' => 'Joueur sans licence valide (art. 10)',
        'report_hors_regle' => 'Report hors délai ou non communiqué (art. 9)',
        'equipe_incomplete' => 'Équipe incomplète ou absente (art. 11)',
        'forfaits_repetes' => 'Forfaits répétés (art. 12)',
        'comportement' => 'Comportement, incident',
        'autre' => 'Autre',
    );

    /** Sanctions possibles et leur effet. */
    public const TYPES = array(
        'avertissement' => 'Avertissement (sans effet sur le classement)',
        'points' => 'Points en moins',
        'defaite_penalite' => 'Défaite par pénalité (3 pts à l\'adversaire, -1 pt)',
        'forfait' => 'Match perdu par forfait (3 pts à l\'adversaire, -1 pt)',
        'annulation_match' => 'Annulation du match (non comptabilisé)',
        'forfait_general' => 'Forfait général (matchs annulés, équipe retirée du classement)',
    );

    /** Sanctions qui portent sur un match précis. */
    public const MATCH_TYPES = array('defaite_penalite', 'forfait', 'annulation_match');

    private const SET_COLUMNS = array(
        'set_1_dom', 'set_1_ext', 'set_2_dom', 'set_2_ext', 'set_3_dom', 'set_3_ext',
        'set_4_dom', 'set_4_ext', 'set_5_dom', 'set_5_ext');

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'sanctions';
    }

    // ---- lecture ---------------------------------------------------------

    /**
     * Toutes les sanctions, pour l'écran d'administration.
     * @throws Exception
     */
    public function getSanctions(): array
    {
        $rows = $this->sql_manager->execute(file_get_contents(__DIR__ . '/../sql/sanctions_admin.sql'));
        foreach ($rows as &$row) {
            $row['motif_label'] = self::MOTIFS[$row['motif']] ?? $row['motif'];
            $row['sanction_label'] = self::describe($row['sanction_type'], (int)$row['points']);
            $row['team_key'] = $row['code_competition'] . '|' . $row['id_equipe'];
        }
        return $rows;
    }

    /**
     * Listes du formulaire : motifs, sanctions, équipes engagées.
     * @throws Exception
     */
    public function getSanctionCatalog(): array
    {
        $teams = $this->sql_manager->execute(
            "SELECT CONCAT(c.code_competition, '|', c.id_equipe) AS value,
                    CONCAT(e.nom_equipe, ' — ', comp.libelle, ' D', c.division) AS label
             FROM classements c
             JOIN equipes e ON e.id_equipe = c.id_equipe
             JOIN competitions comp ON comp.code_competition = c.code_competition
             ORDER BY comp.libelle, CAST(c.division AS UNSIGNED), e.nom_equipe");
        $options = static function (array $map): array {
            $out = array();
            foreach ($map as $value => $label) {
                $out[] = array('value' => $value, 'label' => $label);
            }
            return $out;
        };
        return array(
            'motifs' => $options(self::MOTIFS),
            'types' => $options(self::TYPES),
            'teams' => $teams,
        );
    }

    /** Libellé lisible d'une sanction : « 2 points en moins », « Avertissement… ». */
    public static function describe(string $type, int $points = 0): string
    {
        if ($type === 'points') {
            return $points . ($points > 1 ? ' points en moins' : ' point en moins');
        }
        return self::TYPES[$type] ?? $type;
    }

    /**
     * Sanctions d'une équipe à montrer à son responsable : manquements
     * constatés (en attente de décision) et sanctions appliquées récentes.
     * @throws Exception
     */
    public function get_team_sanctions(int $id_equipe): array
    {
        $rows = $this->sql_manager->execute(
            "SELECT s.motif, s.sanction_type, s.points, s.status, m.code_match,
                    DATE_FORMAT(s.decided_at, '%d/%m/%Y') AS decided_on
             FROM sanctions s
             LEFT JOIN matches m ON m.id_match = s.id_match
             WHERE s.id_equipe = ?
               AND (s.status = 'CONSTATEE'
                    OR (s.status = 'APPLIQUEE' AND s.decided_at >= NOW() - INTERVAL 60 DAY))
             ORDER BY s.status, s.created_at DESC",
            array(array('type' => 'i', 'value' => $id_equipe)));
        foreach ($rows as &$row) {
            $row['motif_label'] = self::MOTIFS[$row['motif']] ?? $row['motif'];
            $row['sanction_label'] = self::describe($row['sanction_type'], (int)$row['points']);
        }
        return $rows;
    }

    // ---- saisie ----------------------------------------------------------

    /**
     * Crée ou modifie une sanction CONSTATÉE (une sanction décidée ne se
     * modifie pas : la remettre à décider d'abord).
     *
     * @param string|null $team_key « code_competition|id_equipe »
     * @param string|null $code_match match concerné (obligatoire pour une
     *                                sanction qui porte sur un match)
     * @throws Exception
     */
    public function saveSanction($id = null, $team_key = null, $code_match = null, $motif = null,
                                 $sanction_type = null, $points = null, $comment = null): int
    {
        if (!empty($id)) {
            $current = $this->get_row((int)$id);
            if ($current['status'] !== self::STATUS_CONSTATEE) {
                throw new Exception("Une sanction décidée ne se modifie pas : remettez-la d'abord à décider.");
            }
        }
        if (!preg_match('/^([a-z]{1,2})\|(\d+)$/', (string)$team_key, $m)) {
            throw new Exception("Choisissez l'équipe sanctionnée.");
        }
        $code_competition = $m[1];
        $id_equipe = (int)$m[2];
        $engaged = $this->sql_manager->execute(
            "SELECT 1 FROM classements WHERE code_competition = ? AND id_equipe = ?",
            array(
                array('type' => 's', 'value' => $code_competition),
                array('type' => 'i', 'value' => $id_equipe),
            ));
        if (empty($engaged)) {
            throw new Exception("Cette équipe n'est pas engagée dans cette compétition.");
        }
        if (!isset(self::MOTIFS[(string)$motif])) {
            throw new Exception("Motif inconnu.");
        }
        if (!isset(self::TYPES[(string)$sanction_type])) {
            throw new Exception("Sanction inconnue.");
        }
        $points = $sanction_type === 'points' ? (int)$points : 0;
        if ($sanction_type === 'points' && ($points < 1 || $points > 20)) {
            throw new Exception("Indiquez le nombre de points en moins (de 1 à 20).");
        }
        $id_match = null;
        $code_match = trim((string)$code_match);
        if ($code_match !== '') {
            $match = $this->sql_manager->execute(
                "SELECT id_match FROM matches
                 WHERE code_match = ? AND code_competition = ? AND ? IN (id_equipe_dom, id_equipe_ext)",
                array(
                    array('type' => 's', 'value' => $code_match),
                    array('type' => 's', 'value' => $code_competition),
                    array('type' => 'i', 'value' => $id_equipe),
                ));
            if (empty($match)) {
                throw new Exception("Le match $code_match n'existe pas, ou l'équipe ne l'a pas joué dans cette compétition.");
            }
            $id_match = (int)$match[0]['id_match'];
        }
        if ($id_match === null && in_array($sanction_type, self::MATCH_TYPES, true)) {
            throw new Exception("Cette sanction porte sur un match : indiquez son code.");
        }
        $comment = trim((string)$comment) === '' ? null : trim((string)$comment);
        $bindings = array(
            array('type' => 's', 'value' => $code_competition),
            array('type' => 'i', 'value' => $id_equipe),
            array('type' => 'i', 'value' => $id_match),
            array('type' => 's', 'value' => $motif),
            array('type' => 's', 'value' => $sanction_type),
            array('type' => 'i', 'value' => $points),
            array('type' => 's', 'value' => $comment),
        );
        if (!empty($id)) {
            $bindings[] = array('type' => 'i', 'value' => (int)$id);
            $this->sql_manager->execute(
                "UPDATE sanctions SET code_competition = ?, id_equipe = ?, id_match = ?, motif = ?,
                        sanction_type = ?, points = ?, comment = ?
                 WHERE id = ? AND status = 'CONSTATEE'", $bindings);
            $this->addActivity("Sanction modifiée : " . $this->summary((int)$id), $id_equipe);
            return (int)$id;
        }
        $bindings[] = array('type' => 'i', 'value' => $this->current_user_id());
        $new_id = (int)$this->sql_manager->execute(
            "INSERT INTO sanctions (code_competition, id_equipe, id_match, motif, sanction_type, points, comment,
                                    source, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'MANUELLE', 'CONSTATEE', ?)", $bindings);
        $this->addActivity("Manquement constaté : " . $this->summary($new_id), $id_equipe);
        return $new_id;
    }

    /**
     * Manquement relevé automatiquement (cron) : une seule fois par match,
     * équipe et motif.
     * @return bool true si la sanction vient d'être créée
     * @throws Exception
     */
    public function record_automatic(int $id_match, int $id_equipe, string $code_competition, string $motif,
                                     string $sanction_type, int $points): bool
    {
        $exists = $this->sql_manager->execute(
            "SELECT 1 FROM sanctions WHERE id_match = ? AND id_equipe = ? AND motif = ? AND source = 'AUTO'",
            array(
                array('type' => 'i', 'value' => $id_match),
                array('type' => 'i', 'value' => $id_equipe),
                array('type' => 's', 'value' => $motif),
            ));
        if (!empty($exists)) {
            return false;
        }
        $this->sql_manager->execute(
            "INSERT INTO sanctions (code_competition, id_equipe, id_match, motif, sanction_type, points, source, status)
             VALUES (?, ?, ?, ?, ?, ?, 'AUTO', 'CONSTATEE')",
            array(
                array('type' => 's', 'value' => $code_competition),
                array('type' => 'i', 'value' => $id_equipe),
                array('type' => 'i', 'value' => $id_match),
                array('type' => 's', 'value' => $motif),
                array('type' => 's', 'value' => $sanction_type),
                array('type' => 'i', 'value' => $points),
            ));
        return true;
    }

    /**
     * Supprime des sanctions saisies par erreur. Une sanction appliquée doit
     * d'abord être remise à décider : sa suppression laisserait son effet.
     * @throws Exception
     */
    public function deleteSanctions($ids): void
    {
        $deleted = 0;
        foreach (self::parse_id_list($ids) as $id) {
            $row = $this->get_row($id);
            if ($row['status'] === self::STATUS_APPLIQUEE) {
                throw new Exception("Une sanction appliquée ne se supprime pas : remettez-la d'abord à décider.");
            }
            $summary = $this->summary($id);
            $this->sql_manager->execute("DELETE FROM sanctions WHERE id = ?",
                array(array('type' => 'i', 'value' => $id)));
            $this->addActivity("Sanction supprimée : $summary", (int)$row['id_equipe']);
            $deleted++;
        }
        if ($deleted === 0) {
            throw new Exception("Aucune sanction sélectionnée.");
        }
    }

    // ---- décisions -------------------------------------------------------

    /**
     * Applique des sanctions constatées (ou levées) : leur effet est porté au
     * classement ou aux matchs.
     * @throws Exception
     */
    public function applySanctions($ids, $decision_comment = null): void
    {
        $this->decide($ids, self::STATUS_APPLIQUEE, $decision_comment);
    }

    /**
     * Lève des sanctions (clémence). Si elles étaient appliquées, leur effet
     * est annulé.
     * @throws Exception
     */
    public function liftSanctions($ids, $decision_comment = null): void
    {
        $this->decide($ids, self::STATUS_LEVEE, $decision_comment);
    }

    /**
     * Remet des sanctions à décider : l'effet d'une sanction appliquée est
     * annulé, la décision effacée.
     * @throws Exception
     */
    public function reopenSanctions($ids): void
    {
        $this->decide($ids, self::STATUS_CONSTATEE, null);
    }

    /**
     * @throws Exception
     */
    private function decide($ids, string $status, $decision_comment): void
    {
        $changed = 0;
        foreach (self::parse_id_list($ids) as $id) {
            $row = $this->get_row($id);
            if ($row['status'] === $status) {
                continue;
            }
            if ($row['status'] === self::STATUS_APPLIQUEE) {
                $this->undo_effect($row);
            }
            $backup = null;
            if ($status === self::STATUS_APPLIQUEE) {
                $backup = $this->apply_effect($row);
            }
            $comment = trim((string)$decision_comment) === '' ? null : trim((string)$decision_comment);
            if ($status === self::STATUS_CONSTATEE) {
                $this->sql_manager->execute(
                    "UPDATE sanctions SET status = 'CONSTATEE', decided_at = NULL, decided_by = NULL,
                            decision_comment = NULL, effect_backup = NULL
                     WHERE id = ?",
                    array(array('type' => 'i', 'value' => $id)));
            } else {
                $this->sql_manager->execute(
                    "UPDATE sanctions SET status = ?, decided_at = NOW(), decided_by = ?, decision_comment = ?,
                            effect_backup = ?
                     WHERE id = ?",
                    array(
                        array('type' => 's', 'value' => $status),
                        array('type' => 'i', 'value' => $this->current_user_id()),
                        array('type' => 's', 'value' => $comment),
                        array('type' => 's', 'value' => $backup === null ? null : json_encode($backup)),
                        array('type' => 'i', 'value' => $id),
                    ));
            }
            if ($row['sanction_type'] === 'points') {
                $this->recompute_penalite((int)$row['id_equipe'], $row['code_competition']);
            }
            $verb = array(
                self::STATUS_APPLIQUEE => 'Sanction appliquée',
                self::STATUS_LEVEE => 'Sanction levée',
                self::STATUS_CONSTATEE => 'Sanction remise à décider',
            )[$status];
            $this->addActivity("$verb : " . $this->summary($id) . ($comment ? " ($comment)" : ''), (int)$row['id_equipe']);
            $changed++;
        }
        if ($changed === 0) {
            throw new Exception("Aucune sanction à changer parmi les lignes sélectionnées.");
        }
    }

    // ---- effets ----------------------------------------------------------

    /**
     * Porte l'effet d'une sanction et renvoie de quoi le défaire.
     * @throws Exception
     */
    private function apply_effect(array $row): ?array
    {
        $type = $row['sanction_type'];
        if ($type === 'avertissement' || $type === 'points') {
            return null;
        }
        if (in_array($type, self::MATCH_TYPES, true)) {
            if (empty($row['id_match'])) {
                throw new Exception("La sanction n°{$row['id']} porte sur un match qui n'existe plus.");
            }
            $this->assert_match_free((int)$row['id_match'], (int)$row['id']);
            $match = $this->get_match((int)$row['id_match']);
            $backup = array('match' => array_intersect_key($match, array_flip(array_merge(
                self::SET_COLUMNS, array('is_sign_match_dom', 'is_sign_match_ext', 'match_status')))));
            if ($type === 'annulation_match') {
                $this->set_match_status((int)$row['id_match'], 'ARCHIVED');
                return $backup;
            }
            // défaite par pénalité / forfait : 0-25 ×3 pour l'équipe sanctionnée
            $sanctioned_home = (int)$match['id_equipe_dom'] === (int)$row['id_equipe'];
            $loser = $sanctioned_home ? 'dom' : 'ext';
            $winner = $sanctioned_home ? 'ext' : 'dom';
            $sets = array();
            foreach (array(1, 2, 3) as $n) {
                $sets["set_{$n}_$loser"] = 0;
                $sets["set_{$n}_$winner"] = 25;
            }
            foreach (array(4, 5) as $n) {
                $sets["set_{$n}_dom"] = 0;
                $sets["set_{$n}_ext"] = 0;
            }
            $this->write_match((int)$row['id_match'], $sets + array('is_sign_match_dom' => 1, 'is_sign_match_ext' => 1));
            return $backup;
        }
        if ($type === 'forfait_general') {
            $matches = $this->sql_manager->execute(
                "SELECT id_match, match_status FROM matches
                 WHERE code_competition = ? AND ? IN (id_equipe_dom, id_equipe_ext) AND match_status <> 'ARCHIVED'",
                array(
                    array('type' => 's', 'value' => $row['code_competition']),
                    array('type' => 'i', 'value' => (int)$row['id_equipe']),
                ));
            foreach ($matches as $match) {
                $this->set_match_status((int)$match['id_match'], 'ARCHIVED');
            }
            $this->sql_manager->execute(
                "UPDATE classements SET withdrawn_at = NOW() WHERE id_equipe = ? AND code_competition = ?",
                array(
                    array('type' => 'i', 'value' => (int)$row['id_equipe']),
                    array('type' => 's', 'value' => $row['code_competition']),
                ));
            return array('matches' => $matches);
        }
        throw new Exception("Sanction inconnue : $type");
    }

    /**
     * Défait l'effet d'une sanction appliquée, d'après `effect_backup`.
     * @throws Exception
     */
    private function undo_effect(array $row): void
    {
        $backup = empty($row['effect_backup']) ? array() : json_decode($row['effect_backup'], true);
        $type = $row['sanction_type'];
        if (in_array($type, self::MATCH_TYPES, true) && !empty($backup['match']) && !empty($row['id_match'])) {
            $this->write_match((int)$row['id_match'], $backup['match']);
            return;
        }
        if ($type === 'forfait_general') {
            foreach ($backup['matches'] ?? array() as $match) {
                $this->sql_manager->execute(
                    "UPDATE matches SET match_status = ? WHERE id_match = ? AND match_status = 'ARCHIVED'",
                    array(
                        array('type' => 's', 'value' => $match['match_status']),
                        array('type' => 'i', 'value' => (int)$match['id_match']),
                    ));
            }
            $this->sql_manager->execute(
                "UPDATE classements SET withdrawn_at = NULL WHERE id_equipe = ? AND code_competition = ?",
                array(
                    array('type' => 'i', 'value' => (int)$row['id_equipe']),
                    array('type' => 's', 'value' => $row['code_competition']),
                ));
        }
    }

    /**
     * Deux sanctions appliquées sur le même match s'écraseraient : refus.
     * @throws Exception
     */
    private function assert_match_free(int $id_match, int $id_sanction): void
    {
        $placeholders = implode(',', array_fill(0, count(self::MATCH_TYPES), '?'));
        $bindings = array(
            array('type' => 'i', 'value' => $id_match),
            array('type' => 'i', 'value' => $id_sanction),
        );
        foreach (self::MATCH_TYPES as $type) {
            $bindings[] = array('type' => 's', 'value' => $type);
        }
        $other = $this->sql_manager->execute(
            "SELECT id FROM sanctions
             WHERE id_match = ? AND id <> ? AND status = 'APPLIQUEE' AND sanction_type IN ($placeholders)",
            $bindings);
        if (!empty($other)) {
            throw new Exception("Une autre sanction déjà appliquée modifie ce match (n°{$other[0]['id']}) : "
                . "remettez-la d'abord à décider.");
        }
    }

    /** `penalite` = somme des points des sanctions appliquées. */
    private function recompute_penalite(int $id_equipe, string $code_competition): void
    {
        $this->sql_manager->execute(
            "UPDATE classements c
             SET c.penalite = (SELECT COALESCE(SUM(s.points), 0) FROM sanctions s
                               WHERE s.id_equipe = c.id_equipe AND s.code_competition = c.code_competition
                                 AND s.status = 'APPLIQUEE' AND s.sanction_type = 'points')
             WHERE c.id_equipe = ? AND c.code_competition = ?",
            array(
                array('type' => 'i', 'value' => $id_equipe),
                array('type' => 's', 'value' => $code_competition),
            ));
    }

    // ---- outils ----------------------------------------------------------

    /** @throws Exception */
    private function get_row(int $id): array
    {
        $rows = $this->sql_manager->execute("SELECT * FROM sanctions WHERE id = ?",
            array(array('type' => 'i', 'value' => $id)));
        if (empty($rows)) {
            throw new Exception("Sanction n°$id introuvable.");
        }
        return $rows[0];
    }

    /** @throws Exception */
    private function get_match(int $id_match): array
    {
        $rows = $this->sql_manager->execute(
            "SELECT id_match, id_equipe_dom, id_equipe_ext, match_status,
                    set_1_dom, set_1_ext, set_2_dom, set_2_ext, set_3_dom, set_3_ext,
                    set_4_dom, set_4_ext, set_5_dom, set_5_ext,
                    is_sign_match_dom + 0 AS is_sign_match_dom, is_sign_match_ext + 0 AS is_sign_match_ext
             FROM matches WHERE id_match = ?",
            array(array('type' => 'i', 'value' => $id_match)));
        if (empty($rows)) {
            throw new Exception("Match introuvable.");
        }
        return $rows[0];
    }

    /** @throws Exception */
    private function write_match(int $id_match, array $values): void
    {
        $allowed = array_merge(self::SET_COLUMNS, array('is_sign_match_dom', 'is_sign_match_ext', 'match_status'));
        $sets = array();
        $bindings = array();
        foreach ($values as $column => $value) {
            if (!in_array($column, $allowed, true)) {
                continue;
            }
            $sets[] = "$column = ?";
            $bindings[] = $column === 'match_status'
                ? array('type' => 's', 'value' => (string)$value)
                : array('type' => 'i', 'value' => (int)$value);
        }
        if (empty($sets)) {
            return;
        }
        $bindings[] = array('type' => 'i', 'value' => $id_match);
        $this->sql_manager->execute("UPDATE matches SET " . implode(', ', $sets) . " WHERE id_match = ?", $bindings);
    }

    /** @throws Exception */
    private function set_match_status(int $id_match, string $status): void
    {
        $this->write_match($id_match, array('match_status' => $status));
    }

    /** « AIXTASY — Feuille de match non signée… — 1 point en moins (match M_2_…) ». @throws Exception */
    private function summary(int $id): string
    {
        $rows = $this->sql_manager->execute(
            "SELECT e.nom_equipe, s.motif, s.sanction_type, s.points, m.code_match
             FROM sanctions s
             JOIN equipes e ON e.id_equipe = s.id_equipe
             LEFT JOIN matches m ON m.id_match = s.id_match
             WHERE s.id = ?",
            array(array('type' => 'i', 'value' => $id)));
        if (empty($rows)) {
            return "n°$id";
        }
        $r = $rows[0];
        return $r['nom_equipe'] . ' — ' . (self::MOTIFS[$r['motif']] ?? $r['motif']) . ' — '
            . self::describe($r['sanction_type'], (int)$r['points'])
            . (empty($r['code_match']) ? '' : ' (match ' . $r['code_match'] . ')');
    }

    private function current_user_id(): ?int
    {
        @session_start();
        return empty($_SESSION['id_user']) ? null : (int)$_SESSION['id_user'];
    }
}

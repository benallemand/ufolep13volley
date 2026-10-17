<?php
/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 02/11/2017
 * Time: 15:51
 */
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/Team.php';
require_once __DIR__ . '/Registry.php';

class Rank extends Generic
{
    /**
     * Championnats classés au barème FFVB (issue #347) : 3-0/3-1 = 3 pts,
     * 3-2 = 2, 2-3 = 1, 0-3/1-3 = 0, forfait = -1 ; départage par victoires,
     * quotient de sets, quotient de points. Les coupes gardent le barème
     * UFOLEP (3/1/0, départage par différence de sets puis confrontation
     * directe).
     */
    const FFVB_COMPETITIONS = array('m', 'f', 'mo');
    /** Début de la première saison au barème FFVB (palmarès des saisons passées). */
    const FFVB_SINCE = '2026-09-01';
    /**
     * Division d'attente des équipes à placer (issue #388) : « Remplir les
     * divisions et les rangs » y met les inscriptions sans équipe classée, et
     * l'écran de réorganisation la présente comme « à placer ».
     */
    const DIVISION_TO_PLACE = 'X';
    /**
     * L'inscription `r` désigne-t-elle l'équipe `e` ? (issue #390)
     *
     * Une réinscription (`old_team_id`) ne désigne QUE son ancienne équipe ;
     * le nom ne sert qu'à une nouvelle équipe. Rapprocher aussi par le nom
     * faisait passer un doublon homonyme pour inscrit, et l'initialisation
     * de saison engageait les deux (deux « Trets & Furious » en 2026).
     */
    const REGISTRATION_MATCHES_TEAM = "(r.old_team_id = e.id_equipe
                              OR (r.old_team_id IS NULL AND r.new_team_name = e.nom_equipe))";
    /**
     * Colonnes « inscrite cette saison » d'une équipe `e` (issue #388), pour
     * la réorganisation des divisions :
     *   - `registered` : une demande non refusée la reconnaît (voir
     *     `REGISTRATION_MATCHES_TEAM`) ;
     *   - `competition_has_registrations` : la compétition a des inscriptions.
     *     Sans elles (coupes), `registered` ne veut rien dire.
     * Même règle que la liste publique « pas réinscrite » (#379).
     */
    const REGISTERED_COLUMNS = "EXISTS (SELECT 1
                        FROM register r
                        JOIN competitions comp ON comp.id = r.id_competition
                        WHERE comp.code_competition = e.code_competition
                          AND r.status <> 'REFUSED'
                          AND " . self::REGISTRATION_MATCHES_TEAM . ") AS registered,
                    EXISTS (SELECT 1
                        FROM register r
                        JOIN competitions comp ON comp.id = r.id_competition
                        WHERE comp.code_competition = e.code_competition) AS competition_has_registrations";
    /**
     * Noms d'une équipe `e` réinscrite (issue #402), pour la réorganisation
     * des divisions :
     *   - `registered_name` : le nom demandé pour la nouvelle saison ;
     *   - `former_name` : son nom au moment de la demande
     *     (`register.old_team_name`), à défaut son nom actuel.
     *
     * « Équipes / comptes » et l'initialisation renomment l'équipe : selon les
     * boutons déjà passés, elle porte l'un ou l'autre nom. L'écran affiche
     * toujours le nom demandé, et « ex-… » quand l'ancien diffère.
     *
     * Seulement les réinscriptions (`old_team_id`) non refusées de la
     * campagne en cours, dans la compétition de l'équipe.
     */
    const RENAMING_COLUMNS = "(SELECT r.new_team_name
                        FROM register r
                        JOIN competitions comp ON comp.id = r.id_competition
                        WHERE comp.code_competition = e.code_competition
                          AND r.status <> 'REFUSED'
                          AND r.old_team_id = e.id_equipe
                          AND r.creation_date >= comp.start_register_date
                        ORDER BY r.id DESC
                        LIMIT 1) AS registered_name,
                    (SELECT COALESCE(r.old_team_name, e.nom_equipe)
                        FROM register r
                        JOIN competitions comp ON comp.id = r.id_competition
                        WHERE comp.code_competition = e.code_competition
                          AND r.status <> 'REFUSED'
                          AND r.old_team_id = e.id_equipe
                          AND r.creation_date >= comp.start_register_date
                        ORDER BY r.id DESC
                        LIMIT 1) AS former_name";

    /**
     * La compétition — et, si une date de fin de période est donnée, cette
     * période — se classe-t-elle au barème FFVB ?
     */
    public static function uses_ffvb_scale(?string $code_competition, ?string $period_end = null): bool
    {
        if (!in_array($code_competition, self::FFVB_COMPETITIONS, true)) {
            return false;
        }
        return $period_end === null || $period_end >= self::FFVB_SINCE;
    }

    private Team $team;
    private Registry $registry;

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'classements';
        $this->team = new Team();
        $this->registry = new Registry();
    }


    public function getSql($query = "1=1"): string
    {
        return "SELECT 
                    c.id,
                    c.code_competition,
                    comp.libelle AS nom_competition,
                    c.division,
                    c.id_equipe,
                    e.nom_equipe,
                    c.penalite,
                    c.report_count,
                    c.rank_start,
                    c.will_register_again
                FROM classements c
                JOIN competitions comp ON comp.code_competition = c.code_competition
                JOIN equipes e ON e.id_equipe = c.id_equipe
                WHERE $query
                ORDER BY c.code_competition, CAST(c.division AS UNSIGNED), c.rank_start";
    }

    /**
     * @param string|null $query
     * @return array
     * @throws Exception
     */
    public function getRanks(?string $query = "1=1"): array
    {
        $sql = $this->getSql($query);
        return $this->sql_manager->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCompetitions(): array
    {
        $sql = "SELECT  c.code_competition, 
                        IFNULL(DATE_FORMAT(c.start_date, '%d/%m/%Y'), '') AS start_date
            FROM competitions c";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $code_competition
     * @return array
     * @throws Exception
     */
    public function getDivisionsFromCompetition($code_competition): array
    {
        // endpoint public : paramètre lié (issue #354)
        $sql = "SELECT DISTINCT division
            FROM classements
            WHERE code_competition = ?
            ORDER BY CAST(division AS UNSIGNED)";
        return $this->sql_manager->execute($sql, array(
            array('type' => 's', 'value' => (string)$code_competition),
        ));
    }

    /**
     * @param $division
     * @param $code_competition
     * @return array
     * @throws Exception
     */
    public function getTeamsFromDivisionAndCompetition($division, $code_competition): array
    {
        $sql = "SELECT DISTINCT c.id_equipe,
                e.nom_equipe,
                CONCAT(e.nom_equipe, ' (', cl.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', c.division, ')'), '')) AS team_full_name,
                IF(cr.id IS NULL, '0', '1') AS has_timeslot
                FROM classements c
                JOIN equipes e ON e.id_equipe = c.id_equipe
                JOIN clubs cl ON cl.id = e.id_club
                JOIN competitions comp ON comp.code_competition = e.code_competition
                LEFT JOIN creneau cr ON cr.id_equipe = c.id_equipe
                WHERE c.code_competition = '$code_competition' 
                AND c.division = '$division'
                ORDER BY c.rank_start";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $code_competition
     * @param $division
     * @return mixed
     * @throws Exception
     */
    public function getLeader($code_competition, $division)
    {
        $results = $this->getRank($code_competition, $division);
        if (count($results) === 0) {
            throw new Exception("Le champion de la division $division de la compétition $code_competition n'a pas pus être déterminé !");
        }
        return $results[0];
    }

    /**
     * @param $code_competition
     * @param $division
     * @return mixed
     * @throws Exception
     */
    public function getViceLeader($code_competition, $division): mixed
    {
        $results = $this->getRank($code_competition, $division);
        if (count($results) <= 1) {
            throw new Exception("Le vice-champion de la division $division de la compétition $code_competition n'a pas pu être déterminé !");
        }
        return $results[1];
    }

    /**
     * @param $code_competition
     * @throws Exception
     */
    public function resetRankPoints($code_competition)
    {
        $sql = "UPDATE classements
         SET penalite = 0,
         report_count = 0
        WHERE code_competition = '$code_competition'";
        $this->sql_manager->execute($sql);
    }

    // --- Classement figé de chaque demi-saison (issue #435)

    /**
     * Phases finales de coupe et leur coupe (comme `Bilan`) : pas de
     * classement, leurs résultats se consultent avec la coupe.
     */
    const CUP_FINALS = array('cf' => 'c', 'kf' => 'kh');
    /** Pseudo-division des résultats de phases finales, dans `getArchivedResults`. */
    const ARCHIVE_FINALS_DIVISION = 'finales';

    /**
     * Écran public « Archives » : les périodes consultables, de la plus
     * récente à la plus ancienne, chacune avec ses compétitions et leurs
     * divisions.
     *
     * @return array<int, array{saison: string, demi_saison: int, label: string, competitions: array}>
     * @throws Exception
     */
    public function getArchivePeriods(): array
    {
        $rows = $this->sql_manager->execute(
            "SELECT saison, demi_saison, code_competition, libelle_competition, division,
                    MAX(source = 'REBUILT') AS rebuilt
             FROM classements_archives
             GROUP BY saison, demi_saison, code_competition, libelle_competition, division
             ORDER BY saison DESC, demi_saison DESC, code_competition, CAST(division AS UNSIGNED), division");
        $finals = array();
        foreach ($this->sql_manager->execute(
            "SELECT DISTINCT code_competition,
                    IF(MONTH(date_reception) >= 7, YEAR(date_reception), YEAR(date_reception) - 1) AS annee
             FROM matches WHERE match_status = 'ARCHIVED' AND code_competition IN ('"
            . implode("','", array_keys(self::CUP_FINALS)) . "')") as $f) {
            $finals[self::CUP_FINALS[$f['code_competition']] . '|' . $f['annee'] . '-' . ($f['annee'] + 1)] = true;
        }
        $periods = array();
        foreach ($rows as $row) {
            $key = $row['saison'] . '|' . $row['demi_saison'];
            $periods[$key] ??= array(
                'saison' => $row['saison'],
                'demi_saison' => (int)$row['demi_saison'],
                'label' => self::period_label($row),
                'competitions' => array(),
            );
            $competition = &$periods[$key]['competitions'][$row['code_competition']];
            $competition ??= array(
                'code_competition' => $row['code_competition'],
                'libelle' => $row['libelle_competition'],
                'rebuilt' => false,
                'divisions' => array(),
                'has_finals' => isset($finals[$row['code_competition'] . '|' . $row['saison']]),
            );
            $competition['divisions'][] = $row['division'];
            $competition['rebuilt'] = $competition['rebuilt'] || (int)$row['rebuilt'] === 1;
            unset($competition);
        }
        foreach ($periods as &$period) {
            $period['competitions'] = array_values($period['competitions']);
        }
        unset($period);
        return array_values($periods);
    }

    /**
     * Classement archivé d'une division (écran public « Archives »).
     * @throws Exception
     */
    public function getArchivedRank($saison = null, $demi_saison = null, $code_competition = null, $division = null): array
    {
        return $this->sql_manager->execute(
            "SELECT rang, id_equipe, nom_equipe AS equipe, club, points, joues, gagnes, perdus,
                    sets_pour, sets_contre, sets_pour - sets_contre AS diff, quotient_sets, quotient_points,
                    penalites, reports, forfaits AS matches_lost_by_forfeit_count, source,
                    DATE_FORMAT(frozen_at, '%d/%m/%Y') AS frozen_at
             FROM classements_archives
             WHERE saison = ? AND demi_saison = ? AND code_competition = ? AND division = ?
             ORDER BY rang, nom_equipe",
            array(
                array('type' => 's', 'value' => (string)$saison),
                array('type' => 'i', 'value' => (int)$demi_saison),
                array('type' => 's', 'value' => (string)$code_competition),
                array('type' => 's', 'value' => (string)$division),
            ));
    }

    /**
     * Résultats archivés d'une division sur sa période (écran public
     * « Archives »), avec les noms de l'époque. Pour une coupe, la division
     * `finales` donne les matchs de ses phases finales (`CUP_FINALS`).
     * @throws Exception
     */
    public function getArchivedResults($saison = null, $demi_saison = null, $code_competition = null, $division = null): array
    {
        $period = array(
            array('type' => 's', 'value' => (string)$saison),
            array('type' => 'i', 'value' => (int)$demi_saison),
            array('type' => 's', 'value' => (string)$code_competition),
        );
        $names = array_column($this->sql_manager->execute(
            "SELECT id_equipe, nom_equipe FROM classements_archives
             WHERE saison = ? AND demi_saison = ? AND code_competition = ?", $period), 'nom_equipe', 'id_equipe');
        if ((string)$division === self::ARCHIVE_FINALS_DIVISION) {
            $finals_code = array_search((string)$code_competition, self::CUP_FINALS, true);
            if ($finals_code === false || !preg_match('/^(\d{4})-(\d{4})$/', (string)$saison, $years)) {
                return array();
            }
            $where = "m.code_competition = ? AND m.date_reception BETWEEN ? AND ?";
            $bindings = array(
                array('type' => 's', 'value' => $finals_code),
                array('type' => 's', 'value' => $years[1] . '-07-01'),
                array('type' => 's', 'value' => $years[2] . '-06-30'),
            );
        } else {
            $range = $this->sql_manager->execute(
                "SELECT MIN(premier_match) AS du, MAX(dernier_match) AS au FROM classements_archives
                 WHERE saison = ? AND demi_saison = ? AND code_competition = ?", $period)[0] ?? array();
            if (empty($range['du'])) {
                return array();
            }
            $where = "m.code_competition = ? AND m.division = ? AND m.date_reception BETWEEN ? AND ?";
            $bindings = array(
                array('type' => 's', 'value' => (string)$code_competition),
                array('type' => 's', 'value' => (string)$division),
                array('type' => 's', 'value' => $range['du']),
                array('type' => 's', 'value' => $range['au']),
            );
        }
        $matches = $this->sql_manager->execute(
            "SELECT m.code_match, m.date_reception, m.id_equipe_dom, m.id_equipe_ext,
                    ed.nom_equipe AS nom_dom, ee.nom_equipe AS nom_ext,
                    m.set_1_dom, m.set_1_ext, m.set_2_dom, m.set_2_ext, m.set_3_dom, m.set_3_ext,
                    m.set_4_dom, m.set_4_ext, m.set_5_dom, m.set_5_ext
             FROM matches m
             JOIN equipes ed ON ed.id_equipe = m.id_equipe_dom
             JOIN equipes ee ON ee.id_equipe = m.id_equipe_ext
             WHERE m.match_status = 'ARCHIVED' AND $where
             ORDER BY m.date_reception, m.code_match", $bindings);
        $results = array();
        foreach ($matches as $m) {
            $sets = array();
            $won = array('dom' => 0, 'ext' => 0);
            for ($s = 1; $s <= 5; $s++) {
                $dom = (int)$m["set_{$s}_dom"];
                $ext = (int)$m["set_{$s}_ext"];
                if ($dom === 0 && $ext === 0) {
                    continue;
                }
                $sets[] = "$dom-$ext";
                $min = $s === 5 ? 15 : 25;
                $won['dom'] += ($dom >= $min && $dom >= $ext + 2) ? 1 : 0;
                $won['ext'] += ($ext >= $min && $ext >= $dom + 2) ? 1 : 0;
            }
            $results[] = array(
                'code_match' => $m['code_match'],
                'date' => DateTime::createFromFormat('Y-m-d', substr($m['date_reception'], 0, 10))->format('d/m/Y'),
                'equipe_dom' => $names[$m['id_equipe_dom']] ?? $m['nom_dom'],
                'equipe_ext' => $names[$m['id_equipe_ext']] ?? $m['nom_ext'],
                'score_dom' => $won['dom'],
                'score_ext' => $won['ext'],
                'sets' => implode(' ', $sets),
                'played' => !empty($sets),
            );
        }
        return $results;
    }

    /**
     * Reconstitue toutes les périodes d'une saison déjà archivée
     * (`rebuild_period`) : championnats en deux demi-saisons, coupure au
     * 1er février (la 1re garde ses rattrapages de janvier) ; coupes sur la
     * saison entière ; phases finales sans classement. Appel ponctuel, en
     * ligne de commande : pas d'action REST.
     *
     * @param string $saison « 2025-2026 »
     * @param array<int, string> $names noms de l'époque, par id d'équipe
     * @param array $penalties pénalités connues par ailleurs (compte rendu de
     *        réunion) : [code_competition][demi_saison][id_equipe] => points
     * @return array une ligne par période : competition, periode, status, divisions, equipes
     * @throws Exception
     */
    public function rebuild_season(string $saison, array $names = array(), array $penalties = array()): array
    {
        if (!preg_match('/^(\d{4})-(\d{4})$/', $saison, $years) || (int)$years[2] !== (int)$years[1] + 1) {
            throw new Exception("Saison attendue au format 2025-2026 : $saison", 400);
        }
        [, $y1, $y2] = $years;
        $codes = array_column($this->sql_manager->execute(
            "SELECT DISTINCT code_competition FROM matches
             WHERE match_status = 'ARCHIVED' AND date_reception BETWEEN ? AND ?
             ORDER BY code_competition",
            array(array('type' => 's', 'value' => "$y1-07-01"), array('type' => 's', 'value' => "$y2-06-30"))),
            'code_competition');
        $report = array();
        foreach ($codes as $code) {
            if (isset(self::CUP_FINALS[$code])) {
                continue;
            }
            $periods = in_array($code, self::FFVB_COMPETITIONS, true)
                ? array(array(1, "$y1-07-01", "$y2-01-31"), array(2, "$y2-02-01", "$y2-06-30"))
                : array(array(0, "$y1-07-01", "$y2-06-30"));
            foreach ($periods as [$half, $from, $to]) {
                $period = array('saison' => $saison, 'demi_saison' => $half);
                $line = array('competition' => $code, 'periode' => self::period_label($period));
                try {
                    $report[] = $line + array('status' => 'rebuilt')
                        + $this->rebuild_period($code, $period, $from, $to, $names, $penalties[$code][$half] ?? array());
                } catch (Exception $e) {
                    if ($e->getCode() !== 409) {
                        throw $e;
                    }
                    $report[] = $line + array('status' => 'skipped', 'message' => $e->getMessage());
                }
            }
        }
        return $report;
    }

    /**
     * Noms d'équipe de la saison précédant les inscriptions en cours : une
     * réinscription garde le nom d'avant (`register.old_team_name`, #402),
     * alors que « Équipes / comptes » a renommé l'équipe. Ne vaut que pour la
     * saison juste avant celle de `register`.
     *
     * @return array<int, string>
     * @throws Exception
     */
    public function previous_season_names(): array
    {
        return array_map('strval', array_column($this->sql_manager->execute(
            "SELECT old_team_id, old_team_name FROM register
             WHERE old_team_id IS NOT NULL AND TRIM(COALESCE(old_team_name, '')) <> ''"),
            'old_team_name', 'old_team_id'));
    }

    /**
     * Classement d'une division calculé sur une LISTE de matchs (archivés),
     * au barème UFOLEP de `get_rank_by_competition_division.sql` : victoire
     * 3, défaite 1, défaite par forfait 0 ; départage par points, différence
     * de sets, puis confrontation directe entre deux équipes (comme
     * `applyHeadToHeadTieBreak`), enfin par nom. Sert à reconstituer une
     * demi-saison passée, dont `classements` ne garde plus rien : pas de
     * pénalité ni de rang de départ, d'où `needs_manual_check` sur les
     * égalités que la confrontation directe ne tranche pas.
     *
     * @param array $matches lignes de `matches` (id_equipe_dom/ext, set_N_dom/ext, is_sign_match_dom/ext)
     * @param array<int, string> $names noms des équipes, par id
     * @param array<int, int> $penalties points de pénalité connus par ailleurs
     *        (compte rendu de réunion), par id d'équipe, déduits avant le tri
     * @return array lignes au format de `getRank` (rang, id_equipe, equipe, points…)
     */
    public static function standings_from_matches(array $matches, array $names, array $penalties = array()): array
    {
        $sets = static function (array $m, string $own, string $opp): int {
            $won = 0;
            for ($s = 1; $s <= 5; $s++) {
                $min = $s === 5 ? 15 : 25;
                $a = (int)($m["set_{$s}_$own"] ?? 0);
                $b = (int)($m["set_{$s}_$opp"] ?? 0);
                $won += ($a >= $min && $a >= $b + 2) ? 1 : 0;
            }
            return $won;
        };
        $forfeit_against = static function (array $m, string $side): bool {
            $opp = $side === 'dom' ? 'ext' : 'dom';
            $signed = (int)($m['is_sign_match_dom'] ?? 0) !== 0 && (int)($m['is_sign_match_ext'] ?? 0) !== 0;
            for ($s = 1; $s <= 3; $s++) {
                if ((int)($m["set_{$s}_$side"] ?? -1) !== 0 || (int)($m["set_{$s}_$opp"] ?? -1) !== 25) {
                    return false;
                }
            }
            return $signed;
        };
        $rows = array();
        $decisive = array();
        foreach ($matches as $m) {
            $sd = $sets($m, 'dom', 'ext');
            $se = $sets($m, 'ext', 'dom');
            if ($sd !== 3 && $se !== 3) {
                continue;
            }
            $decisive[] = array((int)$m['id_equipe_dom'], (int)$m['id_equipe_ext'], $sd, $se);
            foreach (array('dom' => array($sd, $se), 'ext' => array($se, $sd)) as $side => [$own, $opp]) {
                $id = (int)$m["id_equipe_$side"];
                $rows[$id] ??= array('id_equipe' => $id, 'equipe' => $names[$id] ?? (string)$id, 'points' => 0,
                    'joues' => 0, 'gagnes' => 0, 'perdus' => 0, 'sets_pour' => 0, 'sets_contre' => 0,
                    'matches_lost_by_forfeit_count' => 0);
                $ff = $forfeit_against($m, $side);
                $rows[$id]['joues']++;
                $rows[$id]['gagnes'] += $own === 3 ? 1 : 0;
                $rows[$id]['perdus'] += $opp === 3 ? 1 : 0;
                $rows[$id]['sets_pour'] += $own;
                $rows[$id]['sets_contre'] += $opp;
                $rows[$id]['matches_lost_by_forfeit_count'] += $ff ? 1 : 0;
                $rows[$id]['points'] += $own === 3 ? 3 : ($ff ? 0 : 1);
            }
        }
        $rows = array_values($rows);
        foreach ($rows as &$row) {
            $row['penalites'] = (int)($penalties[$row['id_equipe']] ?? 0);
            $row['points'] -= $row['penalites'];
            $row['diff'] = $row['sets_pour'] - $row['sets_contre'];
            $row['needs_manual_check'] = false;
        }
        unset($row);
        usort($rows, static function ($a, $b) {
            return $b['points'] <=> $a['points'] ?: $b['diff'] <=> $a['diff'] ?: strcmp($a['equipe'], $b['equipe']);
        });
        // confrontation directe : groupes de deux à points et différence égaux
        $n = count($rows);
        for ($i = 0; $i < $n; $i = $j) {
            for ($j = $i + 1; $j < $n && $rows[$j]['points'] === $rows[$i]['points'] && $rows[$j]['diff'] === $rows[$i]['diff']; $j++) {
            }
            if ($j - $i === 2) {
                [$a, $b] = array($rows[$i]['id_equipe'], $rows[$i + 1]['id_equipe']);
                $wins = array($a => 0, $b => 0);
                $set_diff = 0;
                foreach ($decisive as [$dom, $ext, $sd, $se]) {
                    if (($dom === $a && $ext === $b) || ($dom === $b && $ext === $a)) {
                        [$for_a, $for_b] = $dom === $a ? array($sd, $se) : array($se, $sd);
                        $wins[$for_a > $for_b ? $a : $b]++;
                        $set_diff += $for_a - $for_b;
                    }
                }
                $cmp = $wins[$a] <=> $wins[$b] ?: $set_diff <=> 0;
                if ($cmp < 0) {
                    [$rows[$i], $rows[$i + 1]] = array($rows[$i + 1], $rows[$i]);
                } elseif ($cmp === 0) {
                    $rows[$i]['needs_manual_check'] = $rows[$i + 1]['needs_manual_check'] = true;
                }
            } elseif ($j - $i >= 3) {
                for ($k = $i; $k < $j; $k++) {
                    $rows[$k]['needs_manual_check'] = true;
                }
            }
        }
        foreach ($rows as $index => &$row) {
            $row['rang'] = $index + 1;
        }
        unset($row);
        return $rows;
    }

    /**
     * Reconstitue après coup le classement d'une période déjà archivée, à
     * partir de ses matchs archivés (`standings_from_matches`) : source
     * REBUILT, sans pénalités ni reports, que la base n'a plus. Ne touche
     * jamais une période figée (FROZEN) ; refait une reconstitution
     * précédente.
     *
     * @param array<int, string> $names noms de l'époque, par id d'équipe (à
     *        défaut, le nom actuel)
     * @param array<int, int> $penalties pénalités connues par ailleurs (compte
     *        rendu de réunion), par id d'équipe
     * @return array{divisions: int, equipes: int}
     * @throws Exception
     */
    public function rebuild_period(string $code_competition, array $period, string $from, string $to,
                                   array $names = array(), array $penalties = array()): array
    {
        if (self::uses_ffvb_scale($code_competition, $to)) {
            // standings_from_matches ne connaît que le barème UFOLEP : une
            // période au barème FFVB doit être figée, pas reconstituée
            throw new Exception("Barème FFVB : cette période se fige, elle ne se reconstitue pas.", 409);
        }
        $period_bindings = array(
            array('type' => 's', 'value' => $period['saison']),
            array('type' => 'i', 'value' => (int)$period['demi_saison']),
            array('type' => 's', 'value' => $code_competition),
        );
        if (!empty($this->sql_manager->execute(
            "SELECT 1 FROM classements_archives WHERE saison = ? AND demi_saison = ? AND code_competition = ?
               AND source = 'FROZEN' LIMIT 1", $period_bindings))) {
            throw new Exception("Cette période a été figée : sa copie fait foi, elle n'est pas reconstituée.", 409);
        }
        $libelle = $this->sql_manager->execute(
            "SELECT libelle FROM competitions WHERE code_competition = ?",
            array(array('type' => 's', 'value' => $code_competition)))[0]['libelle'] ?? $code_competition;
        $matches = $this->sql_manager->execute(
            "SELECT * FROM matches
             WHERE code_competition = ? AND match_status = 'ARCHIVED'
               AND date_reception BETWEEN ? AND ?
             ORDER BY division, date_reception",
            array(
                array('type' => 's', 'value' => $code_competition),
                array('type' => 's', 'value' => $from),
                array('type' => 's', 'value' => $to),
            ));
        $teams = array();
        foreach ($this->sql_manager->execute(
            "SELECT e.id_equipe, e.nom_equipe, cl.nom AS club FROM equipes e LEFT JOIN clubs cl ON cl.id = e.id_club") as $t) {
            $teams[(int)$t['id_equipe']] = $t;
        }
        $by_division = array();
        foreach ($matches as $m) {
            $by_division[$m['division']][] = $m;
        }
        $result = array('divisions' => 0, 'equipes' => 0);
        $db = Database::openDbConnection();
        mysqli_begin_transaction($db);
        try {
            $this->sql_manager->execute(
                "DELETE FROM classements_archives WHERE saison = ? AND demi_saison = ? AND code_competition = ?",
                $period_bindings);
            foreach ($by_division as $division => $division_matches) {
                $label = static fn($id) => $names[$id] ?? ($teams[$id]['nom_equipe'] ?? (string)$id);
                $ids = array_unique(array_merge(array_column($division_matches, 'id_equipe_dom'),
                    array_column($division_matches, 'id_equipe_ext')));
                $standings = self::standings_from_matches($division_matches,
                    array_combine(array_map('intval', $ids), array_map(fn($id) => $label((int)$id), $ids)),
                    $penalties);
                $dates = array_column($division_matches, 'date_reception');
                foreach ($standings as $row) {
                    $this->sql_manager->execute(
                        "INSERT INTO classements_archives SET
                            saison = ?, demi_saison = ?, code_competition = ?, libelle_competition = ?,
                            division = ?, rang = ?, id_equipe = ?, nom_equipe = ?, club = ?,
                            points = ?, joues = ?, gagnes = ?, perdus = ?, sets_pour = ?, sets_contre = ?,
                            penalites = ?, reports = 0, forfaits = ?,
                            premier_match = ?, dernier_match = ?, source = 'REBUILT', frozen_at = NOW()",
                        array_merge($period_bindings, array(
                            array('type' => 's', 'value' => $libelle),
                            array('type' => 's', 'value' => (string)$division),
                            array('type' => 'i', 'value' => $row['rang']),
                            array('type' => 'i', 'value' => $row['id_equipe']),
                            array('type' => 's', 'value' => $row['equipe']),
                            array('type' => 's', 'value' => $teams[$row['id_equipe']]['club'] ?? null),
                            array('type' => 'i', 'value' => $row['points']),
                            array('type' => 'i', 'value' => $row['joues']),
                            array('type' => 'i', 'value' => $row['gagnes']),
                            array('type' => 'i', 'value' => $row['perdus']),
                            array('type' => 'i', 'value' => $row['sets_pour']),
                            array('type' => 'i', 'value' => $row['sets_contre']),
                            array('type' => 'i', 'value' => $row['penalites']),
                            array('type' => 'i', 'value' => $row['matches_lost_by_forfeit_count']),
                            array('type' => 's', 'value' => min($dates)),
                            array('type' => 's', 'value' => max($dates)),
                        )));
                    $result['equipes']++;
                }
                $result['divisions']++;
            }
            mysqli_commit($db);
        } catch (Throwable $e) {
            mysqli_rollback($db);
            throw $e;
        }
        return $result;
    }

    /**
     * Saison et demi-saison d'une période qui commence par un match du
     * `$first_match_date` : la saison va de juillet à juin ; un championnat
     * (m, f, mo) se joue en deux demi-saisons (1re : juillet-décembre et ses
     * rattrapages de janvier, 2e : à partir de février) ; une coupe dure toute
     * la saison (demi-saison 0).
     *
     * @return array{saison: string, demi_saison: int}
     */
    public static function period_of(string $code_competition, string $first_match_date): array
    {
        $date = new DateTime($first_match_date);
        $month = (int)$date->format('n');
        $start_year = $month >= 7 ? (int)$date->format('Y') : (int)$date->format('Y') - 1;
        $half = in_array($code_competition, self::FFVB_COMPETITIONS, true) ? ($month >= 7 ? 1 : 2) : 0;
        return array('saison' => $start_year . '-' . ($start_year + 1), 'demi_saison' => $half);
    }

    public static function period_label(array $period): string
    {
        return $period['saison'] . match ((int)$period['demi_saison']) {
            1 => ' · 1re demi-saison',
            2 => ' · 2e demi-saison',
            default => '',
        };
    }

    /**
     * La période en cours d'une compétition : celle de ses matchs joués et
     * pas encore archivés. Null s'il n'y en a aucun (rien à figer).
     *
     * @return array{saison: string, demi_saison: int, premier_match: string, dernier_match: string}|null
     * @throws Exception
     */
    public function current_period(string $code_competition): ?array
    {
        $rows = $this->sql_manager->execute(
            "SELECT MIN(date_reception) AS premier_match, MAX(date_reception) AS dernier_match
             FROM matches
             WHERE code_competition = ? AND match_status <> 'ARCHIVED'
               AND (set_1_dom > 0 OR set_1_ext > 0)",
            array(array('type' => 's', 'value' => $code_competition)));
        if (empty($rows[0]['premier_match'])) {
            return null;
        }
        return self::period_of($code_competition, $rows[0]['premier_match'])
            + array('premier_match' => $rows[0]['premier_match'], 'dernier_match' => $rows[0]['dernier_match']);
    }

    /**
     * @throws Exception
     */
    public function is_period_frozen(string $code_competition, array $period): bool
    {
        return !empty($this->sql_manager->execute(
            "SELECT 1 FROM classements_archives
             WHERE saison = ? AND demi_saison = ? AND code_competition = ? LIMIT 1",
            array(
                array('type' => 's', 'value' => $period['saison']),
                array('type' => 'i', 'value' => (int)$period['demi_saison']),
                array('type' => 's', 'value' => $code_competition),
            )));
    }

    /**
     * Bouton « Figer le classement » de l'écran Compétitions (admin) : fige la
     * période en cours des compétitions choisies, en remplaçant une copie
     * déjà faite (une pénalité tardive se reporte en refigeant).
     *
     * @return array{message: string, report: array}
     * @throws Exception
     */
    public function freezeRanks($ids): array
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seul un administrateur peut figer un classement !", 403);
        }
        require_once __DIR__ . '/Competition.php';
        $report = array();
        foreach (Generic::parse_id_list($ids) as $id) {
            $competition = (new Competition())->get_by_id($id);
            $report[] = $this->freeze_competition($competition['code_competition']);
        }
        $frozen = array_filter($report, static fn($line) => $line['status'] === 'frozen');
        return array(
            'message' => count($frozen) . " classement(s) figé(s)"
                . (count($frozen) < count($report) ? ", " . (count($report) - count($frozen)) . " sans match joué" : ''),
            'report' => $report,
        );
    }

    /**
     * Recopie dans `classements_archives` le classement de chaque division de
     * la compétition, tel que le site l'affiche (`getRank`) : pénalités,
     * reports, nom et club de l'équipe compris. Il faut le faire AVANT
     * l'archivage des matchs (ensuite, le classement n'est plus calculable :
     * les deux calculs ignorent les matchs archivés) et avant la remise à zéro
     * des pénalités.
     *
     * @param bool $replace false : ne refait pas une période déjà figée
     *                      (appel automatique de `Register::cleanup_before_start`,
     *                      qui ne doit pas écraser la copie faite à la main)
     * @return array{competition: string, periode: ?string, status: string, divisions: int, equipes: int}
     * @throws Exception
     */
    public function freeze_competition(string $code_competition, bool $replace = true): array
    {
        $libelle = $this->sql_manager->execute(
            "SELECT libelle FROM competitions WHERE code_competition = ?",
            array(array('type' => 's', 'value' => $code_competition)))[0]['libelle'] ?? $code_competition;
        $line = array('competition' => $libelle, 'periode' => null, 'status' => 'skipped', 'divisions' => 0, 'equipes' => 0);
        $period = $this->current_period($code_competition);
        if ($period === null) {
            return $line + array('message' => 'aucun match joué à figer');
        }
        $line['periode'] = self::period_label($period);
        if (!$replace && $this->is_period_frozen($code_competition, $period)) {
            return array_merge($line, array('message' => 'déjà figé'));
        }
        $divisions = array_column($this->sql_manager->execute(
            "SELECT DISTINCT division FROM classements WHERE code_competition = ? ORDER BY division",
            array(array('type' => 's', 'value' => $code_competition))), 'division');
        $clubs = array_column($this->sql_manager->execute(
            "SELECT e.id_equipe, cl.nom FROM classements c
             JOIN equipes e ON e.id_equipe = c.id_equipe
             LEFT JOIN clubs cl ON cl.id = e.id_club
             WHERE c.code_competition = ?",
            array(array('type' => 's', 'value' => $code_competition))), 'nom', 'id_equipe');
        $ranks = array();
        foreach ($divisions as $division) {
            $rows = $this->getRank($code_competition, $division);
            // division d'attente (X) ou vide : personne n'y a joué
            if (array_sum(array_map(static fn($row) => (int)$row['joues'], $rows)) > 0) {
                $ranks[$division] = $rows;
            }
        }
        $period_bindings = array(
            array('type' => 's', 'value' => $period['saison']),
            array('type' => 'i', 'value' => (int)$period['demi_saison']),
            array('type' => 's', 'value' => $code_competition),
        );
        @session_start();
        $frozen_by = empty($_SESSION['id_user']) ? null : (int)$_SESSION['id_user'];
        $db = Database::openDbConnection();
        mysqli_begin_transaction($db);
        try {
            $this->sql_manager->execute(
                "DELETE FROM classements_archives WHERE saison = ? AND demi_saison = ? AND code_competition = ?",
                $period_bindings);
            foreach ($ranks as $division => $rows) {
                foreach ($rows as $row) {
                    $this->sql_manager->execute(
                        "INSERT INTO classements_archives SET
                            saison = ?, demi_saison = ?, code_competition = ?, libelle_competition = ?,
                            division = ?, rang = ?, id_equipe = ?, nom_equipe = ?, club = ?,
                            points = ?, joues = ?, gagnes = ?, perdus = ?, sets_pour = ?, sets_contre = ?,
                            quotient_sets = ?, quotient_points = ?, penalites = ?, reports = ?, forfaits = ?,
                            premier_match = ?, dernier_match = ?, source = 'FROZEN', frozen_at = NOW(), frozen_by = ?",
                        array_merge($period_bindings, array(
                            array('type' => 's', 'value' => $libelle),
                            array('type' => 's', 'value' => (string)$division),
                            array('type' => 'i', 'value' => (int)$row['rang']),
                            array('type' => 'i', 'value' => (int)$row['id_equipe']),
                            array('type' => 's', 'value' => $row['equipe']),
                            array('type' => 's', 'value' => $clubs[$row['id_equipe']] ?? null),
                            array('type' => 'i', 'value' => (int)$row['points']),
                            array('type' => 'i', 'value' => (int)$row['joues']),
                            array('type' => 'i', 'value' => (int)$row['gagnes']),
                            array('type' => 'i', 'value' => (int)$row['perdus']),
                            array('type' => 'i', 'value' => (int)$row['sets_pour']),
                            array('type' => 'i', 'value' => (int)$row['sets_contre']),
                            array('type' => 's', 'value' => $row['quotient_sets'] ?? null),
                            array('type' => 's', 'value' => $row['quotient_points'] ?? null),
                            array('type' => 'i', 'value' => (int)($row['penalites'] ?? 0)),
                            array('type' => 'i', 'value' => (int)($row['report_count'] ?? 0)),
                            array('type' => 'i', 'value' => (int)($row['matches_lost_by_forfeit_count'] ?? 0)),
                            array('type' => 's', 'value' => $period['premier_match']),
                            array('type' => 's', 'value' => $period['dernier_match']),
                            array('type' => 'i', 'value' => $frozen_by),
                        )));
                    $line['equipes']++;
                }
                $line['divisions']++;
            }
            mysqli_commit($db);
        } catch (Throwable $e) {
            mysqli_rollback($db);
            throw $e;
        }
        $this->addActivity("Classement figé : $libelle, " . $line['periode']
            . " ({$line['divisions']} division(s), {$line['equipes']} équipe(s))");
        return array_merge($line, array('status' => 'frozen', 'message' => 'figé'));
    }

    public function get_report_count($team_id, mixed $code_competition)
    {
        $sql = "SELECT report_count 
            FROM classements 
            WHERE id_equipe = $team_id 
            AND code_competition = '$code_competition'";
        $results = $this->sql_manager->execute($sql);
        return intval($results[0]['report_count']);
    }

    /**
     * @throws Exception
     */
    public function saveRank($id,
                             $code_competition,
                             $division,
                             $id_equipe,
                             $rank_start,
                             $will_register_again): int|array|string|null
    {
        return $this->save(array(
            'id' => $id,
            'code_competition' => $code_competition,
            'division' => $division,
            'id_equipe' => $id_equipe,
            'rank_start' => $rank_start,
            'will_register_again' => $will_register_again,
        ));
    }

    public function save($inputs)
    {
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " classements SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                    break;
                case 'id_equipe':
                case 'rank_start':
                    $bindings[] = array('type' => 'i', 'value' => $value);
                    $sql .= "$key = ?,";
                    break;
                case 'division':
                    if (is_numeric($value)) {
                        $bindings[] = array('type' => 'i', 'value' => $value);
                    } else {
                        $bindings[] = array('type' => 's', 'value' => $value);
                    }
                    $sql .= "$key = ?,";
                    break;
                case 'will_register_again':
                    $val = Generic::to_flag($value);
                    $bindings[] = array(
                        'type' => 'i',
                        'value' => $val
                    );
                    $sql .= "$key = ?,";
                    break;
                default:
                    $bindings[] = array('type' => 's', 'value' => $value);
                    $sql .= "$key = ?,";
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (!empty($inputs['id'])) {
            $bindings[] = array('type' => 'i', 'value' => $inputs['id']);
            $sql .= " WHERE id = ?";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function get_rank_for_cup($code_competition): int|array|string|null
    {
        $sql = file_get_contents(__DIR__ . '/../sql/get_rank_for_cup.sql');
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $code_competition);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function sort_cup_rank($code_competition): array|int|string
    {
        $results = $this->get_rank_for_cup($code_competition);
        // Le rang_poule est maintenant directement inclus dans les résultats
        $rangs_poules = array_column($results, 'rang_poule');

        array_multisort($rangs_poules, SORT_ASC, $results);
        foreach ($results as $index => $result) {
            $results[$index]['rang'] = $index + 1;
        }
        return $results;
    }

    /**
     * @throws Exception
     */
    public function getRank($competition, $division, $id_team = null): array|int|string|null
    {
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $competition);
        $bindings[] = array('type' => 's', 'value' => $division);
        if (self::uses_ffvb_scale($competition)) {
            // Championnat : barème et départage FFVB entièrement en SQL, sans
            // confrontation directe (issue #347).
            $sql = file_get_contents(__DIR__ . '/../sql/get_rank_ffvb_by_competition_division.sql');
            $bindings[] = array('type' => 's', 'value' => $competition);
            $bindings[] = array('type' => 's', 'value' => $division);
            $results = $this->sql_manager->execute($sql, $bindings);
            if (empty($id_team)) {
                return $results;
            }
            // ranks_view garde le barème UFOLEP des coupes : pour une équipe
            // de championnat, on filtre le classement de sa division.
            return array_values(array_filter($results, static function ($row) use ($id_team) {
                return (int)$row['id_equipe'] === (int)$id_team;
            }));
        }
        if (empty($id_team)) {
            $sql = file_get_contents(__DIR__ . '/../sql/get_rank_by_competition_division.sql');
            // La requête référence code_competition/division deux fois (CTE matches + jointure classements)
            $bindings[] = array('type' => 's', 'value' => $competition);
            $bindings[] = array('type' => 's', 'value' => $division);
            $results = $this->sql_manager->execute($sql, $bindings);
            if (is_array($results) && count($results) > 1) {
                $results = $this->applyHeadToHeadTieBreak($results, $competition, $division);
            }
            return $results;
        }
        $sql = file_get_contents(__DIR__ . '/../sql/get_rank_by_competition_division_id_team.sql');
        $bindings[] = array('type' => 'i', 'value' => $id_team);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Départage les équipes à égalité (mêmes points ET même différence de sets) par
     * la confrontation directe : le vainqueur du match qui les oppose passe devant.
     *
     * - Groupe de 2 équipes : on résout via les matchs joués entre elles (victoires,
     *   puis différence de sets de ces matchs). Si rien de décisif (non joué / double
     *   forfait), l'ordre est conservé et les équipes sont marquées `needs_manual_check`.
     * - Groupe de 3 équipes ou plus : pas de départage automatique, marqué
     *   `needs_manual_check` (à trancher manuellement).
     *
     * Le `rang` est ensuite réattribué séquentiellement selon le nouvel ordre.
     *
     * @param array $rows Résultats de ranks_view (incluant rang, points, diff, id_equipe)
     * @param string $competition
     * @param string $division
     * @return array
     * @throws Exception
     */
    private function applyHeadToHeadTieBreak(array $rows, string $competition, string $division): array
    {
        // Base d'ordre stable : le rang calculé par la vue
        usort($rows, fn($a, $b) => ((int)$a['rang']) <=> ((int)$b['rang']));
        foreach ($rows as &$row) {
            $row['needs_manual_check'] = false;
            $row['tie_broken_h2h'] = false;
        }
        unset($row);

        $n = count($rows);
        $i = 0;
        while ($i < $n) {
            // Trouver la fin du groupe à points + diff identiques
            $j = $i + 1;
            while ($j < $n
                && (string)$rows[$j]['points'] === (string)$rows[$i]['points']
                && (string)$rows[$j]['diff'] === (string)$rows[$i]['diff']) {
                $j++;
            }
            $groupSize = $j - $i;

            if ($groupSize === 2) {
                $cmp = $this->resolveHeadToHead(
                    $competition,
                    $division,
                    (int)$rows[$i]['id_equipe'],
                    (int)$rows[$i + 1]['id_equipe']
                );
                if ($cmp === 0) {
                    // Indécidable : on conserve l'ordre et on signale
                    $rows[$i]['needs_manual_check'] = true;
                    $rows[$i + 1]['needs_manual_check'] = true;
                } else {
                    if ($cmp < 0) {
                        // La 2e équipe a gagné la confrontation directe : elle passe devant
                        $tmp = $rows[$i];
                        $rows[$i] = $rows[$i + 1];
                        $rows[$i + 1] = $tmp;
                    }
                    $rows[$i]['tie_broken_h2h'] = true;
                    $rows[$i + 1]['tie_broken_h2h'] = true;
                }
            } elseif ($groupSize >= 3) {
                for ($k = $i; $k < $j; $k++) {
                    $rows[$k]['needs_manual_check'] = true;
                }
            }

            $i = $j;
        }

        // Réattribution séquentielle du rang après réordonnancement
        foreach ($rows as $index => &$row) {
            $row['rang'] = $index + 1;
        }
        unset($row);

        return $rows;
    }

    /**
     * Détermine le vainqueur de la confrontation directe entre deux équipes.
     * Agrège tous les matchs décisifs (un camp à 3 sets) joués entre elles : on compare
     * d'abord le nombre de victoires, puis la différence de sets de ces matchs.
     *
     * @return int  1 si A devant B, -1 si B devant A, 0 si indécidable (non joué,
     *              double forfait, ou parfaite égalité sur ces matchs)
     * @throws Exception
     */
    private function resolveHeadToHead(string $competition, string $division, int $idA, int $idB): int
    {
        // On interroge directement la table `matches` (et non la vue `matchs_view`, très
        // coûteuse : ~17 jointures + sous-requêtes par ligne). Le score en sets est
        // recalculé inline avec les mêmes règles que la vue (25 pts / 15 au 5e set, +2 d'écart).
        $sql = "SELECT m.id_equipe_dom, m.id_equipe_ext,
                  ((m.set_1_dom >= 25 AND m.set_1_dom >= m.set_1_ext + 2)
                 + (m.set_2_dom >= 25 AND m.set_2_dom >= m.set_2_ext + 2)
                 + (m.set_3_dom >= 25 AND m.set_3_dom >= m.set_3_ext + 2)
                 + (m.set_4_dom >= 25 AND m.set_4_dom >= m.set_4_ext + 2)
                 + (m.set_5_dom >= 15 AND m.set_5_dom >= m.set_5_ext + 2)) AS score_equipe_dom,
                  ((m.set_1_ext >= 25 AND m.set_1_ext >= m.set_1_dom + 2)
                 + (m.set_2_ext >= 25 AND m.set_2_ext >= m.set_2_dom + 2)
                 + (m.set_3_ext >= 25 AND m.set_3_ext >= m.set_3_dom + 2)
                 + (m.set_4_ext >= 25 AND m.set_4_ext >= m.set_4_dom + 2)
                 + (m.set_5_ext >= 15 AND m.set_5_ext >= m.set_5_dom + 2)) AS score_equipe_ext
                FROM matches m
                WHERE m.code_competition = ?
                  AND m.division = ?
                  AND m.match_status <> 'ARCHIVED'
                  AND ((m.id_equipe_dom = ? AND m.id_equipe_ext = ?)
                    OR (m.id_equipe_dom = ? AND m.id_equipe_ext = ?))";
        $bindings = [
            ['type' => 's', 'value' => $competition],
            ['type' => 's', 'value' => $division],
            ['type' => 'i', 'value' => $idA],
            ['type' => 'i', 'value' => $idB],
            ['type' => 'i', 'value' => $idB],
            ['type' => 'i', 'value' => $idA],
        ];
        $matches = $this->sql_manager->execute($sql, $bindings);
        if (!is_array($matches) || empty($matches)) {
            return 0;
        }

        $winsA = 0;
        $winsB = 0;
        $setDiffA = 0;
        foreach ($matches as $match) {
            $scoreDom = (int)$match['score_equipe_dom'];
            $scoreExt = (int)$match['score_equipe_ext'];
            // Match décisif uniquement si un camp atteint 3 sets
            if ($scoreDom !== 3 && $scoreExt !== 3) {
                continue;
            }
            $domIsA = ((int)$match['id_equipe_dom'] === $idA);
            $scoreForA = $domIsA ? $scoreDom : $scoreExt;
            $scoreForB = $domIsA ? $scoreExt : $scoreDom;
            if ($scoreForA > $scoreForB) {
                $winsA++;
            } elseif ($scoreForB > $scoreForA) {
                $winsB++;
            }
            $setDiffA += ($scoreForA - $scoreForB);
        }

        if ($winsA === 0 && $winsB === 0) {
            return 0; // aucun match décisif joué
        }
        if ($winsA !== $winsB) {
            return $winsA > $winsB ? 1 : -1;
        }
        if ($setDiffA !== 0) {
            return $setDiffA > 0 ? 1 : -1;
        }
        return 0; // parfaitement à égalité sur la confrontation directe
    }

    /**
     * Divisions de chaque compétition, pour les menus du site public.
     *
     * Triées numériquement : sans ORDER BY, elles sortaient dans l'ordre des
     * lignes de `classements`, mélangé dès qu'on réorganise les divisions
     * (« division 1, 2, 4, 3, 5 » en octobre 2026). `CAST` met aussi
     * « 10 » après « 2 ».
     */
    public function getDivisions()
    {
        $sql = "SELECT
        DISTINCT c.division,
        c.code_competition,
        comp.libelle AS libelle_competition
      FROM classements c
      JOIN competitions comp ON comp.code_competition = c.code_competition
      ORDER BY c.code_competition, CAST(c.division AS UNSIGNED), c.division";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function getTeamRank($competition, $division, $idTeam)
    {
        $results = $this->getRank($competition, $division, $idTeam);
        if (count($results) !== 1) {
            return '';
        }
        return $results[0]['rang'];
    }

    /**
     * @throws Exception
     */
    public function addPenalty($compet, $id_equipe)
    {
        // issue #270
        $sql = "SELECT penalite,division FROM classements WHERE id_equipe = ? AND code_competition = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $id_equipe),
            array('type' => 's', 'value' => $compet),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        if (count($results) != 1) {
            throw new Exception("Impossible de récupérer les pénalités de l'équipe !");
        }
        $data = $results[0];
        $penalite = $data['penalite'];
        $penalite++;
        $sqlmaj = "UPDATE classements SET penalite = ? WHERE id_equipe = ? AND code_competition = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $penalite),
            array('type' => 'i', 'value' => $id_equipe),
            array('type' => 's', 'value' => $compet),
        );
        $this->sql_manager->execute($sqlmaj, $bindings);
        $this->addActivity("Une penalite a ete infligee a l'equipe " . $this->team->getTeamName($id_equipe), $id_equipe);
        return true;
    }

    public function removePenalty($compet, $id_equipe)
    {
        // issue #270
        $sql = "SELECT penalite,division FROM classements WHERE id_equipe = ? AND code_competition = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $id_equipe),
            array('type' => 's', 'value' => $compet),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        if (count($results) != 1) {
            throw new Exception("Impossible de récupérer les pénalités de l'équipe !");
        }
        $data = $results[0];
        $penalite = $data['penalite'];
        $penalite--;
        if ($penalite < 0) {
            $penalite = 0;
        }
        $sqlmaj = "UPDATE classements SET penalite = ? WHERE id_equipe = ? AND code_competition = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $penalite),
            array('type' => 'i', 'value' => $id_equipe),
            array('type' => 's', 'value' => $compet),
        );
        $this->sql_manager->execute($sqlmaj, $bindings);
        $this->addActivity("Une penalite a ete annulee pour l'equipe " . $this->team->getTeamName($id_equipe), $id_equipe);
        return true;
    }

    public function incrementReportCount($compet, $id_equipe)
    {
        // issue #270
        $sql = "UPDATE classements SET report_count = report_count + 1 WHERE id_equipe = ? AND code_competition = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $id_equipe),
            array('type' => 's', 'value' => $compet),
        );
        $this->sql_manager->execute($sql, $bindings);
        $this->addActivity("Un report a ete comptabilise pour l'equipe " . $this->team->getTeamName($id_equipe), $id_equipe);
        return true;
    }

    public function decrementReportCount($compet, $id_equipe)
    {
        // issue #270
        $sql = "UPDATE classements SET report_count = report_count - 1 WHERE id_equipe = ? AND code_competition = ? AND report_count > 0";
        $bindings = array(
            array('type' => 'i', 'value' => $id_equipe),
            array('type' => 's', 'value' => $compet),
        );
        $this->sql_manager->execute($sql, $bindings);
        $this->addActivity("Un report a ete retire pour l'equipe " . $this->team->getTeamName($id_equipe), $id_equipe);
        return true;
    }

    /**
     * @param string $code_competition
     * @param string $division
     * @param int $id_equipe
     * @param int $rank_start
     * @return array|int|string|null
     * @throws Exception
     */
    public function insert(string $code_competition, string $division, int $id_equipe, int $rank_start): array|int|string|null
    {
        $sql = "INSERT INTO classements(code_competition, division, id_equipe, rank_start)
                VALUES (?, ?, ?, ?)";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $code_competition);
        $bindings[] = array('type' => 's', 'value' => $division);
        $bindings[] = array('type' => 'i', 'value' => $id_equipe);
        $bindings[] = array('type' => 'i', 'value' => $rank_start);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Classement de tout un championnat, division par division, au barème
     * FFVB (issue #347) : même ordre que les classements affichés — sert à
     * composer les poules de coupe (init_classements_isoardi). Comme la
     * requête UFOLEP, seules les équipes inscrites en coupe sont gardées.
     * @throws Exception
     */
    private function get_full_championship_rank(string $code_competition): array
    {
        $clubs = array();
        foreach ($this->sql_manager->execute(
            "SELECT e.id_equipe, cl.nom AS club
             FROM classements c
             JOIN equipes e ON e.id_equipe = c.id_equipe
             LEFT JOIN clubs cl ON cl.id = e.id_club
             WHERE c.code_competition = ? AND e.is_cup_registered = 1",
            array(array('type' => 's', 'value' => $code_competition))) as $row) {
            $clubs[(int)$row['id_equipe']] = $row['club'];
        }
        $full = array();
        foreach ($this->getDivisionsFromCompetition($code_competition) as $division) {
            foreach ($this->getRank($code_competition, $division['division']) as $row) {
                if (!array_key_exists((int)$row['id_equipe'], $clubs)) {
                    continue;
                }
                $row['club'] = $clubs[(int)$row['id_equipe']];
                $row['rang'] = count($full) + 1;
                $full[] = $row;
            }
        }
        return $full;
    }

    /**
     * @param string $code_competition
     * @return array|int|string|null
     * @throws Exception
     */
    public function get_full_competition_rank(string $code_competition): array|int|string|null
    {
        if (self::uses_ffvb_scale($code_competition)) {
            return $this->get_full_championship_rank($code_competition);
        }
        $sql = file_get_contents(__DIR__ . '/../sql/get_rank_by_competition.sql');
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $code_competition);
        $bindings[] = array('type' => 's', 'value' => $code_competition);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function delete_competition($id_competition)
    {
        $sql = "DELETE 
                FROM classements 
                WHERE code_competition IN (SELECT code_competition
                                           FROM competitions 
                                           WHERE id = ?)";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    /**
     * Classements de la saison, reconstruits depuis les inscriptions.
     *
     * Seules les inscriptions VALIDÉES comptent (issue #388), comme pour les
     * équipes, comptes et créneaux de `Register::set_up_season` : une demande
     * refusée ou en attente d'une équipe existante était remise en classement.
     * Une réinscription n'engage que son ancienne équipe, pas un homonyme
     * (issue #390).
     */
    public function insert_from_register($id_competition)
    {
        $sql = "INSERT INTO classements(
                        code_competition,
                        division,
                        id_equipe,
                        rank_start)
                SELECT c.code_competition,
                       CASE WHEN r.division IS NULL THEN ? ELSE r.division END AS division,
                       e.id_equipe,
                       CASE WHEN r.rank_start IS NULL THEN 1 ELSE r.rank_start END AS rank_start
                FROM register r
                JOIN competitions c on r.id_competition = c.id
                JOIN equipes e on e.code_competition = c.code_competition
                                  AND " . self::REGISTRATION_MATCHES_TEAM . "
                WHERE r.id_competition = ?
                  AND r.status = 'VALIDATED'
                ORDER BY code_competition, division, rank_start";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => self::DIVISION_TO_PLACE);
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function getTeamDivision(mixed $code_competition, mixed $id_equipe)
    {
        $result = $this->sql_manager->execute(
            $this->getSql("c.code_competition = '$code_competition' AND c.id_equipe = $id_equipe"));
        if (count($result) !== 1) {
            $team = $this->team->getTeam($id_equipe);
            $team_name = $team['nom_equipe'];
            throw new Exception("division non trouvée pour l'équipe $team_name dans la compétition $code_competition !");
        }
        return $result[0]['division'];
    }

    /**
     * Get teams registered for Khoury Hanna cup, sorted by registration date
     * All teams in one chapeau for fully random draw
     * @param int $max_teams_per_pool Maximum teams per pool (default 4)
     * @return array
     * @throws Exception
     */
    public function getKHCupDrawData(int $max_teams_per_pool = 4): array
    {
        $sql = "SELECT 
                    r.id AS id_register,
                    COALESCE(e.id_equipe, r.old_team_id) AS id_equipe,
                    r.new_team_name AS equipe,
                    c.nom AS club,
                    DATE_FORMAT(r.creation_date, '%d/%m/%Y %H:%i') AS date_inscription
                FROM register r
                JOIN clubs c ON c.id = r.id_club
                JOIN competitions comp ON comp.id = r.id_competition
                LEFT JOIN equipes e ON e.nom_equipe = r.new_team_name 
                    AND e.code_competition = comp.code_competition
                WHERE comp.code_competition = 'kh'
                ORDER BY r.creation_date";

        $teams = $this->sql_manager->execute($sql);

        if (empty($teams)) {
            return [
                'teams' => [],
                'total_teams' => 0,
                'nb_pools' => 0,
                'teams_per_pool' => $max_teams_per_pool
            ];
        }

        $total_teams = count($teams);
        $nb_pools = (int)ceil($total_teams / $max_teams_per_pool);

        // Add order based on registration date
        foreach ($teams as $index => &$team) {
            $team['rang'] = $index + 1;
        }

        return [
            'teams' => $teams,
            'total_teams' => $total_teams,
            'nb_pools' => $nb_pools,
            'teams_per_pool' => $max_teams_per_pool
        ];
    }

    /**
     * Get cup finals draw data for knockout rounds (1/8 finals with 16 teams)
     * Based on pool count: generates position names (1er poule X, meilleur 2e)
     * This is used BEFORE pools are played to define the bracket
     * @param int $nb_pools Total number of pools
     * @param bool $has_tableau Whether the cup has tableau haut/bas (Isoardi) or not (Khoury Hanna)
     * @return array
     */
    public function getCupFinalsDraw(int $nb_pools, bool $has_tableau = true): array
    {
        $nb_qualified = 16;

        if ($nb_pools === 0) {
            return [
                'qualified' => [],
                'bracket' => [],
                'nb_pools' => 0,
                'nb_first_places' => 0,
                'nb_best_seconds' => 0
            ];
        }

        // All 1st places qualify
        $nb_first_places = $nb_pools;
        // Remaining slots go to best 2nd places
        $nb_best_seconds = max(0, $nb_qualified - $nb_first_places);

        // Generate qualified positions list
        $qualified = [];

        if ($has_tableau) {
            // Isoardi: pools split into tableau haut (1-7) and tableau bas (8-14)
            $half_pools = (int)ceil($nb_pools / 2);

            // Tableau haut: 1er poule 1 to 1er poule 7
            for ($i = 1; $i <= $half_pools; $i++) {
                $qualified[] = ['label' => "1er poule $i", 'tableau' => 'haut', 'position' => 1, 'pool' => $i];
            }
            // Tableau bas: 1er poule 8 to 1er poule 14
            for ($i = $half_pools + 1; $i <= $nb_pools; $i++) {
                $qualified[] = ['label' => "1er poule $i", 'tableau' => 'bas', 'position' => 1, 'pool' => $i];
            }
            // Best 2nd places
            for ($i = 1; $i <= $nb_best_seconds; $i++) {
                $qualified[] = ['label' => "meilleur 2e $i/$nb_best_seconds", 'tableau' => 'mixte', 'position' => 2, 'pool' => null];
            }
        } else {
            // Khoury Hanna: no tableau distinction
            for ($i = 1; $i <= $nb_pools; $i++) {
                $qualified[] = ['label' => "1er poule $i", 'tableau' => null, 'position' => 1, 'pool' => $i];
            }
            for ($i = 1; $i <= $nb_best_seconds; $i++) {
                $qualified[] = ['label' => "meilleur 2e $i/$nb_best_seconds", 'tableau' => null, 'position' => 2, 'pool' => null];
            }
        }

        // Generate bracket for 1/8 finals
        $bracket = $this->generateFinalsBracketFromPositions($qualified, $has_tableau);

        return [
            'qualified' => $qualified,
            'bracket' => $bracket,
            'nb_pools' => $nb_pools,
            'nb_first_places' => $nb_first_places,
            'nb_best_seconds' => $nb_best_seconds
        ];
    }

    /**
     * Generate bracket for 1/8 finals from position labels
     * @param array $qualified List of qualified positions (16 teams)
     * @param bool $has_tableau Whether to cross tableaux
     * @return array
     */
    private function generateFinalsBracketFromPositions(array $qualified, bool $has_tableau): array
    {
        $bracket = [];
        $nb_matches = 8; // 1/8 finals = 8 matches

        if ($has_tableau) {
            // Isoardi: cross tableau haut vs tableau bas
            // Separate qualified by tableau
            $haut = array_values(array_filter($qualified, fn($q) => $q['tableau'] === 'haut'));
            $bas = array_values(array_filter($qualified, fn($q) => $q['tableau'] === 'bas'));
            $seconds = array_values(array_filter($qualified, fn($q) => $q['position'] === 2));

            // Add seconds to the smaller tableau to balance
            foreach ($seconds as $second) {
                if (count($haut) <= count($bas)) {
                    $haut[] = $second;
                } else {
                    $bas[] = $second;
                }
            }

            // Now pair haut[0] vs bas[last], haut[1] vs bas[last-1], etc.
            // This ensures crossing: best haut vs worst bas, etc.
            for ($i = 0; $i < $nb_matches && $i < count($haut); $i++) {
                $opponent_idx = count($bas) - 1 - $i;
                if ($opponent_idx >= 0 && isset($bas[$opponent_idx])) {
                    $bracket[] = [
                        'match' => $i + 1,
                        'team1' => $haut[$i]['label'],
                        'team2' => $bas[$opponent_idx]['label']
                    ];
                }
            }
        } else {
            // Khoury Hanna: simple 1 vs 16, 2 vs 15, etc.
            // All qualified in one list, sorted by seed
            for ($i = 0; $i < $nb_matches; $i++) {
                $team1_idx = $i;
                $team2_idx = count($qualified) - 1 - $i;
                if ($team1_idx < $team2_idx && isset($qualified[$team1_idx]) && isset($qualified[$team2_idx])) {
                    $bracket[] = [
                        'match' => $i + 1,
                        'team1' => $qualified[$team1_idx]['label'],
                        'team2' => $qualified[$team2_idx]['label']
                    ];
                }
            }
        }

        return $bracket;
    }

    /**
     * Get cup draw data with chapeaux based on ranking position for a competition
     * Chapeaux are created separately for tableau haut and tableau bas
     * Each chapeau has the same size to ensure max 4 teams per pool
     * @param string $code_competition
     * @param int $max_teams_per_pool Maximum teams per pool (default 4)
     * @return array
     * @throws Exception
     */
    public function getCupDrawData(string $code_competition, int $max_teams_per_pool = 4): array
    {
        $teams = $this->get_full_competition_rank($code_competition);

        if (empty($teams)) {
            return [
                'teams' => [],
                'total_teams' => 0,
                'half_point' => 0,
                'tableau_haut' => ['teams' => [], 'chapeaux' => [], 'nb_pools' => 0],
                'tableau_bas' => ['teams' => [], 'chapeaux' => [], 'nb_pools' => 0]
            ];
        }

        $total_teams = count($teams);
        $half_point = (int)ceil($total_teams / 2);

        $tableau_haut_teams = array_slice($teams, 0, $half_point);
        $tableau_bas_teams = array_slice($teams, $half_point);

        $tableau_haut = $this->assignChapeauxToTeams($tableau_haut_teams, $max_teams_per_pool);
        $tableau_bas = $this->assignChapeauxToTeams($tableau_bas_teams, $max_teams_per_pool);

        return [
            'teams' => $teams,
            'total_teams' => $total_teams,
            'half_point' => $half_point,
            'tableau_haut' => $tableau_haut,
            'tableau_bas' => $tableau_bas
        ];
    }

    /**
     * Save cup pool assignments from drag&drop interface
     * @param string $code_competition The cup competition code
     * @param array|string $pools JSON string or array of pools, each containing team IDs
     * @return array
     * @throws Exception
     */
    public function saveCupPoolAssignments(string $code_competition, array|string $pools): array
    {
        // Check if user is admin
        if (!UserManager::isAdmin()) {
            throw new Exception("Seul un administrateur peut modifier le tirage au sort !");
        }

        // Decode JSON pools if string, otherwise use directly if already an array
        if (is_string($pools)) {
            $poolsArray = json_decode($pools, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Format JSON invalide pour les pools");
            }
        } else {
            $poolsArray = $pools;
        }

        // Validate input
        if (empty($code_competition) || empty($poolsArray)) {
            throw new Exception("code_competition et pools sont requis");
        }

        $pools = $poolsArray;

        // Get database connection for transaction control
        $db = Database::openDbConnection();

        // Start transaction using mysqli_query (not prepared statements)
        mysqli_begin_transaction($db);

        try {
            // Delete existing assignments for this cup competition
            $sql = "DELETE FROM classements WHERE code_competition = ?";
            $bindings = [['type' => 's', 'value' => $code_competition]];
            $this->sql_manager->execute($sql, $bindings);

            // Insert new assignments
            $inserted = 0;
            foreach ($pools as $poolIndex => $pool) {
                $division = strval($poolIndex + 1); // Pool 1, 2, 3... become division "1", "2", "3"...

                if (!is_array($pool) || empty($pool)) {
                    continue;
                }

                foreach ($pool as $rankIndex => $teamId) {
                    if (empty($teamId)) {
                        continue;
                    }

                    $rank_start = $rankIndex + 1; // Position in pool: 1, 2, 3, 4

                    $sql = "INSERT INTO classements (code_competition, division, id_equipe, rank_start) VALUES (?, ?, ?, ?)";
                    $bindings = [
                        ['type' => 's', 'value' => $code_competition],
                        ['type' => 's', 'value' => $division],
                        ['type' => 'i', 'value' => intval($teamId)],
                        ['type' => 'i', 'value' => $rank_start]
                    ];
                    $this->sql_manager->execute($sql, $bindings);
                    $inserted++;
                }
            }

            mysqli_commit($db);

            $this->addActivity("Tirage au sort sauvegardé pour la compétition $code_competition: $inserted équipes réparties dans " . count($pools) . " poules");

            return [
                'success' => true,
                'inserted' => $inserted,
                'pools_count' => count($pools)
            ];

        } catch (Exception $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }

    /**
     * Get all cup competitions for dropdown selection
     * @return array
     * @throws Exception
     */
    public function getCupCompetitions(): array
    {
        $sql = "SELECT 
                    c.code_competition,
                    c.libelle,
                    c.id_compet_maitre,
                    IFNULL(DATE_FORMAT(c.start_date, '%d/%m/%Y'), '') AS start_date
                FROM competitions c
                WHERE c.code_competition IN ('c', 'kh')
                ORDER BY c.libelle";
        return $this->sql_manager->execute($sql);
    }

    /**
     * Get teams available for cup draw (reuses existing methods)
     * @param string $code_competition The cup code (c for Isoardi, kh for Khoury Hanna)
     * @return array
     * @throws Exception
     */
    public function getTeamsForCupDraw(string $code_competition): array
    {
        // Use existing methods based on competition type
        if ($code_competition === 'kh') {
            // Khoury Hanna: use getKHCupDrawData
            $data = $this->getKHCupDrawData();
            return array_map(function ($team) {
                return [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'],
                    'club' => $team['club'],
                    'division_origine' => '-',
                    'rank_start' => $team['rang'],
                    'rang_global' => $team['rang']
                ];
            }, $data['teams'] ?? []);
        } elseif ($code_competition === 'c') {
            // Coupe Isoardi (c): use getCupDrawData
            $data = $this->getCupDrawData('m');

            // Combine tableau haut and tableau bas teams
            $teams = [];

            // Add tableau haut teams
            foreach ($data['tableau_haut']['teams'] ?? [] as $team) {
                $teams[] = [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'],
                    'club' => $team['club'] ?? '',
                    'division_origine' => $team['division'] ?? '-',
                    'rank_start' => $team['rang'],
                    'rang_global' => $team['rang'],
                    'chapeau' => $team['chapeau'],
                    'tableau' => 'haut'
                ];
            }

            // Add tableau bas teams
            foreach ($data['tableau_bas']['teams'] ?? [] as $team) {
                $teams[] = [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'],
                    'club' => $team['club'] ?? '',
                    'division_origine' => $team['division'] ?? '-',
                    'rank_start' => $team['rang'],
                    'rang_global' => $team['rang'],
                    'chapeau' => $team['chapeau'],
                    'tableau' => 'bas'
                ];
            }

            return $teams;
        }
        throw new Exception("cette compétition n'est pas une coupe...");
    }

    /**
     * Get current pool assignments for a cup competition
     * @param string $code_competition
     * @return array
     * @throws Exception
     */
    public function getCupPoolAssignments(string $code_competition): array
    {
        $sql = "SELECT 
                    c.division AS pool,
                    c.id_equipe,
                    e.nom_equipe,
                    cl.nom AS club,
                    c.rank_start
                FROM classements c
                JOIN equipes e ON e.id_equipe = c.id_equipe
                JOIN clubs cl ON cl.id = e.id_club
                WHERE c.code_competition = ?
                ORDER BY CAST(c.division AS UNSIGNED), c.rank_start";

        $bindings = [['type' => 's', 'value' => $code_competition]];
        $results = $this->sql_manager->execute($sql, $bindings);

        // Group by pool
        $pools = [];
        foreach ($results as $row) {
            $poolNum = intval($row['pool']);
            if (!isset($pools[$poolNum])) {
                $pools[$poolNum] = [];
            }
            $pools[$poolNum][] = $row;
        }

        return $pools;
    }

    /**
     * Assign chapeaux to a list of teams based on their position
     * Creates pools of 3-4 teams with proper chapeau distribution
     * @param array $teams
     * @param int $max_teams_per_pool Maximum teams per pool (default 4)
     * @return array
     */
    private function assignChapeauxToTeams(array $teams, int $max_teams_per_pool): array
    {
        $total = count($teams);
        if ($total === 0) {
            return ['teams' => [], 'chapeaux' => [], 'nb_pools' => 0];
        }

        // Calculate number of pools needed (pools of 3-4 teams)
        $nb_pools = (int)ceil($total / $max_teams_per_pool);

        // Calculate how many pools have max teams vs (max-1) teams
        // nb_pools * max - total = number of pools with (max-1) teams
        $pools_with_less = $nb_pools * $max_teams_per_pool - $total;
        $pools_with_max = $nb_pools - $pools_with_less;

        // Number of chapeaux = max teams per pool (usually 4, but could be 3 if not enough teams)
        $nb_chapeaux = min($max_teams_per_pool, (int)ceil($total / $nb_pools));

        // Build chapeaux info
        // Chapeau 1 to (nb_chapeaux-1) have nb_pools teams each
        // Last chapeau has pools_with_max teams (the pools that have max_teams_per_pool teams)
        $chapeaux = [];
        $cumulative = 0;
        for ($i = 1; $i <= $nb_chapeaux; $i++) {
            // Last chapeau may have fewer teams if some pools have only 3 teams
            if ($i <= $nb_chapeaux - 1 || $pools_with_less === 0) {
                $size = $nb_pools;
            } else {
                // Last chapeau: only pools that have max_teams_per_pool teams
                $size = $pools_with_max;
            }

            // Adjust if we're running out of teams
            $size = min($size, $total - $cumulative);

            if ($size > 0) {
                $chapeaux[$i] = [
                    'numero' => $i,
                    'size' => $size
                ];
                $cumulative += $size;
            }
        }

        // Assign chapeau to each team
        // Team 0 to (nb_pools-1) -> chapeau 1
        // Team nb_pools to (2*nb_pools-1) -> chapeau 2
        // etc.
        foreach ($teams as $index => &$team) {
            $chapeau_num = (int)floor($index / $nb_pools) + 1;
            // Make sure we don't exceed nb_chapeaux
            $chapeau_num = min($chapeau_num, $nb_chapeaux);
            $team['chapeau'] = $chapeau_num;
        }

        return [
            'teams' => $teams,
            'chapeaux' => array_values($chapeaux),
            'nb_pools' => $nb_pools
        ];
    }

    /**
     * Équipes de la compétition absentes de ses classements (colonne « Non
     * affectées » de l'écran de réorganisation).
     *
     * Ce sont surtout des équipes d'anciennes saisons (issue #388) : chaque
     * ligne dit donc si l'équipe est `registered`, c'est-à-dire inscrite
     * (demande non refusée, reconnue par son ancienne équipe ou par son nom),
     * et `competition_has_registrations` si la compétition a des inscriptions.
     * L'écran n'affiche par défaut que les inscrites ; les coupes, montées
     * depuis les championnats et sans inscriptions propres, gardent tout.
     *
     * S'y ajoutent les demandes de NOUVELLES équipes pas encore créées
     * (« Équipes / comptes » pas encore passé sur elles) : sans ligne dans
     * `equipes`, elles ne peuvent pas être placées, mais l'écran les montre
     * avec un badge « équipe à créer » plutôt que de les taire. Elles ont
     * `id_equipe` vide et `id_register` renseigné.
     *
     * @throws Exception
     */
    public function getUnassignedTeams(string $code_competition): array
    {
        $sql = "SELECT
                    e.id_equipe,
                    e.nom_equipe,
                    c.nom AS club,
                    " . self::REGISTERED_COLUMNS . ",
                    NULL AS id_register,
                    " . self::RENAMING_COLUMNS . "
                FROM equipes e
                JOIN clubs c ON c.id = e.id_club
                WHERE e.code_competition = ?
                AND e.id_equipe NOT IN (
                    SELECT id_equipe
                    FROM classements
                    WHERE code_competition = ?
                )
                UNION ALL
                SELECT
                    NULL AS id_equipe,
                    r.new_team_name AS nom_equipe,
                    c.nom AS club,
                    1 AS registered,
                    1 AS competition_has_registrations,
                    r.id AS id_register,
                    NULL AS registered_name,
                    NULL AS former_name
                FROM register r
                JOIN competitions comp ON comp.id = r.id_competition
                JOIN clubs c ON c.id = r.id_club
                WHERE comp.code_competition = ?
                  AND r.status <> 'REFUSED'
                  AND r.old_team_id IS NULL
                  AND NOT EXISTS (SELECT 1
                                  FROM equipes e
                                  WHERE e.code_competition = comp.code_competition
                                    AND e.nom_equipe = r.new_team_name)
                ORDER BY nom_equipe";
        $bindings = [
            ['type' => 's', 'value' => $code_competition],
            ['type' => 's', 'value' => $code_competition],
            ['type' => 's', 'value' => $code_competition]
        ];
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Get ranks for a competition grouped by division
     * @param string $code_competition
     * @return array
     * @throws Exception
     */
    public function getRanksByCompetitionGroupedByDivision(string $code_competition): array
    {
        $sql = "SELECT 
                    c.id,
                    c.division,
                    c.id_equipe,
                    e.nom_equipe,
                    cl.nom AS club,
                    c.rank_start,
                    " . self::REGISTERED_COLUMNS . ",
                    " . self::RENAMING_COLUMNS . "
                FROM classements c
                JOIN equipes e ON e.id_equipe = c.id_equipe
                JOIN clubs cl ON cl.id = e.id_club
                WHERE c.code_competition = ?
                ORDER BY CAST(c.division AS UNSIGNED), c.rank_start";
        $bindings = [['type' => 's', 'value' => $code_competition]];
        $results = $this->sql_manager->execute($sql, $bindings);

        // Group by division
        $divisions = [];
        foreach ($results as $row) {
            $div = $row['division'];
            if (!isset($divisions[$div])) {
                $divisions[$div] = [];
            }
            $divisions[$div][] = $row;
        }

        return $divisions;
    }

    /**
     * Update multiple ranks in batch (for drag & drop reordering)
     * @param string $code_competition
     * @param string $updates JSON array of updates [{id, division, rank_start}, ...]
     * @return array
     * @throws Exception
     */
    public function updateRanksBatch(string $code_competition, string $updates): array
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seul un administrateur peut modifier les classements !");
        }

        $updatesArray = json_decode($updates, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Format JSON invalide pour les updates");
        }

        $db = Database::openDbConnection();
        mysqli_begin_transaction($db);

        try {
            $updated = 0;
            foreach ($updatesArray as $update) {
                if (!empty($update['id']) && is_numeric($update['id'])) {
                    // Update existing rank
                    $sql = "UPDATE classements 
                            SET division = ?, rank_start = ? 
                            WHERE id = ? AND code_competition = ?";
                    $bindings = [
                        ['type' => 's', 'value' => $update['division']],
                        ['type' => 'i', 'value' => $update['rank_start']],
                        ['type' => 'i', 'value' => $update['id']],
                        ['type' => 's', 'value' => $code_competition]
                    ];
                    $this->sql_manager->execute($sql, $bindings);
                    $updated++;
                } elseif (isset($update['id_equipe'])) {
                    // Insert new rank (team was unassigned)
                    $sql = "INSERT INTO classements (code_competition, division, id_equipe, rank_start) 
                            VALUES (?, ?, ?, ?)";
                    $bindings = [
                        ['type' => 's', 'value' => $code_competition],
                        ['type' => 's', 'value' => $update['division']],
                        ['type' => 'i', 'value' => $update['id_equipe']],
                        ['type' => 'i', 'value' => $update['rank_start']]
                    ];
                    $this->sql_manager->execute($sql, $bindings);
                    $updated++;
                }
            }

            mysqli_commit($db);

            $this->addActivity("Classements mis à jour pour la compétition $code_competition: $updated modifications");

            return [
                'success' => true,
                'updated' => $updated
            ];

        } catch (Exception $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }

    /**
     * Remove a team from a division (move to unassigned)
     * @param int $id Rank ID to delete
     * @return array
     * @throws Exception
     */
    public function removeFromDivision(int $id): array
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seul un administrateur peut modifier les classements !");
        }

        $sql = "DELETE FROM classements WHERE id = ?";
        $bindings = [['type' => 'i', 'value' => $id]];
        $this->sql_manager->execute($sql, $bindings);

        $this->addActivity("Équipe retirée du classement (id: $id)");

        return ['success' => true];
    }

    /**
     * Get the raw finals draw from registry for a given finals competition
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @return array Associative array: match_number => ['team1' => label, 'team2' => label]
     * @throws Exception
     */
    public function getFinalsDrawRaw(string $code_competition_finals): array
    {
        $entries = $this->registry->find_by_key("finals_draw.$code_competition_finals.1_8.");
        if (empty($entries)) {
            return [];
        }

        $matches = [];
        foreach ($entries as $entry) {
            // Key format: finals_draw.{comp}.1_8.{match_num}.{team1|team2}
            $key = $entry['registry_key'];
            $parts = explode('.', $key);
            if (count($parts) !== 5) {
                continue;
            }
            $matchNum = intval($parts[3]);
            $side = $parts[4]; // 'team1' or 'team2'
            if (!isset($matches[$matchNum])) {
                $matches[$matchNum] = [];
            }
            $matches[$matchNum][$side] = $entry['registry_value'];
        }

        ksort($matches);
        return $matches;
    }

    /**
     * Resolve a finals position label (e.g. "1er poule 3") to an actual team
     * using the pool rankings of the parent competition
     * @param string $positionLabel e.g. "1er poule 3", "2e poule 1", "meilleur 2e 1/2"
     * @param string $code_competition_pools The pool competition code (e.g. 'kh', 'c')
     * @return array|null Team data with id_equipe and nom_equipe, or null if not resolvable
     * @throws Exception
     */
    public function resolveFinalsPosition(string $positionLabel, string $code_competition_pools): ?array
    {
        // Parse "1er poule X" or "2e poule X"
        if (preg_match('/^1er poule (\d+)$/', $positionLabel, $m)) {
            $pool = $m[1];
            $rankInPool = 1;
        } elseif (preg_match('/^2e poule (\d+)$/', $positionLabel, $m)) {
            $pool = $m[1];
            $rankInPool = 2;
        } elseif (preg_match('/^meilleur 2e (\d+)\/(\d+)$/', $positionLabel, $m)) {
            // "meilleur 2e X/Y" — requires sorting all 2nd places across pools
            return $this->resolveBestSecond($code_competition_pools, intval($m[1]));
        } else {
            return null;
        }

        // Get the ranking for this specific pool
        try {
            $rankResults = $this->getRank($code_competition_pools, $pool);
        } catch (Exception) {
            return null;
        }

        if (empty($rankResults) || count($rankResults) < $rankInPool) {
            return null;
        }

        $team = $rankResults[$rankInPool - 1];
        return [
            'id_equipe' => $team['id_equipe'],
            'nom_equipe' => $team['equipe'] ?? $team['nom_equipe'] ?? null,
        ];
    }

    /**
     * Resolve the Nth best 2nd place across all pools
     * @param string $code_competition_pools
     * @param int $nth Which best 2nd (1 = best, 2 = second best, etc.)
     * @return array|null
     * @throws Exception
     */
    private function resolveBestSecond(string $code_competition_pools, int $nth): ?array
    {
        $divisions = $this->getDivisionsFromCompetition($code_competition_pools);
        $seconds = [];

        foreach ($divisions as $division) {
            $pool = $division['division'];
            try {
                $rankResults = $this->getRank($code_competition_pools, $pool);
            } catch (Exception) {
                continue;
            }
            if (count($rankResults) >= 2) {
                $seconds[] = $rankResults[1]; // 2nd place
            }
        }

        if (empty($seconds)) {
            return null;
        }

        // Sort by points_ponderes DESC, diff_sets_ponderes DESC, diff_points_ponderes DESC
        usort($seconds, function ($a, $b) {
            $cmp = ($b['points_ponderes'] ?? 0) <=> ($a['points_ponderes'] ?? 0);
            if ($cmp !== 0) return $cmp;
            $cmp = ($b['diff_sets_ponderes'] ?? 0) <=> ($a['diff_sets_ponderes'] ?? 0);
            if ($cmp !== 0) return $cmp;
            return ($b['diff_points_ponderes'] ?? 0) <=> ($a['diff_points_ponderes'] ?? 0);
        });

        if ($nth > count($seconds)) {
            return null;
        }

        $team = $seconds[$nth - 1];
        return [
            'id_equipe' => $team['id_equipe'],
            'nom_equipe' => $team['equipe'] ?? $team['nom_equipe'] ?? null,
        ];
    }

    /**
     * Save a single finals draw entry in registry
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @param int $matchNum Match number (1-8)
     * @param string $side 'team1' or 'team2'
     * @param string $positionLabel e.g. "1er poule 3"
     * @return bool
     * @throws Exception
     */
    public function saveFinalsDrawEntry(string $code_competition_finals, int $matchNum, string $side, string $positionLabel): bool
    {
        $key = "finals_draw.$code_competition_finals.1_8.$matchNum.$side";

        // Check if entry already exists
        $existing = $this->registry->find_by_key($key);
        if (!empty($existing)) {
            // Update existing
            $sql = "UPDATE registry SET registry_value = ? WHERE registry_key = ?";
        } else {
            // Insert new
            $sql = "INSERT INTO registry SET registry_value = ?, registry_key = ?";
        }
        $bindings = [
            ['type' => 's', 'value' => $positionLabel],
            ['type' => 's', 'value' => $key],
        ];
        $this->sql_manager->execute($sql, $bindings);
        return true;
    }

    /**
     * Save a full finals draw (all 8 matches) in registry
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @param string $drawJson JSON array of [{match, team1, team2}, ...]
     * @return array
     * @throws Exception
     */
    public function saveFullFinalsDraw(string $code_competition_finals, string $drawJson): array
    {
        $drawArray = json_decode($drawJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Format JSON invalide pour le tirage");
        }

        // Delete existing draw entries for this competition (only 1_8, not host)
        $existingEntries = $this->registry->find_by_key("finals_draw.$code_competition_finals.1_8.");
        foreach ($existingEntries as $entry) {
            $sql = "DELETE FROM registry WHERE id = ?";
            $this->sql_manager->execute($sql, [['type' => 'i', 'value' => $entry['id']]]);
        }

        // Insert new entries
        $count = 0;
        foreach ($drawArray as $match) {
            $matchNum = $match['match'];
            $this->saveFinalsDrawEntry($code_competition_finals, $matchNum, 'team1', $match['team1']);
            $this->saveFinalsDrawEntry($code_competition_finals, $matchNum, 'team2', $match['team2']);
            $count += 2;
        }

        $this->addActivity("Tirage des phases finales sauvegardé pour $code_competition_finals: " . count($drawArray) . " matchs");

        return [
            'success' => true,
            'entries_count' => $count,
        ];
    }

    /**
     * Get the parent pool competition code from a finals competition code
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @return string|null e.g. 'c' or 'kh'
     * @throws Exception
     */
    private function getParentCompetitionCode(string $code_competition_finals): ?string
    {
        $sql = "SELECT id_compet_maitre FROM competitions WHERE code_competition = ?";
        $bindings = [['type' => 's', 'value' => $code_competition_finals]];
        $results = $this->sql_manager->execute($sql, $bindings);
        if (empty($results)) {
            return null;
        }
        return $results[0]['id_compet_maitre'];
    }

    /**
     * Get the full resolved finals bracket for a competition
     * Reads draw from registry, resolves positions to real teams, builds full bracket
     * Uses sort_cup_rank to fetch all rankings in a single query for performance
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @return array Full bracket structure with rounds
     * @throws Exception
     */
    public function getFinalsDrawResolved(string $code_competition_finals): array
    {
        $parentCode = $this->getParentCompetitionCode($code_competition_finals);

        // Get raw draw from registry
        $rawDraw = $this->getFinalsDrawRaw($code_competition_finals);

        // Pre-fetch all rankings in ONE query using sort_cup_rank
        $allRankings = [];
        $ranksByPool = [];
        $bestSeconds = [];

        if ($parentCode) {
            $allRankings = $this->sort_cup_rank($parentCode);

            // Build lookup: pool -> [1st place, 2nd place, ...]
            foreach ($allRankings as $team) {
                $pool = $team['division'];
                if (!isset($ranksByPool[$pool])) {
                    $ranksByPool[$pool] = [];
                }
                $ranksByPool[$pool][] = $team;
            }

            // Collect all 2nd places for "meilleur 2e" resolution
            foreach ($ranksByPool as $pool => $teams) {
                if (count($teams) >= 2) {
                    $bestSeconds[] = $teams[1]; // 2nd place (already sorted by rang_poule)
                }
            }

            // Sort best seconds by points_ponderes DESC, diff_sets_ponderes DESC, diff_points_ponderes DESC
            usort($bestSeconds, function ($a, $b) {
                $cmp = ($b['points_ponderes'] ?? 0) <=> ($a['points_ponderes'] ?? 0);
                if ($cmp !== 0) return $cmp;
                $cmp = ($b['diff_sets_ponderes'] ?? 0) <=> ($a['diff_sets_ponderes'] ?? 0);
                if ($cmp !== 0) return $cmp;
                return ($b['diff_points_ponderes'] ?? 0) <=> ($a['diff_points_ponderes'] ?? 0);
            });
        }

        // Build 1/8 finals using cached data
        $eighthFinals = [];
        foreach ($rawDraw as $matchNum => $match) {
            $team1Resolved = $this->resolvePositionFromCache($match['team1'] ?? '', $ranksByPool, $bestSeconds);
            $team2Resolved = $this->resolvePositionFromCache($match['team2'] ?? '', $ranksByPool, $bestSeconds);

            $eighthFinals[] = [
                'match' => $matchNum,
                'team1_label' => $match['team1'] ?? null,
                'team2_label' => $match['team2'] ?? null,
                'team1_resolved' => $team1Resolved,
                'team2_resolved' => $team2Resolved,
            ];
        }

        // Get host draw for quarters and semis
        $hostDraw = $this->getFinalsHostDraw($code_competition_finals);

        return [
            'code_competition' => $code_competition_finals,
            'parent_competition' => $parentCode,
            'rounds' => [
                '1_8' => $eighthFinals,
            ],
            'host_draw' => $hostDraw,
        ];
    }

    /**
     * Get the host draw for quarters and semi-finals
     * Format: which winner of previous round hosts the next match
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @return array ['1_4' => [1 => 2, 2 => 3, ...], '1_2' => [1 => 1, ...]]
     */
    public function getFinalsHostDraw(string $code_competition_finals): array
    {
        $result = [
            '1_4' => [], // For each quarter (1-4), which 1/8 winner hosts (1-8)
            '1_2' => [], // For each semi (1-2), which 1/4 winner hosts (1-4)
        ];

        // Read from registry
        $entries = $this->registry->find_by_key("finals_draw.$code_competition_finals.host.");
        foreach ($entries as $entry) {
            // Key format: finals_draw.{comp}.host.{round}.{match_num}
            $key = $entry['registry_key'];
            $parts = explode('.', $key);
            if (count($parts) === 5) {
                $round = $parts[3]; // '1_4' or '1_2'
                $matchNum = intval($parts[4]);
                $hostWinner = intval($entry['registry_value']);
                if (isset($result[$round])) {
                    $result[$round][$matchNum] = $hostWinner;
                }
            }
        }

        return $result;
    }

    /**
     * Save the host draw for quarters and semi-finals
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @param string $hostDrawJson JSON with format {1_4: {1: 2, 2: 4, ...}, 1_2: {1: 1, ...}}
     * @return array
     * @throws Exception
     */
    public function saveFinalsHostDraw(string $code_competition_finals, string $hostDrawJson): array
    {
        $hostDraw = json_decode($hostDrawJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Format JSON invalide pour le tirage de réception");
        }

        // Delete existing host draw entries for this competition
        $existingEntries = $this->registry->find_by_key("finals_draw.$code_competition_finals.host.");
        foreach ($existingEntries as $entry) {
            $sql = "DELETE FROM registry WHERE id = ?";
            $this->sql_manager->execute($sql, [['type' => 'i', 'value' => $entry['id']]]);
        }

        // Insert new entries
        $count = 0;
        foreach (['1_4', '1_2'] as $round) {
            if (isset($hostDraw[$round]) && is_array($hostDraw[$round])) {
                foreach ($hostDraw[$round] as $matchNum => $hostWinner) {
                    // Skip null values
                    if ($hostWinner === null) {
                        continue;
                    }
                    $key = "finals_draw.$code_competition_finals.host.$round.$matchNum";
                    $sql = "INSERT INTO registry (registry_key, registry_value) VALUES (?, ?)";
                    $bindings = [
                        ['type' => 's', 'value' => $key],
                        ['type' => 's', 'value' => (string)$hostWinner],
                    ];
                    $this->sql_manager->execute($sql, $bindings);
                    $count++;
                }
            }
        }

        $this->addActivity("Tirage de réception des phases finales sauvegardé pour $code_competition_finals: $count entrées");

        return [
            'success' => true,
            'entries_count' => $count,
        ];
    }

    /**
     * Resolve a position label using pre-cached rankings (no additional queries)
     * @param string $positionLabel e.g. "1er poule 3", "meilleur 2e 1/7"
     * @param array $ranksByPool Pool rankings indexed by pool number
     * @param array $bestSeconds Sorted array of best 2nd places
     * @return array|null
     */
    private function resolvePositionFromCache(string $positionLabel, array $ranksByPool, array $bestSeconds): ?array
    {
        if (empty($positionLabel)) {
            return null;
        }

        // Parse "1er poule X"
        if (preg_match('/^1er poule (\d+)$/', $positionLabel, $m)) {
            $pool = intval($m[1]);
            if (isset($ranksByPool[$pool]) && count($ranksByPool[$pool]) >= 1) {
                $team = $ranksByPool[$pool][0];
                return [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'] ?? $team['nom_equipe'] ?? null,
                ];
            }
            return null;
        }

        // Parse "2e poule X"
        if (preg_match('/^2e poule (\d+)$/', $positionLabel, $m)) {
            $pool = intval($m[1]);
            if (isset($ranksByPool[$pool]) && count($ranksByPool[$pool]) >= 2) {
                $team = $ranksByPool[$pool][1];
                return [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'] ?? $team['nom_equipe'] ?? null,
                ];
            }
            return null;
        }

        // Parse "meilleur 2e X/Y"
        if (preg_match('/^meilleur 2e (\d+)\/(\d+)$/', $positionLabel, $m)) {
            $nth = intval($m[1]);
            if ($nth <= count($bestSeconds)) {
                $team = $bestSeconds[$nth - 1];
                return [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'] ?? $team['nom_equipe'] ?? null,
                ];
            }
            return null;
        }

        return null;
    }
}
<?php

require_once __DIR__ . '/Generic.php';

/**
 * Bilan d'activites annuel.
 *
 * Fournit tous les chiffres du bilan officiel pour une saison donnee.
 * Les definitions sont choisies pour rester reconstructibles sur une saison
 * passee (matchs conserves en base, statut ARCHIVED) :
 *  - "participant" = a joue au moins un match (matches / match_player) ;
 *  - le filtre saison se fait uniquement par DATE, jamais par match_status.
 *
 * Conventions de periode dans hall_of_fame :
 *  - championnats : 'AAAA-AAAA+1' (ex. '2025-2026') ;
 *  - coupes       : annee civile des finales = 2e annee de la saison ('2026').
 */
class Bilan extends Generic
{
    /**
     * Derive les bornes d'une saison "AAAA-AAAA+1".
     *
     * @return array{date_debut:string,date_fin:string,period_championnat:string,period_coupe:string}
     * @throws Exception
     */
    public function getSeasonBounds(string $saison): array
    {
        if (!preg_match('/^(\d{4})-(\d{4})$/', trim($saison), $m)) {
            throw new Exception("Format de saison invalide (attendu 'AAAA-AAAA', ex : 2025-2026).");
        }
        $y1 = (int)$m[1];
        $y2 = (int)$m[2];
        if ($y2 !== $y1 + 1) {
            throw new Exception("Saison incoherente : la 2e annee doit suivre la 1ere (ex : 2025-2026).");
        }
        return array(
            'date_debut' => sprintf('%04d-09-01', $y1),
            'date_fin' => sprintf('%04d-06-30', $y2),
            'period_championnat' => sprintf('%04d-%04d', $y1, $y2),
            'period_coupe' => sprintf('%04d', $y2),
        );
    }

    /**
     * Renvoie l'ensemble des donnees chiffrees du bilan pour une saison.
     *
     * @throws Exception
     */
    public function getBilanData($saison = null): array
    {
        if (empty($saison)) {
            throw new Exception("Saison manquante !");
        }
        $bounds = $this->getSeasonBounds($saison);
        $matchs = $this->getMatchsParCompetition($bounds['date_debut'], $bounds['date_fin']);
        $coupes = $this->getCoupes($bounds['period_coupe']);
        $total_matchs = 0;
        foreach ($matchs as $ligne) {
            $total_matchs += (int)$ligne['nb_matchs'];
        }
        return array(
            'saison' => $saison,
            'date_debut' => $bounds['date_debut'],
            'date_fin' => $bounds['date_fin'],
            'matchs' => $matchs,
            'total_matchs' => $total_matchs,
            'nb_clubs' => $this->getNbClubsParticipants($bounds['date_debut'], $bounds['date_fin']),
            'nb_licencies' => $this->getNbLicenciesParticipants($bounds['date_debut'], $bounds['date_fin']),
            'nb_recompenses' => $this->getNbRecompensesChampionnat($bounds['period_championnat']),
            'coupes' => $coupes,
            'nb_coupes' => count($coupes),
        );
    }

    /**
     * Matchs joues par competition (coupes = poule + phases finales regroupees).
     * Un match est "joue" s'il a un score saisi ou un forfait.
     *
     * @return array<array{competition:string,nb_matchs:int}>
     * @throws Exception
     */
    public function getMatchsParCompetition(string $date_debut, string $date_fin): array
    {
        $sql = "SELECT CASE code
                           WHEN 'm' THEN 'Championnat Masculin 6x6'
                           WHEN 'f' THEN 'Championnat Féminin 4x4'
                           WHEN 'mo' THEN 'Championnat Mixte 4x4'
                           WHEN 'c' THEN 'Coupe Départementale Isoardi 6x6'
                           WHEN 'kh' THEN 'Coupe Départementale Khoury Hanna 4x4'
                           ELSE code END              AS competition,
                       COUNT(*)                       AS nb_matchs
                FROM (SELECT CASE
                                 WHEN m.code_competition = 'cf' THEN 'c'
                                 WHEN m.code_competition = 'kf' THEN 'kh'
                                 ELSE m.code_competition END AS code
                      FROM matchs_view m
                      WHERE STR_TO_DATE(m.date_reception, '%d/%m/%Y') BETWEEN ? AND ?
                        AND ((m.score_equipe_dom + m.score_equipe_ext) > 0
                            OR m.forfait_dom = 1 OR m.forfait_ext = 1)) x
                GROUP BY code
                ORDER BY FIELD(code, 'm', 'f', 'mo', 'c', 'kh'), nb_matchs DESC";
        return $this->sql_manager->execute($sql, array(
            array('type' => 's', 'value' => $date_debut),
            array('type' => 's', 'value' => $date_fin),
        ));
    }

    /**
     * Nombre de clubs ayant au moins une equipe ayant joue un match de championnat.
     *
     * @throws Exception
     */
    public function getNbClubsParticipants(string $date_debut, string $date_fin): int
    {
        $sql = "SELECT COUNT(DISTINCT e.id_club) AS nb
                FROM matchs_view m
                         JOIN equipes e ON e.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext)
                WHERE STR_TO_DATE(m.date_reception, '%d/%m/%Y') BETWEEN ? AND ?
                  AND m.code_competition IN ('m', 'f', 'mo')";
        $rows = $this->sql_manager->execute($sql, array(
            array('type' => 's', 'value' => $date_debut),
            array('type' => 's', 'value' => $date_fin),
        ));
        return (int)($rows[0]['nb'] ?? 0);
    }

    /**
     * Nombre de licencies ayant figure sur au moins une feuille de match.
     *
     * @throws Exception
     */
    public function getNbLicenciesParticipants(string $date_debut, string $date_fin): int
    {
        $sql = "SELECT COUNT(DISTINCT mp.id_player) AS nb
                FROM match_player mp
                         JOIN matchs_view m ON m.id_match = mp.id_match
                WHERE STR_TO_DATE(m.date_reception, '%d/%m/%Y') BETWEEN ? AND ?";
        $rows = $this->sql_manager->execute($sql, array(
            array('type' => 's', 'value' => $date_debut),
            array('type' => 's', 'value' => $date_fin),
        ));
        return (int)($rows[0]['nb'] ?? 0);
    }

    /**
     * Nombre d'equipes recompensees en championnat (1er + 2e, mi-saison + Dept.,
     * par division, pour les 3 championnats).
     *
     * @throws Exception
     */
    public function getNbRecompensesChampionnat(string $period_championnat): int
    {
        $sql = "SELECT COUNT(*) AS nb
                FROM hall_of_fame
                WHERE period = ?
                  AND (title LIKE 'Championne%' OR title LIKE 'Vice%')";
        $rows = $this->sql_manager->execute($sql, array(
            array('type' => 's', 'value' => $period_championnat),
        ));
        return (int)($rows[0]['nb'] ?? 0);
    }

    /**
     * Liste des coupes decernees (vainqueurs), Trophee du fair-play inclus.
     *
     * @return array<array{recompense:string,vainqueur:string}>
     * @throws Exception
     */
    public function getCoupes(string $period_coupe): array
    {
        $sql = "SELECT league    AS recompense,
                       team_name AS vainqueur
                FROM hall_of_fame
                WHERE period = ?
                  AND title = 'Vainqueur'
                ORDER BY league";
        return $this->sql_manager->execute($sql, array(
            array('type' => 's', 'value' => $period_coupe),
        ));
    }
}

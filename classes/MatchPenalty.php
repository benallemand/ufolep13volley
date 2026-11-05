<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/Emails.php';

/**
 * Pénalités automatiques (issue #345).
 *
 * Feuille de match non signée des deux côtés 7 jours après l'horaire du match
 * (article 8 du règlement général, issue #456 ; 48 h auparavant) :
 * -1 point à **chacune des deux équipes**, même si l'une a signé (décision de
 * la commission), dans la compétition du match. Championnats seulement.
 *
 * Chaque pénalité est tracée dans `match_penalties` (match, équipe, motif) ;
 * la clé unique rend l'application idempotente. La pénalité reste si la feuille
 * est signée ensuite. L'admin l'annule depuis l'écran « Pénalités automatiques »
 * (issue #458) : `cancelled_at` est renseigné et le point rendu au classement.
 * La ligne reste comme historique et empêche une nouvelle application.
 */
class MatchPenalty extends Generic
{
    /**
     * Motif enregistré dans `match_penalties`. Il date du délai de 48 h et reste
     * tel quel : c'est lui qui empêche de pénaliser deux fois le même match.
     */
    const REASON_UNSIGNED_SHEET = 'feuille_non_signee_48h';
    /** Délai de signature des deux équipes, en jours (article 8 du règlement général). */
    const UNSIGNED_SHEET_DELAY_DAYS = 7;
    /** Mise en service : pas de pénalité rétroactive pour les matchs d'avant. */
    const UNSIGNED_SHEET_SINCE = '2026-10-01';

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'match_penalties';
    }

    /**
     * Applique les pénalités dues. Appelé par le cron horaire.
     * @param string|null $since date de mise en service (aaaa-mm-jj), pour les tests
     * @return int nombre de pénalités appliquées (une par équipe)
     * @throws Exception
     */
    public function apply_unsigned_sheet_penalties(?string $since = null): int
    {
        $matches = $this->sql_manager->execute(
            file_get_contents(__DIR__ . '/../sql/unsigned_match_sheets.sql'),
            array(
                array('type' => 's', 'value' => $since ?? self::UNSIGNED_SHEET_SINCE),
                array('type' => 'i', 'value' => self::UNSIGNED_SHEET_DELAY_DAYS),
                array('type' => 's', 'value' => self::REASON_UNSIGNED_SHEET),
            ));
        $applied = 0;
        foreach ($matches as $match) {
            $penalized = false;
            foreach (array('dom', 'ext') as $side) {
                if ($this->penalize((int)$match['id_match'], (int)$match["id_equipe_$side"], $match['code_competition'])) {
                    // une ligne par équipe, rangée dans son historique (#425)
                    $this->addActivity("Pénalité automatique (-1 pt, feuille de match non signée à "
                        . self::UNSIGNED_SHEET_DELAY_DAYS . " jours) : "
                        . $match["equipe_$side"] . " — match " . $match['code_match'], $match["id_equipe_$side"]);
                    $penalized = true;
                    $applied++;
                }
            }
            if ($penalized) {
                $this->notify($match);
            }
        }
        return $applied;
    }

    /**
     * Pénalités automatiques, actives et annulées, pour l'écran
     * d'administration (issue #458). Les plus récentes d'abord.
     * @throws Exception
     */
    public function getPenalties(): array
    {
        return $this->sql_manager->execute(file_get_contents(__DIR__ . '/../sql/match_penalties_admin.sql'));
    }

    /**
     * Annule des pénalités automatiques (issue #458) : `cancelled_at` est
     * renseigné, le point est rendu au classement de l'équipe et l'annulation
     * est écrite dans son historique. Une pénalité déjà annulée est ignorée.
     *
     * La ligne reste : le cron ne pénalise plus ce match pour cette équipe.
     *
     * @param mixed $ids identifiants joints par des virgules (cases cochées de la grille)
     * @throws Exception si aucune des pénalités n'est encore active
     */
    public function cancelPenalties($ids): void
    {
        $cancelled = 0;
        foreach (self::parse_id_list($ids) as $id) {
            $rows = $this->sql_manager->execute(
                "SELECT p.id_equipe, p.code_competition, m.code_match, e.nom_equipe
                 FROM match_penalties p
                 JOIN matches m ON m.id_match = p.id_match
                 JOIN equipes e ON e.id_equipe = p.id_equipe
                 WHERE p.id = ? AND p.cancelled_at IS NULL",
                array(array('type' => 'i', 'value' => $id)));
            if (empty($rows)) {
                continue;
            }
            $penalty = $rows[0];
            $this->sql_manager->execute(
                "UPDATE match_penalties SET cancelled_at = NOW() WHERE id = ? AND cancelled_at IS NULL",
                array(array('type' => 'i', 'value' => $id)));
            $this->sql_manager->execute(
                "UPDATE classements SET penalite = GREATEST(penalite - 1, 0) WHERE id_equipe = ? AND code_competition = ?",
                array(
                    array('type' => 'i', 'value' => (int)$penalty['id_equipe']),
                    array('type' => 's', 'value' => $penalty['code_competition']),
                ));
            $this->addActivity("Pénalité automatique annulée (+1 pt) : "
                . $penalty['nom_equipe'] . " — match " . $penalty['code_match'], $penalty['id_equipe']);
            $cancelled++;
        }
        if ($cancelled === 0) {
            throw new Exception("Aucune pénalité active parmi les lignes cochées : elles sont déjà annulées.");
        }
    }

    /**
     * Trace la pénalité puis décrémente le classement — seulement si elle
     * n'existait pas (INSERT IGNORE sur la clé unique).
     * @throws Exception
     */
    private function penalize(int $id_match, int $id_equipe, string $code_competition): bool
    {
        $inserted = $this->sql_manager->execute(
            "INSERT IGNORE INTO match_penalties (id_match, id_equipe, code_competition, reason) VALUES (?, ?, ?, ?)",
            array(
                array('type' => 'i', 'value' => $id_match),
                array('type' => 'i', 'value' => $id_equipe),
                array('type' => 's', 'value' => $code_competition),
                array('type' => 's', 'value' => self::REASON_UNSIGNED_SHEET),
            ));
        if (empty($inserted)) {
            return false;
        }
        $this->sql_manager->execute(
            "UPDATE classements SET penalite = penalite + 1 WHERE id_equipe = ? AND code_competition = ?",
            array(
                array('type' => 'i', 'value' => $id_equipe),
                array('type' => 's', 'value' => $code_competition),
            ));
        return true;
    }

    /**
     * Prévient les deux équipes, par la file d'emails.
     * @throws Exception
     */
    private function notify(array $match): void
    {
        $destinations = array_filter(array($match['email_dom'] ?? '', $match['email_ext'] ?? ''));
        if (empty($destinations)) {
            return;
        }
        (new Emails())->insert_generic_email(
            __DIR__ . '/../templates/emails/penalty_unsigned_match_sheet.fr.html',
            array(
                'code_match' => htmlspecialchars($match['code_match'], ENT_QUOTES),
                'equipe_dom' => htmlspecialchars($match['equipe_dom'], ENT_QUOTES),
                'equipe_ext' => htmlspecialchars($match['equipe_ext'], ENT_QUOTES),
                'date_reception' => htmlspecialchars($match['date_reception'], ENT_QUOTES),
            ),
            implode(';', $destinations));
    }
}

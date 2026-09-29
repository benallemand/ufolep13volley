<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/Emails.php';

/**
 * Pénalités automatiques (issue #345).
 *
 * Feuille de match non signée des deux côtés 48 h après l'horaire du match :
 * -1 point à **chacune des deux équipes**, même si l'une a signé (décision de
 * la commission), dans la compétition du match. Championnats seulement.
 *
 * Chaque pénalité est tracée dans `match_penalties` (match, équipe, motif) ;
 * la clé unique rend l'application idempotente. La pénalité reste si la feuille
 * est signée ensuite ; l'admin l'annule par le bouton « -1 » du classement, la
 * ligne restant comme historique (elle empêche une nouvelle application).
 */
class MatchPenalty extends Generic
{
    const REASON_UNSIGNED_SHEET = 'feuille_non_signee_48h';
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
            file_get_contents(__DIR__ . '/../sql/unsigned_match_sheets_48h.sql'),
            array(
                array('type' => 's', 'value' => $since ?? self::UNSIGNED_SHEET_SINCE),
                array('type' => 's', 'value' => self::REASON_UNSIGNED_SHEET),
            ));
        $applied = 0;
        foreach ($matches as $match) {
            $penalized = array();
            foreach (array('dom', 'ext') as $side) {
                if ($this->penalize((int)$match['id_match'], (int)$match["id_equipe_$side"], $match['code_competition'])) {
                    $penalized[] = $match["equipe_$side"];
                    $applied++;
                }
            }
            if (!empty($penalized)) {
                $this->addActivity("Pénalité automatique (-1 pt, feuille de match non signée à 48 h) : "
                    . implode(', ', $penalized) . " — match " . $match['code_match']);
                $this->notify($match);
            }
        }
        return $applied;
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

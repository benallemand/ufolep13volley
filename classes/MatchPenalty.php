<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/Emails.php';
require_once __DIR__ . '/Sanction.php';

/**
 * Manquements relevés automatiquement (issues #345, #475).
 *
 * Feuille de match non signée des deux côtés 7 jours après l'horaire du match
 * (article 8 du règlement général, issue #456) : une sanction « constatée »
 * est enregistrée pour **chacune des deux équipes**, même si l'une a signé
 * (décision de la commission), avec la sanction envisagée de 1 point en moins.
 * Championnats seulement.
 *
 * Depuis #475, rien n'est retiré du classement : la commission applique ou
 * lève la sanction en réunion de fin de demi-saison (écran « Sanctions »,
 * classe `Sanction`). Un manquement n'est relevé qu'une fois par match et par
 * équipe, même levé ensuite, et reste si la feuille est signée plus tard.
 */
class MatchPenalty extends Generic
{
    /** Motif de la sanction relevée (catalogue `Sanction::MOTIFS`). */
    const MOTIF_UNSIGNED_SHEET = 'feuille_non_signee';
    /** Délai de signature des deux équipes, en jours (article 8 du règlement général). */
    const UNSIGNED_SHEET_DELAY_DAYS = 7;
    /** Mise en service : pas de manquement relevé pour les matchs d'avant. */
    const UNSIGNED_SHEET_SINCE = '2026-10-01';
    /** Sanction envisagée : 1 point en moins. */
    const UNSIGNED_SHEET_POINTS = 1;
    /** Compétitions concernées : les championnats (même liste que `sql/unsigned_match_sheets.sql`). */
    const CHAMPIONSHIPS = array('m', 'f', 'mo');

    /**
     * Date limite de signature de la feuille de match, au format « lundi
     * 16/11/2026 à 20:30 » : horaire du match (fin de journée sans créneau
     * connu) + 7 jours, comme le relevé du cron. Null hors championnat ou
     * sans date de match (issue #486).
     * @param array $match ligne de `matchs_view`
     */
    public static function unsigned_sheet_deadline(array $match): ?string
    {
        if (!in_array($match['code_competition'] ?? '', self::CHAMPIONSHIPS, true)
            || empty($match['date_reception'])) {
            return null;
        }
        $time = empty($match['heure_reception']) ? '23:59' : $match['heure_reception'];
        $start = DateTime::createFromFormat('d/m/Y H:i', $match['date_reception'] . ' ' . $time);
        if ($start === false) {
            return null;
        }
        $deadline = $start->modify('+' . self::UNSIGNED_SHEET_DELAY_DAYS . ' days');
        $days = array('dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi');
        return $days[(int)$deadline->format('w')] . ' ' . $deadline->format('d/m/Y') . ' à ' . $deadline->format('H:i');
    }

    private Sanction $sanction;

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'sanctions';
        $this->sanction = new Sanction();
    }

    /**
     * Relève les manquements dus. Appelé par le cron horaire.
     * @param string|null $since date de mise en service (aaaa-mm-jj), pour les tests
     * @return int nombre de sanctions constatées (une par équipe)
     * @throws Exception
     */
    public function apply_unsigned_sheet_penalties(?string $since = null): int
    {
        $matches = $this->sql_manager->execute(
            file_get_contents(__DIR__ . '/../sql/unsigned_match_sheets.sql'),
            array(
                array('type' => 's', 'value' => $since ?? self::UNSIGNED_SHEET_SINCE),
                array('type' => 'i', 'value' => self::UNSIGNED_SHEET_DELAY_DAYS),
                array('type' => 's', 'value' => self::MOTIF_UNSIGNED_SHEET),
            ));
        $recorded = 0;
        foreach ($matches as $match) {
            $new = false;
            foreach (array('dom', 'ext') as $side) {
                if ($this->sanction->record_automatic((int)$match['id_match'], (int)$match["id_equipe_$side"],
                    $match['code_competition'], self::MOTIF_UNSIGNED_SHEET, 'points', self::UNSIGNED_SHEET_POINTS)) {
                    // une ligne par équipe, rangée dans son historique (#425)
                    $this->addActivity("Manquement constaté (feuille de match non signée à "
                        . self::UNSIGNED_SHEET_DELAY_DAYS . " jours, sanction envisagée : 1 point en moins) : "
                        . $match["equipe_$side"] . " — match " . $match['code_match'], $match["id_equipe_$side"]);
                    $new = true;
                    $recorded++;
                }
            }
            if ($new) {
                $this->notify($match);
            }
        }
        return $recorded;
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

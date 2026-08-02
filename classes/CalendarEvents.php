<?php
require_once __DIR__ . '/Generic.php';

/**
 * Evenements du calendrier de la page d'accueil (issue #253).
 *
 * Ils etaient codes en dur dans pages/components/panel/Home.js ; ils vivent
 * desormais en base et se gerent depuis l'administration.
 */
class CalendarEvents extends Generic
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'calendar_events';
    }

    /**
     * Saison en cours, au format '2026-2027'.
     *
     * Meme regle que le front (AnnualCalendar.js) : de janvier a juin on est
     * encore dans la saison ouverte l'annee precedente ; des juillet, on bascule
     * sur la suivante.
     */
    public static function getCurrentSeason(): string
    {
        $startYear = ((int)date('n') <= 6) ? (int)date('Y') - 1 : (int)date('Y');
        return $startYear . '-' . ($startYear + 1);
    }

    /**
     * Lecture publique, consommee par le calendrier de la home.
     *
     * Les dates sortent au format attendu par AnnualCalendar.js, soit
     * 'd/m/Y H:i'. Exception : une heure a minuit signifie "toute la journee"
     * et sort sans heure, sinon les feries s'afficheraient "a 00:00".
     *
     * @throws Exception
     */
    public function getCalendarEvents($season = null): array
    {
        if (empty($season)) {
            $season = self::getCurrentSeason();
        }
        // Les colonnes du ORDER BY sont qualifiees : sans cela MySQL trierait
        // sur les alias, donc sur la date formatee en jj/mm/aaaa, c'est-a-dire
        // sur du texte (le 02/11 passerait avant le 03/09).
        $sql = "SELECT
                    ce.id,
                    ce.season,
                    ce.label,
                    IF(TIME(ce.date_start) = '00:00:00',
                       DATE_FORMAT(ce.date_start, '%d/%m/%Y'),
                       DATE_FORMAT(ce.date_start, '%d/%m/%Y %H:%i')) AS date_start,
                    IF(TIME(ce.date_end) = '00:00:00',
                       DATE_FORMAT(ce.date_end, '%d/%m/%Y'),
                       DATE_FORMAT(ce.date_end, '%d/%m/%Y %H:%i')) AS date_end
                FROM calendar_events ce
                WHERE ce.season = ?
                ORDER BY ce.date_start, ce.id";
        $bindings = array(
            array('type' => 's', 'value' => $season)
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Liste complete pour la grille d'administration, dates au format ISO
     * attendu par le modele ExtJS.
     *
     * @throws Exception
     */
    public function getAllCalendarEvents(): array
    {
        $sql = "SELECT
                    ce.id,
                    ce.season,
                    ce.label,
                    DATE_FORMAT(ce.date_start, '%Y-%m-%d %H:%i:%s') AS date_start,
                    DATE_FORMAT(ce.date_end, '%Y-%m-%d %H:%i:%s') AS date_end
                FROM calendar_events ce
                ORDER BY ce.season DESC, ce.date_start, ce.id";
        return $this->sql_manager->execute($sql);
    }

    /**
     * Saisons presentes en base, la plus recente d'abord.
     *
     * @throws Exception
     */
    public function getSeasons(): array
    {
        $sql = "SELECT DISTINCT season FROM calendar_events ORDER BY season DESC";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function saveCalendarEvent(
        $id = null,
        $season = '',
        $label = '',
        $date_start = null,
        $date_end = null,
        $dirtyFields = null
    ): void
    {
        @session_start();
        if (!UserManager::isAdmin()) {
            throw new Exception("Seuls les administrateurs peuvent modifier le calendrier");
        }
        if (empty($season)) {
            throw new Exception("La saison est obligatoire");
        }
        if (empty($label)) {
            throw new Exception("Le libelle est obligatoire");
        }
        if (empty($date_start)) {
            throw new Exception("La date de debut est obligatoire");
        }
        // Une periode qui se termine avant d'avoir commence n'afficherait rien.
        if (!empty($date_end) && strtotime($date_end) < strtotime($date_start)) {
            throw new Exception("La date de fin doit etre posterieure a la date de debut");
        }

        // Un champ vide venu du formulaire vaut "evenement ponctuel".
        $date_end = empty($date_end) ? null : $date_end;

        if (!empty($id) && is_numeric($id)) {
            $sql = "UPDATE calendar_events
                    SET season = ?, label = ?, date_start = ?, date_end = ?
                    WHERE id = ?";
            $bindings = array(
                array('type' => 's', 'value' => $season),
                array('type' => 's', 'value' => $label),
                array('type' => 's', 'value' => $date_start),
                array('type' => 's', 'value' => $date_end),
                array('type' => 'i', 'value' => $id)
            );
        } else {
            $sql = "INSERT INTO calendar_events (season, label, date_start, date_end)
                    VALUES (?, ?, ?, ?)";
            $bindings = array(
                array('type' => 's', 'value' => $season),
                array('type' => 's', 'value' => $label),
                array('type' => 's', 'value' => $date_start),
                array('type' => 's', 'value' => $date_end)
            );
        }
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function deleteCalendarEvent($id = null): void
    {
        @session_start();
        if (!UserManager::isAdmin()) {
            throw new Exception("Seuls les administrateurs peuvent supprimer un evenement");
        }
        if (empty($id) || !is_numeric($id)) {
            throw new Exception("Identifiant d'evenement invalide");
        }
        $sql = "DELETE FROM calendar_events WHERE id = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $id)
        );
        $this->sql_manager->execute($sql, $bindings);
    }
}

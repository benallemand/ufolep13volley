<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . '/../classes/CalendarEvents.php';

/**
 * Calendrier de la home stocke en base (issue #253).
 *
 * Les fixtures utilisent une saison fictive '1999-2000' pour ne jamais
 * interferer avec les saisons reelles, et sont supprimees en teardown.
 */
class CalendarEventsTest extends UfolepTestCase
{
    private const TEST_SEASON = '1999-2000';
    private const LABEL_PREFIX = 'UT253 ';

    private CalendarEvents $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = new CalendarEvents();
        $this->purge();
    }

    protected function tearDown(): void
    {
        $this->purge();
        parent::tearDown();
    }

    /**
     * La saison fictive suffit pour l'essentiel, mais un test doit poser un
     * evenement dans la saison courante : d'ou le nettoyage complementaire sur
     * le prefixe de libelle.
     */
    private function purge(): void
    {
        $this->sql->execute(
            "DELETE FROM calendar_events WHERE season = ? OR label LIKE ?",
            [
                ['type' => 's', 'value' => self::TEST_SEASON],
                ['type' => 's', 'value' => self::LABEL_PREFIX . '%'],
            ]
        );
    }

    private function insertEvent(string $label, string $start, ?string $end = null, ?string $season = null): int
    {
        return $this->sql->execute(
            "INSERT INTO calendar_events (season, label, date_start, date_end) VALUES (?, ?, ?, ?)",
            [
                ['type' => 's', 'value' => $season ?? self::TEST_SEASON],
                ['type' => 's', 'value' => $label],
                ['type' => 's', 'value' => $start],
                ['type' => 's', 'value' => $end],
            ]
        );
    }

    public function test_get_calendar_events_filters_on_season_and_sorts_by_date()
    {
        $this->insertEvent('Deuxieme', '1999-11-02 19:30:00');
        $this->insertEvent('Premier', '1999-09-03 19:30:00');

        $events = $this->calendar->getCalendarEvents(self::TEST_SEASON);

        $this->assertCount(2, $events);
        $this->assertEquals('Premier', $events[0]['label']);
        $this->assertEquals('Deuxieme', $events[1]['label']);
    }

    /**
     * Regression : les "Ferie / pont" n'avaient pas d'heure dans Home.js.
     * Une sortie 'd/m/Y H:i' afficherait "a 00:00" sur la home.
     */
    public function test_all_day_point_event_is_returned_without_time()
    {
        $this->insertEvent('Ferie / pont', '1999-11-11 00:00:00');

        $events = $this->calendar->getCalendarEvents(self::TEST_SEASON);

        $this->assertEquals('11/11/1999', $events[0]['date_start']);
        $this->assertNull($events[0]['date_end']);
    }

    public function test_point_event_with_time_keeps_its_time()
    {
        $this->insertEvent('Reunion calendrier', '1999-09-03 19:30:00');

        $events = $this->calendar->getCalendarEvents(self::TEST_SEASON);

        $this->assertEquals('03/09/1999 19:30', $events[0]['date_start']);
        $this->assertNull($events[0]['date_end']);
    }

    public function test_period_event_returns_both_bounds()
    {
        $this->insertEvent('Championnats', '1999-11-03 00:00:00', '1999-12-19 23:59:00');

        $events = $this->calendar->getCalendarEvents(self::TEST_SEASON);

        $this->assertEquals('03/11/1999', $events[0]['date_start']);
        $this->assertEquals('19/12/1999 23:59', $events[0]['date_end']);
    }

    public function test_get_calendar_events_defaults_to_current_season()
    {
        $currentSeason = CalendarEvents::getCurrentSeason();
        $startYear = (int)explode('-', $currentSeason)[0];

        $this->insertEvent(self::LABEL_PREFIX . 'Saison courante', "$startYear-11-15 19:30:00", null, $currentSeason);
        $this->insertEvent(self::LABEL_PREFIX . 'Saison fictive', '1999-11-15 19:30:00');

        $labels = array_column($this->calendar->getCalendarEvents(), 'label');

        $this->assertContains(self::LABEL_PREFIX . 'Saison courante', $labels);
        $this->assertNotContains(self::LABEL_PREFIX . 'Saison fictive', $labels);
    }

    public function test_current_season_switches_in_july()
    {
        $season = CalendarEvents::getCurrentSeason();

        // assertRegExp : PHPUnit 8, assertMatchesRegularExpression n'arrive qu'en 9.
        $this->assertRegExp('/^\d{4}-\d{4}$/', $season);
        [$start, $end] = explode('-', $season);
        $this->assertEquals((int)$start + 1, (int)$end);

        $expectedStart = ((int)date('n') <= 6) ? (int)date('Y') - 1 : (int)date('Y');
        $this->assertEquals($expectedStart, (int)$start);
    }

    public function test_save_is_forbidden_for_non_admin()
    {
        $this->connect_as_team_leader(1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Seuls les administrateurs peuvent modifier le calendrier");
        $this->calendar->saveCalendarEvent(null, self::TEST_SEASON, 'Interdit', '1999-09-03 19:30:00');
    }

    public function test_save_creates_then_updates_an_event()
    {
        $this->connect_as_admin();

        $this->calendar->saveCalendarEvent(null, self::TEST_SEASON, 'Reunion', '1999-09-03 19:30:00');
        $events = $this->calendar->getCalendarEvents(self::TEST_SEASON);
        $this->assertCount(1, $events);
        $this->assertEquals('Reunion', $events[0]['label']);

        $this->calendar->saveCalendarEvent(
            $events[0]['id'],
            self::TEST_SEASON,
            'Reunion reportee',
            '1999-09-10 20:00:00'
        );
        $events = $this->calendar->getCalendarEvents(self::TEST_SEASON);
        $this->assertCount(1, $events);
        $this->assertEquals('Reunion reportee', $events[0]['label']);
        $this->assertEquals('10/09/1999 20:00', $events[0]['date_start']);
    }

    public function test_save_turns_an_empty_end_date_into_a_point_event()
    {
        $this->connect_as_admin();

        $this->calendar->saveCalendarEvent(null, self::TEST_SEASON, 'Ponctuel', '1999-09-03 19:30:00', '');

        $events = $this->calendar->getCalendarEvents(self::TEST_SEASON);
        $this->assertNull($events[0]['date_end']);
    }

    public function test_save_rejects_an_end_before_the_start()
    {
        $this->connect_as_admin();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("La date de fin doit etre posterieure a la date de debut");
        $this->calendar->saveCalendarEvent(
            null,
            self::TEST_SEASON,
            'Incoherent',
            '1999-12-19 00:00:00',
            '1999-11-03 00:00:00'
        );
    }

    public function test_save_rejects_missing_mandatory_fields()
    {
        $this->connect_as_admin();

        $this->expectException(Exception::class);
        $this->calendar->saveCalendarEvent(null, self::TEST_SEASON, '', '1999-09-03 19:30:00');
    }

    public function test_delete_is_forbidden_for_non_admin()
    {
        $id = $this->insertEvent('A supprimer', '1999-09-03 19:30:00');
        $this->connect_as_team_leader(1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Seuls les administrateurs peuvent supprimer un evenement");
        $this->calendar->deleteCalendarEvent($id);
    }

    public function test_delete_removes_the_event()
    {
        $id = $this->insertEvent('A supprimer', '1999-09-03 19:30:00');
        $this->connect_as_admin();

        $this->calendar->deleteCalendarEvent($id);

        $this->assertCount(0, $this->calendar->getCalendarEvents(self::TEST_SEASON));
    }

    public function test_get_all_calendar_events_exposes_iso_dates_for_the_admin_grid()
    {
        $this->insertEvent('Championnats', '1999-11-03 00:00:00', '1999-12-19 23:59:00');

        $events = $this->calendar->getAllCalendarEvents();
        $mine = array_values(array_filter($events, fn($e) => $e['season'] === self::TEST_SEASON));

        $this->assertCount(1, $mine);
        $this->assertEquals('1999-11-03 00:00:00', $mine[0]['date_start']);
        $this->assertEquals('1999-12-19 23:59:00', $mine[0]['date_end']);
    }

    public function test_get_seasons_lists_the_test_season()
    {
        $this->insertEvent('Championnats', '1999-11-03 00:00:00');

        $seasons = array_column($this->calendar->getSeasons(), 'season');

        $this->assertContains(self::TEST_SEASON, $seasons);
    }
}

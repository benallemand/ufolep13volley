<?php
/**
 * E2E test helper (#253) — insère un événement de calendrier connu dans la
 * saison en cours, pour vérifier que la home lit bien le calendrier en base.
 *
 * La date est fixée au 15 novembre de l'année d'ouverture de la saison : elle
 * tombe donc toujours dans les mois affichés par AnnualCalendar (septembre à
 * juin), quelle que soit la date d'exécution du test. Le test déplie les mois
 * passés avant d'assertionner, ce qui le rend indépendant du jour de passage.
 *
 * SECURITY: ne doit jamais être déployé en production.
 */
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$isTestEnv   = getenv('APP_ENV') === 'test';
if (!$isLocalhost && !$isTestEnv) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/CalendarEvents.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

const E2E_POINT_LABEL  = 'E2E Evenement ponctuel';
const E2E_PERIOD_LABEL = 'E2E Periode test';
const E2E_ALLDAY_LABEL = 'E2E Journee entiere';

try {
    $sql = new SqlManager();

    $season = CalendarEvents::getCurrentSeason();
    $startYear = (int)explode('-', $season)[0];

    // Nettoyage préventif si un run précédent s'est interrompu.
    $sql->execute(
        "DELETE FROM calendar_events WHERE label IN (?, ?, ?)",
        [
            ['type' => 's', 'value' => E2E_POINT_LABEL],
            ['type' => 's', 'value' => E2E_PERIOD_LABEL],
            ['type' => 's', 'value' => E2E_ALLDAY_LABEL],
        ]
    );

    $rows = [
        // Ponctuel avec heure : la home doit afficher l'heure.
        [E2E_POINT_LABEL, "$startYear-11-15 19:30:00", null],
        // Période : la home doit afficher un intervalle de dates.
        [E2E_PERIOD_LABEL, "$startYear-11-03 00:00:00", "$startYear-11-20 23:59:00"],
        // Ponctuel à minuit : la home ne doit PAS afficher "à 00:00".
        [E2E_ALLDAY_LABEL, "$startYear-11-11 00:00:00", null],
    ];
    foreach ($rows as $row) {
        $sql->execute(
            "INSERT INTO calendar_events (season, label, date_start, date_end) VALUES (?, ?, ?, ?)",
            [
                ['type' => 's', 'value' => $season],
                ['type' => 's', 'value' => $row[0]],
                ['type' => 's', 'value' => $row[1]],
                ['type' => 's', 'value' => $row[2]],
            ]
        );
    }

    echo json_encode([
        'success' => true,
        'season' => $season,
        'point_label' => E2E_POINT_LABEL,
        'period_label' => E2E_PERIOD_LABEL,
        'allday_label' => E2E_ALLDAY_LABEL,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

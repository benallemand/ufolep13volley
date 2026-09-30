<?php
/**
 * E2E test helper (#342) — remplace le dossier Google Drive des règlements par
 * un dossier fictif, déjà en cache, pour que les pages se testent sans réseau
 * et sans dépendre du contenu réel. Le dossier fictif contient une saison
 * « 2026-2027 » avec deux règlements ; le règlement général porte une
 * tentative de XSS.
 *
 * La valeur du registre est mise de côté (`e2e.rules.folder.backup`) ;
 * `rules_teardown.php` la rétablit et vide le cache fictif.
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
require_once __DIR__ . '/../../classes/SqlManager.php';
require_once __DIR__ . '/../../classes/RulesDocument.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

const E2E_ROOT = 'E2eFixtureRoot000000000000000000';
const E2E_SEASON = 'E2eFixtureSeason0000000000000000';
const E2E_GENERAL = 'E2eFixtureGeneral000000000000000';
const E2E_FEMININE = 'E2eFixtureFeminine00000000000000';

function e2e_folder(array $entries): string
{
    $html = '<html><body><div class="flip-entries">';
    foreach ($entries as [$id, $name, $href]) {
        $html .= "<div class=\"flip-entry\" id=\"entry-$id\"><div class=\"flip-entry-info\"><a href=\"$href\">"
            . "<div class=\"flip-entry-title\">$name</div></a></div></div>";
    }
    return $html . '</div></body></html>';
}

try {
    $sql = new SqlManager();
    $key = ['type' => 's', 'value' => RulesDocument::REGISTRY_KEY];
    $backup = ['type' => 's', 'value' => 'e2e.rules.folder.backup'];

    // Mise de côté de la vraie valeur, une seule fois (un setup rejoué sans
    // teardown ne doit pas écraser la sauvegarde par la valeur fictive).
    $saved = $sql->execute("SELECT COUNT(*) AS n FROM registry WHERE registry_key = ?", [$backup]);
    if ((int)$saved[0]['n'] === 0) {
        $current = $sql->execute("SELECT registry_value FROM registry WHERE registry_key = ? ORDER BY id DESC LIMIT 1", [$key]);
        $sql->execute("INSERT INTO registry (registry_key, registry_value) VALUES (?, ?)",
            [$backup, ['type' => 's', 'value' => $current[0]['registry_value'] ?? '']]);
    }
    $sql->execute("DELETE FROM registry WHERE registry_key = ?", [$key]);
    $sql->execute("INSERT INTO registry (registry_key, registry_value) VALUES (?, ?)",
        [$key, ['type' => 's', 'value' => 'https://drive.google.com/drive/folders/' . E2E_ROOT]]);

    $general = '<html><head><style>.c0{font-weight:700}</style></head><body class="doc-content">'
        . '<p><span class="c0">REGLEMENT GENERAL</span></p>'
        . '<p><span class="c0">Article 1 : Saison sportive E2E</span></p>'
        . '<ul><li><span>Championnat fictif de test</span></li></ul>'
        . '<p><span class="c0">Article 2 : Arbitrage E2E</span></p>'
        . '<p><span>Texte </span><span class="c0">en gras</span>'
        . '<img src="x" onerror="window.__rulesXss = 1"><script>window.__rulesXss = 2</script></p>'
        . '<p><span class="c0">Article 3 : Attribution des points E2E</span></p>'
        . '<table><tr><td><p>Victoire 3-0</p></td><td><p>3 points</p></td></tr></table>'
        . '</body></html>';
    $feminine = '<html><body><p>CHAMPIONNAT FEMININ 4x4</p>'
        . '<p>Article 1 : Définition de la compétition E2E</p><p>Championnat féminin fictif.</p></body></html>';
    $rows = [
        ['folder', E2E_ROOT, e2e_folder([
            [E2E_SEASON, '2026-2027', 'https://drive.google.com/drive/folders/' . E2E_SEASON],
        ])],
        ['folder', E2E_SEASON, e2e_folder([
            [E2E_GENERAL, 'REGLEMENT GENERAL_2026_2027.docx', 'https://drive.google.com/file/d/' . E2E_GENERAL . '/view'],
            [E2E_FEMININE, 'CHAMPIONNAT FEMININ 4x4_2026_2027.docx', 'https://drive.google.com/file/d/' . E2E_FEMININE . '/view'],
        ])],
        ['html', E2E_GENERAL, $general],
        ['html', E2E_FEMININE, $feminine],
        ['pdf', E2E_GENERAL, "%PDF-1.4\n% reglement general E2E\n%%EOF\n"],
    ];
    foreach ($rows as [$format, $id, $content]) {
        $sql->execute(
            "REPLACE INTO document_cache (cache_key, source_id, content, content_type, fetched_at, checked_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())",
            [
                ['type' => 's', 'value' => "rules.$format.$id"],
                ['type' => 's', 'value' => $id],
                ['type' => 's', 'value' => $content],
                ['type' => 's', 'value' => $format === 'pdf' ? 'application/pdf' : 'text/html'],
            ]);
    }
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

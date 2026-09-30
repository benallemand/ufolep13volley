<?php
/**
 * E2E test helper (#342) — place un règlement fictif dans le cache du document
 * Google, frais, pour que la page se teste sans réseau et sans dépendre du
 * contenu réel du document. Le document fictif contient une tentative de XSS.
 *
 * Si le registre ne désigne aucun document (base de CI), un identifiant fictif
 * y est posé ; `rules_teardown.php` le retire, et vide le cache dans tous les
 * cas — la prochaine visite relit le vrai document.
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

const E2E_RULES_DOCUMENT_ID = 'E2eFixtureDocument00000000000000';

try {
    $sql = new SqlManager();
    $key = ['type' => 's', 'value' => RulesDocument::REGISTRY_KEY];
    $rows = $sql->execute("SELECT registry_value FROM registry WHERE registry_key = ? ORDER BY id DESC LIMIT 1", [$key]);
    $source_id = RulesDocument::parse_document_id($rows[0]['registry_value'] ?? null);
    if ($source_id === null) {
        $sql->execute("DELETE FROM registry WHERE registry_key = ?", [$key]);
        $sql->execute("INSERT INTO registry (registry_key, registry_value) VALUES (?, ?)",
            [$key, ['type' => 's', 'value' => E2E_RULES_DOCUMENT_ID]]);
        $source_id = E2E_RULES_DOCUMENT_ID;
    }

    $html = '<html><head><style>.c0{font-weight:700}</style></head><body class="doc-content">'
        . '<p><span class="c0">REGLEMENT GENERAL</span></p>'
        . '<p><span class="c0">Article 1 : Saison sportive E2E</span></p>'
        . '<ul><li><span>Championnat fictif de test</span></li></ul>'
        . '<p><span class="c0">Article 2 : Arbitrage E2E</span></p>'
        . '<p><span>Texte </span><span class="c0">en gras</span>'
        . '<img src="x" onerror="window.__rulesXss = 1"><script>window.__rulesXss = 2</script></p>'
        . '<p><span class="c0">Article 3 : Attribution des points E2E</span></p>'
        . '<table><tr><td><p>Victoire 3-0</p></td><td><p>3 points</p></td></tr></table>'
        . '</body></html>';
    $pdf = "%PDF-1.4\n% document E2E\n%%EOF\n";

    foreach (['html' => [$html, 'text/html'], 'pdf' => [$pdf, 'application/pdf']] as $format => [$content, $type]) {
        $sql->execute(
            "REPLACE INTO document_cache (cache_key, source_id, content, content_type, fetched_at, checked_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())",
            [
                ['type' => 's', 'value' => RulesDocument::REGISTRY_KEY . ".$format"],
                ['type' => 's', 'value' => $source_id],
                ['type' => 's', 'value' => $content],
                ['type' => 's', 'value' => $type],
            ]);
    }
    echo json_encode(['success' => true, 'source_id' => $source_id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

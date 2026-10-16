<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rest/json_response.php';

use PHPUnit\Framework\TestCase;

/**
 * Issue #433 — réponses JSON de `rest/action.php`. Les en-têtes, que PHPUnit
 * en ligne de commande ne voit pas, sont vérifiés en E2E
 * (`issue_433_api_json.spec.js`).
 */
class JsonResponseTest extends TestCase
{
    public function test_les_balises_sont_echappees(): void
    {
        $body = json_response_body(array('message' => 'Identifiant <svg onload=alert(1)> invalide'));

        $this->assertStringNotContainsString('<', $body);
        $this->assertStringNotContainsString('>', $body);
        // `<` et `>`, écrits en morceaux pour rester littéraux
        $this->assertStringContainsString('\\' . 'u003Csvg onload=alert(1)\\' . 'u003E', $body);
    }

    public function test_un_client_json_relit_la_valeur_a_l_identique(): void
    {
        $data = array('success' => false, 'message' => "L'équipe <b>Aix'pertes</b> & co", 'n' => 3);

        $this->assertSame($data, json_decode(json_response_body($data), true));
    }
}

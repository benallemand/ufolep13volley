<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/RulesDocument.php';

/**
 * Issue #342 — règlement général lu dans Google Docs.
 *
 * Aucun appel réseau : le téléchargement est remplacé par un faux, qui compte
 * ses appels. La clé du registre est sauvegardée puis restaurée.
 */
class RulesDocumentTest extends UfolepTestCase
{
    private const DOC_A = 'TestDocumentAaaaaaaaaaaaaaaaaaaa';
    private const DOC_B = 'TestDocumentBbbbbbbbbbbbbbbbbbbbb';

    private array $saved_registry = array();
    private int $calls = 0;
    private ?string $next_content = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saved_registry = $this->sql->execute(
            "SELECT registry_key, registry_value FROM registry WHERE registry_key = ?",
            array(array('type' => 's', 'value' => RulesDocument::REGISTRY_KEY)));
        $this->clean();
        $this->calls = 0;
    }

    protected function tearDown(): void
    {
        $this->clean();
        foreach ($this->saved_registry as $row) {
            $this->sql->execute("INSERT INTO registry (registry_key, registry_value) VALUES (?, ?)",
                array(
                    array('type' => 's', 'value' => $row['registry_key']),
                    array('type' => 's', 'value' => $row['registry_value']),
                ));
        }
        parent::tearDown();
    }

    private function clean(): void
    {
        $this->sql->execute("DELETE FROM registry WHERE registry_key = ?",
            array(array('type' => 's', 'value' => RulesDocument::REGISTRY_KEY)));
        $this->sql->execute("DELETE FROM document_cache WHERE cache_key LIKE ?",
            array(array('type' => 's', 'value' => RulesDocument::REGISTRY_KEY . '.%')));
    }

    private function configure(string $value): void
    {
        $this->sql->execute("DELETE FROM registry WHERE registry_key = ?",
            array(array('type' => 's', 'value' => RulesDocument::REGISTRY_KEY)));
        $this->sql->execute("INSERT INTO registry (registry_key, registry_value) VALUES (?, ?)",
            array(
                array('type' => 's', 'value' => RulesDocument::REGISTRY_KEY),
                array('type' => 's', 'value' => $value),
            ));
    }

    private function rules(): RulesDocument
    {
        return new RulesDocument(function (string $url, string $format) {
            $this->calls++;
            return $this->next_content;
        });
    }

    private function age_cache(): void
    {
        $this->sql->execute(
            "UPDATE document_cache SET fetched_at = NOW() - INTERVAL 1 HOUR, checked_at = NOW() - INTERVAL 1 HOUR
             WHERE cache_key LIKE ?",
            array(array('type' => 's', 'value' => RulesDocument::REGISTRY_KEY . '.%')));
    }

    private static function export(string $body, string $styles = ''): string
    {
        return "<html><head><meta content=\"text/html; charset=UTF-8\" http-equiv=\"content-type\">"
            . "<style type=\"text/css\">$styles</style></head><body class=\"doc-content\">$body</body></html>";
    }

    public function test_le_contenu_hostile_est_neutralise(): void
    {
        $raw = self::export(
            '<p><span>Article 1 : Titre</span></p>'
            . '<p onclick="alert(1)"><span>texte</span><script>window.pwned = 1</script></p>'
            . '<img src="x" onerror="alert(2)">'
            . '<iframe src="https://evil.example"></iframe>'
            . '<p><a href="javascript:alert(3)">piège</a> <a href="#h.abc">signet</a></p>'
            . '<p><a href="https://www.google.com/url?q=https://ufolep13.example/page&amp;sa=D">lien</a></p>'
            . '<p><a href="mailto:commission@example.test">écrire</a></p>'
            . '<p>&lt;b&gt;pas une balise&lt;/b&gt;</p>'
            . '<style>body{display:none}</style>');
        $html = RulesDocument::sanitize($raw)['articles'][0]['html'];

        foreach (array('<script', 'pwned', 'onclick', 'onerror', '<img', '<iframe', 'javascript:', 'display:none',
                     '#h.abc', 'google.com/url', '<b>') as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html, "« $forbidden » ne doit pas passer");
        }
        $this->assertStringContainsString('<p>texte</p>', $html);
        $this->assertStringContainsString('piège', $html, 'le texte d\'un lien refusé reste');
        $this->assertStringContainsString(
            '<a href="https://ufolep13.example/page" target="_blank" rel="noopener noreferrer">lien</a>', $html,
            'la redirection google.com/url est retirée');
        $this->assertStringContainsString('href="mailto:commission@example.test"', $html);
        $this->assertStringContainsString('&lt;b&gt;pas une balise&lt;/b&gt;', $html, 'le texte reste échappé');
    }

    public function test_le_document_est_decoupe_en_articles(): void
    {
        $raw = self::export(
            '<p class="c5"><span class="c10">REGLEMENT GENERAL</span></p>'
            . '<p><span>Préambule de la commission.</span></p>'
            . '<p class="c3"><span class="c0">&nbsp;</span></p>'
            . '<p><span class="c0">Article 1 : Saison sportive</span></p>'
            . '<ul class="c6"><li class="c1"><span class="c4">Championnat </span><span class="c0">masculin</span></li></ul>'
            . '<p><span class="c4">&#57619;</span></p>'
            . '<p><span class="c0">Article 2 : Arbitrage</span></p>'
            . '<table class="c11"><tr><td colspan="2" rowspan="1" class="c7"><p><span class="c9">Victoire</span></p></td></tr></table>',
            '.c0{font-weight:700;font-style:normal}.c9{font-style:italic;text-decoration:underline}.c4{font-weight:400}');
        $result = RulesDocument::sanitize($raw);

        $this->assertSame('<p>Préambule de la commission.</p>', $result['intro_html'],
            'titre du document retiré, préambule conservé');
        $this->assertCount(2, $result['articles']);
        $this->assertSame(array(1, 'Saison sportive', 'article-1'),
            array($result['articles'][0]['number'], $result['articles'][0]['title'], $result['articles'][0]['anchor']));
        $this->assertSame('<ul><li>Championnat <strong>masculin</strong></li></ul>', $result['articles'][0]['html'],
            'gras rendu en balise, paragraphe d\'un caractère Word invisible retiré');
        $this->assertSame('<table><tr><td colspan="2"><p><u><em>Victoire</em></u></p></td></tr></table>',
            $result['articles'][1]['html']);
    }

    public function test_l_identifiant_du_document_se_lit_dans_toutes_les_formes_d_url(): void
    {
        $cases = array(
            'https://docs.google.com/document/d/1__0tiCfP-6Rs0bq6Ir2rkLgMrN5DvJ50/edit' => '1__0tiCfP-6Rs0bq6Ir2rkLgMrN5DvJ50',
            'https://docs.google.com/document/d/1__0tiCfP-6Rs0bq6Ir2rkLgMrN5DvJ50/edit?usp=sharing&ouid=1' => '1__0tiCfP-6Rs0bq6Ir2rkLgMrN5DvJ50',
            'https://docs.google.com/document/d/1__0tiCfP-6Rs0bq6Ir2rkLgMrN5DvJ50/' => '1__0tiCfP-6Rs0bq6Ir2rkLgMrN5DvJ50',
            ' 1__0tiCfP-6Rs0bq6Ir2rkLgMrN5DvJ50 ' => '1__0tiCfP-6Rs0bq6Ir2rkLgMrN5DvJ50',
            'https://evil.example/?x=/document/d/../../etc' => null,
            'court' => null,
            '' => null,
        );
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, RulesDocument::parse_document_id($value), "valeur « $value »");
        }
    }

    public function test_sans_document_configure_rien_n_est_demande_a_google(): void
    {
        $this->assertSame('not_configured', $this->rules()->getGeneralRules()['status']);
        $this->configure('pas un identifiant');
        $this->assertSame('not_configured', $this->rules()->getGeneralRules()['status']);
        $this->assertSame(0, $this->calls);
    }

    public function test_le_cache_evite_un_appel_a_chaque_visite(): void
    {
        $this->configure('https://docs.google.com/document/d/' . self::DOC_A . '/edit');
        $this->next_content = self::export('<p><span>Article 1 : Un</span></p><p>contenu</p>');

        $first = $this->rules()->getGeneralRules();
        $second = $this->rules()->getGeneralRules();

        $this->assertSame(1, $this->calls, 'une seule récupération tant que le cache est frais');
        $this->assertSame('ok', $first['status']);
        $this->assertSame('ok', $second['status']);
        $this->assertSame('Un', $second['articles'][0]['title']);
        $this->assertSame('/rest/action.php/rules/getGeneralRulesPdf', $second['pdf_url'],
            'le lien du document original passe par le serveur, jamais par Google');
    }

    public function test_si_google_ne_repond_plus_la_derniere_version_reste_servie(): void
    {
        $this->configure(self::DOC_A);
        $this->next_content = self::export('<p><span>Article 1 : Ancien</span></p>');
        $this->rules()->getGeneralRules();
        $this->age_cache();

        $this->next_content = null;
        $stale = $this->rules()->getGeneralRules();
        $this->assertSame('stale', $stale['status']);
        $this->assertSame('Ancien', $stale['articles'][0]['title']);
        $this->assertSame(2, $this->calls);

        $this->rules()->getGeneralRules();
        $this->assertSame(2, $this->calls, 'pas de nouvelle tentative avant RETRY_SECONDS');
    }

    public function test_sans_aucune_version_la_page_est_indisponible(): void
    {
        $this->configure(self::DOC_A);
        $this->next_content = null;
        $this->assertSame('unavailable', $this->rules()->getGeneralRules()['status']);
        $this->assertSame('unavailable', $this->rules()->getGeneralRules()['status']);
        $this->assertSame(1, $this->calls, 'l\'échec est noté : pas de nouvel appel immédiat');
    }

    public function test_changer_de_document_invalide_le_cache(): void
    {
        $this->configure(self::DOC_A);
        $this->next_content = self::export('<p><span>Article 1 : Document A</span></p>');
        $this->rules()->getGeneralRules();

        $this->configure(self::DOC_B);
        $this->next_content = self::export('<p><span>Article 1 : Document B</span></p>');
        $result = $this->rules()->getGeneralRules();

        $this->assertSame(2, $this->calls);
        $this->assertSame('Document B', $result['articles'][0]['title']);
    }

    public function test_le_pdf_est_servi_depuis_le_cache_ou_refuse_proprement(): void
    {
        $this->configure(self::DOC_A);
        $this->next_content = null;
        try {
            $this->rules()->get_pdf_content();
            $this->fail('sans PDF, une exception 404 est attendue');
        } catch (Exception $e) {
            $this->assertSame(404, $e->getCode());
        }

        $this->clean();
        $this->configure(self::DOC_A);
        $this->next_content = "%PDF-1.4 faux";
        $this->assertSame("%PDF-1.4 faux", $this->rules()->get_pdf_content());
        $this->assertSame("%PDF-1.4 faux", $this->rules()->get_pdf_content());
        $this->assertSame(2, $this->calls, 'un appel raté, puis un appel réussi mis en cache');
    }
}

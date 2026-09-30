<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/RulesDocument.php';

/**
 * Issue #342 — règlements lus dans le dossier Google Drive de la commission.
 *
 * Aucun appel réseau : le téléchargement est remplacé par un faux, qui sert des
 * réponses par URL et compte ses appels. La clé du registre est sauvegardée
 * puis restaurée.
 */
class RulesDocumentTest extends UfolepTestCase
{
    private const ROOT = 'TestRootFolderAaaaaaaaaaaaaaaaaa';
    private const SEASON_2526 = 'TestSeason2526Aaaaaaaaaaaaaaaaaa';
    private const SEASON_2627 = 'TestSeason2627Aaaaaaaaaaaaaaaaaa';
    private const DRAFT = 'TestSeasonDraftAaaaaaaaaaaaaaaaa';
    private const GENERAL_2526 = 'TestGeneral2526Aaaaaaaaaaaaaaaaa';
    private const GENERAL_2627 = 'TestGeneral2627Aaaaaaaaaaaaaaaaa';
    private const FEMININE_2627 = 'TestFeminine2627Aaaaaaaaaaaaaaaa';
    private const MASCULINE_2526 = 'TestMasculine2526Aaaaaaaaaaaaaaa';

    private array $saved_registry = array();
    /** @var array<string, ?string> URL => réponse (null : échec) */
    private array $responses = array();
    /** @var array<string, int> URL => nombre d'appels */
    private array $calls = array();

    protected function setUp(): void
    {
        parent::setUp();
        $this->saved_registry = $this->sql->execute(
            "SELECT registry_key, registry_value FROM registry WHERE registry_key = ?",
            array(array('type' => 's', 'value' => RulesDocument::REGISTRY_KEY)));
        $this->clean();
        $this->responses = array();
        $this->calls = array();
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
        $this->sql->execute("DELETE FROM document_cache WHERE cache_key LIKE 'rules.%'");
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
            $this->calls[$url] = ($this->calls[$url] ?? 0) + 1;
            return $this->responses[$url] ?? null;
        });
    }

    private function total_calls(): int
    {
        return array_sum($this->calls);
    }

    private function age_cache(): void
    {
        $this->sql->execute("UPDATE document_cache SET fetched_at = NOW() - INTERVAL 1 HOUR,
                                    checked_at = NOW() - INTERVAL 1 HOUR WHERE cache_key LIKE 'rules.%'");
    }

    private static function export(string $body, string $styles = ''): string
    {
        return "<html><head><meta content=\"text/html; charset=UTF-8\" http-equiv=\"content-type\">"
            . "<style type=\"text/css\">$styles</style></head><body class=\"doc-content\">$body</body></html>";
    }

    /**
     * Liste de dossier au format de `embeddedfolderview`.
     * @param array<int, array{0: string, 1: string, 2: string}> $entries [id, nom, folder|docx|doc|pdf]
     */
    private static function folder(array $entries): string
    {
        $html = '<html><body><div class="flip-entries">';
        foreach ($entries as [$id, $name, $type]) {
            $href = match ($type) {
                'folder' => "https://drive.google.com/drive/folders/$id",
                'doc' => "https://docs.google.com/document/d/$id/edit?usp=drive_web",
                default => "https://drive.google.com/file/d/$id/view?usp=drive_web",
            };
            $html .= "<div class=\"flip-entry\" id=\"entry-$id\" tabindex=\"0\" role=\"link\">"
                . "<div class=\"flip-entry-info\"><a href=\"$href\" target=\"_blank\">"
                . "<div class=\"flip-entry-title\">" . htmlspecialchars($name) . "</div></a></div>"
                . "<div class=\"flip-entry-last-modified\"><div>Sep 28</div></div></div>";
        }
        return $html . '</div></body></html>';
    }

    /** Un dossier racine avec deux saisons, un brouillon et un document égaré. */
    private function publish_folders(): void
    {
        $this->configure('https://drive.google.com/drive/folders/' . self::ROOT . '?usp=drive_link');
        $url = fn(string $id, string $format) => RulesDocument::source_url($id, $format);
        $this->responses[$url(self::ROOT, 'folder')] = self::folder(array(
            array(self::SEASON_2526, '2025-2026', 'folder'),
            array(self::SEASON_2627, '2026-2027', 'folder'),
            array(self::DRAFT, '2027-2028 brouillon', 'folder'),
            array('TestStrayDocumentAaaaaaaaaaaaaaaa', 'REGLEMENTS 2024-2025', 'doc'),
        ));
        $this->responses[$url(self::SEASON_2627, 'folder')] = self::folder(array(
            array(self::GENERAL_2627, 'REGLEMENT GENERAL_2026_2027.docx', 'docx'),
            array(self::FEMININE_2627, 'CHAMPIONNAT FEMININ 4x4_2026_2027.docx', 'docx'),
            array('TestPdfAaaaaaaaaaaaaaaaaaaaaaaaaa', 'Affiche.pdf', 'pdf'),
        ));
        $this->responses[$url(self::SEASON_2526, 'folder')] = self::folder(array(
            array(self::GENERAL_2526, 'REGLEMENT GENERAL_2025_2026.docx', 'docx'),
            array(self::MASCULINE_2526, 'CHAMPIONNAT MASCULIN 6x6_2025_2026.docx', 'docx'),
        ));
        $this->responses[$url(self::DRAFT, 'folder')] = self::folder(array(
            array('TestDraftGeneralAaaaaaaaaaaaaaaaa', 'REGLEMENT GENERAL_2027_2028.docx', 'docx'),
        ));
        $this->responses[$url(self::GENERAL_2627, 'html')] = self::export(
            '<p><span>REGLEMENT GENERAL</span></p><p><span>Article 1 : Général 2026</span></p><p>texte</p>');
        $this->responses[$url(self::GENERAL_2526, 'html')] = self::export(
            '<p><span>REGLEMENT GENERAL</span></p><p><span>Article 1 : Général 2025</span></p>');
        $this->responses[$url(self::MASCULINE_2526, 'html')] = self::export(
            '<p><span>CHAMPIONNAT MASCULIN 6x6</span></p><p><span>Article 1 : Masculin 2025</span></p>');
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
            '<p class="c5"><span class="c10">CHAMPIONNAT FEMININ 4x4</span></p>'
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

    public function test_l_identifiant_google_se_lit_dans_toutes_les_formes_d_url(): void
    {
        // Identifiant fictif : aucune adresse Drive réelle dans ce dépôt public.
        $id = '1FakeFolderId_ForTests-0123456789';
        $cases = array(
            "https://drive.google.com/drive/folders/$id?usp=drive_link" => $id,
            "https://docs.google.com/document/d/$id/edit?usp=sharing" => $id,
            "https://drive.google.com/file/d/$id/view?usp=drive_web" => $id,
            "https://drive.google.com/embeddedfolderview?id=$id#list" => $id,
            " $id " => $id,
            'https://evil.example/?x=/folders/../../etc' => null,
            'court' => null,
            '' => null,
        );
        foreach ($cases as $value => $expected) {
            $this->assertSame($expected, RulesDocument::parse_google_id($value), "valeur « $value »");
        }
    }

    public function test_chaque_fichier_est_reconnu_par_son_nom(): void
    {
        $cases = array(
            'REGLEMENT GENERAL_2026_2027.docx' => array('general', 'Règlement général'),
            'CHAMPIONNAT FEMININ 4x4_2026_2027.docx' => array('feminine', 'Championnat féminin 4×4'),
            'CHAMPIONNAT MASCULIN 6x6_2026_2027.docx' => array('masculine', 'Championnat masculin 6×6'),
            'CHAMPIONNAT MIXTE 4x4_2026_2027.docx' => array('mixte', 'Championnat mixte 4×4'),
            'COUPE ISOARDI MASCULIN 6x6_2026_2027.docx' => array('isoardi', 'Coupe masculine Isoardi'),
            'COUPE KHOURI HANNA MIXTE 4x4_2026_2027.docx' => array('koury-hanna', 'Coupe mixte Koury Hanna'),
            'Coupe féminine 6x6 2027-2028.docx' => array('coupe-feminine', 'Coupe féminine 6×6'),
            "Tournoi d'été_2026_2027.docx" => array('tournoi-d-ete', "Tournoi d'été"),
        );
        foreach ($cases as $filename => [$slug, $label]) {
            [$found_slug, $meta] = RulesDocument::identify($filename);
            $this->assertSame(array($slug, $label), array($found_slug, $meta['label']), $filename);
        }
    }

    public function test_la_liste_de_dossier_ne_garde_que_dossiers_et_documents(): void
    {
        $entries = RulesDocument::parse_folder(self::folder(array(
            array('TestFolderAaaaaaaaaaaaaaaaaaaaaaa', '2026-2027', 'folder'),
            array('TestDocxAaaaaaaaaaaaaaaaaaaaaaaaa', 'REGLEMENT GENERAL_2026_2027.docx', 'docx'),
            array('TestNativeAaaaaaaaaaaaaaaaaaaaaaa', 'REGLEMENTS 2025-2026', 'doc'),
            array('TestPdfAaaaaaaaaaaaaaaaaaaaaaaaaa', 'Affiche.pdf', 'pdf'),
        )));
        $this->assertSame(array(
            array('id' => 'TestFolderAaaaaaaaaaaaaaaaaaaaaaa', 'name' => '2026-2027', 'type' => 'folder'),
            array('id' => 'TestDocxAaaaaaaaaaaaaaaaaaaaaaaaa', 'name' => 'REGLEMENT GENERAL_2026_2027.docx',
                'type' => 'document'),
            array('id' => 'TestNativeAaaaaaaaaaaaaaaaaaaaaaa', 'name' => 'REGLEMENTS 2025-2026', 'type' => 'document'),
        ), $entries);
    }

    public function test_chaque_reglement_vient_de_la_saison_la_plus_recente_qui_le_contient(): void
    {
        $this->publish_folders();
        $list = $this->rules()->getRulesList();

        $this->assertSame(array('general', 'feminine', 'masculine'), array_column($list, 'slug'),
            'ordre des règlements connus ; brouillon et document hors saison ignorés');
        $this->assertSame(array('2026-2027', '2026-2027', '2025-2026'), array_column($list, 'season'),
            'le masculin, absent de 2026-2027, reste celui de 2025-2026');
        $this->assertSame('/rest/action.php/rules/getRulesPdf?slug=general', $list[0]['pdf_url']);

        $general = $this->rules()->getRules('general');
        $this->assertSame('ok', $general['status']);
        $this->assertSame('Général 2026', $general['articles'][0]['title']);
        $masculine = $this->rules()->getRules('masculine');
        $this->assertSame(array('2025-2026', 'Masculin 2025'),
            array($masculine['season'], $masculine['articles'][0]['title']));
        $this->assertArrayNotHasKey(RulesDocument::source_url(self::DRAFT, 'folder'), $this->calls,
            'un dossier qui n\'est pas exactement « AAAA-AAAA » n\'est même pas ouvert');
    }

    public function test_sans_dossier_configure_rien_n_est_demande_a_google(): void
    {
        $this->assertSame(array(), $this->rules()->getRulesList());
        $this->assertSame('not_configured', $this->rules()->getRules('general')['status']);
        $this->configure('pas un identifiant');
        $this->assertSame('not_configured', $this->rules()->getRules('general')['status']);
        $this->assertSame(0, $this->total_calls());
    }

    public function test_un_reglement_absent_du_dossier_est_signale(): void
    {
        $this->publish_folders();
        $this->assertSame('not_found', $this->rules()->getRules('isoardi')['status']);
    }

    public function test_le_cache_evite_un_appel_a_chaque_visite(): void
    {
        $this->publish_folders();
        $this->rules()->getRules('general');
        $before = $this->total_calls();
        $second = $this->rules()->getRules('general');

        $this->assertSame($before, $this->total_calls(), 'aucune récupération tant que le cache est frais');
        $this->assertSame('ok', $second['status']);
        $this->assertSame('/rest/action.php/rules/getRulesPdf?slug=general', $second['pdf_url'],
            'le lien du document original passe par le serveur, jamais par Google');
    }

    public function test_si_google_ne_repond_plus_la_derniere_version_reste_servie(): void
    {
        $this->publish_folders();
        $this->rules()->getRules('general');
        $this->age_cache();

        $this->responses = array();
        $stale = $this->rules()->getRules('general');
        $this->assertSame('stale', $stale['status']);
        $this->assertSame('Général 2026', $stale['articles'][0]['title']);

        $before = $this->total_calls();
        $this->rules()->getRules('general');
        $this->assertSame($before, $this->total_calls(), 'pas de nouvelle tentative avant RETRY_SECONDS');
    }

    public function test_sans_aucune_version_la_page_est_indisponible(): void
    {
        $this->publish_folders();
        unset($this->responses[RulesDocument::source_url(self::GENERAL_2627, 'html')]);
        $this->assertSame('unavailable', $this->rules()->getRules('general')['status']);
        $this->assertSame('unavailable', $this->rules()->getRules('general')['status']);
        $this->assertSame(1, $this->calls[RulesDocument::source_url(self::GENERAL_2627, 'html')],
            'l\'échec est noté : pas de nouvel appel immédiat');
    }

    public function test_le_pdf_est_servi_depuis_le_cache_ou_refuse_proprement(): void
    {
        $this->publish_folders();
        try {
            $this->rules()->get_pdf_content('general');
            $this->fail('sans PDF, une exception 404 est attendue');
        } catch (Exception $e) {
            $this->assertSame(404, $e->getCode());
        }

        $this->sql->execute("DELETE FROM document_cache WHERE cache_key LIKE 'rules.pdf.%'");
        $pdf_url = RulesDocument::source_url(self::GENERAL_2627, 'pdf');
        $this->responses[$pdf_url] = "%PDF-1.4 faux";
        $this->assertSame("%PDF-1.4 faux", $this->rules()->get_pdf_content('general'));
        $this->assertSame("%PDF-1.4 faux", $this->rules()->get_pdf_content('general'));
        $this->assertSame(2, $this->calls[$pdf_url], 'un appel raté, puis un appel réussi mis en cache');
    }
}

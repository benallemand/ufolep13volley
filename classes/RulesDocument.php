<?php
require_once __DIR__ . '/Generic.php';

/**
 * Règlement général tenu dans Google Docs, affiché sur le site (issue #342).
 *
 * Le document est désigné par le registre (`rules.general.document`, écran
 * « Base de registres » de l'admin) : son URL Google Docs, ou son seul
 * identifiant. Le serveur en récupère l'export HTML et l'export PDF, les met
 * en cache dans `document_cache`, et ne rend au navigateur que du HTML
 * reconstruit à partir d'une liste blanche.
 *
 * L'URL du document ne sort jamais du serveur : le lien « document original »
 * passe par `getGeneralRulesPdf`. Le partage du document peut donc rester en
 * lecture seule (« Tous les utilisateurs disposant du lien : Lecteur »), sans
 * que son lien d'édition apparaisse nulle part sur le site.
 *
 * Si Google ne répond pas, on sert la dernière version en cache, marquée
 * comme telle ; sans cache du tout, la page affiche un message.
 */
class RulesDocument extends Generic
{
    public const REGISTRY_KEY = 'rules.general.document';
    /** Durée pendant laquelle un export récupéré est servi sans redemander. */
    public const TTL_SECONDS = 900;
    /** Délai minimal entre deux tentatives quand Google ne répond pas. */
    public const RETRY_SECONDS = 300;
    private const MAX_BYTES = 10 * 1024 * 1024;
    private const CONTENT_TYPES = array('html' => 'text/html', 'pdf' => 'application/pdf');

    /** Balises dont le contenu est jeté avec elles. */
    private const DROPPED_TAGS = array('head', 'script', 'style', 'noscript', 'template', 'iframe', 'object',
        'embed', 'svg', 'math', 'form', 'input', 'button', 'select', 'textarea', 'img', 'video', 'audio');
    /** Balises conservées telles quelles, sans attribut. */
    private const KEPT_TAGS = array('ul', 'ol', 'li', 'strong', 'em', 'u', 'sup', 'sub', 'tr', 'thead', 'tbody');

    /** @var callable(string, string): ?string */
    private $fetcher;

    /**
     * @param callable|null $fetcher (url, format) => contenu, ou null en cas
     *                               d'échec ; remplaçable pour les tests.
     */
    public function __construct(?callable $fetcher = null)
    {
        parent::__construct();
        $this->table_name = 'document_cache';
        $this->fetcher = $fetcher ?? array(self::class, 'http_get');
    }

    /**
     * Le règlement général, prêt à afficher.
     *
     * @return array{status: string, fetched_at: ?string, intro_html: string,
     *               articles: array, pdf_url: ?string}
     *   status : ok | stale (Google ne répond pas, dernière version connue)
     *          | unavailable (aucune version connue) | not_configured
     * @throws Exception
     */
    public function getGeneralRules(): array
    {
        $result = array('status' => 'not_configured', 'fetched_at' => null, 'intro_html' => '',
            'articles' => array(), 'pdf_url' => null);
        $source_id = $this->get_source_id();
        if ($source_id === null) {
            return $result;
        }
        $document = $this->get_document('html', $source_id);
        if ($document === null) {
            $result['status'] = 'unavailable';
            return $result;
        }
        $result = array_merge($result, self::sanitize($document['content']));
        $result['status'] = $document['stale'] ? 'stale' : 'ok';
        $result['fetched_at'] = $document['fetched_at'];
        $result['pdf_url'] = '/rest/action.php/rules/getGeneralRulesPdf';
        return $result;
    }

    /**
     * Le document original, en PDF. Termine la requête.
     * @throws Exception
     */
    public function getGeneralRulesPdf(): void
    {
        $pdf = $this->get_pdf_content();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="reglement-general-ufolep13.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit(0);
    }

    /**
     * @throws Exception 404 quand aucun PDF n'est disponible
     */
    public function get_pdf_content(): string
    {
        $source_id = $this->get_source_id();
        $document = $source_id === null ? null : $this->get_document('pdf', $source_id);
        if ($document === null) {
            throw new Exception("Le document original est momentanément indisponible.", 404);
        }
        return $document['content'];
    }

    /**
     * Identifiant du document Google, lu dans le registre. Accepte l'URL
     * complète (édition, consultation, partage…) ou l'identifiant seul.
     * @throws Exception
     */
    public function get_source_id(): ?string
    {
        $rows = $this->sql_manager->execute(
            "SELECT registry_value FROM registry WHERE registry_key = ? ORDER BY id DESC LIMIT 1",
            array(array('type' => 's', 'value' => self::REGISTRY_KEY)));
        return self::parse_document_id($rows[0]['registry_value'] ?? null);
    }

    public static function parse_document_id(?string $value): ?string
    {
        $value = trim((string)$value);
        if (preg_match('#/document/d/([A-Za-z0-9_-]{20,})#', $value, $m)) {
            return $m[1];
        }
        if (preg_match('#^[A-Za-z0-9_-]{20,}$#', $value)) {
            return $value;
        }
        return null;
    }

    public static function export_url(string $source_id, string $format): string
    {
        return "https://docs.google.com/document/d/$source_id/export?format=$format";
    }

    /**
     * Export en cache, rafraîchi au-delà de TTL_SECONDS. Si le rafraîchissement
     * échoue, la version précédente est rendue avec `stale` à vrai.
     *
     * @return array{content: string, fetched_at: string, stale: bool}|null
     * @throws Exception
     */
    private function get_document(string $format, string $source_id): ?array
    {
        $cache_key = self::REGISTRY_KEY . '.' . $format;
        $rows = $this->sql_manager->execute(
            "SELECT content, source_id, DATE_FORMAT(fetched_at, '%d/%m/%Y à %H:%i') AS fetched_at,
                    TIMESTAMPDIFF(SECOND, fetched_at, NOW()) AS age,
                    TIMESTAMPDIFF(SECOND, checked_at, NOW()) AS since_check
             FROM document_cache WHERE cache_key = ?",
            array(array('type' => 's', 'value' => $cache_key)));
        $row = $rows[0] ?? null;
        // Un autre document configuré : l'ancien cache ne vaut plus rien.
        if ($row !== null && $row['source_id'] !== $source_id) {
            $row = null;
        }
        $has_content = $row !== null && $row['content'] !== null;
        if ($has_content && (int)$row['age'] < self::TTL_SECONDS) {
            return array('content' => $row['content'], 'fetched_at' => $row['fetched_at'], 'stale' => false);
        }
        if ($row === null || (int)$row['since_check'] >= self::RETRY_SECONDS) {
            $content = ($this->fetcher)(self::export_url($source_id, $format), $format);
            if ($content !== null) {
                $this->store($cache_key, $source_id, $format, $content);
                return $this->get_document($format, $source_id);
            }
            $this->mark_failed_attempt($cache_key, $source_id, $format, !$has_content);
        }
        if ($has_content) {
            return array('content' => $row['content'], 'fetched_at' => $row['fetched_at'], 'stale' => true);
        }
        return null;
    }

    /**
     * Note la tentative ratée, pour ne pas redemander à Google à chaque visite.
     * @param bool $clear aucune version précédente valable (ou autre document)
     * @throws Exception
     */
    private function mark_failed_attempt(string $cache_key, string $source_id, string $format, bool $clear): void
    {
        $this->sql_manager->execute(
            "INSERT INTO document_cache (cache_key, source_id, content, content_type, fetched_at, checked_at)
             VALUES (?, ?, NULL, ?, NULL, NOW())
             ON DUPLICATE KEY UPDATE checked_at = NOW(), source_id = VALUES(source_id)"
            . ($clear ? ", content = NULL, fetched_at = NULL" : ""),
            array(
                array('type' => 's', 'value' => $cache_key),
                array('type' => 's', 'value' => $source_id),
                array('type' => 's', 'value' => self::CONTENT_TYPES[$format]),
            ));
    }

    /**
     * @throws Exception
     */
    private function store(string $cache_key, string $source_id, string $format, string $content): void
    {
        $this->sql_manager->execute(
            "INSERT INTO document_cache (cache_key, source_id, content, content_type, fetched_at, checked_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE source_id = VALUES(source_id), content = VALUES(content),
                                     content_type = VALUES(content_type), fetched_at = NOW(), checked_at = NOW()",
            array(
                array('type' => 's', 'value' => $cache_key),
                array('type' => 's', 'value' => $source_id),
                array('type' => 's', 'value' => $content),
                array('type' => 's', 'value' => self::CONTENT_TYPES[$format]),
            ));
    }

    /**
     * Récupère un export Google Docs. Refuse toute réponse qui n'est pas le
     * document attendu : page de connexion (document devenu privé), mauvais
     * type, fichier vide ou démesuré.
     */
    public static function http_get(string $url, string $format): ?string
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ));
        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $type = (string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $host = (string)parse_url((string)curl_getinfo($curl, CURLINFO_EFFECTIVE_URL), PHP_URL_HOST);
        curl_close($curl);
        if (!is_string($body) || $status !== 200 || $body === '' || strlen($body) > self::MAX_BYTES) {
            error_log("RulesDocument : export $format indisponible (HTTP $status)");
            return null;
        }
        $google = $host === 'docs.google.com' || str_ends_with($host, '.googleusercontent.com');
        if (!$google || !str_starts_with($type, self::CONTENT_TYPES[$format])) {
            error_log("RulesDocument : réponse inattendue pour l'export $format ($host, $type)");
            return null;
        }
        if ($format === 'pdf' && !str_starts_with($body, '%PDF')) {
            return null;
        }
        return $body;
    }

    /**
     * Reconstruit l'export HTML de Google Docs à partir d'une liste blanche :
     * rien de ce qui vient du document n'est recopié tel quel. Le texte est
     * échappé, les attributs sont tous abandonnés sauf `href` (http, https,
     * mailto) et les fusions de cellules. Gras, italique et souligné, que
     * Google exprime par des classes CSS, redeviennent des balises.
     *
     * Les paragraphes « Article N : Titre » ouvrent un article.
     *
     * @return array{intro_html: string, articles: array<int, array{number: int,
     *               title: string, anchor: string, html: string}>}
     */
    public static function sanitize(string $raw): array
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $raw, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $styles = '';
        foreach ($dom->getElementsByTagName('style') as $style) {
            $styles .= $style->textContent;
        }
        $formats = self::class_formats($styles);

        $body = $dom->getElementsByTagName('body')->item(0);
        $intro = '';
        $articles = array();
        $current = null;
        $title_skipped = false;
        if ($body !== null) {
            foreach ($body->childNodes as $node) {
                $text = self::normalize_text($node->textContent);
                if ($node instanceof DOMElement && in_array(strtolower($node->tagName),
                        array('p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'), true)) {
                    if ($text === '') {
                        continue;
                    }
                    if (!$title_skipped && $current === null && $intro === ''
                        && preg_match('/^r[eè]glement g[eé]n[eé]ral$/iu', $text)) {
                        // Le titre du document : la page a déjà le sien.
                        $title_skipped = true;
                        continue;
                    }
                    if (preg_match('/^article\s+(\d+)\s*[:.\-–]\s*(.+)$/iu', $text, $m)) {
                        if ($current !== null) {
                            $articles[] = $current;
                        }
                        $number = (int)$m[1];
                        $current = array('number' => $number, 'title' => trim($m[2]),
                            'anchor' => "article-$number", 'html' => '');
                        continue;
                    }
                }
                $html = self::render($node, $formats);
                if ($current === null) {
                    $intro .= $html;
                } else {
                    $current['html'] .= $html;
                }
            }
        }
        if ($current !== null) {
            $articles[] = $current;
        }
        return array('intro_html' => $intro, 'articles' => $articles);
    }

    /**
     * @return array<string, string[]> classe CSS => balises de mise en forme
     */
    private static function class_formats(string $styles): array
    {
        $formats = array();
        preg_match_all('/\.([A-Za-z0-9_-]+)\{([^}]*)\}/', $styles, $rules, PREG_SET_ORDER);
        foreach ($rules as [, $class, $declarations]) {
            $declarations = strtolower(str_replace(' ', '', $declarations));
            $tags = array();
            if (preg_match('/font-weight:(700|800|900|bold)/', $declarations)) {
                $tags[] = 'strong';
            }
            if (str_contains($declarations, 'font-style:italic')) {
                $tags[] = 'em';
            }
            if (str_contains($declarations, 'text-decoration:underline')) {
                $tags[] = 'u';
            }
            if ($tags) {
                $formats[$class] = $tags;
            }
        }
        return $formats;
    }

    private static function render(DOMNode $node, array $formats): string
    {
        if ($node instanceof DOMText) {
            // Zone d'usage privé : symboles Word sans équivalent (U+E113…),
            // qui s'affichent en carré vide.
            $text = preg_replace('/\p{Co}/u', '', $node->textContent);
            return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (!$node instanceof DOMElement) {
            return ''; // commentaires, instructions, CDATA
        }
        $tag = strtolower($node->tagName);
        if (in_array($tag, self::DROPPED_TAGS, true)) {
            return '';
        }
        if ($tag === 'br') {
            return '<br>';
        }
        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= self::render($child, $formats);
        }
        switch ($tag) {
            case 'p':
                return self::is_blank($inner) ? '' : "<p>$inner</p>";
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                return self::is_blank($inner) ? '' : "<h4>$inner</h4>";
            case 'table':
                return "<table>$inner</table>";
            case 'td':
            case 'th':
                return "<$tag" . self::span_attributes($node) . ">$inner</$tag>";
            case 'a':
                $href = self::safe_href($node->getAttribute('href'));
                return $href === null ? $inner
                    : '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                    . '" target="_blank" rel="noopener noreferrer">' . $inner . '</a>';
            case 'span':
                foreach (preg_split('/\s+/', $node->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY) as $class) {
                    foreach ($formats[$class] ?? array() as $format) {
                        $inner = "<$format>$inner</$format>";
                    }
                }
                return $inner;
            default:
                return in_array($tag, self::KEPT_TAGS, true) ? "<$tag>$inner</$tag>" : $inner;
        }
    }

    private static function span_attributes(DOMElement $cell): string
    {
        $attributes = '';
        foreach (array('colspan', 'rowspan') as $name) {
            $value = (int)$cell->getAttribute($name);
            if ($value > 1 && $value <= 50) {
                $attributes .= " $name=\"$value\"";
            }
        }
        return $attributes;
    }

    /**
     * Lien sortant autorisé : http, https ou mailto. Google enveloppe les liens
     * externes dans une redirection `google.com/url?q=` : on la retire. Les
     * signets internes du document (`#h.…`) ne pointent plus sur rien ici.
     */
    public static function safe_href(string $href): ?string
    {
        $href = trim($href);
        $parts = parse_url($href);
        if ($parts === false) {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        if (in_array($host, array('www.google.com', 'google.com'), true) && ($parts['path'] ?? '') === '/url') {
            parse_str($parts['query'] ?? '', $query);
            return is_string($query['q'] ?? null) ? self::safe_href($query['q']) : null;
        }
        $scheme = strtolower($parts['scheme'] ?? '');
        if (in_array($scheme, array('http', 'https'), true) && $host !== '') {
            return $href;
        }
        if ($scheme === 'mailto') {
            return $href;
        }
        return null;
    }

    private static function normalize_text(string $text): string
    {
        return trim(preg_replace(array('/\p{Co}/u', '/[\s\x{00A0}]+/u'), array('', ' '), $text));
    }

    private static function is_blank(string $html): bool
    {
        return self::normalize_text(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '';
    }
}

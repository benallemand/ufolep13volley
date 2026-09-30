<?php
require_once __DIR__ . '/Generic.php';

/**
 * Règlements lus dans le dossier Google Drive de la commission (issue #342).
 *
 * Le registre (`rules.folder`, écran « Base de registres » de l'admin) désigne
 * le dossier racine, par son URL ou son seul identifiant. Il contient un
 * sous-dossier par saison, nommé exactement « AAAA-AAAA » ; chaque sous-dossier
 * contient les règlements de la saison (documents Google ou .docx).
 *
 * Chaque règlement est pris dans la saison la plus récente qui le contient :
 * une nouvelle saison ne porte que les règlements qui changent, les autres
 * restent ceux de la saison précédente. Un règlement est reconnu par son nom de
 * fichier (`KINDS`) ; un document inconnu s'affiche quand même, sous son nom.
 *
 * Le serveur récupère la liste des dossiers, les exports HTML et les exports
 * PDF, et les garde en cache dans `document_cache`. Il ne rend au navigateur
 * que du HTML reconstruit à partir d'une liste blanche, et aucune adresse
 * Google : le lien « document original » est le PDF qu'il sert lui-même. Le
 * partage peut donc rester en lecture seule, sans que le lien d'édition
 * apparaisse nulle part sur le site.
 *
 * Si Google ne répond pas, on sert la dernière version en cache, marquée comme
 * telle ; sans cache du tout, la page affiche un message.
 */
class RulesDocument extends Generic
{
    public const REGISTRY_KEY = 'rules.folder';
    /** Durée pendant laquelle une récupération est servie sans redemander. */
    public const TTL_SECONDS = 900;
    /** Délai minimal entre deux tentatives quand Google ne répond pas. */
    public const RETRY_SECONDS = 300;
    private const MAX_BYTES = 10 * 1024 * 1024;
    private const CONTENT_TYPES = array('folder' => 'text/html', 'html' => 'text/html', 'pdf' => 'application/pdf');
    private const SEASON_PATTERN = '/^(\d{4})-(\d{4})$/';
    private const DOCUMENT_EXTENSIONS = '/\.(docx?|odt|rtf)$/i';

    /**
     * Règlements connus, dans l'ordre d'affichage. `match` : mots qui doivent
     * tous figurer dans le nom du fichier (majuscules, sans accents).
     */
    public const KINDS = array(
        'general' => array('match' => array('GENERAL'), 'label' => 'Règlement général', 'icon' => 'fa-scale-balanced',
            'description' => 'Cadre commun UFOLEP 13 (organisation, arbitrage, licences, etc.).'),
        'feminine' => array('match' => array('CHAMPIONNAT', 'FEMININ'), 'label' => 'Championnat féminin 4×4',
            'icon' => 'fa-venus',
            'description' => 'Inscription, organisation et règles spécifiques des divisions féminines.'),
        'masculine' => array('match' => array('CHAMPIONNAT', 'MASCULIN'), 'label' => 'Championnat masculin 6×6',
            'icon' => 'fa-mars', 'description' => 'Organisation des divisions masculines et règles associées.'),
        'mixte' => array('match' => array('CHAMPIONNAT', 'MIXTE'), 'label' => 'Championnat mixte 4×4',
            'icon' => 'fa-people-arrows',
            'description' => 'Format mixte obligatoire avec règles de rotation et organisation dédiées.'),
        'coupe-feminine' => array('match' => array('COUPE', 'FEMININ'), 'label' => 'Coupe féminine 6×6',
            'icon' => 'fa-trophy', 'description' => 'Format coupe pour équipes féminines (poules et phases finales).'),
        'koury-hanna' => array('match' => array('HANNA'), 'label' => 'Coupe mixte Koury Hanna', 'icon' => 'fa-heart',
            'description' => 'Coupe 4×4 mixte avec règles spécifiques de composition et rotation.'),
        'isoardi' => array('match' => array('ISOARDI'), 'label' => 'Coupe masculine Isoardi', 'icon' => 'fa-medal',
            'description' => 'Coupe 6×6 avec handicaps selon les divisions et phases de poules/élimination.'),
    );

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
     * Les règlements en ligne, dans l'ordre d'affichage.
     *
     * @return array<int, array{slug: string, label: string, description: string, icon: string, season: string}>
     * @throws Exception
     */
    public function getRulesList(): array
    {
        $list = array();
        foreach ($this->catalog() as $slug => $entry) {
            $list[] = array(
                'slug' => $slug,
                'label' => $entry['label'],
                'description' => $entry['description'],
                'icon' => $entry['icon'],
                'season' => $entry['season'],
                'pdf_url' => self::pdf_url($slug),
            );
        }
        return $list;
    }

    /**
     * Un règlement, prêt à afficher.
     *
     * @return array{status: string, slug: string, label: ?string, season: ?string, fetched_at: ?string,
     *               intro_html: string, articles: array, pdf_url: ?string}
     *   status : ok | stale (Google ne répond pas, dernière version connue)
     *          | unavailable (aucune version connue) | not_found (pas de tel
     *          règlement dans le dossier) | not_configured
     * @throws Exception
     */
    public function getRules(string $slug = 'general'): array
    {
        $result = array('status' => 'not_configured', 'slug' => $slug, 'label' => null, 'season' => null,
            'fetched_at' => null, 'intro_html' => '', 'articles' => array(), 'pdf_url' => null);
        if ($this->get_folder_id() === null) {
            return $result;
        }
        $entry = $this->catalog()[$slug] ?? null;
        if ($entry === null) {
            $result['status'] = 'not_found';
            return $result;
        }
        $result['label'] = $entry['label'];
        $result['season'] = $entry['season'];
        $document = $this->get_document('html', $entry['id']);
        if ($document === null) {
            $result['status'] = 'unavailable';
            return $result;
        }
        $sanitized = self::sanitize($document['content']);
        $result['intro_html'] = $sanitized['intro_html'];
        $result['articles'] = $sanitized['articles'];
        $result['status'] = $document['stale'] ? 'stale' : 'ok';
        $result['fetched_at'] = $document['fetched_at'];
        $result['pdf_url'] = self::pdf_url($slug);
        return $result;
    }

    /**
     * Le document original d'un règlement, en PDF. Termine la requête.
     * @throws Exception
     */
    public function getRulesPdf(string $slug = 'general'): void
    {
        $pdf = $this->get_pdf_content($slug);
        $filename = 'reglement-' . preg_replace('/[^a-z0-9-]/', '', $slug) . '-ufolep13.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit(0);
    }

    /**
     * @throws Exception 404 quand aucun PDF n'est disponible
     */
    public function get_pdf_content(string $slug): string
    {
        $entry = $this->get_folder_id() === null ? null : ($this->catalog()[$slug] ?? null);
        $document = $entry === null ? null : $this->get_document('pdf', $entry['id']);
        if ($document === null) {
            throw new Exception("Le document original est momentanément indisponible.", 404);
        }
        return $document['content'];
    }

    private static function pdf_url(string $slug): string
    {
        return '/rest/action.php/rules/getRulesPdf?slug=' . rawurlencode($slug);
    }

    /**
     * Dossier racine des règlements, lu dans le registre.
     * @throws Exception
     */
    public function get_folder_id(): ?string
    {
        $rows = $this->sql_manager->execute(
            "SELECT registry_value FROM registry WHERE registry_key = ? ORDER BY id DESC LIMIT 1",
            array(array('type' => 's', 'value' => self::REGISTRY_KEY)));
        return self::parse_google_id($rows[0]['registry_value'] ?? null);
    }

    /**
     * Identifiant Google d'un dossier ou d'un document : URL complète
     * (partage, édition, consultation…) ou identifiant seul.
     */
    public static function parse_google_id(?string $value): ?string
    {
        $value = trim((string)$value);
        if (preg_match('#/(?:folders|document/d|file/d)/([A-Za-z0-9_-]{20,})#', $value, $m)) {
            return $m[1];
        }
        if (preg_match('#[?&]id=([A-Za-z0-9_-]{20,})#', $value, $m)) {
            return $m[1];
        }
        if (preg_match('#^[A-Za-z0-9_-]{20,}$#', $value)) {
            return $value;
        }
        return null;
    }

    public static function source_url(string $id, string $format): string
    {
        if ($format === 'folder') {
            return "https://drive.google.com/embeddedfolderview?id=$id";
        }
        return "https://docs.google.com/document/d/$id/export?format=$format";
    }

    /**
     * Règlements en ligne : pour chacun, le document de la saison la plus
     * récente qui le contient.
     *
     * @return array<string, array{id: string, season: string, label: string, description: string, icon: string}>
     * @throws Exception
     */
    private function catalog(): array
    {
        $root_id = $this->get_folder_id();
        $root = $root_id === null ? null : $this->get_document('folder', $root_id);
        if ($root === null) {
            return array();
        }
        $seasons = array();
        foreach (self::parse_folder($root['content']) as $entry) {
            if ($entry['type'] === 'folder' && preg_match(self::SEASON_PATTERN, $entry['name'], $m)) {
                $seasons[$entry['name']] = $entry['id'];
            }
        }
        krsort($seasons, SORT_STRING);
        $found = array();
        foreach ($seasons as $season => $season_id) {
            $listing = $this->get_document('folder', $season_id);
            if ($listing === null) {
                continue;
            }
            foreach (self::parse_folder($listing['content']) as $entry) {
                if ($entry['type'] !== 'document') {
                    continue;
                }
                [$slug, $meta] = self::identify($entry['name']);
                if (!isset($found[$slug])) {
                    $found[$slug] = array_merge($meta, array('id' => $entry['id'], 'season' => $season));
                }
            }
        }
        // Règlements connus dans l'ordre de KINDS, puis les autres par nom.
        $order = array_flip(array_keys(self::KINDS));
        uksort($found, static function ($a, $b) use ($order, $found) {
            return array($order[$a] ?? PHP_INT_MAX, $found[$a]['label'])
                <=> array($order[$b] ?? PHP_INT_MAX, $found[$b]['label']);
        });
        return $found;
    }

    /**
     * Règlement désigné par un nom de fichier.
     * @return array{0: string, 1: array{label: string, description: string, icon: string}}
     */
    public static function identify(string $filename): array
    {
        $name = preg_replace(self::DOCUMENT_EXTENSIONS, '', trim($filename));
        // Suffixe de saison : « _2026_2027 », « 2026-2027 »…
        $name = trim(preg_replace('/[\s_-]*\d{4}[\s_-]+\d{4}\s*$/', '', $name));
        $key = strtoupper(self::without_accents($name));
        foreach (self::KINDS as $slug => $kind) {
            $all = true;
            foreach ($kind['match'] as $word) {
                $all = $all && str_contains($key, $word);
            }
            if ($all) {
                return array($slug, array('label' => $kind['label'], 'description' => $kind['description'],
                    'icon' => $kind['icon']));
            }
        }
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(self::without_accents($name))), '-');
        return array($slug === '' ? 'reglement' : $slug,
            array('label' => $name, 'description' => '', 'icon' => 'fa-file-lines'));
    }

    private static function without_accents(string $text): string
    {
        return strtr($text, array(
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'î' => 'i',
            'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
            'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Î' => 'I',
            'Ï' => 'I', 'Ô' => 'O', 'Ö' => 'O', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ç' => 'C',
        ));
    }

    /**
     * Entrées d'une liste de dossier Drive (vue `embeddedfolderview`). Seuls
     * les sous-dossiers et les documents de traitement de texte sont retenus.
     *
     * @return array<int, array{id: string, name: string, type: string}>
     */
    public static function parse_folder(string $raw): array
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $raw, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $entries = array();
        foreach ((new DOMXPath($dom))->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' flip-entry ')]") as $div) {
            $link = $div->getElementsByTagName('a')->item(0);
            $href = $link instanceof DOMElement ? $link->getAttribute('href') : '';
            $id = self::parse_google_id($href);
            $name = '';
            foreach ($div->getElementsByTagName('div') as $child) {
                if (str_contains(' ' . $child->getAttribute('class') . ' ', ' flip-entry-title ')) {
                    $name = self::normalize_text($child->textContent);
                }
            }
            if ($id === null || $name === '') {
                continue;
            }
            if (str_contains($href, '/drive/folders/')) {
                $type = 'folder';
            } elseif (str_contains($href, '/document/d/') || preg_match(self::DOCUMENT_EXTENSIONS, $name)) {
                $type = 'document';
            } else {
                continue; // PDF, image… : pas d'export HTML possible
            }
            $entries[] = array('id' => $id, 'name' => $name, 'type' => $type);
        }
        return $entries;
    }

    /**
     * Contenu en cache, rafraîchi au-delà de TTL_SECONDS. Si le rafraîchissement
     * échoue, la version précédente est rendue avec `stale` à vrai.
     *
     * @return array{content: string, fetched_at: string, stale: bool}|null
     * @throws Exception
     */
    private function get_document(string $format, string $id): ?array
    {
        $cache_key = "rules.$format.$id";
        $rows = $this->sql_manager->execute(
            "SELECT content, DATE_FORMAT(fetched_at, '%d/%m/%Y à %H:%i') AS fetched_at,
                    TIMESTAMPDIFF(SECOND, fetched_at, NOW()) AS age,
                    TIMESTAMPDIFF(SECOND, checked_at, NOW()) AS since_check
             FROM document_cache WHERE cache_key = ?",
            array(array('type' => 's', 'value' => $cache_key)));
        $row = $rows[0] ?? null;
        $has_content = $row !== null && $row['content'] !== null;
        if ($has_content && (int)$row['age'] < self::TTL_SECONDS) {
            return array('content' => $row['content'], 'fetched_at' => $row['fetched_at'], 'stale' => false);
        }
        if ($row === null || (int)$row['since_check'] >= self::RETRY_SECONDS) {
            $content = ($this->fetcher)(self::source_url($id, $format), $format);
            if ($content !== null) {
                $this->store($cache_key, $id, $format, $content);
                return $this->get_document($format, $id);
            }
            $this->mark_failed_attempt($cache_key, $id, $format);
        }
        if ($has_content) {
            return array('content' => $row['content'], 'fetched_at' => $row['fetched_at'], 'stale' => true);
        }
        return null;
    }

    /**
     * Note la tentative ratée, pour ne pas redemander à Google à chaque visite.
     * @throws Exception
     */
    private function mark_failed_attempt(string $cache_key, string $id, string $format): void
    {
        $this->sql_manager->execute(
            "INSERT INTO document_cache (cache_key, source_id, content, content_type, fetched_at, checked_at)
             VALUES (?, ?, NULL, ?, NULL, NOW())
             ON DUPLICATE KEY UPDATE checked_at = NOW()",
            array(
                array('type' => 's', 'value' => $cache_key),
                array('type' => 's', 'value' => $id),
                array('type' => 's', 'value' => self::CONTENT_TYPES[$format]),
            ));
    }

    /**
     * @throws Exception
     */
    private function store(string $cache_key, string $id, string $format, string $content): void
    {
        $this->sql_manager->execute(
            "INSERT INTO document_cache (cache_key, source_id, content, content_type, fetched_at, checked_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE content = VALUES(content), content_type = VALUES(content_type),
                                     fetched_at = NOW(), checked_at = NOW()",
            array(
                array('type' => 's', 'value' => $cache_key),
                array('type' => 's', 'value' => $id),
                array('type' => 's', 'value' => $content),
                array('type' => 's', 'value' => self::CONTENT_TYPES[$format]),
            ));
    }

    /**
     * Récupère une liste de dossier ou un export Google Docs. Refuse toute
     * réponse qui n'est pas celle attendue : page de connexion (document
     * devenu privé), mauvais type, fichier vide ou démesuré.
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
            error_log("RulesDocument : $format indisponible (HTTP $status)");
            return null;
        }
        $google = in_array($host, array('docs.google.com', 'drive.google.com'), true)
            || str_ends_with($host, '.googleusercontent.com');
        if (!$google || !str_starts_with($type, self::CONTENT_TYPES[$format])) {
            error_log("RulesDocument : réponse inattendue pour $format ($host, $type)");
            return null;
        }
        if ($format === 'pdf' && !str_starts_with($body, '%PDF')) {
            return null;
        }
        if ($format === 'folder' && !str_contains($body, 'flip-entries')) {
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
     * Le premier paragraphe, avant tout article, est le titre du document : la
     * page a le sien, il est retiré. Les paragraphes « Article N : Titre »
     * ouvrent un article.
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
        $title_seen = false;
        if ($body !== null) {
            foreach ($body->childNodes as $node) {
                $text = self::normalize_text($node->textContent);
                if ($node instanceof DOMElement && in_array(strtolower($node->tagName),
                        array('p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'), true)) {
                    if ($text === '') {
                        continue;
                    }
                    if (preg_match('/^article\s+(\d+)\s*[:.\-–]\s*(.+)$/iu', $text, $m)) {
                        if ($current !== null) {
                            $articles[] = $current;
                        }
                        $number = (int)$m[1];
                        $current = array('number' => $number, 'title' => trim($m[2]),
                            'anchor' => "article-$number", 'html' => '');
                        $title_seen = true;
                        continue;
                    }
                    if (!$title_seen) {
                        $title_seen = true;
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

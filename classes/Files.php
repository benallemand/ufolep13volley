<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Smalot\PdfParser\Parser;

class Files extends Generic
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'files';
    }

    /**
     * @throws Exception
     */
    public function cleanup_files(): void
    {
        // Detect files without hash written in database
        $results_select = $this->get("hash IS NULL");
        foreach ($results_select as $result_select) {
            $file_path = __DIR__ . "/../" . $result_select['path_file'];
            if (file_exists($file_path)) {
                // compute md5 if file exists and save in database
                $hash = md5_file($file_path);
                $this->save(array(
                    'id' => $result_select['id'],
                    'hash' => $hash));
            } else {
                // if file does not exist, delete from database
                $this->delete($result_select['id']);
            }
        }
        // clean duplicate files in db
        $this->delete_duplicates();
        // list db files
        $results_select = $this->get();
        $db_file_paths = array_column($results_select, 'path_file');
        // list files under directory match_files
        $existing_files = scandir(__DIR__ . "/../match_files");
        foreach ($existing_files as $current_existing_file) {
            if (in_array($current_existing_file, array('.', '..'))) {
                continue;
            }
            if (in_array("match_files/$current_existing_file", $db_file_paths)) {
                continue;
            }
            // if file is not found in database, delete it
            unlink(__DIR__ . "/../match_files/$current_existing_file");
        }

    }

    /**
     * @throws Exception
     */
    private function delete_duplicates(): void
    {
        $sql = "DELETE f1
                FROM files f1,
                    files f2
                WHERE f1.id > f2.id
                 AND f1.hash = f2.hash
                 AND f1.hash IS NOT NULL
                 AND f2.hash IS NOT NULL";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    private function check_action_allowed(string $function_name, $file_path): void
    {
        if (!UserManager::is_connected()) {
            throw new Exception("Connectez-vous pour télécharger ce(s) fichier(s) !", 401);
        }
        switch ($function_name) {
            case 'download_match_file':
                $code_match = $this->get_code_match_from_file_path($file_path);
                $match_manager = new MatchMgr();
                $match = $match_manager->get_match_by_code_match($code_match);
                // allow admin
                if (UserManager::isAdmin()) {
                    return;
                }
                if (!UserManager::isTeamLeader()) {
                    throw new Exception("Seuls les responsables d'équipes peuvent télécharger ce fichier !", 401);
                }
                // allow only playing teams
                @session_start();
                if (!in_array($_SESSION['id_equipe'], array($match['id_equipe_dom'], $match['id_equipe_ext']))) {
                    throw new Exception("Seules les équipes ayant participé au match peuvent dire qui était là !", 401);
                }
                break;
            default:
                break;
        }
    }

    /**
     * @param $file_path
     * @throws Exception
     */
    function download_match_file($file_path): void
    {
        $this->check_action_allowed(__FUNCTION__, $file_path);
        $dir = __DIR__ . '/../match_files';
        $name = basename($file_path);
        $file_path = "$dir/$name";
        if (!file_exists($file_path)) {
            throw new Exception("Fichier $name introuvable !");
        }
        header("Content-type: " . mime_content_type($file_path));
        header("Content-Disposition: filename=$name");
        header("Content-length: " . filesize($file_path));
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile($file_path);
        die();
    }

    private function get_code_match_from_file_path($file_path): string
    {
        $file_name = pathinfo($file_path, PATHINFO_FILENAME);
        return substr($file_name, 0, strpos($file_name, "file"));
    }

    /**
     * @throws Exception
     */
    public function get_licences_data_from_pdf($input_pdf_path): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($input_pdf_path);
        $extractedText = $pdf->getText();
        $raw_licences = explode("Plus d'informations sur www.ufolep.org", $extractedText);
        $results = array();
        foreach ($raw_licences as $raw_licence) {
            if (strlen($raw_licence) == 0) {
                continue;
            }
            $raw_data = explode("\n", trim($raw_licence));
            $result = array();
            foreach ($raw_data as $index => $item) {
                $item = trim($item);
                if (self::starts_with($item, "LICENCE N°")) {
                    if (!preg_match('/LICENCE N°0(\d{2})_(\d+)/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['departement'] = $matches[1];
                    $result['licence_number'] = $matches[2];
                    $result['last_first_name'] = $raw_data[$index + 2];
                } elseif (self::starts_with($item, "Né(e) le")) {
                    if (!preg_match('/Né\(e\) le (\d{2}\/\d{2}\/\d{4}).*/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['date_of_birth'] = $matches[1];
                } elseif (str_contains($item, "- Sexe :")) {
                    if (!preg_match('/(\d+) ans - Sexe : (\w)/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['age'] = $matches[1];
                    $result['sexe'] = $matches[2] == 'H' ? 'M' : 'F';
                } elseif (self::starts_with($item, "Asso")) {
                    if (!preg_match('/Asso (.*)/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['club'] = $matches[1];
                } elseif (self::starts_with($item, "N°") && strlen($item) == 12) {
                    if (!preg_match('/N°(\d+)/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['licence_club'] = $matches[1];
                } elseif (self::starts_with($item, "N°") && strlen($item) == 15) {
                    if (!preg_match('/N°(.+)/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['licence_number_2'] = $matches[1];
                } elseif (self::starts_with($item, "Homologuée :")) {
                    if (!preg_match('/Homologuée : (\d{2}\/\d{2}\/\d{4})/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['homologation_date'] = $matches[1];
                }
            }
            if (count($result) != 10) {
                print_r($result);
                continue;
            }
            $results[] = $result;
        }
        return $results;
    }

    /**
     * Convertit le contenu PDF en chaîne de caractères
     */
    private function convertContentToString($content): string
    {
        if (is_string($content)) {
            return $content;
        }
        
        if (is_array($content)) {
            $result = [];
            foreach ($content as $item) {
                if (is_string($item)) {
                    $result[] = $item;
                } elseif (is_object($item) && method_exists($item, 'getContent')) {
                    $result[] = $this->convertContentToString($item->getContent());
                }
            }
            return implode(' ', $result);
        }
        
        if (is_object($content) && method_exists($content, 'getContent')) {
            return $this->convertContentToString($content->getContent());
        }
        
        return '';
    }

    public function get_licences_data(string $input_pdf_path): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($input_pdf_path);
        $pages = $pdf->getPages();
        $results = array();
        
        // ÉTAPE 1: Identifier l'image du canevas (présente sur toutes les pages)
        $imageOccurrences = [];
        
        foreach ($pages as $page) {
            $pageContent = $page->get('Contents');
            if ($pageContent) {
                $content = $pageContent->getContent();
                // Convertir le contenu en chaîne
                $content = $this->convertContentToString($content);
                preg_match_all('/\/([A-Za-z]\d+)\s+Do/', $content, $matches);
                $xobjectRefs = array_unique($matches[1]);
                
                foreach ($xobjectRefs as $refName) {
                    if (!isset($imageOccurrences[$refName])) {
                        $imageOccurrences[$refName] = 0;
                    }
                    $imageOccurrences[$refName]++;
                }
            }
        }
        
        // Le canevas est l'image qui apparaît sur toutes les pages
        $totalPages = count($pages);
        $canvasImage = null;
        foreach ($imageOccurrences as $refName => $count) {
            if ($count == $totalPages) {
                $canvasImage = $refName;
                break;
            }
        }
        
        // ÉTAPE 2: Extraire les images de chaque page en excluant le canevas
        foreach ($pages as $pageNum => $page) {
            $data_tm = $page->getDataTm();
            
            // Parser le contenu de la page pour extraire les positions des images
            $pageContent = $page->get('Contents');
            $imagePositions = [];
            
            if ($pageContent) {
                $content = $pageContent->getContent();
                // Convertir le contenu en chaîne
                $content = $this->convertContentToString($content);
                
                // Extraire les positions X et Y des images avec une regex plus précise
                // Format typique: "q 1 0 0 1 x y cm /ImageName Do Q"
                preg_match_all('/(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s+cm\s*\/([A-Za-z]\d+)\s+Do/s', $content, $matches, PREG_SET_ORDER);
                
                foreach ($matches as $match) {
                    $imageName = $match[3];
                    $xPosition = floatval($match[1]);
                    $yPosition = floatval($match[2]);
                    
                    // Exclure le canevas
                    if ($imageName !== $canvasImage) {
                        $imagePositions[$imageName] = [
                            'x' => $xPosition,
                            'y' => $yPosition
                        ];
                    }
                }
            }
            
            // Récupérer les XObjects de la page via Resources
            $pageResources = $page->get('Resources');
            $pageXObjects = [];
            
            if ($pageResources && $pageResources->has('XObject')) {
                $xobjectDict = $pageResources->get('XObject');
                
                foreach ($imagePositions as $refName => $positions) {
                    if ($xobjectDict->has($refName)) {
                        $xobj = $xobjectDict->get($refName);
                        $details = $xobj->getDetails();
                        
                        if (isset($details['Subtype']) && $details['Subtype'] == 'Image') {
                            $pageXObjects[$refName] = [
                                'object' => $xobj,
                                'x_position' => $positions['x'],
                                'y_position' => $positions['y']
                            ];
                        }
                    }
                }
            }
            
            // Trier les images par position Y (du haut vers le bas)
            uasort($pageXObjects, function($a, $b) {
                return $b['y_position'] <=> $a['y_position'];
            });
            
            
            // Parser les licences de la page avec leurs positions X et Y
            $licences = [];
            $tm_index = 0;
            $result = [];
            $licenceXPosition = null;
            $licenceYPosition = null;
            
            while ($tm_index < count($data_tm)) {
                $item = trim($data_tm[$tm_index][1]);
                
                if (self::starts_with($item, "N°")) {
                    // Nouvelle licence détectée - sauvegarder la précédente si elle existe
                    if (count($result) == 10) {
                        $licences[] = [
                            'data' => $result,
                            'x_position' => $licenceXPosition,
                            'y_position' => $licenceYPosition
                        ];
                    }
                    
                    // Commencer une nouvelle licence
                    $result = [];
                    $licenceXPosition = $data_tm[$tm_index][0][4] ?? null; // Position X du texte "N°"
                    $licenceYPosition = $data_tm[$tm_index][0][5] ?? null; // Position Y du texte "N°"
                    
                    if (!preg_match('/N°0(\d{2})_(\d+)/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['departement'] = $matches[1];
                    $result['licence_number'] = $matches[2];
                    if (!preg_match('/^([\'a-zA-ZÀ-ÖØ-öø-ÿ\s-]+?)(?=\s*Volley ball|$)/m', $data_tm[$tm_index+1][1], $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: " . $data_tm[$tm_index+1][1] . " !");
                    }
                    $result['last_first_name'] = $matches[1];
                } elseif (self::starts_with($item, "Né(e) le")) {
                    if (!preg_match('/Né\(e\) le (\d{2}\/\d{2}\/\d{4}) - (\d+) ans - ([A-Za-z]+).*/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['date_of_birth'] = $matches[1];
                    $result['age'] = $matches[2];
                    $result['sexe'] = $matches[3] == 'Femme' ? 'F' : 'M';
                } elseif (self::starts_with($item, "Asso")) {
                    if (!preg_match('/Asso (\d+) - (.+)/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['licence_club'] = $matches[1];
                    $result['club'] = $matches[2];
                } elseif (self::starts_with($item, "votre identifiant")) {
                    if (!preg_match('/votre identifiant (0\d{2}_\d+).*/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['licence_number_2'] = $matches[1];
                } elseif (self::starts_with($item, "Délivrée le : ")) {
                    if (!preg_match('/Délivrée le : (\d{2}\/\d{2}\/\d{4})/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['homologation_date'] = $matches[1];
                }
                $tm_index++;
            }
            
            // Ajouter la dernière licence
            if (count($result) == 10) {
                $licences[] = [
                    'data' => $result,
                    'x_position' => $licenceXPosition,
                    'y_position' => $licenceYPosition
                ];
            }
            
            
            // Associer chaque image à la licence la plus proche (1 image = 1 licence max)
            $usedImages = [];
            $licenceImageMap = [];
            
            // Pour chaque image, trouver la licence la plus proche (distance euclidienne)
            // Filtrer pour ne garder que les photos à gauche (position X < 300)
            foreach ($pageXObjects as $imageName => $imageData) {
                $imageX = $imageData['x_position'];
                $imageY = $imageData['y_position'];
                
                // Ignorer les images à droite (logos de club, généralement X > 300)
                // Les photos de licenciés sont typiquement à gauche (X < 300)
                if ($imageX > 300) {
                    continue;
                }
                
                $closestLicence = null;
                $minDistance = PHP_FLOAT_MAX;
                
                foreach ($licences as $idx => $licence) {
                    $licenceX = $licence['x_position'];
                    $licenceY = $licence['y_position'];
                    
                    // Calculer la distance euclidienne entre l'image et la licence
                    $distance = sqrt(pow($imageX - $licenceX, 2) + pow($imageY - $licenceY, 2));
                    
                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                        $closestLicence = $idx;
                    }
                }
                
                if ($closestLicence !== null) {
                    $licenceImageMap[$closestLicence] = $imageName;
                    $usedImages[$imageName] = true;
                }
            }
            
            
            // Ajouter toutes les licences aux résultats avec leur photo associée
            foreach ($licences as $idx => $licence) {
                $licenceData = $licence['data'];
                
                // Ajouter la photo si elle existe
                if (isset($licenceImageMap[$idx])) {
                    $imageName = $licenceImageMap[$idx];
                    $imageObject = $pageXObjects[$imageName]['object'];
                    $imageContent = $imageObject->getContent();
                    
                    // Vérifier que c'est bien un JPEG valide (commence par 0xFF 0xD8)
                    if (strlen($imageContent) >= 2 && ord($imageContent[0]) === 0xFF && ord($imageContent[1]) === 0xD8) {
                        $licenceData['photo'] = $imageContent;
                    } else {
                        // Image invalide, ignorer
                        $licenceData['photo'] = null;
                    }
                } else {
                    $licenceData['photo'] = null;
                }
                
                $results[] = $licenceData;
            }
        }
        return $results;
    }
}
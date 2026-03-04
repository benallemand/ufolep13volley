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
        
        // Détecter le format du PDF (nouveau format liguasso vs ancien format)
        $text = $pdf->getText();
        if ($this->isLiguassoFormat($text)) {
            return $this->parseLiguassoFormat($pdf);
        }
        
        // Ancien format
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
                    $club_name = $matches[2];
                    // Vérifier si le nom du club continue sur la ligne suivante
                    if ($tm_index + 1 < count($data_tm)) {
                        $next_item = trim($data_tm[$tm_index + 1][1]);
                        // Si la ligne suivante ne commence pas par un mot-clé connu, c'est la suite du nom
                        if (!self::starts_with($next_item, "Délivrée le") &&
                            !self::starts_with($next_item, "Cert.") &&
                            !self::starts_with($next_item, "Connectez") &&
                            !self::starts_with($next_item, "votre identifiant") &&
                            !self::starts_with($next_item, "Licence à") &&
                            !self::starts_with($next_item, "Plus d'informations") &&
                            !self::starts_with($next_item, "N°") &&
                            !self::starts_with($next_item, "Né(e) le") &&
                            !self::starts_with($next_item, "Asso") &&
                            !self::starts_with($next_item, "MULTISPORTS") &&
                            !self::starts_with($next_item, "Pratiquant") &&
                            !self::starts_with($next_item, "Vos activités")) {
                            $club_name .= ' ' . $next_item;
                            $tm_index++; // Sauter la ligne suivante
                        }
                    }
                    $result['club'] = $club_name;
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

    /**
     * Détecte si le PDF utilise le nouveau format liguasso
     * Le nouveau format contient "www.liguasso.org" et peut avoir:
     * - Numéros au format "N° XX00000000" (initiales + 8 chiffres)
     * - Numéros au format "N° 0XX_XXXXXXXX" (ancien format dans template liguasso)
     */
    private function isLiguassoFormat(string $text): bool
    {
        return str_contains($text, 'www.liguasso.org');
    }

    /**
     * Parse le nouveau format de licence liguasso
     * Format du numéro: 2 lettres (initiales) + 8 chiffres (ex: SB10000025)
     */
    private function parseLiguassoFormat($pdf): array
    {
        $pages = $pdf->getPages();
        $results = array();
        
        foreach ($pages as $pageNum => $page) {
            $data_tm = $page->getDataTm();
            
            // Parser le contenu de la page pour extraire les positions des images
            $pageContent = $page->get('Contents');
            $imagePositions = [];
            
            // Identifier l'image du canevas
            $allPages = $pdf->getPages();
            $totalPages = count($allPages);
            $canvasImage = null;
            
            if ($totalPages > 1) {
                // Pour les PDFs multi-pages: le canevas est l'image présente sur toutes les pages
                $imageOccurrences = [];
                foreach ($allPages as $p) {
                    $pContent = $p->get('Contents');
                    if ($pContent) {
                        $content = $pContent->getContent();
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
                
                foreach ($imageOccurrences as $refName => $count) {
                    if ($count == $totalPages) {
                        $canvasImage = $refName;
                        break;
                    }
                }
            } else {
                // Pour les PDFs à une seule page: le canevas est l'image la plus large (header)
                $pageResources = $page->get('Resources');
                if ($pageResources && $pageResources->has('XObject')) {
                    $xobjectDict = $pageResources->get('XObject');
                    $details = $xobjectDict->getDetails();
                    $maxWidth = 0;
                    
                    foreach ($details as $name => $info) {
                        if (is_array($info) && isset($info['Subtype']) && $info['Subtype'] == 'Image') {
                            $width = $info['Width'] ?? 0;
                            if ($width > $maxWidth) {
                                $maxWidth = $width;
                                $canvasImage = $name;
                            }
                        }
                    }
                }
            }
            
            if ($pageContent) {
                $content = $pageContent->getContent();
                $content = $this->convertContentToString($content);
                
                preg_match_all('/(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s+cm\s*\/([A-Za-z]\d+)\s+Do/s', $content, $matches, PREG_SET_ORDER);
                
                foreach ($matches as $match) {
                    $imageName = $match[3];
                    $xPosition = floatval($match[1]);
                    $yPosition = floatval($match[2]);
                    
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
            $departement = null;
            
            while ($tm_index < count($data_tm)) {
                $item = trim($data_tm[$tm_index][1]);
                
                // Détecter le département via UFOLEP0XX
                if (preg_match('/UFOLEP(\d{3})/', $item, $matches)) {
                    $departement = $matches[1];
                }
                
                // Nouveau format liguasso: "N° XX00000000" (2 lettres + 8 chiffres) ou "N° 0XX_XXXXXXXX" (ancien format dans liguasso)
                $isNewLicence = false;
                $licenceNumber = null;
                $extractedDepartement = null;
                
                // Format 1: initiales + 8 chiffres (ex: SB10000025)
                if (preg_match('/^N°\s+([A-Z]{2})(\d{8})$/', $item, $matches)) {
                    $isNewLicence = true;
                    $licenceNumber = $matches[1] . $matches[2];
                    $extractedDepartement = $departement ? ltrim($departement, '0') : '13';
                }
                // Format 2: ancien format dans liguasso (ex: 013_40232028)
                elseif (preg_match('/^N°\s+0?(\d{2,3})_(\d+)$/', $item, $matches)) {
                    $isNewLicence = true;
                    $extractedDepartement = ltrim($matches[1], '0');
                    $licenceNumber = $matches[2];
                }
                
                if ($isNewLicence) {
                    // Nouvelle licence détectée - sauvegarder la précédente si elle existe
                    if (count($result) >= 9) {
                        $licences[] = [
                            'data' => $result,
                            'x_position' => $licenceXPosition,
                            'y_position' => $licenceYPosition
                        ];
                    }
                    
                    // Commencer une nouvelle licence
                    $result = [];
                    $licenceXPosition = $data_tm[$tm_index][0][4] ?? null;
                    $licenceYPosition = $data_tm[$tm_index][0][5] ?? null;
                    
                    $result['departement'] = $extractedDepartement;
                    $result['licence_number'] = $licenceNumber;
                    
                    // Le nom est sur la ligne suivante
                    if ($tm_index + 1 < count($data_tm)) {
                        $nextItem = trim($data_tm[$tm_index + 1][1]);
                        // Format: "NOM Prénom"
                        $result['last_first_name'] = $nextItem;
                    }
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
                    $club_name = $matches[2];
                    // Vérifier si le nom du club continue sur la ligne suivante
                    if ($tm_index + 1 < count($data_tm)) {
                        $next_item = trim($data_tm[$tm_index + 1][1]);
                        if (!self::starts_with($next_item, "Délivrée le") &&
                            !self::starts_with($next_item, "Cert.") &&
                            !self::starts_with($next_item, "Connectez") &&
                            !self::starts_with($next_item, "votre identifiant") &&
                            !self::starts_with($next_item, "Licence à") &&
                            !self::starts_with($next_item, "Plus d'informations") &&
                            !preg_match('/^N°\s+[A-Z]{2}\d{8}$/', $next_item) &&
                            !self::starts_with($next_item, "Né(e) le") &&
                            !self::starts_with($next_item, "Asso") &&
                            !self::starts_with($next_item, "MULTISPORTS") &&
                            !self::starts_with($next_item, "Pratiquant") &&
                            !self::starts_with($next_item, "Vos activités") &&
                            !self::starts_with($next_item, "Refus individuel")) {
                            $club_name .= ' ' . $next_item;
                            $tm_index++;
                        }
                    }
                    $result['club'] = $club_name;
                } elseif (self::starts_with($item, "votre identifiant")) {
                    // Format 1: initiales + 8 chiffres (ex: SB10000025)
                    // Format 2: ancien format (ex: 013_40232028)
                    if (preg_match('/votre identifiant ([A-Z]{2}\d{8}|0?\d{2,3}_\d+).*/', $item, $matches)) {
                        $result['licence_number_2'] = $matches[1];
                    } else {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                } elseif (self::starts_with($item, "Délivrée le : ")) {
                    if (!preg_match('/Délivrée le : (\d{2}\/\d{2}\/\d{4})/', $item, $matches)) {
                        throw new Exception("Impossible de déchiffrer cette chaîne: $item !");
                    }
                    $result['homologation_date'] = $matches[1];
                }
                $tm_index++;
            }
            
            // Ajouter la dernière licence
            if (count($result) >= 9) {
                $licences[] = [
                    'data' => $result,
                    'x_position' => $licenceXPosition,
                    'y_position' => $licenceYPosition
                ];
            }
            
            // Associer chaque image à la licence la plus proche
            $usedImages = [];
            $licenceImageMap = [];
            
            // Calculer la distance de chaque image à chaque licence
            $imageDistances = [];
            foreach ($pageXObjects as $imageName => $imageData) {
                $imageX = $imageData['x_position'];
                $imageY = $imageData['y_position'];
                
                if ($imageX > 300) {
                    continue;
                }
                
                foreach ($licences as $idx => $licence) {
                    $licenceX = $licence['x_position'];
                    $licenceY = $licence['y_position'];
                    
                    $distance = sqrt(pow($imageX - $licenceX, 2) + pow($imageY - $licenceY, 2));
                    $imageDistances[] = [
                        'imageName' => $imageName,
                        'licenceIdx' => $idx,
                        'distance' => $distance
                    ];
                }
            }
            
            // Trier par distance croissante
            usort($imageDistances, function($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });
            
            // Associer chaque licence à l'image la plus proche (une image par licence, une licence par image)
            foreach ($imageDistances as $entry) {
                $imageName = $entry['imageName'];
                $licenceIdx = $entry['licenceIdx'];
                
                // Si cette licence n'a pas encore d'image et cette image n'est pas déjà utilisée
                if (!isset($licenceImageMap[$licenceIdx]) && !isset($usedImages[$imageName])) {
                    $licenceImageMap[$licenceIdx] = $imageName;
                    $usedImages[$imageName] = true;
                }
            }
            
            // Ajouter toutes les licences aux résultats avec leur photo associée
            foreach ($licences as $idx => $licence) {
                $licenceData = $licence['data'];
                
                if (isset($licenceImageMap[$idx])) {
                    $imageName = $licenceImageMap[$idx];
                    $imageObject = $pageXObjects[$imageName]['object'];
                    $imageContent = $imageObject->getContent();
                    
                    if (strlen($imageContent) >= 2 && ord($imageContent[0]) === 0xFF && ord($imageContent[1]) === 0xD8) {
                        $licenceData['photo'] = $imageContent;
                    } else {
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
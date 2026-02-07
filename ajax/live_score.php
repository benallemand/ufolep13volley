<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../classes/LiveScore.php';
require_once __DIR__ . '/../classes/MatchMgr.php';
require_once __DIR__ . '/../classes/UserManager.php';

/**
 * Check if user can modify live score for a match
 */
function canModifyLiveScore($id_match): bool {
    @session_start();
    
    // Admin can always modify
    if (UserManager::isAdmin()) {
        return true;
    }
    
    // Must be logged in with a team
    if (!isset($_SESSION['id_equipe'])) {
        return false;
    }
    
    // Check if user's team is playing this match
    try {
        $manager = new MatchMgr();
        $match = $manager->get_match_by_code_match($id_match);
        $userTeamId = $_SESSION['id_equipe'];
        return ($userTeamId == $match['id_equipe_dom'] || $userTeamId == $match['id_equipe_ext']);
    } catch (Exception $e) {
        return false;
    }
}

try {
    $liveScore = new LiveScore();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $id_match = filter_input(INPUT_GET, 'id_match', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        if ($id_match) {
            $result = $liveScore->getLiveScore($id_match);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } else {
            $results = $liveScore->getActiveLiveScores();
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['action']) || empty($input['id_match'])) {
            throw new Exception("Missing required parameters: action and id_match");
        }

        $id_match = $input['id_match'];
        $action = $input['action'];

        // Authorization check for all POST actions
        if (!canModifyLiveScore($id_match)) {
            http_response_code(403);
            throw new Exception("Non autorisé: vous devez être administrateur ou responsable d'une des équipes du match");
        }

        switch ($action) {
            case 'start':
                $id = $liveScore->startLiveScore($id_match);
                echo json_encode([
                    'success' => true,
                    'message' => 'Live score started',
                    'id' => $id
                ]);
                break;

            case 'increment':
                if (empty($input['team']) || !in_array($input['team'], ['dom', 'ext'])) {
                    throw new Exception("Invalid team parameter");
                }
                $liveScore->incrementScore($id_match, $input['team']);
                $result = $liveScore->getLiveScore($id_match);
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);
                break;

            case 'decrement':
                if (empty($input['team']) || !in_array($input['team'], ['dom', 'ext'])) {
                    throw new Exception("Invalid team parameter");
                }
                $liveScore->decrementScore($id_match, $input['team']);
                $result = $liveScore->getLiveScore($id_match);
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);
                break;

            case 'next_set':
                $setWinner = $input['set_winner'] ?? null;
                $liveScore->nextSet($id_match, $setWinner);
                $result = $liveScore->getLiveScore($id_match);
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);
                break;

            case 'end':
                $liveScore->endLiveScore($id_match);
                echo json_encode([
                    'success' => true,
                    'message' => 'Live score ended'
                ]);
                break;

            case 'upsert':
                if (!isset($input['score_data']) || !is_array($input['score_data'])) {
                    throw new Exception("Missing or invalid score_data parameter");
                }
                if (!isset($input['version']) || !is_numeric($input['version'])) {
                    throw new Exception("Missing or invalid version parameter");
                }
                $result = $liveScore->upsertScore($id_match, $input['score_data'], (int)$input['version']);
                if (!$result['success']) {
                    http_response_code(409);
                }
                echo json_encode($result);
                break;

            case 'save_to_match':
                $liveScore->saveToMatch($id_match);
                echo json_encode([
                    'success' => true,
                    'message' => 'Scores enregistrés dans le match'
                ]);
                break;

            default:
                throw new Exception("Unknown action: $action");
        }
    } else {
        throw new Exception("Method not allowed");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

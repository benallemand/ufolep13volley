<?php

require_once __DIR__ . '/../includes/db_inc.php';
require_once __DIR__ . '/../libs/Unirest.php';
$pathPhoto = filter_input(INPUT_GET, 'path_photo');
$url = "https://faceplusplus-faceplusplus.p.mashape.com/detection/detect?url=http://www.ufolep13volley.org/$pathPhoto";
Unirest\Request::verifyPeer(false);
try {
    $response = Unirest\Request::get(
        $url, array(
        "X-Mashape-Authorization" => $mashape_api_key
    ), null
    );
} catch (\Unirest\Exception $e) {
}
echo $response->__get('raw_body');

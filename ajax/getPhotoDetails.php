<?php

require_once '../includes/db_inc.php';
require_once '../libs/Unirest.php';
$pathPhoto = filter_input(INPUT_GET, 'path_photo');
$url = "https://faceplusplus-faceplusplus.p.mashape.com/detection/detect?url=http://www.ufolep13volley.org/$pathPhoto";
Unirest\Request::verifyPeer(false);
$response = Unirest\Request::get(
                $url, array(
            "X-Mashape-Authorization" => $mashape_api_key
                ), null
);
echo $response->__get('raw_body');

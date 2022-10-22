<?php
require_once '../libs/Unirest.php';
require_once '../includes/conf.php';
$url = "https://api.flickr.com/services/rest?method=flickr.photos.search&sort=relevance&per_page=50&api_key=$flickr_api_key&text=volleyball&format=json&nojsoncallback=1";
//$url = "https://api.flickr.com/services/rest/?method=flickr.people.getPhotos&api_key=$flickr_api_key&user_id=42227760@N04&format=json&nojsoncallback=1";
Unirest\Request::verifyPeer(false);
try {
    $response = Unirest\Request::get($url);
} catch (Unirest\Exception $e) {
    // If localhost and proxy needed
    Unirest\Request::proxy($proxy_url, 3128, CURLPROXY_HTTP);
    $response = Unirest\Request::get($url);
}
$test = json_decode($response->raw_body);
echo json_encode($test->photos);

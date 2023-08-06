<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/conf.php';
global $flickr_api_key;
global $proxy_url;
$url = "https://api.flickr.com/services/rest?method=flickr.photos.search&sort=relevance&per_page=50&api_key=$flickr_api_key&text=volleyball&format=json&nojsoncallback=1";
//$url = "https://api.flickr.com/services/rest/?method=flickr.people.getPhotos&api_key=$flickr_api_key&user_id=42227760@N04&format=json&nojsoncallback=1";
Unirest\Request::verifyPeer(false);
try {
    $headers = array('Accept' => 'application/json');
    $query = array(
        'method' => 'flickr.photos.search',
        'sort' => 'relevance',
        'per_page' => '10',
        'api_key' => $flickr_api_key,
        'text' => 'volleyball',
        'format' => 'json',
        'nojsoncallback' => 1,
        );

    $response = Unirest\Request::get('https://api.flickr.com/services/rest', $headers, $query);
//    $response = Unirest\Request::get($url);
} catch (Unirest\Exception $e) {
    // If localhost and proxy needed
    Unirest\Request::proxy($proxy_url, 3128, CURLPROXY_HTTP);
    $response = Unirest\Request::get($url);
}
$test = json_decode($response->raw_body);
echo json_encode($test->photos);

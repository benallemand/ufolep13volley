<?php
require_once '../libs/Unirest.php';
$serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
require_once '../includes/db_inc.php';
$url = "https://api.flickr.com/services/rest?method=flickr.photos.search&sort=relevance&per_page=50&api_key=$flickr_api_key&text=volleyball&format=json&nojsoncallback=1";
//$url = "https://api.flickr.com/services/rest/?method=flickr.people.getPhotos&api_key=$flickr_api_key&user_id=42227760@N04&format=json&nojsoncallback=1";
Unirest\Request::verifyPeer(false);
switch ($serverName) {
    case 'localhost':
        Unirest\Request::proxy('aixproxyprod.insidefr.com', 3128, CURLPROXY_HTTP);
        break;
    default:
        break;
}
$response = Unirest\Request::get($url);
$test = json_decode($response->raw_body);
echo json_encode($test->photos);

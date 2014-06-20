<?php
require_once '../includes/db_inc.php';
require_once '../libs/Unirest.php';
$url = "https://api.flickr.com/services/rest?method=flickr.photos.search&sort=relevance&api_key=$flickr_api_key&text=volleyball&format=json&extras=url_c&nojsoncallback=1";
Unirest::verifyPeer(false);
$response = Unirest::get($url);
echo $response->__get('raw_body');

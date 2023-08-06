<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Configuration.php';
$configuration = new Configuration();
$flickr_api_key = $configuration->flickr_api_key;
if (empty($flickr_api_key)) {
    echo json_encode(array());
    exit(0);
}
$proxy_url = $configuration->proxy_url;
Unirest\Request::verifyPeer(false);
if (!empty($proxy_url)) {
    Unirest\Request::proxy($proxy_url, 3128, CURLPROXY_HTTP);
}
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
$test = json_decode($response->raw_body);
echo json_encode($test->photos);

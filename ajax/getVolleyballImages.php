<?php
function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$data = curl_exec($ch);
        if($data === false) {
            echo curl_error($ch);
        }
	curl_close($ch);
	return $data;
}
require_once '../includes/db_inc.php';
$results = array();

$url = "https://api.flickr.com/services/rest?method=flickr.photos.search&sort=relevance&api_key=$flickr_api_key&text=volleyball&format=json&extras=url_c&nojsoncallback=1";
echo get_data($url);

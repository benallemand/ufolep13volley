<?php

require_once '../libs/Unirest.php';
require_once '../includes/db_inc.php';
$url = "https://api.github.com/repos/benallemand/ufolep13volley/commits";
Unirest\Request::verifyPeer(false);
try {
    $response = Unirest\Request::get($url);
} catch (Unirest\Exception $e) {
    // If localhost and proxy needed
    Unirest\Request::proxy($proxy_url, 3128, CURLPROXY_HTTP);
    $response = Unirest\Request::get($url);
}
$test = json_decode($response->raw_body);
$commitURL = $test[0]->commit->url;
date_default_timezone_set('Europe/Paris');
$time = strtotime($test[0]->commit->committer->date);
$formattedLastDate = date('d/m/Y H:i:s', $time);
echo "Derniere modification: (<a href='$commitURL'>$formattedLastDate</a>)";

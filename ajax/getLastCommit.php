<?php

require_once '../libs/Unirest.php';
$serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
$url = "https://api.github.com/repos/benallemand/ufolep13volley/commits";
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
$commitURL = $test[0]->commit->url;
date_default_timezone_set('Europe/Paris');
$time = strtotime($test[0]->commit->committer->date);
$formattedLastDate = date('d/m/Y H:i:s', $time);
echo "Derniere modification: (<a href='$commitURL'>$formattedLastDate</a>)";

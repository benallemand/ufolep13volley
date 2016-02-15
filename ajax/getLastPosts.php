<?php

header('Content-Type: application/json');
$feed = new DOMDocument();
$feed->load('http://ufolep13volley.forumzen.com/feed');
$json = array();
$json['title'] = $feed->getElementsByTagName('channel')->item(0)->getElementsByTagName('title')->item(0)->firstChild->nodeValue;
$json['description'] = $feed->getElementsByTagName('channel')->item(0)->getElementsByTagName('description')->item(0)->firstChild->nodeValue;
$json['link'] = $feed->getElementsByTagName('channel')->item(0)->getElementsByTagName('link')->item(0)->firstChild->nodeValue;
$items = $feed->getElementsByTagName('channel')->item(0)->getElementsByTagName('item');
$json['items'] = array();
foreach ($items as $item) {
    $title = $item->getElementsByTagName('title')->item(0)->firstChild->nodeValue;
    $description = $item->getElementsByTagName('description')->item(0)->firstChild->nodeValue;
    $pubDate = $item->getElementsByTagName('pubDate')->item(0)->firstChild->nodeValue;
    $guid = $item->getElementsByTagName('guid')->item(0)->firstChild->nodeValue;
    $creator = $item->getElementsByTagName('creator')->item(0)->firstChild->nodeValue;
    $category = $item->getElementsByTagName('category')->item(0)->firstChild->nodeValue;
    $json['items'][] = array(
        'title' => $title,
        'description' => $description,
        'pubdate' => $pubDate,
        'guid' => $guid,
        'creator' => $creator,
        'category' => $category
    );
    if (count($json['items']) > 15) {
        break;
    }
}

if(filter_input(INPUT_GET, 'callback') !== null) {
    echo filter_input(INPUT_GET, 'callback') . "(" . json_encode($json['items']) . ")";
}
else {
    echo json_encode($json['items']);
}

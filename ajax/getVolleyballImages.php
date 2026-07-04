<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Configuration.php';
$configuration = new Configuration();
Unirest\Request::verifyPeer(false);
$proxy_url = $configuration->proxy_url;
if (!empty($proxy_url)) {
    Unirest\Request::proxy($proxy_url, 3128, CURLPROXY_HTTP);
}
$headers = array('Accept' => 'application/json');

function get_facebook_photos(Configuration $configuration, array $headers): ?array
{
    if (empty($configuration->facebook_page_id) || empty($configuration->facebook_page_token)) {
        return null;
    }
    $query = array(
        'type' => 'uploaded',
        'fields' => 'images',
        'limit' => '30',
        'access_token' => $configuration->facebook_page_token,
    );
    $url = sprintf('https://graph.facebook.com/v21.0/%s/photos', $configuration->facebook_page_id);
    $response = Unirest\Request::get($url, $headers, $query);
    if ($response->code !== 200) {
        return null;
    }
    $body = json_decode($response->raw_body);
    if (empty($body->data)) {
        return null;
    }
    $photos = array();
    foreach ($body->data as $photo) {
        if (empty($photo->images)) {
            continue;
        }
        // images est trié de la plus grande à la plus petite : prendre la première <= 1280px de large
        $src = end($photo->images)->source;
        foreach ($photo->images as $image) {
            if ($image->width <= 1280) {
                $src = $image->source;
                break;
            }
        }
        $photos[] = array('id' => $photo->id, 'src' => $src);
    }
    if (empty($photos)) {
        return null;
    }
    return array(
        'source' => 'facebook',
        'more_link' => 'https://www.facebook.com/ufolep13volley/',
        'photos' => $photos,
    );
}

function get_flickr_photos(Configuration $configuration, array $headers): ?array
{
    if (empty($configuration->flickr_api_key)) {
        return null;
    }
    $query = array(
        'method' => 'flickr.photos.search',
        'sort' => 'date-posted-desc',
        'per_page' => '30',
        'api_key' => $configuration->flickr_api_key,
        'text' => 'ufolep volley',
        'orientation' => 'landscape',
        'format' => 'json',
        'nojsoncallback' => 1,
    );
    $response = Unirest\Request::get('https://api.flickr.com/services/rest', $headers, $query);
    if ($response->code !== 200) {
        return null;
    }
    $body = json_decode($response->raw_body);
    if (empty($body->photos->photo)) {
        return null;
    }
    $photos = array();
    foreach ($body->photos->photo as $photo) {
        $photos[] = array(
            'id' => $photo->id,
            'src' => sprintf('https://farm%s.staticflickr.com/%s/%s_%s.jpg',
                $photo->farm, $photo->server, $photo->id, $photo->secret),
        );
    }
    return array(
        'source' => 'flickr',
        'more_link' => 'https://www.flickr.com/photos/149988821@N04/albums/',
        'photos' => $photos,
    );
}

try {
    $result = get_facebook_photos($configuration, $headers);
    if ($result === null) {
        $result = get_flickr_photos($configuration, $headers);
    }
} catch (Exception $e) {
    $result = null;
}
if ($result === null) {
    $result = array('source' => '', 'more_link' => '', 'photos' => array());
}
header('Content-Type: application/json');
echo json_encode($result);

<?php

function get_json($url) {
    $base = "https://api.github.com/";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $base . $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.4) Gecko/20030624 Netscape/7.1 (ax)");

    //curl_setopt($curl, CONNECTTIMEOUT, 1);
    $content = curl_exec($curl);
    curl_close($curl);
    return $content;
}

function get_latest_repo($user) {
    // Get the json from github for the repos
    $json = json_decode(get_json("users/$user/repos"), true);

    // Sort the array returend by pushed_at time
    function compare_pushed_at($b, $a) {
        return strnatcmp($a['pushed_at'], $b['pushed_at']);
    }

    usort($json, 'compare_pushed_at');

    //Now just get the latest repo
    $json = $json[0];

    return $json;
}

function get_commits($repo, $user) {
    // Get the name of the repo that we'll use in the request url
    $repoName = $repo["name"];
    return json_decode(get_json("repos/$user/$repoName/commits"), true);
}
$login = "benallemand";
$latestRepo = get_latest_repo($login);
$commits = get_commits($latestRepo, $login);
$latestCommit = $commits[0];

// Relevant information
$repoURL = $latestRepo["html_url"];
$repoName = $latestRepo["name"];
$repoDescription = $latestRepo["description"];
$gravatar = $latestRepo["owner"]["avatar_url"];
$author = $latestCommit["commit"]["author"]["name"];
$dateLast = $latestCommit["commit"]["author"]["date"];
$userURL = "https://github.com/$login";
$commitMessage = $latestCommit["commit"]["message"];
$commitSHA = $latestCommit["sha"];
$commitURL = "https://github.com/$login/$repoName/commit/$commitSHA";

$time = strtotime($dateLast);
$formattedLastDate = date('d/m/Y H:i:s', $time);
echo "Derniere modification: (<a href='$commitURL'>$formattedLastDate</a>)";

<?php

require_once '../includes/db_inc.php';
$urlLogin = "http://www.affiligue.org/Login.aspx";
$urlSecuredPage = "http://www.affiligue.org/Pages/accueil.aspx";
$urlSearchPage = "http://www.affiligue.org/Pages/ufolep_ImprLicence.aspx";
$nameUsername = 'Login1$UserName';
$namePassword = 'Login1$Password';
$nameLoginBtn = 'Login1$LoginButton';
$valUsername = $affiligueUsername;
$valPassword = $affiliguePassword;
$valLoginBtn = 'Connexion';
$cookieFile = 'cookie.txt';
$regexViewstate = '/__VIEWSTATE\" value=\"(.*)\"/i';
$regexEventVal = '/__EVENTVALIDATION\" value=\"(.*)\"/i';
$licenceNumber = filter_input(INPUT_POST, 'licence_number');

function regexExtract($text, $regex, $regs, $nthValue) {
    if (preg_match($regex, $text, $regs)) {
        $result = $regs[$nthValue];
    } else {
        $result = "";
    }
    return $result;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlLogin);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
$data = curl_exec($ch);
$regs = array();
$viewstate = regexExtract($data, $regexViewstate, $regs, 1);
$eventval = regexExtract($data, $regexEventVal, $regs, 1);

$postData = '__VIEWSTATE=' . rawurlencode($viewstate)
        . '&__EVENTVALIDATION=' . rawurlencode($eventval)
        . '&' . $nameUsername . '=' . $valUsername
        . '&' . $namePassword . '=' . $valPassword
        . '&' . $nameLoginBtn . '=' . $valLoginBtn;
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_URL, $urlLogin);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$data = curl_exec($ch);

curl_setopt($ch, CURLOPT_POST, FALSE);
curl_setopt($ch, CURLOPT_URL, $urlSecuredPage);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$data = curl_exec($ch);

curl_setopt($ch, CURLOPT_POST, FALSE);
curl_setopt($ch, CURLOPT_URL, $urlSearchPage);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$data = curl_exec($ch);

$viewstate = regexExtract($data, $regexViewstate, $regs, 1);
$eventval = regexExtract($data, $regexEventVal, $regs, 1);
$postData = '__VIEWSTATE=' . rawurlencode($viewstate)
        . '&__EVENTARGUMENT='
        . '&__SCROLLPOSITIONX=0'
        . '&__SCROLLPOSITIONY=0'
        . '&__EVENTVALIDATION=' . rawurlencode($eventval)
        . '&__EVENTTARGET=ctl00$ContentBody$lbtnRechercher'
        . '&ctl00$ContentBody$txtRechercheNom='
        . '&ctl00$ContentBody$txtRecherchePrenom='
        . '&ctl00$ContentBody$txtRechercheNumLicence=' . $licenceNumber;
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_URL, $urlSearchPage);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

$data = curl_exec($ch);
echo $data;
curl_close($ch);

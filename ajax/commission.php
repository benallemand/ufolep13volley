<?php

require_once __DIR__ . "/../classes/Rest.php";

$restClass = new Rest(__FILE__);
$restClass->parseRequest();


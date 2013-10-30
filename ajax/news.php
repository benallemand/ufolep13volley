<?php

require_once "./classes/Rest.php";

$restClass = new Rest(__FILE__);
$restClass->parseRequest();

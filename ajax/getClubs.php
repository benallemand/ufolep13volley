<?php

require_once __DIR__ . "/../classes/ClubManager.php";
$manager = new ClubManager();
echo json_encode($manager->get());

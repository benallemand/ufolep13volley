<?php
require_once __DIR__ . '/../classes/Files.php';

use PHPUnit\Framework\TestCase;

class FilesTest extends TestCase
{

    public function testDownload_match_file()
    {
        $mgr = new Files();
        $mgr->download_match_file('match_files/M2210211file11.PDF');
    }
}

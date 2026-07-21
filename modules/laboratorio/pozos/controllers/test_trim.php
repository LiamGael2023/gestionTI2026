<?php
error_reporting(E_ALL); ini_set('display_errors', '1');
$file = file_get_contents("PozoImportAPI.php");
$lines = explode("\n", $file);
foreach ($lines as $i => $line) {
    if (strpos($line, 'trim(') !== false) {
        echo ($i+1) . ": " . trim($line) . "\n";
    }
}

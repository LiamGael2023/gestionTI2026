<?php
$dir = new RecursiveDirectoryIterator('d:\SISTEMAS\gestionTI2026');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$fixed = 0;
foreach($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    if (!mb_check_encoding($content, 'UTF-8')) {
        $utf8 = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        file_put_contents($path, $utf8);
        $fixed++;
    }
}
echo "Fixed $fixed files.\n";

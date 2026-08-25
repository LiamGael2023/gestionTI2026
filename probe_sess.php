<?php
header('Content-Type: text/plain');
session_start();
$sid = session_id();
$_SESSION['probe'] = 'ok';
session_write_close();
clearstatcache();
$p = rtrim(ini_get('session.save_path'), '\\/');
$f = $p . '\\sess_' . $sid;
echo "save_path=" . ini_get('session.save_path') . "\n";
echo "sid=" . $sid . "\n";
echo "expected file=" . $f . "\n";
echo "file_exists=" . (file_exists($f) ? 'YES' : 'NO') . "\n";
echo "is_writable(dir)=" . (is_writable($p) ? 'YES' : 'NO') . "\n";
// try writing a marker to confirm
$marker = $p . '\\probe_marker.txt';
file_put_contents($marker, date('c'));
echo "marker_write=" . (file_exists($marker) ? 'YES' : 'NO') . "\n";
?>

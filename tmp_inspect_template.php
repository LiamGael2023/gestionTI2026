<?php
require 'd:/SISTEMAS/gestionTI2026/libs/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$path = 'd:/SISTEMAS/gestionTI2026/modules/laboratorio/muestra/plantilla/CSJ-DRDYCS-LAYS – R - 2- RESULTADOS ANALISIS DE AGUAS.xlsx';
$ss = IOFactory::load($path);
$sh = $ss->getActiveSheet();
$maxRow = $sh->getHighestRow();
$maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sh->getHighestColumn());
for ($r=1;$r<=120;$r++) {
  $vals=[];
  for ($c=1;$c<=10;$c++) {
    $v = trim((string)$sh->getCellByColumnAndRow($c,$r)->getFormattedValue());
    if ($v!=='') $vals[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c).$r.'='.$v;
  }
  if ($vals) echo implode(' | ',$vals)."\n";
}

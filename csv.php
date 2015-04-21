#!/usr/bin/env php
<?php

if ($argc < 2)
	die("usage: php ".$argv[0]." <txt>\n");

require 'SberTXT.php';

$filename = $argv[1];

$sber = new SberTXT($filename);
$rows = $sber->parse();

$sum = 0;
$csv = fopen("$filename.csv", 'w');
foreach ($rows as $i => $fields) {
	$sum += $fields['sum'];
	$fields['round'] = round($fields['sum']);
	fputcsv($csv, $fields);
}
fclose($csv);

echo "sum: $sum\n";

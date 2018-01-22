#!/usr/bin/env php
<?php

if(!isset($argv[1])) {
	fputs(STDERR, "Usage: $argv[0] <webmap_outfile>\n\n");
	die();
}

$file = fopen($argv[1], 'r');
$data = array();
while(($line = fgets($file)) !== false) {
	$line = trim($line);
	$line = explode(':::', $line);
	$data[] = $line;
}
fclose($file);
usort($data, function($a, $b) {
	return $a[1] > $b[1];
});
foreach($data as $item) {
	echo implode(':::', $item)."\n";
}

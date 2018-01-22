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
	$parsed = parse_url($line[1]);
	if(!isset($parsed['path'])) {
		$parsed['path'] = '/';
	}
	if(preg_match('/\.([a-z0-9]+)$/i', $parsed['path'], $matches)) {
		$current_ext = strtolower($matches[1]);
	} else {
		$current_ext = '';
	}
	if(!isset($ext[$current_ext])) {
		$ext[$current_ext] = 0;
	}
	$ext[$current_ext]++;
}
fclose($file);
ksort($ext);
foreach($ext as $ext_name => $ext_count) {
	if($ext_name === '') {
		echo '(no extension): ';
	} else {
		echo "$ext_name: ";
	}
	echo "$ext_count\n";
}

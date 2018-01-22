#!/usr/bin/env php
<?php

if(!isset($argv[1]) || !isset($argv[2])) {
	fputs(STDERR, "Usage: $argv[0] <webmap_outfile> <extension>\n\n");
	die();
}

$file = fopen($argv[1], 'r');
$search_ext = $argv[2];
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
	if($current_ext === $search_ext) {
		echo implode(':::', $line)."\n";
	}
}
fclose($file);

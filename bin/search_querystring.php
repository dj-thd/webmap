#!/usr/bin/env php
<?php

if(!isset($argv[1])) {
	fputs(STDERR, "Usage: $argv[0] <webmap_outfile>\n\n");
	die();
}

$file = fopen($argv[1], 'r');
while(($line = fgets($file)) !== false) {
	$line = trim($line);
	$line = explode(':::', $line);
	$parsed = parse_url($line[1]);
	if(isset($parsed['query'])) {
		echo implode(':::', $line)."\n";
	}
}
fclose($file);

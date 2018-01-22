#!/usr/bin/env php
<?php

require dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$loop = React\EventLoop\Factory::create();

if(!isset($argv[1])) {
	fputs(STDERR, "Usage: $argv[0] <url> | tee <outfile>\n\n");
	die();
}

// Configurable options
$base_url = $argv[1];
$dns_server = '8.8.8.8';
$request_timeout = 20;

$options = array(
	'max_concurrent_requests' => 50,
	'max_depth' => 5,
	'silent' => true,
	'headers' => array(
		'User-Agent' => 'dj.thd/webmap 1.0',
	)
);


// Build connector parameters
$connector = new React\Socket\Connector($loop, array(
	'tcp' => true,
	'tls' => array(
		'verify_peer' => false,
		'verify_peer_name' => false,
		'allow_self_signed' => true,
		'disable_compression' => false
	),
	'dns' => $dns_server,
	'timeout' => $request_timeout,
	'unix' => false
));

// Build Http Client
$client = new React\HttpClient\Client($loop, $connector);

// Output streams
$output = new React\Stream\WritableResourceStream(STDOUT, $loop);
$progress = new React\Stream\WritableResourceStream(STDERR, $loop);

// Bootstrap and emit first request
$webmap = new DjThd\WebmapCore($loop, $client, $progress, $output, $options);
$webmap->run(array('url' => $base_url, 'depth' => 0));

$loop->run();

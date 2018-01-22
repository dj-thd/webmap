<?php

namespace DjThd;

class WebmapCore
{
	protected $loop;
	protected $httpClient;
	protected $progressStream;
	protected $outputStream;
	protected $options;

	protected $concurrentRequests = 0;

	protected $processedQueue = array();
	protected $requestQueue = array();

	public function __construct($loop, $httpClient, $progressStream, $outputStream, $options)
	{
		$this->loop = $loop;
		$this->httpClient = $httpClient;
		$this->progressStream = $progressStream;
		$this->outputStream = $outputStream;
		$this->options = $options;
	}

	public function run()
	{
		while(!empty($this->requestQueue) && $this->concurrentRequests < $this->options['max_concurrent_requests']) {
			$depth = end($this->requestQueue);
			$url = key($this->requestQueue);
			array_pop($this->requestQueue);
			$this->emitRequest($url, $depth);
		}

		if(!empty($this->requestQueue) || $this->concurrentRequests > 0) {
			$this->loop->addTimer(0.1, array($this, 'run'));
		}
	}

	public function emitRequest($url, $depth)
	{
		if($depth > $this->options['max_depth']) {
			$this->addToProcessed($url, $depth, -1);
			return;
		}

		if($this->concurrentRequests >= $this->options['max_concurrent_requests']) {
			$this->addToQueue($url, $depth);
			return;
		}

		if(!$this->options['silent']) {
			$len = 120-strlen($url);
			$this->progressStream->write("Testing: $url" . str_repeat(" ", $len > 0 ? $len : 1) . "\r");
		}

		$requestBegin = microtime(true);
		$request = $this->httpClient->request('GET', $url, isset($this->options['headers']) ? $this->options['headers'] : array());

		$request->on('response', function($response) use ($url, $depth, $requestBegin) {
			$requestTime = microtime(true) - $requestBegin;
			$this->addToProcessed($url, $depth, $requestTime);
			$processor = new ResponseProcessor($this->progressStream, $response, $url);
			$processor->on('url', function($url) use ($depth) {
				$this->addToQueue($url, $depth+1);
			});
		});

		$request->on('error', function($error) use ($url, $depth) {
			$this->progressStream->write("ERROR: $url, $error\n");
			$this->addToQueue($url, $depth);
		});

		$request->on('close', function() {
			$this->concurrentRequests--;
		});

		$this->concurrentRequests++;

		$request->end();
	}

	protected function addToProcessed($url, $depth, $time)
	{
		$this->outputStream->write("$depth:::$url:::$time\n");
		$this->processedQueue[$url] = array($depth, $time);
	}

	protected function addToQueue($url, $depth)
	{
		if(!isset($this->processedQueue[$url]) && !isset($this->requestQueue[$url])) {
			$this->requestQueue[$url] = $depth;
		}
	}
}

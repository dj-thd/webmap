<?php

namespace DjThd;

class WebmapCore
{
	protected $loop;
	protected $httpClient;
	protected $progressStream;
	protected $outputStream;
	protected $options;

	protected $processed = array();
	protected $processing = array();

	public function __construct($loop, $httpClient, $progressStream, $outputStream, $options)
	{
		$this->loop = $loop;
		$this->httpClient = $httpClient;
		$this->progressStream = $progressStream;
		$this->outputStream = $outputStream;
		$this->options = $options;
		$this->concurrencyLimiter = new ConcurrencyLimiter($loop, $options['max_concurrent_requests']);
	}

	public function run($baseUrl)
	{
		$this->processed = array();
		$this->processing = array();

		$this->concurrencyLimiter->run(function($data, $endCallback) {
			$this->emitRequest($data['url'], $data['depth'], $endCallback, array($this->concurrencyLimiter, 'enqueueItem'));
		});

		$this->concurrencyLimiter->handleData($baseUrl);
	}

	public function emitRequest($url, $depth, $endCallback, $errorCallback)
	{
		if($this->isProcessing($url) || $this->isProcessed($url)) {
			call_user_func($endCallback);
			return;
		}

		if($depth > $this->options['max_depth']) {
			$this->addToProcessed($url, $depth, -1);
			call_user_func($endCallback);
			return;
		}

		if(!$this->options['silent']) {
			$len = 120-strlen($url);
			$this->progressStream->write("Testing: $url" . str_repeat(" ", $len > 0 ? $len : 1) . "\r");
		}

		$this->addToProcessing($url);

		$requestBegin = microtime(true);
		$request = $this->httpClient->request('GET', $url, isset($this->options['headers']) ? $this->options['headers'] : array());

		$request->on('response', function($response) use ($url, $depth, $requestBegin) {
			$requestTime = microtime(true) - $requestBegin;
			$this->removeFromProcessing($url);
			$this->addToProcessed($url, $depth, $requestTime);
			$processor = new ResponseProcessor($this->progressStream, $response, $url);
			$processor->on('url', function($url) use ($depth) {
				$this->concurrencyLimiter->handleData(array('url' => $url, 'depth' => $depth+1));
			});
		});

		$request->on('error', function($error) use ($url, $depth, $errorCallback) {
			$this->removeFromProcessing($url);
			call_user_func($errorCallback, array('url' => $url, 'depth' => $depth));
		});

		$request->on('close', $endCallback);

		$request->end();
	}

	protected function addToProcessed($url, $depth, $time)
	{
		$this->outputStream->write("$depth:::$url:::$time\n");
		$this->processed[$url] = array($depth, $time);
	}

	protected function isProcessed($url)
	{
		return isset($this->processed[$url]);
	}

	protected function isProcessing($url)
	{
		return isset($this->processing[$url]);
	}

	protected function addToProcessing($url)
	{
		$this->processing[$url] = true;
	}

	protected function removeFromProcessing($url)
	{
		unset($this->processing[$url]);
	}
}

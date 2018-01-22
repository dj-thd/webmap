<?php

namespace DjThd;

use \Evenement\EventEmitter;

class ResponseProcessor extends EventEmitter
{
	protected $data = '';
	protected $errorStream;
	protected $parsedBaseUrl;
	protected $urlResolver;
	protected $urlExtractor;

	public function __construct($errorStream, $response, $baseUrl)
	{
		$this->errorStream = $errorStream;
		$this->response = $response;
		$this->parsedBaseUrl = parse_url($baseUrl);
		$this->urlResolver = new UrlResolver($baseUrl);
		$this->urlExtractor = new UrlExtractor();

		// Store data into buffer
		$response->on('data', function($data) use ($response, $baseUrl) {
			// Data longer than 10MB -> abort
			if(strlen($this->data) + strlen($data) > 10*1024*1024) {
				$response->close();
				$this->errorStream->write("$baseUrl: response was too big\n");
			} else {
				$this->data .= $data;
			}
		});

		// Extract and resolve URLs
		$this->response->on('close', function() {
			// Get URL from Location header
			$headers = $this->response->getHeaders();
			$headers = array_change_key_case($headers, CASE_LOWER);
			if(isset($headers['location'])) {
				$this->resolveAndEmit($headers['location']);
			}

			// Launch URL extractor
			$this->urlExtractor->extract($this->data);
		});

		// Resolve and emit URLs from extractor
		$this->urlExtractor->on('url', array($this, 'resolveAndEmit'));
	}

	// Resolve and emit applicable URLs (within same host as baseUrl)
	protected function resolveAndEmit($url)
	{
		$absoluteUrl = $this->urlResolver->resolve($url);

		// URL is resolved
		if($absoluteUrl !== false) {

			// Emit if host matches
			$parsedUrl = parse_url($absoluteUrl);
			if(strtolower($parsedUrl['host']) === strtolower($this->parsedBaseUrl['host'])) {
				$this->emit('url', array($absoluteUrl));
			}
		}
	}
}

<?php

namespace DjThd;

class UrlResolver
{
	protected $baseUrl;
	protected $parsedBaseUrl;

	public function __construct($baseUrl)
	{
		$this->baseUrl = $baseUrl;
		$this->parsedBaseUrl = $this->parseUrl($baseUrl);

		// Force base path to end with '/'
		if(!preg_match('#/$#', $this->parsedBaseUrl['path'])) {
			$path = explode('/', $this->parsedBaseUrl['path']);
			array_pop($path);
			$this->parsedBaseUrl['path'] = implode('/', $path) . '/';
		}
	}

	public function resolve($relativeUrl)
	{
		$basePath = $this->parsedBaseUrl['path'];
		$relativeUrl = trim($relativeUrl);

		// Empty URL -> base URL
		if(strlen($relativeUrl) == 0) {
			return $this->baseUrl;
		}

		// Fragment URL -> base URL
		if(preg_match('/^#/', $relativeUrl)) {
			return $this->baseUrl;
		}

		// handle ./ and ../
		while(preg_match('#^\.\.?/#', $relativeUrl)) {
			if(preg_match('#^\./#', $relativeUrl)) {
				// ./ -> remove
				$relativeUrl = substr($relativeUrl, 2);
			} else {
				// ../ -> remove, then go up one level at basePath
				$relativeUrl = substr($relativeUrl, 3);
				$path = explode('/', $basePath);

				// Last is empty
				array_pop($path);
				array_pop($path);

				// Regenerate path again
				$path = implode('/', $path);
				if(strlen($path) == 0) {
					$path = '/';
				}
				if(!preg_match('#/$#', $path)) {
					$path .= '/';
				}
				$basePath = $path;
			}
		}

		// Handle URLs starting with // (same scheme, different host)
		if(preg_match('#^//#', $relativeUrl)) {
			$relativeUrl = $this->parsedBaseUrl['scheme'] . ':' . $relativeUrl;
		}

		// Handle URLs starting with single / (same scheme, same host)
		else if(preg_match('#^/#', $relativeUrl)) {
			$relativeUrl = $this->parsedBaseUrl['scheme'] . '://' . $this->parsedBaseUrl['host'] . (isset($this->parsedBaseUrl['port']) ? ':'.$this->parsedBaseUrl['port'] : '')
				. $relativeUrl;
		}

		// Handle URLs starting with xxxxxx: and not following / (about:xxxx, javascript:xxxx) -> do not parse them
		else if(preg_match('#^[a-z]+:[^/]#i', $relativeUrl)) {
			return false;
		}

		// Specifically disallow anything starting with javascript: and about: (some websites malformed URLs like about://)
		else if(preg_match('#^(javascript|about):#i', $relativeUrl)) {
			return false;
		}

		// Handle any other URL not starting with scheme:// as relative URL
		else if(!preg_match('#^[a-z]+://#i', $relativeUrl)) {
			$relativeUrl = $this->parsedBaseUrl['scheme'] . '://' . $this->parsedBaseUrl['host'] . (isset($this->parsedBaseUrl['port']) ? ':'.$this->parsedBaseUrl['port'] : '') .
				$basePath . $relativeUrl;
		}

		// Parse the new URL to see if its absolute and remove fragment part
		$parsedUrl = $this->parseUrl($relativeUrl);

		// Malformed URL, cannot parse
		if(!isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
			return false;
		}

		// Remove fragment part
		$fixedUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . (isset($parsedUrl['port']) ? ':'.$parsedUrl['port'] : '') . $parsedUrl['path'] .
			(isset($parsedUrl['query']) ? '?'.$parsedUrl['query'] : '');

		return $fixedUrl;
	}

	// Parse URL and force scheme and path to defaults
	protected function parseUrl($url)
	{
		$result = parse_url($url);
		return array_merge(array(
			'scheme' => 'http',
			'path' => '/'
		), $result);
	}
}

<?php

namespace DjThd;

use \Evenement\EventEmitter;

class UrlExtractor extends EventEmitter
{
	public function extract($data)
	{
		// Load DOM document and extract from HTML elements
		$dom = new \DOMDocument();
		if(@$dom->loadHTML($data)) {
			$this->extractFromDomElement($dom, 'a', array('href'));
			$this->extractFromDomElement($dom, 'applet', array('codebase'));
			$this->extractFromDomElement($dom, 'area', array('href'));
			$this->extractFromDomElement($dom, 'audio', array('src'));
			$this->extractFromDomElement($dom, 'base', array('href'));
			$this->extractFromDomElement($dom, 'blockquote', array('cite'));
			$this->extractFromDomElement($dom, 'body', array('background'));
			$this->extractFromDomElement($dom, 'button', array('formaction'));
			$this->extractFromDomElement($dom, 'command', array('icon'));
			$this->extractFromDomElement($dom, 'del', array('cite'));
			$this->extractFromDomElement($dom, 'embed', array('src'));
			$this->extractFromDomElement($dom, 'form', array('action'));
			$this->extractFromDomElement($dom, 'frame', array('src', 'longdesc'));
			$this->extractFromDomElement($dom, 'html', array('manifest'));
			$this->extractFromDomElement($dom, 'iframe', array('src', 'longdesc'));
			$this->extractFromDomElement($dom, 'img', array('src', 'longdesc', 'usemap'));
			$this->extractFromDomElement($dom, 'image', array('href'));
			$this->extractFromDomElement($dom, 'input', array('formaction', 'src', 'usemap'));
			$this->extractFromDomElement($dom, 'ins', array('cite'));
			$this->extractFromDomElement($dom, 'link', array('href'));
			$this->extractFromDomElement($dom, 'object', array('classid', 'codebase', 'data', 'usemap'));
			$this->extractFromDomElement($dom, 'q', array('cite'));
			$this->extractFromDomElement($dom, 'script', array('src'));
			$this->extractFromDomElement($dom, 'source', array('src'));
			$this->extractFromDomElement($dom, 'track', array('src'));
			$this->extractFromDomElement($dom, 'video', array('poster', 'src'));
		} else {
			// Cannot load as DOM document, try to extract using regex
			$this->extractFromRegex($data);
		}
	}

	protected function extractFromDomElement($dom, $tagName, $attributeNames)
	{
		$tags = $dom->getElementsByTagName($tagName);
		foreach($tags as $tag) {
			foreach($attributeNames as $attributeName) {
				if($tag->getAttribute($attributeName)) {
					$this->emit('url', array($tag->getAttribute($attributeName)));
				}
			}
		}
	}

	protected function extractFromRegex($data)
	{
		// Simple regex
		$regex = '#https?://[a-z0-9_/-~%?&=\\.]+#i';
		if(preg_match_all($regex, $data, $matches)) {
			foreach($matches[0] as $match) {
				$this->emit('url', array($match));
			}
		}
	}
}

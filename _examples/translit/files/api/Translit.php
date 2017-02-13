<?php

require_once('Simpla.php');


class Translit extends Simpla {
		
	protected $tail_bytes = array();
	protected $map = array();

		
	public function url($url, $unknown = '-', $langcode='ru') {
		
		$res = $this->get($url, $unknown, $langcode);
		$res = preg_replace('/[^\w\d'.preg_quote($unknown).']+/', $unknown, $res);
	 	$res = strtolower($res);
		
		return trim($res, $unknown);
	}
	
	
	public function get($string, $unknown = '?', $langcode='ru') {
		if (!preg_match('/[\x80-\xff]/', $string)) return $string;

		for ($n = 0; $n < 256; $n++) {
			if ($n < 0xc0) {
				$remaining = 0;
			}elseif ($n < 0xe0) {
				$remaining = 1;
			}elseif ($n < 0xf0) {
				$remaining = 2;
			}elseif ($n < 0xf8) {
				$remaining = 3;
			}elseif ($n < 0xfc) {
				$remaining = 4;
			}elseif ($n < 0xfe) {
				$remaining = 5;
			}else{
				$remaining = 0;
			}
			$this->tail_bytes[chr($n)] = $remaining;
		}
		preg_match_all('/[\x00-\x7f]+|[\x80-\xff][\x00-\x40\x5b-\x5f\x7b-\xff]*/', $string, $matches);
		$result = '';
		foreach ($matches[0] as $str) {
			if ($str[0] < "\x80") {
				$result .= $str;
				continue;
			}
			$head = '';
			$chunk = strlen($str);
			$len = $chunk + 1;
			for ($i = -1; --$len; ) {
				$c = $str[++$i];
				if ($remaining = $this->tail_bytes[$c]) {
					$sequence = $head = $c;
					do{
						if (--$len && ($c = $str[++$i]) >= "\x80" && $c < "\xc0") {
							$sequence .= $c;
						}else{
							if ($len == 0) {
								$result .= $unknown;
								break 2;
							}else{
								$result .= $unknown;
								--$i;
								++$len;
								continue 2;
							}
						}
					} while (--$remaining);
					$n = ord($head);
					if ($n <= 0xdf) {
						$ord = ($n - 192) * 64 + (ord($sequence[1]) - 128);
					}elseif ($n <= 0xef) {
						$ord = ($n - 224) * 4096 + (ord($sequence[1]) - 128) * 64 + (ord($sequence[2]) - 128);
					}elseif ($n <= 0xf7) {
						$ord = ($n - 240) * 262144 + (ord($sequence[1]) - 128) * 4096 + (ord($sequence[2]) - 128) * 64 + (ord($sequence[3]) - 128);
					}elseif ($n <= 0xfb) {
						$ord = ($n - 248) * 16777216 + (ord($sequence[1]) - 128) * 262144 + (ord($sequence[2]) - 128) * 4096 + (ord($sequence[3]) - 128) * 64 + (ord($sequence[4]) - 128);
					}elseif ($n <= 0xfd) {
						$ord = ($n - 252) * 1073741824 + (ord($sequence[1]) - 128) * 16777216 + (ord($sequence[2]) - 128) * 262144 + (ord($sequence[3]) - 128) * 4096 + (ord($sequence[4]) - 128) * 64 + (ord($sequence[5]) - 128);
					}
					$result .= $this->_transliteration_replace($ord, $unknown);
					$head = '';
				}elseif ($c < "\x80") {
					$result .= $c;
					$head = '';
				}elseif ($c < "\xc0") {
					if ($head == '') $result .= $unknown;
				}else{
					$result .= $unknown;
					$head = '';
				}
			}
		}
		return $result;
	}

	function _transliteration_replace($ord, $unknown = '?', $langcode='ru') {
		$bank = $ord >> 8;
		if (!isset($this->map[$bank][$langcode])){
			$file = $this->config->root_dir . 'api/translit/'.sprintf('x%02x', $bank).'.php';
			if (file_exists($file)){
				include $file;
				if ($langcode != 'en' && isset($variant[$langcode])) {
					$this->map[$bank][$langcode] = $variant[$langcode] + $base;
				}else{
					$this->map[$bank][$langcode] = $base;
				}
			}else{
				$this->map[$bank][$langcode] = array();
			}
		}
		$ord = $ord & 255;
		return isset($this->map[$bank][$langcode][$ord]) ? $this->map[$bank][$langcode][$ord] : $unknown;
	}



}
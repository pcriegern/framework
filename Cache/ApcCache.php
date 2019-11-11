<?php

namespace Wlec\Framework\Cache;

/**
 * APC Cache Wrapper
 *
 * Example 1 (Standard):
 * $cache = new ApcCache('sample-key');
 * $value = $cache->read();
 * if ($value === false) {
 *     $value = someExpensiveFunction('sample_value');
 *     $cache->write($value);
 * }
 * print $value;
 *
 *
 * Example 2 (Function Wrapper):
 * $cache = new ApcCache();
 * $value = $cache->multiply(3,4);
 * print "3 x 4 = $value";
 *
 * function multiply ( $v1, $v2 ) {
 *     return $v1 * $v2;
 * }
 *
 *
 * EXAMPLE 3 (Method Wrapper):
 * $obj   = new ExpensiveClass();
 * $cache = new ApcCache();
 * $value = $cache->obj__expensiveMethod('sample_value');
 * print $value;
 *
 * class ExpensiveClass {
 *     function expensiveMethod ( $var ) {
 *         return 'some value that needs time to calculate...';
 *     }
 * }
 *
 *
 * User: Philipp von Criegern
 * Date: 22.08.13
 * Time: 20:17
 */
class ApcCache {

	/**
	 * @var int
	 */
	var $timeOut = 120;

	/**
	 * @var string
	 */
	var $key = '';

	/**
	 * @var string
	 */
	var $salt = '';

	/**
	 * @var
	 */
	public $isActive = true;

	/**
	 * Constructor
	 * Define Default Cache Key
	 * @param string $key
	 */
	function __construct($key = '', $salt = '') {
		$this->salt = $salt;
		$this->setKey($key);
	}

	/**
	 * Disable Cache (for Testing purposes)
	 */
	public function disable () {
		$this->isActive = false;
	}

	/**
	 * Set TTL for Cache Storage (in Seconds)
	 * Default: 2 Minutes
	 * @param $ttl
	 */
	function setTimeOut($ttl) {
		$this->timeOut = $ttl;
	}

	/**
	 * Fetch Cached Value
	 * @param string $key
	 * @return mixed
	 */
	function read($key = '') {
		if (!$this->isActive) {
			return false;
		}
		$this->setKey($key);
		return apc_fetch($this->key);
	}

	/**
	 * check if key exists
	 * @param string $key
	 * @return bool
	 */
	public function exists($key = '') {
		if (!$this->isActive) {
			return false;
		}
		$this->setKey($key);
		return apc_exists($this->key);
	}

	/**
	 * Store Value to Cache
	 * @param $value
	 * @param $ttl
	 * @return array|bool
	 */
	function store($value, $ttl = -1) {
		if (!$this->isActive || !strlen($this->key)) {
			return false;
		}
		if ($ttl < 0) {
			$ttl = $this->timeOut;
		}
		$key       = $this->key;
		$this->key = '';
		return apc_store($key, $value, $ttl);
	}

	/**
	 * Store Value to Cache with (temporary) specified key
	 * @param $key
	 * @param $value
	 * @param int $ttl
	 * @return array|bool
	 */
	function storeAt($key, $value, $ttl = -1) {
		if (!$this->isActive || !strlen($this->key)) {
			return false;
		}
		if ($ttl < 0) {
			$ttl = $this->timeOut;
		}
		$temporaryKey = $this->createKey($key);
		return apc_store($temporaryKey, $value, $ttl);
	}

	/**
	 * Store Cache Key
	 * @param $key
	 */
	function setKey($key) {
		if (strlen($key)) {
			//  Multi-Host-Umgebung, Cache aufteilen
			$this->key = $this->createKey($key);
		}
	}

	/**
	 * @param $key
	 * @return string
	 */
	private function createKey ( $key ) {
		return $_SERVER['HTTP_HOST'] . '|' . $this->salt . '|' . $key;
	}

}

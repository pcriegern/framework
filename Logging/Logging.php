<?php
/**
 * Created by PhpStorm.
 * User: philipp
 * Date: 17.08.15
 * Time: 19:20
 */

namespace Wlec\Framework\Logging;

/**
 * Class Logging
 * @package Wlec\Framework\Logging
 */
class Logging {

	/**
	 * @var
	 */
	public $context;
	/**
	 * @var
	 */
	public $fileName;
	/**
	 * @var
	 */
	public $lastLogEntry;
    /**
     * @var
     */
	public $dirPath;

	/**
	 * Logging constructor.
	 * @param string $context
	 * @param string $dirPath
	 */
	function __construct ( $context = 'common', $dirPath = __DIR__ . '/../../../../log/' ) {
		$this->setDirPath($dirPath);
		$this->setContext($context);
	}

	/**
	 * @param string $dirPath
	 */
	public function setDirPath ($dirPath) {
		$this->dirPath = $dirPath;
	}

	/**
	 * @param string $context
	 */
	public function setContext ( $context ) {
		$this->context  = $context;
		$this->fileName = $this->dirPath . $this->context . '_' . date('Y-m-d') . '.log';
	}

	/**
	 * @param string $text
	 * @param string $context
	 */
	public function addToLogfile ( $text, $context = '' ) {
		if (strlen($context)) {
			$this->setContext($context);
		}
		$text = date('d.m.Y H:i:s') . "\t" . $GLOBALS['projectName'] . "\t" . $_SERVER['REMOTE_ADDR'] . "\t" . trim($text) . "\r\n";
		$this->lastLogEntry = trim($text);

		if (strlen($this->dirPath) && !is_dir($this->dirPath)) {
			if (!mkdir($this->dirPath, 0774, true)) {
				return;
			}
		}
		file_put_contents($this->fileName, $text, FILE_APPEND);
	}

    /**
     * @param $text
     * @param $code
     */
	public function logError ( $text, $code = 'undef') {
        $logText  = date('d.m.Y H:i:s') . "\t" . $_SERVER['REMOTE_ADDR'] . "\t" . trim($text) . ' #' . $code . "\n";
        $filename = $this->dirPath . 'error-' . date('Y-m-d') . '.log';
        file_put_contents($filename, $logText, FILE_APPEND);
    }
}

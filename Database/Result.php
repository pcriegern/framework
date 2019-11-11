<?php

namespace Wlec\Framework\Database;

class Result implements \IteratorAggregate {

	/** @var \PDOStatement */
	private $statement;

	function __construct(\PDOStatement $statement) {
		$this->statement = $statement;
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
	}

	public function getIterator() {
		return $this->statement;
	}

	/**
	 * Returns the number of rows found
	 * @return int
	 */
	public function numRows() {
		return $this->statement->rowCount();
	}

	/**
	 * Fetches a record as associative array
	 * @return array
	 */
	public function getHash() {
		return $this->statement->fetch(\PDO::FETCH_ASSOC);
	}

	/**
	 * Fetches a record as numeric array
	 * @return array
	 */
	public function getArray() {
		return $this->statement->fetch(\PDO::FETCH_NUM);
	}

	/**
	 * Fetches a record as object
	 * @param string $className
	 * @return \stdClass
	 */
	public function getObject($className = "stdClass") {
		return $this->statement->fetchObject($className);
	}

	/**
	 * Fetches a singe value
	 * @param int $key column number
	 * @return mixed
	 */
	public function getValue( $key = 0 ) {
		$erg = $this->getArray();
		return $erg[$key];
	}

	/**
	 * Fetch all records in two dimensional accociative array
	 * @param string $key
	 * @return array
	 */
	public function getTable( $key = NULL ) {
		$erg = array();

		if( $key ) while( $row = $this->getHash()) $erg[$row[$key]] = $row;
		else while( $row = $this->getHash()) $erg[] = $row;

		return $erg;
	}

	/**
	 * @return array
	 */
	public function getHashes() {
		$erg = [];

		$row = $this->getTable();
		if ($row) {
			$keys = array_keys($row[0]);
			if ($keys) {
				$pkey = $keys[0];
				$key2 = $keys[1];
				foreach ($row as $line) {
					$erg[$line[$pkey]] = $line[$key2];
				}
			}
		}

		return $erg;
	}

	/**
	 * Fetch all records in two dimensional numeric array
	 * @param int $key
	 * @return array
	 */
	public function getArrays( $key = NULL ) {
		$erg = array();

		if( $key ) while( $row = $this->getArray()) $erg[$row[$key]] = $row;
		else while( $row = $this->getArray()) $erg[] = $row;

		return $erg;
	}

	/**
	 * Fetch all records in array of objects
	 * @param string $key
	 * @param string $className
	 * @return array
	 */
	public function getObjects( $key = NULL, $className = "stdClass") {
		$erg = array();

		if( $key ) while( $row = $this->getObject($className)) $erg[$row->{$key}] = $row;
		else while( $row = $this->getObject($className)) $erg[] = $row;

		return $erg;
	}

	/**
	 * Fetches a list of values
	 * @param int $key
	 * @return array
	 */
	public function getValues( $key = 0 ) {
		$erg = array();

		while( $row = $this->statement->fetch(\PDO::FETCH_BOTH))
			$erg[] = $row[$key];

		return $erg;
	}

	/**
	 * Fetches the two given columns into an associative array
	 * The first column is used as value the second one as key
	 * @param string $value
	 * @param string $key
	 * @return array
	 */
	public function relate( $value = 'name', $key = 'id' ) {
		$erg = array();

		while( $row = $this->getHash())
			$erg[$row[$key]] = $row[$value];

		return $erg;
	}
}

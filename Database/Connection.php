<?php

namespace wlec\Framework\Database;

require_once __DIR__ . '/Result.php';
require_once __DIR__ . '/../Logging/Logging.php';

use wlec\Framework\Logging\Logging;

class Connection {

    /** @var \PDO */
    protected $connection;

    /** @var string */
    private $conString;

    /**
     * @var int
     */
    public $queryCount = 0;

    /**
     * @var int
     */
    public $queryTime = 0;

   /**
     * @var int
     */
    public $resultCount = 0;


    /**
     * @vars
     */
    public $projectName;

    /**
     * Connection constructor.
     * @param $db
     * @param $user
     * @param $pass
     * @param string $host
     * @param int $port
     */
    public function __construct( $db, $user, $pass, $host = 'localhost', $port = 5432 ) {
        if (empty($db)) {
            return;
        }
        $this->projectName = $db;
        if( empty( $port )) $port = 5432;
        if( empty( $host )) $host = 'localhost';
        $this->conString = "pgsql:host=$host;port=$port;user=$user;password=$pass;dbname=";
        $this->connection = new \PDO( $this->conString.$db );
    }

	/**
	 * @param $db
	 */
	public function changeDb( $db ) {
		$this->connection = new \PDO( $this->conString.$db );
	}

	/**
	 * Queries the database using prepared statements,
	 * use ? as placeholder for parameters
	 *
	 * Example:
	 *   $db->query("SELECT * FROM user WHERE name = ?", $username );
	 *
	 * @param $sql
	 * @param null $params
	 * @return \wlec\Framework\Database\Result
	 * @throws \Exception
	 */
	public function query($sql, $params = null) {
		if (!is_array($params) && $params !== null) {
			$params = array_slice(func_get_args(), 1 );
		}

		$this->queryCount++;
		$start = microtime(true);

		$statement = $this->connection->prepare( $sql );

        $res = $statement->execute($params);
        $this->resultCount += $statement->rowCount();

        $logging = new Logging('db_' . $this->projectName);

        try {
            if ($res) {
                $this->queryTime += microtime(true) - $start;

                return new Result($statement);
            } else {
                $this->queryTime += microtime(true) - $start;

                if ($logging instanceof Logging) {
                    $logging->addToLogfile(sprintf("SQL Error: %s\nIn Query: %s", print_r($statement->errorInfo(), 1), $sql));
                }

                throw new \Exception(sprintf("SQL Error: %s\nIn Query: %s", print_r($statement->errorInfo(), 1), $sql), 0, null);
            }
        } catch (\PDOException $e) {
            if ($logging instanceof Logging) {
                $logging->addToLogfile(print_r($e->getMessage(), true));
            }
        }
	}

	/**
	 * Get the last insert id
	 * @return string
	 */
	public function id() {
		return $this->connection->lastInsertId();
	}

    /**
     * @return \PDO
     */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * @param $str
	 * @return mixed
	 */
	public function quote($str) {
		return str_replace("'", "''", $str);
	}

	/**
	 * @param $table
	 * @param $data
	 * @return string
	 */
	public function insertQuery($table, $data) {
		$insertData = [];
		foreach ($data as $k => $v) {
			if ($v !== null && $v !== '') {
				$insertData[$k] = self::quote($v);
			}
		}
		$query = "insert into $table (" . join(',', array_keys($insertData)) . ") values ('" . join("','", $insertData) . "') RETURNING id;";
		return $query;
	}

	/**
	 * @param $table
	 * @param $item
	 * @param null $id
	 * @return string
	 */
	public function updateQuery($table, $item, $id = null) {
		if (empty($id)) {
			$id = $item['id'];
		}
		if (!is_numeric($id)) {
			return 'ERROR: ID missing!';
		}
		$query = "update $table set ";
		foreach ($item as $k => $v) {
			if ($k != 'id') {
				$query .= "$k=" . (strlen($v) ? "'" . self::quote($v) . "'" : 'NULL') . ',';
			}
		}
		$query = substr($query, 0, -1) . " where id=" . $id . ';';
		return $query;
	}

    /**
     * Gibt einen SQl Query zum löschen eines DB Eintrags zurück
     *
     * @param string $table
     * @param int    $id
     * @return string
     */
    public function deleteQuery($table, $id) {
        if (!is_numeric($id)) {
            return 'ERROR: ID missing!';
        }
        $query = 'DELETE FROM ' . $table . ' WHERE id = ' . (int)$id;

        return $query;
    }

    /**
     * Löscht einen DB Eintrag
     *
     * @param string $table
     * @param int $id
     * @throws \Exception
     */
    public function deleteObject($table, $id) {
        $this->query($this->deleteQuery($table, $id));
    }
    
	/**
	 * @param $table
	 * @param $data
	 * @return string
	 * @throws \Exception
	 */
	public function insertObject ( $table, $data ) {
		$query = $this->insertQuery($table, $data);
		return $this->query($query)->getValue();
	}

	/**
	 * @param $table
	 * @param $data
	 * @param null $id
	 * @return \wlec\Framework\Database\Result
	 * @throws \Exception
	 */
	public function updateObject ( $table, $data, $id = null) {
		return $this->query($this->updateQuery($table, $data, $id));
	}

    /**
     * Transaction BEGIN
     */
	public function begin() {
	    $this->query('BEGIN;');
	}

    /**
     * Transaction COMMIT
     */
	public function commit() {
	    $this->query('COMMIT;');
	}

    /**
     * Transaction Rollback
     */
	public function rollback() {
	    $this->query('ROLLBACK;');
	}
}

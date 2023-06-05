<?php

require_once __DIR__ . '/Factory.php';

class Database
{
    /** @var null|PDO $db */
    private $db = null;
    /** @var string $exception */
    private $exception = '';

    private static $database = null;

    public static function getInstance()
    {
        if (self::$database === null) {
            self::$database = new Database(DBNAME, HOST, USER, PASS);
        }
        return self::$database;
    }

    /** Database constructor. */
    public function __construct(
        $dbname = null,
        $host = null,
        $user = null,
        $pass = null,
        $port = null
    ) {
        try {
            if ($dbname === null || $host === null || $user === null
                || $pass === null
                || $port === null
            ) {
                $this->db = new PDO(
                    'mysql:dbname=' . DBNAME . ';host=' . HOST . ';port='
                    . PORT,
                    USER,
                    PASS,
                    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'")
                );
            } else {
                $this->db = new PDO(
                    'mysql:dbname=' . $dbname . ';host=' . $host . ';port='
                    . $port,
                    $user,
                    $pass,
                    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'")
                );
            }
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $this->exception = $e->getMessage();
        }
    }

    /** @var string $query */
    private $query = '';
    /** @var null */
    private $result = null;

    /**
     * SELECT method returns an array of objects for the given class name
     *
     * @param $class  - the name of the class.
     * @param $query  - sql query without parameters.
     * @param $params - an array of parameters to bind to the query.
     *
     * @return $class[] - array of objects.
     */
    public function select($class, $query, $params = [])
    {
        $result = $this->db->prepare($query);

        foreach ($params as $key => &$val) {
            $result->bindParam($key, $val);
        }
        $result->execute();

        Factory::autoload($class);
        $result->setFetchMode(PDO::FETCH_CLASS, $class);
        $arr = [];
        while ($object = $result->fetch()) {
            array_push($arr, $object);
        }
        return $arr;
    }

    /**
     * SELECT method returns records in the form of an associative array
     *
     * @param $query  - sql query without parameters
     * @param $params - an array of parameters to bind to the query
     *
     * @return mixed
     */
    public function selectQuery($query, $params = [])
    {
        $result = $this->db->prepare($query);

        foreach ($params as $key => &$val) {
            $result->bindParam($key, $val);
        }

        $result->execute();
        $result = $result->fetch();
        return $result;
    }

    /**
     * INSERT method that enters data into the database
     * if a query_select query is given, it is first checked whether
     * the record exists, and if it exists, nothing is written to the database
     *
     * @param $class - class name
     * @param $query_insert
     * @param $query_select
     * @param $params_insert
     * @param $params_select
     *
     * @return bool
     */
    public function insert(
        $class,
        $query_insert,
        $params_insert = [],
        $query_select = '',
        $params_select = []
    ) {
        if (strlen($query_select) > 0) {
            if (count(self::select($class, $query_select, $params_select))
                > 0
            ) {
                return false;
            }
        }
        $this->result = $this->db->prepare($query_insert);

        foreach ($params_insert as $key => &$val) {
            $this->result->bindParam($key, $val);
        }

        return $this->result->execute();
    }

    /**
     * UPDATE method updates data in the database
     * if a query_select query is given, it is first checked whether
     * the record exists, and if it does not exist, it is not updated
     *
     * @param $class - class name
     * @param $query_update
     * @param $query_select
     * @param $params_update
     * @param $params_select
     *
     * @return bool
     */
    public function update(
        $class,
        $query_update,
        $params_update,
        $query_select = '',
        $params_select = []
    ) {
        if (strlen($query_select) > 0) {
            $arr = self::select($class, $query_select, $params_select);
            if (count($arr) === 0) {
                return null;
            }
        }
        $this->result = $this->db->prepare($query_update);

        foreach ($params_update as $key => &$val) {
            $this->result->bindParam($key, $val);
        }
        $this->result->execute();

        $arr = [];
        if (strlen($query_select) > 0) {
            $arr = self::select($class, $query_select, $params_select);
            if (count($arr) === 0) {
                return null;
            }
        }
        foreach ($arr as $a) {
            return $a;
        }
        return null;
    }

    /**
     * DELETE method deletes all data from the database for the specified query
     *
     * @param $table
     * @param $query
     * @param $params_delete
     *
     * @return bool
     */
    public function delete($query, $params_delete = [])
    {
        $result = $this->db->prepare($query);

        foreach ($params_delete as $key => &$val) {
            $result->bindParam($key, $val);
        }
        $result->execute();
        return true;
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Initiates a transaction.
     *
     * @link http://php.net/manual/en/pdo.begintransaction.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Commits a transaction.
     *
     * @link http://php.net/manual/en/pdo.commit.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Rolls back a transaction.
     *
     * @link http://php.net/manual/en/pdo.rollback.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function rollBack()
    {
        return $this->db->rollBack();
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Returns the ID of the last inserted row or sequence value
     *
     * @link http://php.net/manual/en/pdo.lastinsertid.php
     *
     * @param string $name [optional] <p>
     *                     Name of the sequence object from which the ID should be returned.
     *                     </p>
     *
     * @return string If a sequence name was not specified for the <i>name</i>
     * parameter, <b>PDO::lastInsertId</b> returns a
     * string representing the row ID of the last row that was inserted into
     * the database.
     * </p>
     * <p>
     * If a sequence name was specified for the <i>name</i>
     * parameter, <b>PDO::lastInsertId</b> returns a
     * string representing the last value retrieved from the specified sequence
     * object.
     * </p>
     * <p>
     * If the PDO driver does not support this capability,
     * <b>PDO::lastInsertId</b> triggers an
     * IM001 SQLSTATE.
     */
    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return $this->db->lastInsertId();
        } else {
            return $this->db->lastInsertId($name);
        }
    }
}
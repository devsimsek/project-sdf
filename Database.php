<?php

/**
 * smskSoft Database Library
 * This file is taken from the project spf (Closed Source)
 * Copyright smskSoft, mtnsmsk, devsimsek, Metin Şimşek.
 * @package     SDF Library Dist
 * @subpackage  Database
 * @file        Database.php
 * @version     v1.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2021, smskSoft, mtnsmsk
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/blob/libraries/Database.php
 * @since       Version 1.0
 * @filesource
 */
class Database extends SDF\Library
{
    /**
     * @var PDO
     */
    protected $dbh;

    /**
     * @var
     */
    private $stmt;

    /**
     * Database constructor.
     */
    public function __construct($hostname, $database, $user, $password)
    {
        // Set DSN
        $dsn = 'mysql:host=' . $hostname . ';dbname=' . $database;
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        // Create a new PDO instance
        try {
            $this->dbh = new PDO ($dsn, $user, $password, $options);
            $this->dbh->exec("SET NAMES utf8");
        }        // Catch any errors
        catch (PDOException $e) {
            $this->error = $e->getMessage();
        }
    }

    /**
     * Prepare statement with query
     * @param $query
     */
    public function query($query)
    {
        $this->stmt = $this->dbh->prepare($query);
    }

    /**
     * Bind values
     * @param $param
     * @param $value
     * @param null $type
     */
    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value) :
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value) :
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value) :
                    $type = PDO::PARAM_NULL;
                    break;
                default :
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Execute the prepared statement
     * @return mixed
     */
    public function execute(array $arr = null)
    {
        if ($arr != null)
            return $this->stmt->execute($arr);
        return $this->stmt->execute();
    }

    /**
     * Get result set as array of objects
     * @return mixed
     */
    public function resultset()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get single record as object
     * @return mixed
     */
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Get record row count
     * @return mixed
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Returns the last inserted ID
     * @return string
     */
    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }
}

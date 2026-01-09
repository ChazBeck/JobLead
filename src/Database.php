<?php

class Database {
    private $connection;

    public function __construct() {
        $this->connect();
    }

    public function connect() {
        // Try to connect with socket first (for CLI/local), fallback to standard connection
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                null,
                defined('DB_SOCKET') ? DB_SOCKET : null
            );
        } catch (Exception $e) {
            // Fallback to standard connection without socket
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );
        }

        if ($this->connection->connect_error) {
            throw new Exception('Connection Failed: ' . $this->connection->connect_error);
        }

        $this->connection->set_charset(DB_CHARSET);
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

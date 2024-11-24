<?php
class Database {
    private $host = 'localhost';
    private $username = 'root';  // Sesuaikan dengan username database Anda
    private $password = '';      // Sesuaikan dengan password database Anda
    private $database = 'e_learning';
    public $conn;

    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host=$this->host;dbname=$this->database", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
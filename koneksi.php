<?php
class Koneksi {
    private $hostName = "localhost";
    private $userName = "root";
    private $password = "";
    private $dbName = "e_learning";
    private $koneksi;

    public function __construct() {
        // Buat koneksi
        $this->koneksi = mysqli_connect($this->hostName, $this->userName, $this->password, $this->dbName);

        // Periksa koneksi
        if (!$this->koneksi) {
            die(json_encode([
                "success" => false,
                "message" => "Koneksi gagal: " . mysqli_connect_error()
            ]));
        }
    }

    public function getKoneksi() {
        return $this->koneksi;
    }

    public function tutupKoneksi() {
        if ($this->koneksi) {
            mysqli_close($this->koneksi);
        }
    }
}
?>

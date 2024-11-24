<?php
require_once 'dbconfig.php';

class Auth {
    private $db;
    private $error;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        // session_start() seharusnya dipanggil di file login.php
    }

   public function login($nama, $password) {
    try {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE nama = :nama");
        $stmt->bindParam(":nama", $nama);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role_user'] = $user['role_user'];
            return true;
        } else {
            $this->error = "Nama atau password salah";
            return false;
        }
    } catch (PDOException $e) {
        $this->error = "Error: " . $e->getMessage();
        return false;
    }
}


    public function isLoggedIn() {
        return isset($_SESSION['id']);
    }
    
    

    public function getUserRole() {
        return isset($_SESSION['role_user']) ? $_SESSION['role_user'] : null;
    }

    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            try {
                $stmt = $this->db->prepare("SELECT id, nama, email, role_user FROM users WHERE id = :id");
                $stmt->bindParam(":id", $_SESSION['id']);
                $stmt->execute();
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                $this->error = "Error: " . $e->getMessage();
                return false;
            }
        }
        return null; // Kembalikan null jika tidak ada sesi
    }

    public function logout() {
        // Hapus semua data sesi
        session_unset();
        session_destroy();
        return true;
    }

    public function getLastError() {
        return $this->error;
    }

    // Fungsi untuk memeriksa akses berdasarkan sebagai
    public function checkAccess($allowed_role = []) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $role = $this->getUserRole();
        return in_array($role, $allowed_role);
    }
}
?>

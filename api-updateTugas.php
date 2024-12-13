<?php
header('Content-Type: application/json');
require_once 'Koneksi.php';

class UpdateTugas {
    private $koneksi;
    
    public function __construct() {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    public function update() {
        try {
            // Cek method request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            // Ambil data dari request
            $id_tugas = isset($_POST['id_tugas']) ? mysqli_real_escape_string($this->koneksi, $_POST['id_tugas']) : null;
            $id_guru = isset($_POST['id_guru']) ? mysqli_real_escape_string($this->koneksi, $_POST['id_guru']) : null;
            $judul_tugas = isset($_POST['judul_tugas']) ? mysqli_real_escape_string($this->koneksi, $_POST['judul_tugas']) : null;
            $deskripsi = isset($_POST['deskripsi']) ? mysqli_real_escape_string($this->koneksi, $_POST['deskripsi']) : null;
            $id_kelas = isset($_POST['id_kelas']) ? mysqli_real_escape_string($this->koneksi, $_POST['id_kelas']) : null;
            $deadline = isset($_POST['deadline']) ? mysqli_real_escape_string($this->koneksi, $_POST['deadline']) : null;

            // Validasi input
            if (!$id_tugas || !$id_guru || !$judul_tugas || !$id_kelas || !$deadline) {
                throw new Exception('Data tidak lengkap');
            }

            // Handle file upload jika ada
            $file_path = null;
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/tugas/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Validasi tipe file
                $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
                $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_extension, $allowed_types)) {
                    throw new Exception('Tipe file tidak diizinkan');
                }

                // Generate nama file unik
                $file_name = uniqid() . '_' . date('Ymd') . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;

                // Upload file
                if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
                    $file_path = $target_path;
                } else {
                    throw new Exception('Gagal mengupload file');
                }
            }

            // Buat query update
            if ($file_path) {
                // Update dengan file baru
                $query = "UPDATE materi SET 
                        judul_tugas = '$judul_tugas',
                        deskripsi = '$deskripsi',
                        deadline = '$deadline',
                        jenis_materi= '$jenis_materi',
                        updated_at = NOW()
                        WHERE id_tugas = $id_tugas 
                        AND id_guru = $id_guru 
                        AND id_kelas = $id_kelas";
            } else {
                // Update tanpa file
                $query = "UPDATE materi SET 
                        judul_tugas = '$judul_tugas',
                        deskripsi = '$deskripsi',
                        deadline = '$deadline',
                        updated_at = NOW()
                        WHERE id_tugas = $id_tugas 
                        AND id_guru = $id_guru 
                        AND id_kelas = $id_kelas";
            }

            // Eksekusi query
            $result = mysqli_query($this->koneksi, $query);

            if ($result) {
                // Cek apakah ada baris yang terupdate
                if (mysqli_affected_rows($this->koneksi) > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Tugas berhasil diperbarui'
                    ]);
                } else {
                    throw new Exception('Tidak ada data yang diperbarui');
                }
            } else {
                throw new Exception('Gagal memperbarui tugas: ' . mysqli_error($this->koneksi));
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } finally {
            // Tutup koneksi
            if (isset($db)) {
                $db->tutupKoneksi();
            }
        }
    }
}

// Jalankan update
$updateTugas = new UpdateTugas();
$updateTugas->update();
?>
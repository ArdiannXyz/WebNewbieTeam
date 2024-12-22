<?php
declare(strict_types=1);

header('Content-Type: application/json');
require_once 'Koneksi.php';

class UpdateTugas {
    private $koneksi;
    private $upload_dir = 'uploads/tugas/';
    private $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
    
    public function __construct() {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    private function validateInput($data): array {
        $validated = [];
        
        // Validasi ID dan data numerik
        $validated['id_tugas'] = filter_var($data['id_tugas'] ?? null, FILTER_VALIDATE_INT);
        $validated['id_guru'] = filter_var($data['id_guru'] ?? null, FILTER_VALIDATE_INT);
        $validated['id_mapel'] = filter_var($data['id_mapel'] ?? null, FILTER_VALIDATE_INT);
        $validated['id_kelas'] = filter_var($data['id_kelas'] ?? null, FILTER_VALIDATE_INT);
        
        // Validasi string input
        $validated['judul_tugas'] = trim($data['judul_tugas'] ?? '');
        $validated['deskripsi'] = trim($data['deskripsi'] ?? '');
        $validated['deadline'] = trim($data['deadline'] ?? '');

        // Validasi data wajib
        if (!$validated['id_tugas'] || !$validated['id_guru'] || 
            !$validated['id_kelas'] || !$validated['judul_tugas'] || 
            !$validated['deadline'] || !$validated['id_mapel']) {
            throw new Exception('Data tidak lengkap atau tidak valid');
        }

        // Validasi format dan logika deadline
        $deadline_timestamp = strtotime($validated['deadline']);
        if (!$deadline_timestamp) {
            throw new Exception('Format deadline tidak valid');
        }
        
        $validated['deadline'] = date('Y-m-d H:i:s', $deadline_timestamp);

        return $validated;
    }

    private function validateTeacherClass($id_guru, $id_kelas, $id_mapel): void {
        $stmt = $this->koneksi->prepare("
            SELECT COUNT(*) as is_teaching 
            FROM kelas_mapel 
            WHERE id_guru = ? AND id_kelas = ? AND id_mapel = ?
        ");
        
        $stmt->bind_param("iii", $id_guru, $id_kelas, $id_mapel);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['is_teaching'] == 0) {
            throw new Exception('Guru tidak mengajar mata pelajaran ini di kelas tersebut');
        }
        
        $stmt->close();
    }

    private function handleFileUpload(): ?string {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if (!file_exists($this->upload_dir)) {
            if (!mkdir($this->upload_dir, 0755, true)) {
                throw new Exception('Gagal membuat direktori upload');
            }
        }

        $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $this->allowed_types)) {
            throw new Exception('Tipe file tidak diizinkan. Tipe yang diizinkan: ' . implode(', ', $this->allowed_types));
        }

        if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
            throw new Exception('Ukuran file terlalu besar (maksimal 10MB)');
        }

        $original_filename = $_FILES['file']['name'];
        $file_name = sprintf('%s_%s_%s.%s', 
            uniqid('tugas_', true),
            date('Ymd_His'),
            substr(md5($original_filename), 0, 8),
            $file_extension
        );
        $target_path = $this->upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
            throw new Exception('Gagal mengupload file');
        }

        return $target_path;
    }

    private function updateTugas(array $data, ?string $file_path): array {
        $this->koneksi->begin_transaction();

        try {
            // Validasi akses guru ke kelas dan mapel
            $this->validateTeacherClass($data['id_guru'], $data['id_kelas'], $data['id_mapel']);

            // Ambil data tugas lama untuk file
            $stmt = $this->koneksi->prepare("
                SELECT file_tugas, is_active 
                FROM tugas 
                WHERE id_tugas = ? AND id_guru = ?
            ");
            $stmt->bind_param("ii", $data['id_tugas'], $data['id_guru']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Tugas tidak ditemukan atau Anda tidak memiliki akses');
            }
            
            $old_data = $result->fetch_assoc();
            if (!$old_data['is_active']) {
                throw new Exception('Tugas sudah tidak aktif');
            }

            // Update query
            $query = "UPDATE tugas SET 
                    judul_tugas = ?,
                    deskripsi = ?,
                    deadline = ?,
                    " . ($file_path ? "file_tugas = ?," : "") . "
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id_tugas = ? 
                    AND id_guru = ? 
                    AND is_active = 1";

            $stmt = $this->koneksi->prepare($query);
            if (!$stmt) {
                throw new Exception('Gagal mempersiapkan query: ' . $this->koneksi->error);
            }

            if ($file_path) {
                $stmt->bind_param("ssssii", 
                    $data['judul_tugas'],
                    $data['deskripsi'],
                    $data['deadline'],
                    $file_path,
                    $data['id_tugas'],
                    $data['id_guru']
                );
            } else {
                $stmt->bind_param("sssii", 
                    $data['judul_tugas'],
                    $data['deskripsi'],
                    $data['deadline'],
                    $data['id_tugas'],
                    $data['id_guru']
                );
            }

            if (!$stmt->execute()) {
                throw new Exception('Gagal mengupdate tugas: ' . $stmt->error);
            }

            if ($stmt->affected_rows === 0) {
                throw new Exception('Tidak ada perubahan pada data');
            }

            // Hapus file lama jika ada file baru
            if ($file_path && $old_data['file_tugas'] && file_exists($old_data['file_tugas'])) {
                unlink($old_data['file_tugas']);
            }

            $this->koneksi->commit();
            
            return [
                'id_tugas' => $data['id_tugas'],
                'id_kelas' => $data['id_kelas'],
                'judul_tugas' => $data['judul_tugas'],
                'deskripsi' => $data['deskripsi'],
                'deadline' => $data['deadline'],
                'file_tugas' => $file_path ?? $old_data['file_tugas']
            ];

        } catch (Exception $e) {
            $this->koneksi->rollback();
            throw $e;
        }
    }

    public function update(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }

            $validated_data = $this->validateInput($_POST);
            $file_path = $this->handleFileUpload();
            $updated_data = $this->updateTugas($validated_data, $file_path);

            echo json_encode([
                'status' => 'sukses',
                'pesan' => 'Tugas berhasil diperbarui',
                'tugas_data' => $updated_data
            ]);

        } catch (Exception $e) {
            $http_code = $e->getCode() ?: 500;
            http_response_code($http_code);
            
            echo json_encode([
                'status' => 'gagal',
                'pesan' => $e->getMessage(),
                'tugas_data' => $validated_data 
            ]);
            
        } finally {
            if (isset($this->koneksi)) {
                $this->koneksi->close();
            }
        }
    }
}

// Jalankan update
$updateTugas = new UpdateTugas();
$updateTugas->update();


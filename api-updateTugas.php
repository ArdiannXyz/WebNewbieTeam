<?php
declare(strict_types=1);

header('Content-Type: application/json');
require_once 'Koneksi.php';

class UpdateTugas {
    private $koneksi;
    private $upload_dir = 'uploads/tugas/';
    private $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    
    public function __construct() {
        $db = new Koneksi();
        $this->koneksi = $db->getKoneksi();
    }

    private function validateInput($data): array {
        $validated = [];
        
        $validated['id_tugas'] = filter_var($data['id_tugas'] ?? null, FILTER_VALIDATE_INT);
        $validated['id_guru'] = filter_var($data['id_guru'] ?? null, FILTER_VALIDATE_INT);
        $validated['id_kelas'] = filter_var($data['id_kelas'] ?? null, FILTER_VALIDATE_INT);
        
        $validated['judul_tugas'] = trim($data['judul_tugas'] ?? '');
        $validated['deskripsi'] = trim($data['deskripsi'] ?? '');
        $validated['deadline'] = trim($data['deadline'] ?? '');

        // Validasi data wajib
        if (!$validated['id_tugas'] || !$validated['id_guru'] || 
            !$validated['id_kelas'] || !$validated['judul_tugas'] || 
            !$validated['deadline']) {
            throw new Exception('Data tidak lengkap atau tidak valid');
        }

        // Validasi format tanggal deadline
        if (!strtotime($validated['deadline'])) {
            throw new Exception('Format deadline tidak valid');
        }

        return $validated;
    }

    private function handleFileUpload(): ?string {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Buat direktori jika belum ada
        if (!file_exists($this->upload_dir)) {
            if (!mkdir($this->upload_dir, 0755, true)) {
                throw new Exception('Gagal membuat direktori upload');
            }
        }

        // Validasi tipe file
        $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $this->allowed_types)) {
            throw new Exception('Tipe file tidak diizinkan. Tipe yang diizinkan: ' . implode(', ', $this->allowed_types));
        }

        // Validasi ukuran file (max 10MB)
        if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
            throw new Exception('Ukuran file terlalu besar (maksimal 10MB)');
        }

        // Generate nama file yang aman
        $file_name = sprintf('%s_%s.%s', 
            uniqid('tugas_', true),
            date('Ymd'),
            $file_extension
        );
        $target_path = $this->upload_dir . $file_name;

        // Upload file dengan validasi mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['file']['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ];

        if (!in_array($mime_type, $allowed_mimes)) {
            throw new Exception('Tipe file tidak valid');
        }

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
            throw new Exception('Gagal mengupload file');
        }

        return $target_path;
    }

    private function updateTugas(array $data, ?string $file_path): void {
        // Prepare statement untuk mencegah SQL injection
        $query = "UPDATE tugas SET 
                judul_tugas = ?,
                deskripsi = ?,
                deadline = ?,
                " . ($file_path ? "file_tugas = ?," : "") . "
                updated_at = NOW()
                WHERE id_tugas = ? 
                AND id_guru = ? 
                AND id_kelas = ?";

        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            throw new Exception('Gagal mempersiapkan query: ' . $this->koneksi->error);
        }

        // Bind parameter
        if ($file_path) {
            $stmt->bind_param("ssssiis", 
                $data['judul_tugas'],
                $data['deskripsi'],
                $data['deadline'],
                $file_path,
                $data['id_tugas'],
                $data['id_guru'],
                $data['id_kelas']
            );
        } else {
            $stmt->bind_param("sssiis", 
                $data['judul_tugas'],
                $data['deskripsi'],
                $data['deadline'],
                $data['id_tugas'],
                $data['id_guru'],
                $data['id_kelas']
            );
        }

        if (!$stmt->execute()) {
            throw new Exception('Gagal mengeksekusi query: ' . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception('Tidak ada data yang diperbarui');
        }

        $stmt->close();
    }

    public function update(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            // Validasi input
            $validated_data = $this->validateInput($_POST);

            // Handle file upload jika ada
            $file_path = $this->handleFileUpload();

            // Update data tugas
            $this->updateTugas($validated_data, $file_path);

            echo json_encode([
                'success' => true,
                'message' => 'Tugas berhasil diperbarui'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
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

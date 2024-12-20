<?php
// File: api.php

require_once 'koneksi.php';

// Get the action from the request
$action = $_GET['action'] ?? '';

// Switch statement to handle different actions
switch ($action) {
    case 'updateMateri':
        updateMateri();
        break;
    case 'getMateriById':
        getMateriById();
        break;
    case 'deleteMateri':
        deleteMateri();
        break;
    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        break;
}

// Function to update materi
function updateMateri() {
    // Create a connection to the database
    $db = new Koneksi();
    $conn = $db->getKoneksi();

    // Get the input data from POST request
    $id_tugas = $_POST['id_tugas'] ?? '';
    $judul_tugas = $_POST['judul_tugas'] ?? '';
    $jenis_materi = $_POST['jenis_materi'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $id_kelas = $_POST['id_kelas'] ?? '';
    $tanggal_dibuat = $_POST['tanggal_dibuat'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    $video_url = $_POST['video_url'] ?? '';

    // Validate required fields
    if (empty($id_tugas) || empty($judul_tugas) || empty($jenis_materi) || empty($deskripsi) || empty($id_kelas) || empty($tanggal_dibuat) || empty($deadline) || empty($video_url)) {
        echo json_encode(["success" => false, "message" => "All fields must be filled"]);
        return;
    }

    // Prepare the SQL query to update the materi
    $sql = "UPDATE materi SET 
            judul_tugas = ?, 
            jenis_materi = ?, 
            deskripsi = ?, 
            id_kelas = ?, 
            tanggal_dibuat = ?, 
            deadline = ?, 
            video_url = ? 
            WHERE id_tugas = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $judul_tugas, $jenis_materi, $deskripsi, $id_kelas, $tanggal_dibuat, $deadline, $video_url, $id_tugas);

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Materi updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update materi"]);
    }
}

// Function to get materi by ID
function getMateriById() {
    // Create a connection to the database
    $db = new Koneksi();
    $conn = $db->getKoneksi();

    // Get the materi ID
    $id_tugas = $_GET['id_tugas'] ?? '';

    // Validate input
    if (empty($id_tugas)) {
        echo json_encode(["success" => false, "message" => "id_tugas is required"]);
        return;
    }

    // Prepare the SQL query to fetch the materi
    $sql = "SELECT * FROM materi WHERE id_tugas = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_tugas);

    // Execute the query and fetch the result
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the materi data
        $materi = $result->fetch_assoc();
        echo json_encode(["success" => true, "materi" => $materi]);
    } else {
        echo json_encode(["success" => false, "message" => "Materi not found"]);
    }
}

// Function to delete materi
function deleteMateri() {
    // Create a connection to the database
    $db = new Koneksi();
    $conn = $db->getKoneksi();

    // Get the materi ID from POST request
    $id_tugas = $_POST['id_tugas'] ?? '';

    // Validate input
    if (empty($id_tugas)) {
        echo json_encode(["success" => false, "message" => "id_tugas is required"]);
        return;
    }

    // Prepare the SQL query to delete the materi
    $sql = "DELETE FROM materi WHERE id_tugas = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_tugas);

    // Execute the query and check if the deletion was successful
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Materi deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete materi"]);
    }
}
?>

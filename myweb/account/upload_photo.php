<?php
session_start();
require __DIR__ . '/../service/database.php';

// Redirect jika tidak login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Anda harus login terlebih dahulu";
    header("Location: ../login.php");
    exit();
}

// Cek apakah file diupload
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['photo'])) {
    $_SESSION['error'] = "Permintaan tidak valid";
    header("Location: ../dashboard.php");
    exit();
}

// Konfigurasi upload
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 2 * 1024 * 1024; // 2MB
$upload_dir = __DIR__ . '/../uploads/';
$user_id = $_SESSION['user_id'];

try {
    // Validasi file
    $file = $_FILES['photo'];
    
    // Cek error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error upload file: " . $file['error']);
    }
    
    // Validasi tipe file
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime, $allowed_types)) {
        throw new Exception("Hanya file JPG, PNG, atau GIF yang diperbolehkan");
    }
    
    // Validasi ukuran
    if ($file['size'] > $max_size) {
        throw new Exception("Ukuran file maksimal 2MB");
    }
    
    // Generate nama file aman
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = sprintf("user_%d_%s.%s", 
        $user_id, 
        bin2hex(random_bytes(8)), 
        $ext
    );
    $target_path = $upload_dir . $filename;
    
    // Buat folder upload jika belum ada
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Pindahkan file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception("Gagal menyimpan file");
    }
    
    // Update database
    $stmt = $pdo->prepare("UPDATE users SET photo = ? WHERE id = ?");
    $stmt->bindParam(1, $filename, PDO::PARAM_STR);
    $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        // Rollback: hapus file jika gagal update database
        unlink($target_path);
        throw new Exception("Gagal update database");
    }
    
    // Update session
    $_SESSION['photo'] = $filename;
    $_SESSION['success'] = "Foto profil berhasil diupdate!";

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    error_log("Upload Error [User ID: $user_id]: " . $e->getMessage());
}

header("Location: ../dashboard.php");
exit();
?>
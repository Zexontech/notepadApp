<?php
session_start();
require __DIR__ . '/../service/database.php';

// Check if user is logged in and request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Akses ditolak");
}

try {
    // Validate and sanitize input
    $note_id = filter_input(INPUT_POST, 'note_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$note_id || $note_id < 1) {
        throw new Exception("ID catatan tidak valid");
    }

    // Prepare delete statement with user_id check for security
    $stmt = $pdo->prepare("
        DELETE FROM notes 
        WHERE id = :note_id 
        AND user_id = :user_id
    ");

    $stmt->bindValue(':note_id', $note_id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Check if any row was actually deleted
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Catatan berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Catatan tidak ditemukan atau tidak memiliki akses";
        }
    } else {
        $_SESSION['error'] = "Gagal menghapus catatan";
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "Error database: " . $e->getMessage();
} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}


// Redirect back to the notes page
header("Location: ../index.php");
exit();
?>
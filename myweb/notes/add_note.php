<?php
session_start();
require __DIR__ . '/../service/database.php';

// +++ TAMBAHKAN DI BAWAH SESSION START +++
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:;");
ini_set('session.cookie_httponly', 1);
// ini_set('session.cookie_secure', 1); // untuk https
ini_set('session.cookie_samesite', 'Strict');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // +++ SANITASI INPUT +++
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS);
        
        // +++ TRIM DAN VALIDASI +++
        $title = trim($title);
        $content = trim($content);
        
        if(empty($title) || empty($content)) {
            $_SESSION['error'] = "Judul dan konten tidak boleh kosong";
            header("Location: ../index.php");
            exit();
        }
        
        // +++ VALIDASI PANJANG KARAKTER +++
        if(strlen($title) > 100) {
            $_SESSION['error'] = "Judul maksimal 100 karakter";
            header("Location: ../index.php");
            exit();
        }
        
        if(strlen($content) > 1000) {
            $_SESSION['error'] = "Konten maksimal 1000 karakter";
            header("Location: ../index.php");
            exit();
        }

        if (isset($_SESSION['user_id'])) {
            // +++ PREPARED STATEMENT DENGAN TYPE SAFETY +++
            $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, content, created_at, updated_at) 
                                  VALUES (:user_id, :title, :content, NOW(), NOW())");
            
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);

            // +++ EXECUTE DAN HANDLE ERROR +++
            if ($stmt->execute()) {
                $_SESSION['success'] = "Catatan berhasil ditambahkan!";
            } else {
                $_SESSION['error'] = "Gagal menambahkan catatan";
            }
            
            header("Location: ../index.php");
            exit();
            
        } else {
            $_SESSION['error'] = "Silakan login terlebih dahulu";
            header("Location: ../login.php");
            exit();
        }
    }
    
} catch (PDOException $e) {
    // +++ ERROR MESSAGE AMAN +++
    $_SESSION['error'] = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
    error_log("Database Error: " . $e->getMessage()); // Log error ke server
    header("Location: ../index.php");
    exit();
}
?>
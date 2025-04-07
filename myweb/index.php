<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/service/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
    unset($_SESSION['error']);
}


?>

<style>
        .note-card {
            transition: transform 0.2s;
            min-height: 200px;
        }
        .note-card:hover {
            transform: translateY(-5px);
        }
        .created-at {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .btn-danger-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
            }
    </style>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notepad App</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="index.php">Notepad App</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav ms-auto">
            <?php if(isset($_SESSION['user_id'])): ?>
            <!-- Tampilan setelah login -->
            <li class="nav-item">
                <a class="nav-link fw-bold" href="dashboard.php">
                    <?php 
                    // Pastikan username ada di session
                    echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; 
                    ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="btn btn-danger ms-2" href="account/logout.php">Logout</a>
            </li>
            <?php else: ?>
            <!-- Tampilan sebelum login -->
            <li class="nav-item">
                <a class="btn btn-primary" href="account/login.php">Login</a>
            </li>
            <li class="nav-item ms-2">
                <a class="btn btn-success" href="account/register.php">Register</a>
            </li>
            <?php endif; ?>
        </ul>
        </div>
    </div>
    </nav>

  <div class="container mt-5">
    <h1 class="text-center">Selamat Datang di Notepad App</h1>
    <p class="text-center">Aplikasi sederhana untuk mencatat ide dan catatan Anda.</p>
  </div>

  <div class="container py-5">
        <!-- Form Tambah Catatan -->
        <div class="row mb-5">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        Buat Catatan Baru
                    </div>
                    <div class="card-body">
                        <form action="notes/add_note.php" method="post">
                            <div class="mb-3">
                                <label for="noteTitle" class="form-label">Judul</label>
                                <input type="text" class="form-control" id="noteTitle" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="noteContent" class="form-label">Konten</label>
                                <textarea class="form-control" id="noteContent" name="content" rows="3" required></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

       <!-- Daftar Catatan -->
<h2 class="mb-4 text-center">Catatan Anda</h2>
<div class="row row-cols-1 row-cols-md-3 g-4">
    <?php
    // Initialize $notes as empty array
    $notes = [];
    
    try {
        if(isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            
            $stmt = $pdo->prepare("
                SELECT * FROM notes 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch(PDOException $e) {
        echo '<div class="col-12 text-center"><div class="alert alert-danger">Error: '.$e->getMessage().'</div></div>';
    }

    // Display notes or messages
    if(empty($notes)) {
        echo '<div class="col-12 text-center">';
        if(isset($_SESSION['user_id'])) {
            echo '<p class="text-muted">Belum ada catatan</p>';
        } else {
            echo '<div class="alert alert-warning">Silakan login untuk melihat catatan</div>';
        }
        echo '</div>';
    } else {
        foreach($notes as $note) {
            echo '
            <div class="col">
                <div class="card note-card h-100 shadow">
                    <div class="card-body">
                        <h5 class="card-title">'.htmlspecialchars($note['title']).'</h5>
                        <p class="card-text">'.nl2br(htmlspecialchars($note['content'])).'</p>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                        <small class="created-at">
                            Dibuat: '.date('d M Y H:i', strtotime($note['created_at'])).'
                        </small>
                        <form action="notes/delete_note.php" method="POST" class="delete-form">
                            <input type="hidden" name="note_id" value="'.$note['id'].'">
                            <button type="submit" class="btn btn-danger btn-sm" 
                                    onclick="return confirm(\'Yakin ingin menghapus catatan ini?\')">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>';
        }
    }
    ?>
</div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
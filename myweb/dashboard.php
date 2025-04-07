<?php
session_start();
require_once __DIR__ . '/service/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = $error = "";

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Update info
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $bio = trim($_POST['bio']);

    if (!$username) {
        $error = "Username tidak boleh kosong.";
    } else {
        // Check if the username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        $username_exists = $stmt->fetchColumn();

        if ($username_exists) {
            $error = "Username sudah digunakan oleh pengguna lain.";
        } else {
            // Default to updating without photo
            $sql = "UPDATE users SET username = ?, bio = ? WHERE id = ?";
            $params = [$username, $bio, $user_id];
            
            // File upload handling
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                $file_tmp = $_FILES['photo']['tmp_name'];
                $file_name = $_FILES['photo']['name'];
                $file_size = $_FILES['photo']['size'];
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed_ext)) {
                    $error = "Hanya boleh mengupload file JPG, PNG, atau GIF.";
                } elseif ($file_size > 2 * 1024 * 1024) {
                    $error = "Ukuran file maksimal 2MB.";
                } else {
                    $new_name = uniqid() . "." . $ext;
                    $target_path = __DIR__ . "/uploads/" . $new_name;
                    
                    if (move_uploaded_file($file_tmp, $target_path)) {
                        // Update with new photo
                        $sql = "UPDATE users SET username = ?, bio = ?, photo = ? WHERE id = ?";
                        $params = [$username, $bio, $new_name, $user_id];
                    } else {
                        $error = "Gagal mengupload file.";
                    }
                }
            }
            
            // Only proceed with update if no errors
            if (empty($error)) {
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($params);
                
                if ($result) {
                    $success = "Profil berhasil diperbarui.";
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Gagal memperbarui profil.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Notepad App</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
      <a class="navbar-brand" href="#">Notepad App</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="account/logout.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Konten Dashboard -->
  <div class="container mt-5">
    <h1>Dashboard</h1>
    <p>Selamat datang, <?php echo htmlspecialchars($user['username']); ?>!</p>

    <?php if($success): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
      <!-- Tampilkan foto profil -->
      <div class="col-md-4 mb-4">
        <?php if(!empty($user['photo'])): ?>
          <img src="uploads/<?php echo htmlspecialchars($user['photo']); ?>" class="img-fluid rounded" alt="Foto Profil">
        <?php else: ?>
          <img src="https://via.placeholder.com/300" class="img-fluid rounded" alt="Default Profile Picture">
        <?php endif; ?>
      </div>

      <!-- Form edit profil -->
      <div class="col-md-8">
        <form action="" method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="username" class="form-label">Edit Username Kamu Disini!</label>
            <input type="text" name="username" id="username" class="form-control" 
                   value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>" 
                   placeholder="Masukkan username">
          </div>
          <div class="mb-3">
            <label for="bio" class="form-label">Bio</label>
            <textarea name="bio" id="bio" class="form-control" rows="3" 
                      placeholder="Tuliskan bio singkat"><?php echo isset($user['bio']) ? htmlspecialchars($user['bio']) : ''; ?></textarea>
          </div>
          <div class="mb-3">
            <label for="photo" class="form-label">Foto Profil</label>
            <input 
                type="file" 
                name="photo" 
                id="photo" 
                class="form-control"
                accept="image/jpeg, image/png, image/gif"
            >
            <small class="text-muted">Format: JPG/PNG/GIF (Maks. 2MB)</small>
          </div>
          
          <button type="submit" class="btn btn-primary">Perbarui Profil</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
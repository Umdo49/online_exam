<?php
session_start();
require 'db.php';

// Güvenlik: Öğretmen mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$mesaj = "";
$hata = "";

// Geçerli sınavları al (Sadece aktif veya gelecekteki sınavlar değil, hepsi olabilir ki materyal eklenebilsin)
$stmt = $pdo->prepare("SELECT id, title FROM exams WHERE creator_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Form Gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['material'])) {
    $file = $_FILES['material'];
    $exam_id = !empty($_POST['exam_id']) ? $_POST['exam_id'] : null;
    $title = trim($_POST['title']);
    
    // İzin verilen dosya türleri
    $allowed_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
        'image/jpeg', 'image/png'
    ];

    if ($file['error'] === 0) {
        if (in_array($file['type'], $allowed_types)) {
            // Klasör kontrolü
            $uploads_dir = 'uploads/';
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0755, true);
            }

            // Dosya adı güvenliği
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $clean_filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $new_filename = uniqid() . "_" . $clean_filename . "." . $ext;
            $filepath = $uploads_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Veritabanına Kayıt
                $sql = "INSERT INTO exam_materials (exam_id, creator_id, filename, filepath, title) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$exam_id, $user_id, $file['name'], $filepath, $title])) {
                    $mesaj = "Dosya başarıyla yüklendi.";
                } else {
                    $hata = "Veritabanı hatası oluştu.";
                }
            } else {
                $hata = "Dosya sunucuya yüklenemedi.";
            }
        } else {
            $hata = "Geçersiz dosya türü! (Sadece PDF, Word, Excel ve Resim)";
        }
    } else {
        $hata = "Dosya seçilmedi veya yükleme hatası: " . $file['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Materyal Yükle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        
        /* Sidebar */
        .sidebar { width: 260px; height: 100vh; background: #212529; color: #fff; position: fixed; top: 0; left: 0; padding-top: 20px; z-index: 1000; }
        .sidebar-header { padding: 15px 25px; border-bottom: 1px solid #343a40; margin-bottom: 20px; }
        .sidebar a { padding: 12px 25px; text-decoration: none; font-size: 16px; color: #adb5bd; display: block; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #0d6efd; border-radius: 0 25px 25px 0; }
        .sidebar i { width: 25px; }

        .main-content { margin-left: 260px; padding: 30px; }

        /* Kart */
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 30px; }
        
        .form-label { font-weight: 500; color: #495057; }
        .form-control, .form-select { padding: 10px 15px; border-radius: 8px; border: 1px solid #dee2e6; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); border-color: #0d6efd; }

        /* Dosya Yükleme Alanı */
        .file-upload-wrapper {
            position: relative;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        .file-upload-wrapper:hover { border-color: #0d6efd; background: #e9ecef; }
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: 0; top: 0; width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header d-flex align-items-center">
            <i class="fa-solid fa-graduation-cap fa-2x me-2 text-primary"></i>
            <h5 class="m-0 fw-bold">Sınav Sis.</h5>
        </div>
        <a href="dashboard.php"><i class="fa-solid fa-house"></i> Ana Sayfa</a>
        <a href="create_exam.php"><i class="fa-solid fa-plus-circle"></i> Sınav Oluştur</a>
        <a href="manage_exams.php"><i class="fa-solid fa-list-check"></i> Sınavları Yönet</a>
        <a href="student_results.php"><i class="fa-solid fa-chart-line"></i> Öğrenci Sonuçları</a>
        <a href="upload_material.php" class="active"><i class="fa-solid fa-upload"></i> Materyal Yükle</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Materyal Yükle</h2>
                <a href="manage_exams.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
            </div>

            <?php if ($mesaj): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?= $mesaj ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($hata): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= $hata ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card card-custom">
                        <form method="POST" enctype="multipart/form-data">
                            
                            <div class="mb-4">
                                <label class="form-label">Materyal Başlığı</label>
                                <input type="text" name="title" class="form-control" placeholder="Örn: Ders Notları - Hafta 1" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">İlgili Sınav (Opsiyonel)</label>
                                <select name="exam_id" class="form-select">
                                    <option value="">Genel Materyal (Sınavsız)</option>
                                    <?php foreach ($exams as $exam): ?>
                                        <option value="<?= $exam['id'] ?>"><?= htmlspecialchars($exam['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Eğer bu dosya belirli bir sınav içinse buradan seçin.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Dosya Seç</label>
                                <div class="file-upload-wrapper">
                                    <i class="fa-solid fa-cloud-arrow-up fa-3x text-primary mb-3"></i>
                                    <h5>Dosyayı buraya sürükleyin veya tıklayın</h5>
                                    <p class="text-muted small">PDF, Word, Excel, JPG, PNG (Max: 10MB)</p>
                                    <input type="file" name="material" required onchange="showFileName(this)">
                                </div>
                                <div id="fileNameDisplay" class="mt-2 text-success fw-bold text-center"></div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary py-2 fw-bold">
                                    <i class="fa-solid fa-upload"></i> Yüklemeyi Başlat
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : '';
            const display = document.getElementById('fileNameDisplay');
            if(fileName) {
                display.innerHTML = '<i class="fa-solid fa-file me-2"></i> Seçilen: ' + fileName;
            } else {
                display.innerHTML = '';
            }
        }
    </script>

</body>
</html>
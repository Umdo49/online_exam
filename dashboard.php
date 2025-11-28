<?php
session_start();
require 'db.php';

// Oturum kontrolü
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$role = $user['role']; // 'ogrenci' veya 'ogretmen'

// Verileri tutacağımız diziler
$upcoming_exams = [];
$my_exams = [];
$materials = [];
$stats = ['total_exams' => 0, 'completed' => 0];

if ($role === 'ogrenci') {
    // ÖĞRENCİ İÇİN VERİLER
    
    // 1. Yaklaşan Sınavlar (Gelecek tarihli)
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE start_time > NOW() ORDER BY start_time ASC LIMIT 5");
    $stmt->execute();
    $upcoming_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Materyaller
    $stmt = $pdo->query("SELECT * FROM exam_materials ORDER BY created_at DESC LIMIT 5");
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. İstatistik (Basitçe katıldığı sınav sayısı - answers tablosundan)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT exam_id) FROM answers WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['completed'] = $stmt->fetchColumn();

} else {
    // ÖĞRETMEN İÇİN VERİLER

    // 1. Kendi Oluşturduğu Sınavlar
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE creator_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $my_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['total_exams'] = count($my_exams);

    // 2. Kendi Yüklediği Materyaller
    $stmt = $pdo->prepare("SELECT * FROM exam_materials WHERE creator_id = ?");
    $stmt->execute([$user['id']]);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel | Uzaktan Sınav Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f3f4f6; }
        
        /* Sidebar Tasarımı */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: #212529;
            color: #fff;
            position: fixed;
            top: 0; left: 0;
            padding-top: 20px;
            transition: all 0.3s;
            z-index: 1000;
        }
        .sidebar-header { padding: 15px 25px; border-bottom: 1px solid #343a40; margin-bottom: 20px; }
        .sidebar a {
            padding: 12px 25px;
            text-decoration: none;
            font-size: 16px;
            color: #adb5bd;
            display: block;
            transition: 0.2s;
        }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #0d6efd; border-radius: 0 25px 25px 0; }
        .sidebar i { width: 25px; }

        /* Ana İçerik */
        .main-content { margin-left: 260px; padding: 30px; transition: all 0.3s; }
        
        /* Kartlar */
        .card-custom {
            border: none;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            transition: transform 0.2s;
        }
        .card-custom:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(0,0,0,0.06); }
        .card-icon {
            width: 50px; height: 50px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        /* Hoşgeldin Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Mobil Uyumluluk */
        @media (max-width: 768px) {
            .sidebar { margin-left: -260px; }
            .sidebar.active { margin-left: 0; }
            .main-content { margin-left: 0; }
            .overlay { display: none; position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
            .overlay.active { display: block; }
        }
    </style>
</head>
<body>

    <div class="overlay" id="overlay" onclick="toggleMenu()"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header d-flex align-items-center">
            <i class="fa-solid fa-graduation-cap fa-2x me-2 text-primary"></i>
            <h5 class="m-0 fw-bold">Sınav Sis.</h5>
        </div>
        
        <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Ana Sayfa</a>
        
        <?php if($role === 'ogrenci'): ?>
            <a href="available_exams.php"><i class="fa-solid fa-pen-to-square"></i> Sınavlara Katıl</a>
            <a href="exam_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Sonuçlarım</a>
        <?php else: ?>
            <a href="create_exam.php"><i class="fa-solid fa-plus-circle"></i> Sınav Oluştur</a>
            <a href="manage_exams.php"><i class="fa-solid fa-list-check"></i> Sınavları Yönet</a>
            <a href="student_results.php"><i class="fa-solid fa-chart-line"></i> Öğrenci Sonuçları</a>
            <a href="upload_material.php"><i class="fa-solid fa-upload"></i> Materyal Yükle</a>
            <a href="payment_approvals.php"><i class="fa-solid fa-file-invoice-dollar"></i> Ödeme Onayları</a>
        <?php endif; ?>
        
        <a href="profile_manage.php"><i class="fa-solid fa-user-gear"></i> Profil Ayarları</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        
        <button class="btn btn-primary d-md-none mb-3" onclick="toggleMenu()">
            <i class="fa-solid fa-bars"></i> Menü
        </button>

        <div class="welcome-banner shadow-sm">
            <div>
                <h2 class="fw-bold">Hoş Geldin, <?= htmlspecialchars($user['name']) ?>!</h2>
                <p class="mb-0 opacity-75">
                    <?= $role === 'ogrenci' ? 'Başarılar dileriz. Yaklaşan sınavlarını aşağıdan kontrol edebilirsin.' : 'Sınavlarınızı ve öğrencilerinizi buradan yönetebilirsiniz.' ?>
                </p>
            </div>
            <div class="d-none d-md-block">
                <i class="fa-solid fa-user-astronaut fa-4x opacity-50"></i>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <?php if($role === 'ogrenci'): ?>
                <div class="col-md-6">
                    <div class="card card-custom p-4">
                        <div class="d-flex align-items-center">
                            <div class="card-icon bg-primary bg-opacity-10 text-primary me-3">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Tamamlanan Sınavlar</h6>
                                <h3 class="fw-bold m-0"><?= $stats['completed'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-custom p-4">
                        <div class="d-flex align-items-center">
                            <div class="card-icon bg-warning bg-opacity-10 text-warning me-3">
                                <i class="fa-solid fa-calendar-alt"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Yaklaşan Sınav Sayısı</h6>
                                <h3 class="fw-bold m-0"><?= count($upcoming_exams) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-12">
                    <div class="card card-custom p-4">
                        <div class="d-flex align-items-center">
                            <div class="card-icon bg-success bg-opacity-10 text-success me-3">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Oluşturulan Toplam Sınav</h6>
                                <h3 class="fw-bold m-0"><?= $stats['total_exams'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                        <h5 class="m-0 fw-bold text-primary">
                            <?= $role === 'ogrenci' ? '📅 Yaklaşan Sınavlar' : '📂 Son Eklenen Sınavlarınız' ?>
                        </h5>
                        <?php if($role === 'ogretmen'): ?>
                            <a href="create_exam.php" class="btn btn-sm btn-primary">Yeni Ekle</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php 
                            $liste = $role === 'ogrenci' ? $upcoming_exams : $my_exams;
                            if (empty($liste)): 
                            ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="fa-regular fa-folder-open fa-2x mb-2"></i>
                                    <p>Henüz kayıt bulunmamaktadır.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($liste as $exam): ?>
                                    <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($exam['title']) ?></h6>
                                            <small class="text-muted">
                                                <i class="fa-regular fa-clock me-1"></i> 
                                                <?= date('d.m.Y H:i', strtotime($exam['start_time'])) ?>
                                                <span class="ms-2 badge bg-light text-dark border"><?= $exam['duration_minutes'] ?> dk</span>
                                            </small>
                                        </div>
                                        <?php if($role === 'ogrenci'): ?>
                                            <a href="available_exams.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Katıl</a>
                                        <?php else: ?>
                                            <a href="edit_exam.php?id=<?= $exam['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white border-bottom p-3">
                        <h5 class="m-0 fw-bold text-secondary">📎 Materyaller</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php if (empty($materials)): ?>
                                <div class="p-4 text-center text-muted">Materyal yok.</div>
                            <?php else: ?>
                                <?php foreach ($materials as $m): ?>
                                    <div class="list-group-item p-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center text-truncate">
                                                <i class="fa-solid fa-file-pdf text-danger me-3 fs-4"></i>
                                                <div>
                                                    <h6 class="mb-0 text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($m['title']) ?>">
                                                        <?= htmlspecialchars($m['title'] ?: $m['filename']) ?>
                                                    </h6>
                                                    <small class="text-muted">Ders Notu</small>
                                                </div>
                                            </div>
                                            <a href="<?= htmlspecialchars($m['filepath']) ?>" class="btn btn-sm btn-light" download>
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if($role === 'ogretmen'): ?>
                        <div class="card-footer bg-white text-center">
                            <a href="upload_material.php" class="btn btn-sm btn-link text-decoration-none">Yeni Yükle</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('overlay').classList.toggle('active');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
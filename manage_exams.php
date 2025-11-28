<?php
session_start();
require 'db.php';

// Güvenlik: Öğretmen mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$ogretmen_id = $user['id'];

// Öğretmenin sınavlarını getir (En yeniden en eskiye)
$stmt = $pdo->prepare("SELECT * FROM exams WHERE creator_id = ? ORDER BY created_at DESC");
$stmt->execute([$ogretmen_id]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınavları Yönet</title>
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

        /* Sınav Kartları */
        .exam-card { 
            border: none; 
            border-radius: 12px; 
            background: #fff; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); 
            transition: transform 0.2s; 
            overflow: hidden;
        }
        .exam-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0,0,0,0.06); }
        .card-header-custom { background: #f8f9fa; border-bottom: 1px solid #eee; padding: 15px; }
        .card-body { padding: 20px; }
        
        .badge-soft-success { background-color: #d1e7dd; color: #0f5132; }
        .badge-soft-danger { background-color: #f8d7da; color: #842029; }
        .badge-soft-warning { background-color: #fff3cd; color: #664d03; }

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
        <a href="manage_exams.php" class="active"><i class="fa-solid fa-list-check"></i> Sınavları Yönet</a>
        <a href="student_results.php"><i class="fa-solid fa-chart-line"></i> Öğrenci Sonuçları</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Sınavlarım</h2>
                <a href="create_exam.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Yeni Sınav</a>
            </div>

            <?php if (empty($exams)): ?>
                <div class="alert alert-info border-0 shadow-sm text-center py-5">
                    <i class="fa-solid fa-folder-open fa-3x mb-3 text-info"></i>
                    <h5>Henüz hiç sınav oluşturmadınız.</h5>
                    <p class="text-muted">Hemen yeni bir sınav oluşturarak başlayın.</p>
                    <a href="create_exam.php" class="btn btn-sm btn-info text-white fw-bold">Oluştur</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($exams as $exam): ?>
                        <?php 
                            $now = new DateTime();
                            $start_time = new DateTime($exam['start_time']);
                            $end_time = new DateTime($exam['end_time']);
                            
                            $status_badge = '';
                            $is_finished = false;

                            if ($now > $end_time) {
                                $status_badge = '<span class="badge badge-soft-danger">Tamamlandı</span>';
                                $is_finished = true;
                            } elseif ($now >= $start_time && $now <= $end_time) {
                                $status_badge = '<span class="badge badge-soft-success">Yayında</span>';
                            } else {
                                $status_badge = '<span class="badge badge-soft-warning">Planlandı</span>';
                            }
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card exam-card h-100">
                                <div class="card-header-custom d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0 fw-bold text-truncate" title="<?= htmlspecialchars($exam['title']) ?>">
                                        <?= htmlspecialchars($exam['title']) ?>
                                    </h5>
                                    <?= $status_badge ?>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <ul class="list-unstyled mb-4 small text-muted">
                                        <li class="mb-2"><i class="fa-regular fa-calendar me-2"></i> <?= $start_time->format('d.m.Y H:i') ?></li>
                                        <li class="mb-2"><i class="fa-solid fa-stopwatch me-2"></i> <?= $exam['duration_minutes'] ?> dk</li>
                                        <li><i class="fa-solid fa-layer-group me-2"></i> <?= ucfirst(str_replace('_', ' ', $exam['format'])) ?></li>
                                    </ul>
                                    
                                    <div class="mt-auto d-grid gap-2">
                                        <?php if ($is_finished): ?>
                                            <a href="exam_details.php?exam_id=<?= $exam['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-chart-pie"></i> Sonuçları Gör</a>
                                            <a href="delete_exam.php?exam_id=<?= $exam['id'] ?>" onclick="return confirm('Bu sınavı silmek istediğinize emin misiniz?')" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash"></i> Sil</a>
                                        <?php else: ?>
                                            <div class="btn-group">
                                                <a href="edit_exam.php?exam_id=<?= $exam['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-pen"></i> Düzenle</a>
                                                <a href="add_question.php?exam_id=<?= $exam['id'] ?>" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-plus"></i> Soru Ekle</a>
                                            </div>
                                            <a href="upload_material.php?exam_id=<?= $exam['id'] ?>" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-file-upload"></i> Dosya Yükle</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
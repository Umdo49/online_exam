<?php
session_start();
require 'db.php';

// Güvenlik: Öğretmen mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) {
    header("Location: manage_exams.php");
    exit;
}

// Sınav Bilgisi
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Sınav bulunamadı.");
}

// Sınav bitmiş mi?
$now = new DateTime();
$end_time = new DateTime($exam['end_time']);
$finished = $end_time < $now;

// Katılımcılar ve Sonuçlar
$participants = [];
if ($finished) {
    $sql = "SELECT ep.user_id, u.name, r.score 
            FROM exam_participants ep 
            JOIN users u ON ep.user_id = u.id 
            LEFT JOIN results r ON ep.id = r.participant_id
            WHERE ep.exam_id = ? ORDER BY r.score DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$exam_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınav Detayları</title>
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

        /* Kartlar */
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 25px; margin-bottom: 25px; }
        
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-active { background-color: #d1e7dd; color: #0f5132; }
        .status-finished { background-color: #f8d7da; color: #842029; }

        /* Tablo */
        .table th { background-color: #f8f9fa; font-weight: 600; color: #495057; }
        .table td { vertical-align: middle; }

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
                <div>
                    <h2 class="fw-bold text-dark m-0">Sınav Detayları</h2>
                    <p class="text-muted m-0">Sınav ID: #<?= $exam['id'] ?></p>
                </div>
                <div>
                    <a href="manage_exams.php" class="btn btn-outline-secondary me-2"><i class="fa-solid fa-arrow-left"></i> Geri</a>
                    <?php if ($finished): ?>
                        <a href="delete_exam.php?exam_id=<?= $exam['id'] ?>" onclick="return confirm('Bu sınavı silmek istediğinize emin misiniz?')" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Sil</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card card-custom border-top border-4 border-primary">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="fw-bold text-primary mb-3"><?= htmlspecialchars($exam['title']) ?></h4>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($exam['description'])) ?></p>
                    </div>
                    <div class="col-md-4 border-start">
                        <ul class="list-unstyled">
                            <li class="mb-2"><strong><i class="fa-regular fa-calendar me-2"></i> Başlangıç:</strong> <br> <?= date('d.m.Y H:i', strtotime($exam['start_time'])) ?></li>
                            <li class="mb-2"><strong><i class="fa-solid fa-hourglass-end me-2"></i> Bitiş:</strong> <br> <?= date('d.m.Y H:i', strtotime($exam['end_time'])) ?></li>
                            <li class="mb-2"><strong><i class="fa-regular fa-clock me-2"></i> Süre:</strong> <?= $exam['duration_minutes'] ?> dk</li>
                            <li>
                                <strong>Durum:</strong> 
                                <?php if ($finished): ?>
                                    <span class="status-badge status-finished">Tamamlandı</span>
                                <?php else: ?>
                                    <span class="status-badge status-active">Aktif / Bekleniyor</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if ($finished): ?>
                <div class="card card-custom">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-users-viewfinder me-2 text-success"></i> Katılımcı Sonuçları</h5>
                    
                    <?php if (count($participants) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Öğrenci Adı</th>
                                        <th>Puan</th>
                                        <th>Durum</th>
                                        <th class="text-end">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($participants as $p): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                                            <td>
                                                <span class="badge bg-secondary fs-6"><?= number_format($p['score'], 2) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($p['score'] >= 50): ?>
                                                    <span class="badge bg-success"><i class="fa-solid fa-check"></i> Başarılı</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><i class="fa-solid fa-xmark"></i> Başarısız</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="participant_details.php?exam_id=<?= $exam_id ?>&user_id=<?= $p['user_id'] ?>" class="btn btn-sm btn-info text-white">
                                                    <i class="fa-solid fa-magnifying-glass"></i> İncele
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning m-0">Bu sınava henüz katılım olmamış veya sonuçlanmamış.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info shadow-sm">
                    <i class="fa-solid fa-info-circle me-2"></i> Bu sınav henüz tamamlanmadığı için sonuçlar görüntülenemiyor. Sınav bitiş tarihinden sonra raporlar burada görünecektir.
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
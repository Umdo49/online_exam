<?php
session_start();
require 'db.php';

// Güvenlik: Öğrenci mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogrenci') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// Sınav Geçmişini Getir
// Hem cevaplanmış hem de süresi bitmiş sınavları getirelim.
// Ayrıca başarı puanı hesaplanmışsa (results tablosundan) onu da çekelim.
$sql = "
    SELECT DISTINCT e.id AS exam_id, e.title, e.start_time, e.end_time, e.duration_minutes,
           r.score,
           (SELECT COUNT(*) FROM answers WHERE exam_id = e.id AND user_id = ?) as answered_count
    FROM exams e
    JOIN answers a ON e.id = a.exam_id
    LEFT JOIN results r ON e.id = r.exam_id AND r.participant_id = ?
    WHERE a.user_id = ?
    ORDER BY e.end_time DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $user_id, $user_id]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınav Geçmişi</title>
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
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 0; overflow: hidden; }
        
        /* Tablo */
        .table th { background-color: #f8f9fa; font-weight: 600; color: #495057; border-bottom: 2px solid #dee2e6; padding: 15px; }
        .table td { vertical-align: middle; padding: 15px; }
        .table-hover tbody tr:hover { background-color: #f1f5f9; }

        /* Rozetler */
        .badge-soft-success { background-color: #d1fae5; color: #065f46; }
        .badge-soft-warning { background-color: #fef3c7; color: #92400e; }
        .badge-soft-danger { background-color: #fee2e2; color: #991b1b; }

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
        <a href="available_exams.php"><i class="fa-solid fa-pen-to-square"></i> Sınavlara Katıl</a>
        <a href="exam_history.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> Sonuçlarım</a>
        <a href="profile_manage.php"><i class="fa-solid fa-user-gear"></i> Profil Ayarları</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Sınav Geçmişi</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
            </div>

            <div class="card card-custom">
                <?php if (empty($exams)): ?>
                    <div class="text-center py-5">
                        <i class="fa-regular fa-folder-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Henüz katıldığınız bir sınav bulunmamaktadır.</h5>
                        <a href="available_exams.php" class="btn btn-primary mt-2">Sınavlara Göz At</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sınav Adı</th>
                                    <th>Tarih</th>
                                    <th>Durum</th>
                                    <th class="text-center">Puan</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exams as $exam): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($exam['title']) ?></div>
                                            <small class="text-muted"><?= $exam['duration_minutes'] ?> Dakika</small>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column small text-muted">
                                                <span><i class="fa-regular fa-calendar me-1"></i> <?= date('d.m.Y', strtotime($exam['start_time'])) ?></span>
                                                <span><i class="fa-regular fa-clock me-1"></i> <?= date('H:i', strtotime($exam['start_time'])) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-success rounded-pill">
                                                <i class="fa-solid fa-check me-1"></i> Katılım Sağlandı
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (isset($exam['score'])): ?>
                                                <span class="fw-bold fs-5 <?= $exam['score'] >= 50 ? 'text-success' : 'text-danger' ?>">
                                                    <?= number_format($exam['score'], 2) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-soft-warning text-dark">Değerlendiriliyor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="exam_results.php?exam_id=<?= $exam['exam_id'] ?>" class="btn btn-sm btn-outline-primary fw-bold">
                                                <i class="fa-solid fa-chart-pie me-1"></i> Detaylar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
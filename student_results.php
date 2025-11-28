<?php
session_start();
require 'db.php';

// Güvenlik: Öğretmen mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Öğrencileri ve İstatistiklerini Çek
// NOT: Profil resmi için veritabanındaki sütun adı 'profile_picture' olmalıdır.
$sql = "
    SELECT 
        u.id, u.name, u.email, u.profile_picture,
        COUNT(r.exam_id) AS exams_taken,
        COALESCE(AVG(r.score), 0) AS average_score
    FROM users u
    LEFT JOIN results r ON u.id = r.participant_id
    WHERE u.role = 'ogrenci'
    GROUP BY u.id
    ORDER BY average_score DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sınıf Genel Ortalaması Hesapla
$total_score = 0;
$total_students = count($students);
foreach($students as $s) { $total_score += $s['average_score']; }
$class_average = $total_students > 0 ? number_format($total_score / $total_students, 2) : 0;

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Öğrenci Sonuçları</title>
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
        
        /* Tablo */
        .table th { background-color: #f8f9fa; font-weight: 600; color: #495057; border-bottom: 2px solid #dee2e6; }
        .table td { vertical-align: middle; }
        .table-hover tbody tr:hover { background-color: #f1f5f9; cursor: pointer; }

        /* Profil Resmi */
        .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px; border: 1px solid #dee2e6; }
        
        /* Rozetler */
        .badge-grade { width: 40px; text-align: center; }
        .grade-high { background-color: #d1fae5; color: #065f46; } /* Yeşil */
        .grade-med { background-color: #fef3c7; color: #92400e; } /* Sarı */
        .grade-low { background-color: #fee2e2; color: #991b1b; } /* Kırmızı */

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
        <a href="student_results.php" class="active"><i class="fa-solid fa-chart-line"></i> Öğrenci Sonuçları</a>
        <a href="payment_approvals.php"><i class="fa-solid fa-file-invoice-dollar"></i> Ödeme Onayları</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
    </div>

    <div class="main-content">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Öğrenci Başarı Analizi</h2>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card card-custom d-flex flex-row align-items-center">
                        <div class="fs-1 text-primary me-3"><i class="fa-solid fa-users"></i></div>
                        <div>
                            <h6 class="text-muted mb-0">Toplam Öğrenci</h6>
                            <h3 class="fw-bold mb-0"><?= $total_students ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-custom d-flex flex-row align-items-center">
                        <div class="fs-1 text-success me-3"><i class="fa-solid fa-chart-pie"></i></div>
                        <div>
                            <h6 class="text-muted mb-0">Genel Sınıf Ortalaması</h6>
                            <h3 class="fw-bold mb-0"><?= $class_average ?> / 100</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-custom">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Öğrenci</th>
                                <th>E-Posta</th>
                                <th class="text-center">Girilen Sınav</th>
                                <th class="text-center">Ortalama Puan</th>
                                <th class="text-center">Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Henüz kayıtlı öğrenci yok.</td></tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): 
                                    // Profil resmi kontrolü
                                    $pp = !empty($s['profile_picture']) ? htmlspecialchars($s['profile_picture']) : 'uploads/default_student.png';
                                    $avg = number_format($s['average_score'], 2);
                                    
                                    // Renk belirleme
                                    $badge_class = 'grade-low';
                                    if ($avg >= 70) $badge_class = 'grade-high';
                                    elseif ($avg >= 50) $badge_class = 'grade-med';
                                ?>
                                <tr onclick="window.location.href='student_details.php?student_id=<?= $s['id'] ?>'" title="Detayları görüntülemek için tıklayın">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= $pp ?>" class="avatar" alt="pp">
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($s['email']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= $s['exams_taken'] ?></span>
                                    </td>
                                    <td class="text-center fw-bold text-dark">
                                        <?= $avg ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($avg >= 50): ?>
                                            <span class="badge rounded-pill bg-success"><i class="fa-solid fa-check"></i> Başarılı</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-danger"><i class="fa-solid fa-triangle-exclamation"></i> Kritik</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
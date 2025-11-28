<?php
session_start();
require 'db.php';

// Güvenlik: Öğretmen mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$student_id = $_GET['student_id'] ?? null;
if (!$student_id) {
    header("Location: student_results.php");
    exit;
}

// Öğrenci Bilgisi
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Girdiği Sınavlar
$sql = "
    SELECT e.id, e.title, e.format, e.start_time,
           r.score,
           (SELECT COUNT(*) FROM answers WHERE exam_id = e.id AND user_id = ?) as answered_count
    FROM exams e
    JOIN answers a ON e.id = a.exam_id
    LEFT JOIN results r ON e.id = r.exam_id AND r.participant_id = ?
    WHERE a.user_id = ?
    GROUP BY e.id
    ORDER BY e.start_time DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$student_id, $student_id, $student_id]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Öğrenci Detayı | <?= htmlspecialchars($student['name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .sidebar { width: 260px; height: 100vh; background: #212529; color: #fff; position: fixed; top: 0; left: 0; padding-top: 20px; }
        .sidebar a { padding: 12px 25px; text-decoration: none; color: #adb5bd; display: block; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #0d6efd; }
        .main-content { margin-left: 260px; padding: 30px; }
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 25px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div style="padding: 15px 25px; border-bottom: 1px solid #343a40; margin-bottom: 20px;">
            <h5 class="m-0 fw-bold">Sınav Sis.</h5>
        </div>
        <a href="dashboard.php"><i class="fa-solid fa-house me-2"></i> Ana Sayfa</a>
        <a href="create_exam.php"><i class="fa-solid fa-plus-circle me-2"></i> Sınav Oluştur</a>
        <a href="manage_exams.php"><i class="fa-solid fa-list-check me-2"></i> Sınavları Yönet</a>
        <a href="student_results.php" class="active"><i class="fa-solid fa-chart-line me-2"></i> Öğrenci Sonuçları</a>
        <a href="logout.php" class="text-danger mt-3"><i class="fa-solid fa-right-from-bracket me-2"></i> Çıkış</a>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark m-0"><?= htmlspecialchars($student['name']) ?></h2>
                    <p class="text-muted m-0"><?= htmlspecialchars($student['email']) ?></p>
                </div>
                <a href="student_results.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
            </div>

            <div class="card card-custom">
                <h5 class="fw-bold mb-3">Girdiği Sınavlar</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Sınav Adı</th>
                                <th>Format</th>
                                <th>Durum</th>
                                <th>Puan</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $exam): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($exam['title']) ?></td>
                                    <td><?= ucfirst($exam['format']) ?></td>
                                    <td>
                                        <?php if (isset($exam['score'])): ?>
                                            <span class="badge bg-success">Sonuçlandı</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Değerlendirme Bekliyor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= isset($exam['score']) ? number_format($exam['score'], 2) : '-' ?></td>
                                    <td class="text-end">
                                        <a href="evaluate_exam.php?exam_id=<?= $exam['id'] ?>&student_id=<?= $student_id ?>" class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-pen-to-square"></i> Değerlendir / İncele
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
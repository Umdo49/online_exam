<?php
session_start();
require 'db.php';

// Güvenlik: Öğrenci mi?
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogrenci') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['exam_id'])) {
    header("Location: exam_history.php");
    exit;
}

$exam_id = intval($_GET['exam_id']);
$user_id = $_SESSION['user']['id'];

// Katılım Kontrolü
$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM answers WHERE user_id = ? AND exam_id = ?");
$checkStmt->execute([$user_id, $exam_id]);
if ($checkStmt->fetchColumn() == 0) {
    die("Bu sınava katılımınız bulunmamaktadır.");
}

// Sınav Bilgileri
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// Toplam Puan (Results Tablosundan)
$scoreStmt = $pdo->prepare("SELECT score FROM results WHERE exam_id = ? AND participant_id = ?");
$scoreStmt->execute([$exam_id, $user_id]);
$total_score = $scoreStmt->fetchColumn();

// Sorular
$questionsStmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
$questionsStmt->execute([$exam_id]);
$questions = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Cevaplar
$answersStmt = $pdo->prepare("SELECT question_id, answer FROM answers WHERE user_id = ? AND exam_id = ?");
$answersStmt->execute([$user_id, $exam_id]);
$answers = $answersStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Manuel Puanlar (Klasik Sorular İçin)
$manualStmt = $pdo->prepare("SELECT question_id, score, explanation FROM manual_scores WHERE user_id = ? AND exam_id = ?");
$manualStmt->execute([$user_id, $exam_id]);
$manual_scores = [];
foreach ($manualStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $manual_scores[$row['question_id']] = [
        'score' => $row['score'],
        'explanation' => $row['explanation'] ?? ''
    ];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınav Sonucu | <?= htmlspecialchars($exam['title']) ?></title>
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
        .card-custom { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); padding: 0; margin-bottom: 25px; overflow: hidden; }
        .card-header-custom { background-color: #fff; padding: 20px; border-bottom: 1px solid #eee; }
        .card-body-custom { padding: 20px; }

        /* Puan Kartı */
        .score-card { background: linear-gradient(135deg, #0d6efd, #0dcaf0); color: white; border-radius: 12px; padding: 30px; text-align: center; margin-bottom: 30px; }
        .score-display { font-size: 3rem; font-weight: 700; }
        
        /* Tablo */
        .table th { background-color: #f8f9fa; color: #495057; font-weight: 600; }
        .table td { vertical-align: middle; }

        /* Durum Rozetleri */
        .badge-correct { background-color: #d1e7dd; color: #0f5132; }
        .badge-wrong { background-color: #f8d7da; color: #842029; }
        .badge-partial { background-color: #fff3cd; color: #664d03; }
        .badge-pending { background-color: #e2e3e5; color: #383d41; }

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
                <div>
                    <h2 class="fw-bold text-dark m-0">Sınav Sonucu</h2>
                    <p class="text-muted m-0"><?= htmlspecialchars($exam['title']) ?></p>
                </div>
                <a href="exam_history.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
            </div>

            <?php if ($total_score !== false): ?>
                <div class="score-card shadow">
                    <h5>Toplam Puanınız</h5>
                    <div class="score-display"><?= number_format($total_score, 2) ?></div>
                    <p class="mb-0 opacity-75">100 üzerinden değerlendirilmiştir.</p>
                </div>
            <?php else: ?>
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <i class="fa-solid fa-clock me-2"></i> Sınavınızın değerlendirmesi henüz tamamlanmamış olabilir.
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="fw-bold m-0"><i class="fa-solid fa-list-check me-2 text-primary"></i> Cevap Anahtarı</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Soru</th>
                                <th style="width: 20%;">Cevabınız</th>
                                <th style="width: 20%;">Doğru Cevap / Puan</th>
                                <th style="width: 20%;">Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $q): 
                                $qid = $q['id'];
                                $type = $q['question_type'];
                                $user_answer = $answers[$qid] ?? null;
                                $correct_answer = $q['correct_answer'];
                                $score = $manual_scores[$qid]['score'] ?? null;
                                $explanation = $manual_scores[$qid]['explanation'] ?? '';
                                
                                $status_badge = '';
                                
                                if ($type === 'coktan_secimli') {
                                    if ($user_answer === null || $user_answer === '') {
                                        $status_badge = '<span class="badge badge-pending">Boş</span>';
                                    } elseif ($user_answer === $correct_answer) {
                                        $status_badge = '<span class="badge badge-correct"><i class="fa-solid fa-check"></i> Doğru</span>';
                                    } else {
                                        $status_badge = '<span class="badge badge-wrong"><i class="fa-solid fa-xmark"></i> Yanlış</span>';
                                    }
                                } elseif ($type === 'klasik') {
                                    if ($score === null) {
                                        $status_badge = '<span class="badge badge-pending">Değerlendiriliyor</span>';
                                    } elseif ($score == 2) { // Tam puan (Varsayım: Klasik soru max 2 puan)
                                        $status_badge = '<span class="badge badge-correct">Tam Puan</span>';
                                    } elseif ($score > 0) {
                                        $status_badge = '<span class="badge badge-partial">Kısmi Puan</span>';
                                    } else {
                                        $status_badge = '<span class="badge badge-wrong">Puan Yok</span>';
                                    }
                                }
                            ?>
                            <tr>
                                <td><?= nl2br(htmlspecialchars($q['question_text'])) ?></td>
                                <td>
                                    <?php if ($user_answer === null || $user_answer === ''): ?>
                                        <span class="text-muted fst-italic">Cevap verilmedi</span>
                                    <?php else: ?>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($user_answer) ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($type === 'klasik' && $explanation): ?>
                                        <div class="mt-2 p-2 bg-light border rounded small text-muted">
                                            <i class="fa-solid fa-comment-dots me-1"></i> <strong>Not:</strong> <?= htmlspecialchars($explanation) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($type === 'coktan_secimli'): ?>
                                        <span class="text-success fw-bold"><?= htmlspecialchars($correct_answer) ?></span>
                                    <?php else: ?>
                                        <?= ($score !== null) ? "<strong>$score</strong> Puan" : '<span class="text-muted">-</span>' ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= $status_badge ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
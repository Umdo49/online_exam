<?php
session_start();
require 'db.php';

// Güvenlik
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$exam_id = $_GET['exam_id'] ?? null;
$student_id = $_GET['student_id'] ?? null;
$ogretmen_id = $_SESSION['user']['id'];

if (!$exam_id || !$student_id) {
    die("Eksik parametre.");
}

// Sınav ve Öğrenci Bilgisi
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Sorular, Cevaplar ve Manuel Puanlar
$sql = "
    SELECT q.id, q.question_text, q.question_type, q.correct_answer, 
           a.answer, ms.score as manual_score
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id AND a.user_id = ?
    LEFT JOIN manual_scores ms ON q.id = ms.question_id AND ms.user_id = ?
    WHERE q.exam_id = ?
    ORDER BY q.id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$student_id, $student_id, $exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Puanlama Kaydetme (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total_score = 0;
    $question_count = count($questions);
    
    // 1. Puanları Hesapla ve Manuel Skorları Kaydet
    foreach ($questions as $q) {
        $qid = $q['id'];
        $score = 0;

        if ($q['question_type'] === 'coktan_secimli') {
            // Otomatik Puanlama
            // Eşit ağırlıklı puanlama: 100 / Soru Sayısı
            $points_per_question = ($question_count > 0) ? (100 / $question_count) : 0;
            if (trim($q['answer']) === trim($q['correct_answer'])) {
                $score = $points_per_question;
            }
        } else {
            // Manuel Puanlama (Formdan gelen)
            $score_val = $_POST['score_' . $qid] ?? 0;
            $score = floatval($score_val); // Öğretmenin verdiği puan
            
            // Manuel Puanı Kaydet
            $stmt = $pdo->prepare("INSERT INTO manual_scores (exam_id, user_id, question_id, score, evaluator_id) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE score = VALUES(score)");
            $stmt->execute([$exam_id, $student_id, $qid, $score, $ogretmen_id]);
        }
        $total_score += $score;
    }

    // 2. HATAYI ÇÖZEN KISIM: Katılımcı ID'sini Bul
    // user_id değil, exam_participants tablosundaki ID lazım.
    $p_stmt = $pdo->prepare("SELECT id FROM exam_participants WHERE user_id = ? AND exam_id = ?");
    $p_stmt->execute([$student_id, $exam_id]);
    $participant_row = $p_stmt->fetch(PDO::FETCH_ASSOC);

    if ($participant_row) {
        $real_participant_id = $participant_row['id'];

        // 3. Toplam Puanı Sonuçlara Yaz (results tablosu participant_id ister)
        $stmt = $pdo->prepare("INSERT INTO results (exam_id, participant_id, score, evaluated, evaluation_time) VALUES (?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE score = VALUES(score), evaluated = 1, evaluation_time = NOW()");
        $stmt->execute([$exam_id, $real_participant_id, $total_score]);

        $mesaj = "Değerlendirme kaydedildi. Toplam Puan: " . number_format($total_score, 2);
        // Sayfayı yenile
        header("Refresh: 2; url=evaluate_exam.php?exam_id=$exam_id&student_id=$student_id");
    } else {
        // Eğer exam_participants kaydı yoksa (Örn: Manuel veritabanı müdahalesi) oluştur.
        // Normal akışta buraya düşmemesi lazım.
        $mesaj = "Hata: Öğrenci sınav kayıt listesinde bulunamadı.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Değerlendirme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #f3f4f6; padding: 30px; }</style>
</head>
<body>
    <div class="container bg-white p-4 rounded shadow">
        <div class="d-flex justify-content-between mb-4">
            <h3><?= htmlspecialchars($exam['title']) ?> - <?= htmlspecialchars($student['name']) ?></h3>
            <a href="student_details.php?student_id=<?= $student_id ?>" class="btn btn-secondary">Geri Dön</a>
        </div>

        <?php if (isset($mesaj)): ?>
            <div class="alert <?= strpos($mesaj, 'Hata') !== false ? 'alert-danger' : 'alert-success' ?>">
                <?= $mesaj ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php foreach ($questions as $i => $q): ?>
                <div class="card mb-3">
                    <div class="card-header fw-bold">Soru <?= $i + 1 ?> (<?= ucfirst(str_replace('_', ' ', $q['question_type'])) ?>)</div>
                    <div class="card-body">
                        <p class="mb-2 fw-bold"><?= nl2br(htmlspecialchars($q['question_text'])) ?></p>
                        
                        <div class="alert alert-secondary">
                            <strong>Öğrenci Cevabı:</strong><br>
                            <?= nl2br(htmlspecialchars($q['answer'] ?? 'Cevap yok')) ?>
                        </div>

                        <?php if ($q['question_type'] === 'coktan_secimli'): ?>
                            <div class="text-success border p-2 rounded bg-light">
                                <strong>Doğru Cevap:</strong> <?= $q['correct_answer'] ?> <br>
                                Durum: 
                                <?php if (trim($q['answer']) === trim($q['correct_answer'])) echo '<span class="badge bg-success">✅ Doğru</span>'; else echo '<span class="badge bg-danger">❌ Yanlış</span>'; ?>
                            </div>
                        <?php else: ?>
                            <div class="mt-2 p-3 border rounded bg-light">
                                <label class="form-label fw-bold text-primary">Puan Ver (Bu soru için):</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" name="score_<?= $q['id'] ?>" class="form-control w-25" 
                                           value="<?= $q['manual_score'] ?? '' ?>" step="0.5" min="0" placeholder="0">
                                    <span class="text-muted small">(Puan üzerinden değerlendirin)</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-success btn-lg w-100 mt-3">Değerlendirmeyi Tamamla ve Kaydet</button>
        </form>
    </div>
</body>
</html>
<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT DISTINCT e.id, e.title
    FROM exams e
    JOIN questions q ON e.id = q.exam_id
    WHERE e.creator_id = ? AND q.question_type = 'klasik'
    ORDER BY e.id DESC
");
$stmt->execute([$teacher_id]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Klasik Sınavlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f9fb; padding: 30px; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #007bff; text-align: center; margin-bottom: 30px; }
        .exam-item { padding: 15px; border-bottom: 1px solid #eee; }
        .exam-item:last-child { border-bottom: none; }
        .exam-link { text-decoration: none; color: #007bff; font-weight: bold; }
        .exam-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>Değerlendirilecek Klasik Sorulu Sınavlar</h2>
    <?php if (empty($exams)): ?>
        <div class="alert alert-warning text-center">Klasik sorulu sınav bulunamadı.</div>
    <?php else: ?>
        <?php foreach ($exams as $exam): ?>
            <div class="exam-item">
                ✅ <a class="exam-link" href="grade_classic_answers.php?exam_id=<?= $exam['id'] ?>">
                    <?= htmlspecialchars($exam['title']) ?>
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>

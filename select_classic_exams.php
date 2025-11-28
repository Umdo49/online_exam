<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ogretmen') {
    header("Location: login.php");
    exit;
}

$ogretmen_id = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT DISTINCT e.id, e.title 
                       FROM exams e 
                       JOIN questions q ON e.id = q.exam_id 
                       WHERE e.creator_id = ? AND q.question_type = 'klasik'");
$stmt->execute([$ogretmen_id]);
$exams = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Klasik Sınavlar</title>
</head>
<body>
    <h2>Klasik Soru İçeren Sınavlar</h2>
    <ul>
        <?php foreach ($exams as $exam): ?>
            <li>
                <?= htmlspecialchars($exam['title']) ?>
                - <a href="grade_classic_answers.php?exam_id=<?= $exam['id'] ?>">Cevapları Değerlendir</a>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>

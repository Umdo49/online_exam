<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['user'];

$mysqli = new mysqli("localhost", "root", "", "sinav_sistemi");
if ($mysqli->connect_error) {
    die("Veritabanı bağlantı hatası: " . $mysqli->connect_error);
}

/* --- Sınav ID’yi al (id veya exam_id) --- */
$exam_id = 0;
if (isset($_GET['id']))       $exam_id = (int)$_GET['id'];
elseif (isset($_GET['exam_id'])) $exam_id = (int)$_GET['exam_id'];

if ($exam_id <= 0) {
    die("Geçerli sınav ID'si sağlanmadı.");
}

/* --- Sınav verisini çek --- */
$stmt = $mysqli->prepare("
    SELECT exams.*, users.name AS teacher_name
    FROM exams
    INNER JOIN users ON exams.creator_id = users.id
    WHERE exams.id = ?
");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

if (!$exam) {
    die("Sınav bulunamadı.");
}

/* --- Tarih hesabı --- */
$now        = time();
$start_time = strtotime($exam['start_time']);   // ‘start_time’ sütunu kullanılıyor
$is_active  = $start_time > $now;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınav Bilgileri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3><?= htmlspecialchars($exam['title']) ?></h3>
    <p><?= htmlspecialchars($exam['description']) ?></p>
    <p><strong>Öğretmen:</strong> <?= htmlspecialchars($exam['teacher_name']) ?></p>
    <p><strong>Sınav Başlangıcı:</strong> <?= date("d.m.Y H:i", $start_time) ?></p>

    <?php if ($is_active): ?>
        <form action="start_exam.php" method="post">
            <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
            <button class="btn btn-success">Sınava Katıl</button>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">
            Bu sınavın süresi dolmuş. Sonuç sayfasına gidebilirsiniz.
        </div>
        <a href="exam_result.php?exam_id=<?= $exam['id'] ?>" class="btn btn-secondary">Sonuçları Gör</a>
    <?php endif; ?>
</div>
</body>
</html>

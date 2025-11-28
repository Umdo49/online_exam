<?php
require_once 'db.php';
session_start();

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($user_id == 0) die("Geçerli bir kullanıcı ID'si sağlanmadı.");

$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();
if (!$user) die("Kullanıcı bulunamadı.");

$profileImage = !empty($user['profile_picture']) ? $user['profile_picture'] : 'images/default_profile.png';

$stmtResults = $pdo->prepare("SELECT e.title AS exam_title, r.score, e.date FROM results r JOIN exams e ON r.exam_id = e.id WHERE r.participant_id = ? ORDER BY e.date DESC");
$stmtResults->execute([$user_id]);
$results = $stmtResults->fetchAll();

$totalExamsTaken = count($results);
$averageScore = $totalExamsTaken > 0 ? array_sum(array_column($results, 'score')) / $totalExamsTaken : 0;
$lastExam = $results[0] ?? null;

$stmtTotalExams = $pdo->query("SELECT COUNT(*) FROM exams");
$totalExamCount = $stmtTotalExams->fetchColumn();

$createdExams = 0;
$activeExams = [];
if ($user['role'] === 'teacher') {
    $stmtCreated = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE creator_id = ?");
    $stmtCreated->execute([$user_id]);
    $createdExams = $stmtCreated->fetchColumn();

    $stmtActiveExams = $pdo->prepare("SELECT id, title, date FROM exams WHERE creator_id = ? AND date >= CURDATE() ORDER BY date ASC");
    $stmtActiveExams->execute([$user_id]);
    $activeExams = $stmtActiveExams->fetchAll();
}

function formatDate($dateStr) {
    $date = new DateTime($dateStr);
    return $date->format('d M Y');
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($user['name']) ?> - Profil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f7fafc;
            color: #2d3748;
            font-family: 'Segoe UI', sans-serif;
        }
        .topbar {
            height: 60px;
            background-color: #fff;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .topbar img.logo {
            height: 40px;
        }
        .main-content {
            margin-top: 80px;
            padding: 20px;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            margin: 20px auto;
            border: 3px solid #3182ce;
        }
        h1, h2 {
            text-align: center;
            color: #2c5282;
            margin-bottom: 20px;
        }
        .info-list, .stat-list {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
        }
        .info-list li, .stat-list li {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-list li strong, .stat-list li strong {
            color: #2b6cb0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background-color: #3182ce;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f1f5f9;
        }
        tr:hover {
            background-color: #bee3f8;
        }
        .btn-back {
            margin-top: 10px;
            display: inline-block;
        }
        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                height: auto;
                padding: 10px 15px;
            }
            .info-list li, .stat-list li {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <img src="logo.png" alt="Logo" class="logo">
        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">🔙 Panele Dön</a>
    </div>
    <div>
        <?php if (isset($_SESSION['user'])): ?>
            <strong><?= htmlspecialchars($_SESSION['user']['name']) ?> (<?= $_SESSION['user']['role'] ?>)</strong>
            <a href="logout.php" class="btn btn-sm btn-outline-danger ms-2">Çıkış</a>
        <?php endif; ?>
    </div>
</div>

<div class="main-content">
    <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profil Resmi" class="profile-image" />
    <h1><?= htmlspecialchars($user['name']) ?> - Profil</h1>

    <h2>Genel Bilgiler</h2>
    <ul class="info-list">
        <li><strong>Kullanıcı ID:</strong> <span><?= $user['id'] ?></span></li>
        <li><strong>Adı:</strong> <span><?= htmlspecialchars($user['name']) ?></span></li>
        <li><strong>Email:</strong> <span><?= htmlspecialchars($user['email']) ?></span></li>
        <li><strong>Rolü:</strong> <span><?= ucfirst($user['role']) ?></span></li>
        <li><strong>Kayıt Tarihi:</strong> <span><?= formatDate($user['created_at']) ?></span></li>
    </ul>

    <h2>İstatistikler</h2>
    <ul class="stat-list">
        <li><strong>Katıldığı Sınav Sayısı:</strong> <span><?= $totalExamsTaken ?></span></li>
        <li><strong>Ortalama Puan:</strong> <span><?= number_format($averageScore, 2) ?></span></li>
        <li><strong>Katılım Oranı:</strong> <span><?= $totalExamCount > 0 ? round(($totalExamsTaken / $totalExamCount) * 100, 2) . '%' : 'Yok' ?></span></li>
        <?php if ($lastExam): ?>
        <li><strong>Son Sınav:</strong> <span><?= htmlspecialchars($lastExam['exam_title']) ?> (<?= formatDate($lastExam['date']) ?>)</span></li>
        <?php endif; ?>
        <?php if ($user['role'] === 'teacher'): ?>
        <li><strong>Oluşturduğu Sınav Sayısı:</strong> <span><?= $createdExams ?></span></li>
        <?php endif; ?>
    </ul>

    <?php if ($user['role'] === 'teacher' && !empty($activeExams)): ?>
        <h2>Yaklaşan Sınavlar</h2>
        <table>
            <thead>
                <tr>
                    <th>Sınav Başlığı</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activeExams as $exam): ?>
                    <tr>
                        <td><?= htmlspecialchars($exam['title']) ?></td>
                        <td><?= formatDate($exam['date']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($user['role'] === 'teacher'): ?>
        <p class="text-center text-muted fst-italic">Henüz yaklaşan sınav bulunmamaktadır.</p>
    <?php endif; ?>

    <h2>Sınav Sonuçları</h2>
    <?php if (empty($results)): ?>
        <p class="text-center text-muted fst-italic">Bu kullanıcı için sınav sonucu bulunmamaktadır.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Sınav</th>
                    <th>Tarih</th>
                    <th>Puan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $res): ?>
                    <tr>
                        <td><?= htmlspecialchars($res['exam_title']) ?></td>
                        <td><?= formatDate($res['date']) ?></td>
                        <td><?= htmlspecialchars($res['score']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>

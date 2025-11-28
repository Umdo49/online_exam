<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

$search_results = ['exams' => [], 'users' => []];
$search_query = '';

if (isset($_GET['q'])) {
    $search_query = trim($_GET['q']);
    $like_query = '%' . $search_query . '%';

    // Sınavlar
    $stmt_exam = $pdo->prepare("
        SELECT exams.id, exams.title, exams.description, exams.date, users.name AS teacher_name 
        FROM exams
        INNER JOIN users ON exams.creator_id = users.id
        WHERE exams.title LIKE ? OR exams.description LIKE ? OR users.name LIKE ?
    ");
    $stmt_exam->execute([$like_query, $like_query, $like_query]);
    $search_results['exams'] = $stmt_exam->fetchAll(PDO::FETCH_ASSOC);

    // Kullanıcılar
    $stmt_user = $pdo->prepare("
        SELECT id, name, email FROM users 
        WHERE name LIKE ? OR email LIKE ?
    ");
    $stmt_user->execute([$like_query, $like_query]);
    $search_results['users'] = $stmt_user->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Arama Sonuçları</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
        }
        .topbar {
            height: 60px;
            background-color: #fff;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }
        .topbar img.logo {
            height: 40px;
        }
        .sidebar {
            width: 250px;
            background-color: #fff;
            border-right: 1px solid #dee2e6;
            position: fixed;
            top: 60px;
            bottom: 0;
            left: 0;
            padding-top: 20px;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #f1f1f1;
        }
        .main-content {
            margin-left: 250px;
            margin-top: 80px;
            padding: 20px;
        }
        .mobile-toggle, .mobile-close {
            display: none;
            cursor: pointer;
        }
        #overlay {
            display: none;
            position: fixed;
            top: 60px;
            left: 0;
            width: 100%;
            height: calc(100% - 60px);
            background: rgba(0,0,0,0.3);
            z-index: 999;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            #overlay.active {
                display: block;
            }
            .main-content {
                margin-left: 0;
            }
            .mobile-toggle {
                display: block;
            }
            .mobile-close {
                display: block;
                font-size: 22px;
                font-weight: bold;
                text-align: right;
                padding: 10px 15px;
            }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="d-flex align-items-center">
        <div class="mobile-toggle me-3" onclick="toggleSidebar()">☰</div>
        <img src="logo.png" alt="Logo" class="logo">
    </div>
    <div>
        <strong><?= htmlspecialchars($user['name']) ?> (<?= $user['role'] ?>)</strong>
        <a href="logout.php" class="btn btn-sm btn-outline-danger ms-3">Çıkış Yap</a>
    </div>
</div>

<div id="overlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="mobile-close" onclick="closeSidebar()">×</div>
    <a href="dashboard.php">Ana Sayfa</a>
    <a href="search.php">Arama</a>
    <?php if ($user['role'] === 'ogrenci'): ?>
        <a href="available_exams.php">Sınavlara Katıl</a>
        <a href="exam_history.php">Geçmiş Sınavlar</a>
    <?php else: ?>
        <a href="create_exam.php">Yeni Sınav</a>
        <a href="manage_exams.php">Sınavları Yönet</a>
        <a href="student_results.php">Öğrenci Sonuçları</a>
        <a href="upload_material.php">Materyal Yükle</a>
    <?php endif; ?>
    <a href="profile_manage.php">Profil</a>
</div>

<div class="main-content">
    <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary mb-4">← Geri Dön</a>

    <form action="search.php" method="get" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" name="q" placeholder="Kullanıcı veya sınav ara..." value="<?= htmlspecialchars($search_query) ?>">
            <button class="btn btn-primary">Ara</button>
        </div>
    </form>

    <?php if ($search_query): ?>

        <h5>Kullanıcılar</h5>
        <?php if (count($search_results['users']) > 0): ?>
            <ul class="list-group mb-4">
                <?php foreach ($search_results['users'] as $u): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($u['name']) ?></strong><br>
                            <small><?= htmlspecialchars($u['email']) ?></small>
                        </div>
                        <a href="profile.php?user_id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">Profili Gör</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="alert alert-warning">Kullanıcı bulunamadı.</div>
        <?php endif; ?>

        <h5>Sınavlar</h5>
        <?php if (count($search_results['exams']) > 0): ?>
            <ul class="list-group">
                <?php foreach ($search_results['exams'] as $exam): 
                    $exam_date = strtotime($exam['date']);
                    $now = time();
                    $exam_link = ($exam_date > $now) ? "exam_info.php?exam_id={$exam['id']}" : "exam_result.php?exam_id={$exam['id']}";
                ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($exam['title']) ?></strong><br>
                            <?= htmlspecialchars($exam['description']) ?><br>
                            <small>Öğretmen: <?= htmlspecialchars($exam['teacher_name']) ?> - Tarih: <?= date("d.m.Y H:i", $exam_date) ?></small>
                        </div>
                        <a href="<?= $exam_link ?>" class="btn btn-sm btn-outline-success">Detay</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="alert alert-warning">Sınav bulunamadı.</div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('overlay').classList.toggle('active');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

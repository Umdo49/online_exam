<?php
require 'db.php';

// Yaklaşan sınavları çek
$simdi = date('Y-m-d H:i:s');
$sorgu = $pdo->prepare("SELECT * FROM exams WHERE start_time > ? ORDER BY start_time ASC LIMIT 4");
$sorgu->execute([$simdi]);
$sinavlar = $sorgu->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>Sınav Portalı | Online Sınav Oluşturma ve Satış Platformu</title>
    <meta name="description" content="Özel ders verenler, kurslar ve okullar için online sınav sistemi. Kendi sınavınızı oluşturun, öğrencilerinize ücretli veya ücretsiz sunun. Anlık değerlendirme ve analiz imkanı.">
    <meta name="keywords" content="online sınav, sınav oluşturma, sınav satışı, uzaktan eğitim, test hazırlama, sınav sistemi, öğretmen paneli">
    <meta name="author" content="Sınav Portalı">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; overflow-x: hidden; }
        
        /* Navbar */
        .navbar { backdrop-filter: blur(10px); background-color: rgba(13, 110, 253, 0.95) !important; }

        /* Hero Alanı */
        .hero-section {
            background: linear-gradient(135deg, #0d6efd, #052c65);
            color: white;
            padding: 120px 0 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-title { font-weight: 800; letter-spacing: -1px; margin-bottom: 20px; }
        .hero-text { font-weight: 300; opacity: 0.9; max-width: 700px; margin: 0 auto 30px auto; font-size: 1.2rem; }
        
        /* Dalga Efekti (SVG) */
        .wave-bottom { position: absolute; bottom: 0; left: 0; width: 100%; overflow: hidden; line-height: 0; transform: rotate(180deg); }
        .wave-bottom svg { position: relative; display: block; width: calc(100% + 1.3px); height: 60px; }
        .wave-bottom .shape-fill { fill: #f4f6f9; }

        /* Kart Tasarımı */
        .exam-card {
            border: none; border-radius: 15px; background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease; overflow: hidden;
        }
        .exam-card:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .card-header-custom { background-color: #fff; padding: 20px; font-weight: 700; color: #0d6efd; border-bottom: 1px solid #f0f0f0; }
        .card-body { padding: 25px; }

        /* Tanıtım Bölümü */
        .promo-section { padding: 80px 0; background: white; }
        .promo-box { padding: 30px; border-radius: 20px; background: #f8f9fa; height: 100%; transition: 0.3s; border: 1px solid transparent; }
        .promo-box:hover { background: white; border-color: #0d6efd; box-shadow: 0 10px 30px rgba(13, 110, 253, 0.1); }
        .promo-icon { font-size: 2.5rem; color: #0d6efd; margin-bottom: 20px; }

        /* Hedef Kitle Bölümü (Yeni Eklenen) */
        .target-section { padding: 80px 0; background: #0d6efd; color: white; text-align: center; }
        .target-card { background: rgba(255,255,255,0.1); border-radius: 15px; padding: 30px; backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.2); height: 100%; }
        .target-icon { font-size: 3rem; margin-bottom: 15px; color: #ffc107; }

        /* Footer */
        footer { background-color: #212529; color: #adb5bd; padding: 60px 0 30px 0; margin-top: auto; }
        footer a { color: #adb5bd; text-decoration: none; transition: 0.2s; }
        footer a:hover { color: white; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <i class="fa-solid fa-graduation-cap me-2 fs-4"></i> Sınav Portalı
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link text-white" href="login.php">Giriş Yap</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-light fw-bold text-primary px-4" href="register.php">Ücretsiz Kayıt Ol</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container position-relative z-1">
            <span class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill fw-bold"><i class="fa-solid fa-star me-1"></i> Eğitimciler İçin İdeal Çözüm</span>
            <h1 class="display-3 hero-title">Kendi Sınavını Oluştur</h1>
            <p class="lead hero-text">
                Kurslar, özel ders verenler ve eğitim kurumları için tasarlandı. 
                Sınavlarınızı kolayca hazırlayın, öğrencilerinize sunun ve detaylı analizlerle başarıyı ölçün.
            </p>
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="register.php" class="btn btn-light btn-lg px-5 py-3 fw-bold text-primary shadow">Hemen Başla</a>
                <a href="#nasil-calisir" class="btn btn-outline-light btn-lg px-5 py-3 fw-bold">Nasıl Çalışır?</a>
            </div>
        </div>
        <div class="wave-bottom">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
        </div>
    </section>

    <section class="promo-section" id="nasil-calisir">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold text-dark">Kimler İçin Uygundur?</h2>
                <p class="text-muted w-75 mx-auto">Platformumuz, eğitim veren herkesin ihtiyaçlarına göre özelleştirilebilir bir altyapı sunar.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="promo-box text-center">
                        <i class="fa-solid fa-chalkboard-user promo-icon"></i>
                        <h4 class="fw-bold">Özel Ders Verenler</h4>
                        <p class="text-muted">Öğrencilerinizin seviyesini belirlemek veya gelişimlerini takip etmek için özel sınavlar hazırlayın.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="promo-box text-center">
                        <i class="fa-solid fa-school promo-icon"></i>
                        <h4 class="fw-bold">Kurslar ve Dershaneler</h4>
                        <p class="text-muted">Deneme sınavlarınızı online ortama taşıyın, kağıt israfından kurtulun ve anında sonuç raporları alın.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="promo-box text-center">
                        <i class="fa-solid fa-briefcase promo-icon"></i>
                        <h4 class="fw-bold">Eğitim Girişimcileri</h4>
                        <p class="text-muted">Hazırladığınız kaliteli soruları ve deneme setlerini ücretli olarak sunarak gelir modeli oluşturun.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="target-section">
        <div class="container">
            <div class="row g-5">
                <div class="col-md-4">
                    <div class="target-card">
                        <i class="fa-solid fa-file-invoice-dollar target-icon"></i>
                        <h4>Kolay Ödeme Sistemi</h4>
                        <p class="mb-0 opacity-75">Sınavlarınızı ücretli yapabilir, güvenli ödeme altyapısı ile gelirinizi artırabilirsiniz.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="target-card">
                        <i class="fa-solid fa-chart-line target-icon"></i>
                        <h4>Detaylı Analiz</h4>
                        <p class="mb-0 opacity-75">Hangi konuda eksiklik var? Öğrenci ve sınıf bazlı detaylı başarı raporları alın.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="target-card">
                        <i class="fa-solid fa-mobile-screen target-icon"></i>
                        <h4>Mobil Uyumlu</h4>
                        <p class="mb-0 opacity-75">Öğrencileriniz sınava diledikleri cihazdan (telefon, tablet, PC) sorunsuz erişebilir.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5 py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark m-0">📅 Yaklaşan Sınavlar</h3>
                <p class="text-muted small m-0">Platformdaki en yeni sınav fırsatlarını kaçırmayın.</p>
            </div>
            <a href="login.php" class="btn btn-outline-primary fw-bold">Tümünü Gör <i class="fa-solid fa-arrow-right ms-2"></i></a>
        </div>

        <div class="row g-4">
            <?php if (count($sinavlar) > 0): ?>
                <?php foreach ($sinavlar as $sinav): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card exam-card h-100">
                            <div class="card-header-custom d-flex justify-content-between align-items-center">
                                <span class="text-truncate w-75"><?= htmlspecialchars($sinav['title']) ?></span>
                                <?php if ($sinav['is_paid']): ?>
                                    <span class="badge bg-warning text-dark">₺<?= $sinav['price'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success">Ücretsiz</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <p class="card-text text-muted small mb-3">
                                    <?= htmlspecialchars(substr($sinav['description'], 0, 80)) ?>...
                                </p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between text-muted small mb-3">
                                        <span><i class="fa-regular fa-clock me-1"></i> <?= $sinav['duration_minutes'] ?> dk</span>
                                        <span><i class="fa-regular fa-calendar me-1"></i> <?= date('d.m', strtotime($sinav['start_time'])) ?></span>
                                    </div>
                                    <a href="login.php" class="btn btn-primary w-100 btn-sm fw-bold py-2">Sınava Git</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 bg-light rounded-3">
                    <i class="fa-regular fa-calendar-xmark fa-3x text-muted mb-3"></i>
                    <p class="text-muted fs-5 m-0">Şu anda planlanmış sınav bulunmamaktadır.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-4 col-md-6">
                    <h5 class="text-white fw-bold mb-3"><i class="fa-solid fa-graduation-cap me-2"></i>Sınav Portalı</h5>
                    <p class="small opacity-75">Eğitimi dijitalleştiriyoruz. Öğretmenler ve öğrenciler için güvenilir, hızlı ve modern sınav platformu.</p>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Hızlı Erişim</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="login.php">Giriş Yap</a></li>
                        <li class="mb-2"><a href="register.php">Kayıt Ol</a></li>
                        <li><a href="#">Sınavlar</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Çözümler</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#">Özel Ders Verenler</a></li>
                        <li class="mb-2"><a href="#">Kurs Merkezleri</a></li>
                        <li><a href="#">Kurumsal Sınavlar</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">İletişim</h6>
                    <p class="small opacity-75 mb-1"><i class="fa-solid fa-envelope me-2"></i> destek@sinavportali.com</p>
                    <p class="small opacity-75"><i class="fa-solid fa-phone me-2"></i> 0850 123 45 67</p>
                </div>
            </div>
            <div class="text-center mt-5 pt-4 border-top border-secondary small opacity-50">
                &copy; <?= date("Y") ?> Sınav Portalı. Tüm Hakları Saklıdır.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 28 Kas 2025, 14:51:45
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `online_exam_db`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `answers`
--

CREATE TABLE `answers` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` text DEFAULT NULL,
  `classic_feedback` enum('dogru','kismen_dogru','yanlis') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `answers`
--

INSERT INTO `answers` (`id`, `exam_id`, `user_id`, `question_id`, `answer`, `classic_feedback`) VALUES
(5, 5, 3, 8, 'D', NULL),
(6, 5, 2, 8, 'B', NULL),
(8, 7, 2, 12, 'A', NULL),
(16, 13, 2, 51, 'A', NULL),
(17, 16, 2, 52, 'B', NULL),
(18, 16, 2, 53, 'B', NULL),
(19, 16, 2, 54, 'B', NULL),
(20, 16, 2, 55, 'C', NULL),
(21, 16, 2, 56, 'C', NULL),
(22, 16, 2, 57, 'D', NULL),
(23, 16, 2, 58, 'C', NULL),
(24, 16, 2, 59, 'blabla', 'yanlis'),
(25, 16, 2, 60, 'Cevap: Fiilimsi, fiil kökünden türeyip cümlede isim, sıfat veya zarf gibi kullanılan kelimelerdir.\r\n\r\nİsim-fiil: gelmek\r\n\r\nSıfat-fiil: gelen\r\n\r\nZarf-fiil: gelince*', 'dogru'),
(26, 16, 2, 61, 'Uzun” → niteleme sıfatı\r\n\r\n“boylu” → belirtme sıfatı (belirtili niteleme)\r\n\r\n“çocuk” → sıfat tamlamasının ismi*', 'dogru'),
(27, 17, 2, 62, 'a', 'dogru'),
(28, 17, 2, 63, 'b', 'kismen_dogru'),
(29, 17, 2, 64, 'c', 'kismen_dogru'),
(30, 17, 2, 65, 'd', 'dogru'),
(31, 17, 2, 66, 'e', 'dogru'),
(32, 17, 8, 62, '1 a', NULL),
(33, 17, 8, 63, '2 b', NULL),
(34, 17, 8, 64, '3 c', NULL),
(35, 17, 8, 65, '4 d', NULL),
(36, 17, 8, 66, '5 e', NULL),
(37, 17, 12, 62, 'a', NULL),
(38, 17, 12, 63, 'b', NULL),
(39, 17, 12, 64, 'c', NULL),
(40, 17, 12, 65, 'd', NULL),
(41, 17, 12, 66, 'e', NULL),
(42, 20, 2, 70, 'A', NULL),
(43, 20, 2, 71, 'c', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `format` enum('coktan_secimli','klasik','yazili','karisik') DEFAULT NULL,
  `is_paid` tinyint(1) DEFAULT 1,
  `date` datetime DEFAULT NULL,
  `price` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `exams`
--

INSERT INTO `exams` (`id`, `title`, `description`, `start_time`, `end_time`, `duration_minutes`, `creator_id`, `format`, `is_paid`, `date`, `price`, `created_at`) VALUES
(5, 'matematik', 'sınavım', '2025-05-08 21:57:00', '2025-05-08 22:57:00', 60, 4, 'coktan_secimli', 0, NULL, 0, '2025-05-28 12:14:15'),
(7, 'yazılım', 'sınav', '2025-05-09 11:36:00', '2025-05-09 11:43:00', 10, 7, 'coktan_secimli', 0, NULL, 0, '2025-05-28 12:14:15'),
(13, 'Sınav 1', 'quiz', '2025-06-04 14:23:00', '2025-06-20 15:23:00', 60, 1, 'klasik', 1, NULL, 100, '2025-06-04 14:24:07'),
(14, 'Matematik', 'Vize', '2025-06-20 10:00:00', '2025-06-20 11:00:00', 60, 1, 'karisik', 1, NULL, 250, '2025-06-04 14:36:31'),
(15, 'PDF', 'Bilgi içerikli pdf', '2025-06-04 15:01:00', '2025-06-20 15:01:00', 180, 1, 'yazili', 0, NULL, 0, '2025-06-04 15:01:52'),
(16, 'Türkçe', 'Dil Bilgisi', '2025-06-04 16:35:00', '2025-06-21 16:35:00', 60, 1, 'karisik', 1, NULL, 150, '2025-06-04 16:35:47'),
(17, 'Klasik Sınav', 'klasik', '2025-06-04 18:42:00', '2025-06-21 18:42:00', 30, 1, 'klasik', 0, NULL, 0, '2025-06-04 18:42:34'),
(18, 'Matematik Fİnal', 'google.databases', '2025-06-14 13:15:00', '2025-06-15 13:15:00', 100, 9, 'klasik', 1, NULL, 10, '2025-06-08 13:15:59'),
(19, 'demo1', 'demodur', '2025-12-07 15:00:00', '2025-12-07 16:30:00', 90, 1, 'karisik', 1, NULL, 150, '2025-11-28 15:16:08'),
(20, 'şimdi', 'örnek', '2025-11-28 16:09:00', '2025-11-28 17:09:00', 60, 1, 'karisik', 0, NULL, 0, '2025-11-28 16:09:34');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `exam_files`
--

CREATE TABLE `exam_files` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `exam_materials`
--

CREATE TABLE `exam_materials` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `filepath` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `exam_materials`
--

INSERT INTO `exam_materials` (`id`, `exam_id`, `creator_id`, `filename`, `filepath`, `title`, `created_at`) VALUES
(5, NULL, 1, 'Serway_Fizik_2_Turkce.pdf', 'uploads/68403600cb01e_Serway_Fizik_2_Turkce.pdf', 'PDF', '2025-06-04 15:03:12'),
(6, NULL, 1, '10.3. IP Protokulu.pdf', 'uploads/68403652e3b4d_10.3. IP Protokulu.pdf', 'PDF', '2025-06-04 15:04:34'),
(7, NULL, 1, 'Adi diferansiyel denklemlere giriş ve eğri aileleri.pdf', 'uploads/684046ab5055f_Adi diferansiyel denklemlere giriş ve eğri aileleri.pdf', 'Matematik', '2025-06-04 16:14:19'),
(8, NULL, 1, 'integrasyon çarpanı.pdf', 'uploads/6840481a22a38_integrasyon çarpanı.pdf', 'Sınav 1', '2025-06-04 16:20:26'),
(9, NULL, 1, 'bil.organizasyonu 2 li.pdf', 'uploads/68404d99e156a_bil.organizasyonu 2 li.pdf', 'Türkçe', '2025-06-04 16:43:53'),
(10, 19, 1, 'PROGRAMMING WITH PIC MICROCONTROLLER.pdf', 'uploads/692995157d3ad_PROGRAMMING_WITH_PIC_MICROCONTROLLER.pdf', 'demo', '2025-11-28 15:27:01');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `exam_participants`
--

CREATE TABLE `exam_participants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `payment_status` tinyint(1) DEFAULT 0,
  `start_time` datetime DEFAULT NULL,
  `submitted` tinyint(1) DEFAULT 0,
  `invoice_file` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `exam_participants`
--

INSERT INTO `exam_participants` (`id`, `user_id`, `exam_id`, `payment_status`, `start_time`, `submitted`, `invoice_file`, `contact_email`, `contact_phone`) VALUES
(1, 2, 13, 1, NULL, 0, NULL, 'umuta1649@gmail.com', '05052947900'),
(2, 2, 13, 1, NULL, 0, NULL, 'umuta1649@gmail.com', '905052947900'),
(3, 3, 14, 1, NULL, 0, NULL, 'umuta1649@gmail.com', '905052947900'),
(4, 2, 14, 1, NULL, 0, NULL, NULL, NULL),
(5, 2, 16, 1, NULL, 0, NULL, NULL, NULL),
(6, 12, 18, 0, NULL, 0, NULL, 'hackedbystylus02@gmail.com', '905439107826'),
(7, 2, 19, 1, NULL, 0, 'dekont_2_19_1764333516.pdf', 'rperihan12@gmail.com', '905052947900');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `manual_scores`
--

CREATE TABLE `manual_scores` (
  `exam_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `evaluator_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `manual_scores`
--

INSERT INTO `manual_scores` (`exam_id`, `user_id`, `question_id`, `score`, `explanation`, `evaluator_id`) VALUES
(16, 2, 59, 0, NULL, 1),
(16, 2, 60, 2, NULL, 1),
(16, 2, 61, 2, NULL, 1),
(17, 2, 62, 2, NULL, 1),
(17, 2, 63, 1, NULL, 1),
(17, 2, 64, 1, NULL, 1),
(17, 2, 65, 2, NULL, 1),
(17, 2, 66, 2, NULL, 1),
(16, 2, 59, 0, NULL, 1),
(16, 2, 60, 2, NULL, 1),
(16, 2, 61, 2, NULL, 1),
(17, 2, 62, 2, NULL, 1),
(17, 2, 63, 1, NULL, 1),
(17, 2, 64, 1, NULL, 1),
(17, 2, 65, 2, NULL, 1),
(17, 2, 66, 2, NULL, 1),
(17, 2, 62, 2, NULL, 1),
(17, 2, 63, 1, NULL, 1),
(17, 2, 64, 1, NULL, 1),
(17, 2, 65, 2, NULL, 1),
(17, 2, 66, 2, NULL, 1),
(16, 2, 59, 0, NULL, 1),
(16, 2, 60, 2, NULL, 1),
(16, 2, 61, 2, NULL, 1),
(17, 2, 62, 2, NULL, 1),
(17, 2, 63, 1, NULL, 1),
(17, 2, 64, 1, NULL, 1),
(17, 2, 65, 2, NULL, 1),
(17, 2, 66, 2, NULL, 1),
(16, 2, 59, 0, NULL, 1),
(16, 2, 60, 2, NULL, 1),
(16, 2, 61, 2, NULL, 1),
(20, 2, 71, 100, NULL, 1),
(20, 2, 71, 50, NULL, 1),
(20, 2, 71, 30, NULL, 1),
(20, 2, 71, 30, NULL, 1),
(20, 2, 71, 10, NULL, 1),
(20, 2, 71, 10, NULL, 1),
(20, 2, 71, 10, NULL, 1),
(20, 2, 71, 10, NULL, 1),
(17, 2, 62, 2, NULL, 1),
(17, 2, 62, 2, NULL, 1),
(17, 2, 62, 2, NULL, 1),
(17, 2, 62, 2, NULL, 1),
(17, 2, 63, 1, NULL, 1),
(17, 2, 63, 1, NULL, 1),
(17, 2, 63, 1, NULL, 1),
(17, 2, 63, 1, NULL, 1),
(17, 2, 64, 1, NULL, 1),
(17, 2, 64, 1, NULL, 1),
(17, 2, 64, 1, NULL, 1),
(17, 2, 64, 1, NULL, 1),
(17, 2, 65, 2, NULL, 1),
(17, 2, 65, 2, NULL, 1),
(17, 2, 65, 2, NULL, 1),
(17, 2, 65, 2, NULL, 1),
(17, 2, 66, 2, NULL, 1),
(17, 2, 66, 2, NULL, 1),
(17, 2, 66, 2, NULL, 1),
(17, 2, 66, 2, NULL, 1),
(16, 2, 59, 5, NULL, 1),
(16, 2, 59, 5, NULL, 1),
(16, 2, 59, 5, NULL, 1),
(16, 2, 59, 5, NULL, 1),
(16, 2, 60, 5, NULL, 1),
(16, 2, 60, 5, NULL, 1),
(16, 2, 60, 5, NULL, 1),
(16, 2, 60, 5, NULL, 1),
(16, 2, 61, 2, NULL, 1),
(16, 2, 61, 2, NULL, 1),
(16, 2, 61, 2, NULL, 1),
(16, 2, 61, 2, NULL, 1),
(17, 8, 62, 20, NULL, 1),
(17, 8, 63, 15, NULL, 1),
(17, 8, 64, 15, NULL, 1),
(17, 8, 65, 5, NULL, 1),
(17, 8, 66, 25, NULL, 1),
(17, 8, 62, 20, NULL, 1),
(17, 8, 63, 15, NULL, 1),
(17, 8, 64, 15, NULL, 1),
(17, 8, 65, 5, NULL, 1),
(17, 8, 66, 25, NULL, 1),
(17, 8, 62, 20, NULL, 1),
(17, 8, 62, 20, NULL, 1),
(17, 8, 63, 15, NULL, 1),
(17, 8, 63, 15, NULL, 1),
(17, 8, 64, 15, NULL, 1),
(17, 8, 64, 15, NULL, 1),
(17, 8, 65, 5, NULL, 1),
(17, 8, 65, 5, NULL, 1),
(17, 8, 66, 25, NULL, 1),
(17, 8, 66, 25, NULL, 1),
(17, 8, 62, 20, NULL, 1),
(17, 8, 62, 20, NULL, 1),
(17, 8, 62, 20, NULL, 1),
(17, 8, 62, 20, NULL, 1),
(17, 8, 63, 15, NULL, 1),
(17, 8, 63, 15, NULL, 1),
(17, 8, 63, 15, NULL, 1),
(17, 8, 63, 15, NULL, 1),
(17, 8, 64, 15, NULL, 1),
(17, 8, 64, 15, NULL, 1),
(17, 8, 64, 15, NULL, 1),
(17, 8, 64, 15, NULL, 1),
(17, 8, 65, 5, NULL, 1),
(17, 8, 65, 5, NULL, 1),
(17, 8, 65, 5, NULL, 1),
(17, 8, 65, 5, NULL, 1),
(17, 8, 66, 25, NULL, 1),
(17, 8, 66, 25, NULL, 1),
(17, 8, 66, 25, NULL, 1),
(17, 8, 66, 25, NULL, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `channel` enum('email','sms','whatsapp') DEFAULT 'email',
  `message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `question_text` text DEFAULT NULL,
  `question_type` enum('coktan_secimli','klasik','yazili') DEFAULT NULL,
  `correct_answer` text DEFAULT NULL,
  `options` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `question_text`, `question_type`, `correct_answer`, `options`) VALUES
(8, 5, '7+13', 'coktan_secimli', '20', '{\"A\":\"30\",\"B\":\"20\",\"C\":\"25\",\"D\":\"21\"}'),
(12, 7, '22', 'coktan_secimli', 'A', '{\"A\":\"22\",\"B\":\"23\",\"C\":\"2\",\"D\":\"27\"}'),
(36, 14, '1. 8 + 5 işleminin sonucu kaçtır?', 'coktan_secimli', 'C', '{\"A\":\"A) 10\",\"B\":\"B) 12\",\"C\":\"C) 13\",\"D\":\"D) 15\",\"E\":\"E) 16\"}'),
(37, 14, '2. 36 sayısının karekökü kaçtır?', 'coktan_secimli', 'B', '{\"A\":\"A) 4\",\"B\":\"B) 6\",\"C\":\"C) 9\",\"D\":\"D) 12\",\"E\":\"E) 18\"}'),
(38, 14, '3. 2x + 5 = 15 denklemini çözelim. x kaçtır?', 'coktan_secimli', 'B', '{\"A\":\"A) 4\",\"B\":\"B) 5\",\"C\":\"C) 6\",\"D\":\"D) 7\",\"E\":\"E) 10\"}'),
(39, 14, '4. Aşağıdakilerden hangisi asal sayıdır?', 'coktan_secimli', 'D', '{\"A\":\"A) 4\",\"B\":\"B) 6\",\"C\":\"C) 9\",\"D\":\"D) 11\",\"E\":\"E) 15\"}'),
(40, 14, '5. 24 ile 36 sayılarının EBOB\'u kaçtır?', 'coktan_secimli', 'C', '{\"A\":\"A) 6\",\"B\":\"B) 8\",\"C\":\"C) 12\",\"D\":\"D) 18\",\"E\":\"E) 24\"}'),
(41, 14, '6. 5! (faktöriyel) sonucu nedir?', 'coktan_secimli', 'C', '{\"A\":\"A) 60\",\"B\":\"B) 100\",\"C\":\"C) 120\",\"D\":\"D) 150\",\"E\":\"E) 180\"}'),
(42, 14, '7. 1/2 + 1/3 işleminin sonucu nedir?\r\n', 'coktan_secimli', 'A', '{\"A\":\"A) 5\\/6\",\"B\":\"B) 2\\/5\",\"C\":\"C) 2\\/3\",\"D\":\"D) 3\\/5\",\"E\":\"E) 1\\/6\"}'),
(43, 14, '8. (x - 3)(x + 2) = 0 denkleminin kökleri nelerdir?', 'coktan_secimli', 'C', '{\"A\":\"A) 3 ve 2\",\"B\":\"B) -3 ve -2\",\"C\":\"C) -2 ve 3\",\"D\":\"D) 2 ve -2\",\"E\":\"E) 3 ve -3\"}'),
(44, 14, '9. Bir üçgenin iç açılar toplamı kaç derecedir?', 'coktan_secimli', 'B', '{\"A\":\"A) 90\",\"B\":\"B) 180\",\"C\":\"C) 270\",\"D\":\"D) 360\",\"E\":\"E) 540\"}'),
(45, 14, '10. 2⁵ değeri kaçtır?', 'coktan_secimli', 'C', '{\"A\":\"A) 10\",\"B\":\"B) 16\",\"C\":\"C) 32\",\"D\":\"D) 64\",\"E\":\"E) 128\"}'),
(46, 14, '11. 20 ile 30 arasındaki asal sayıları listeleyiniz.', 'klasik', NULL, NULL),
(47, 14, '3x + 7 = 22 denklemini çözünüz.', 'klasik', NULL, NULL),
(48, 14, 'Bir dik üçgenin dik kenarları 6 cm ve 8 cm. Hipotenüs uzunluğunu bulunuz.', 'klasik', NULL, NULL),
(49, 14, '1’den 100’e kadar olan sayılardan 5’in katı olanları listeleyiniz.', 'klasik', NULL, NULL),
(50, 14, 'Bir küre ile silindirin hacim formüllerini yazınız ve açıklayınız.', 'klasik', NULL, NULL),
(51, 13, 'A', 'coktan_secimli', 'A', '{\"A\":\"A) 10\",\"B\":\"B) 12\",\"C\":\"C) 13\",\"D\":\"D) 15\",\"E\":\"E) 16\"}'),
(52, 16, '1. Aşağıdaki cümlelerin hangisinde zamir (adıl) kullanılmıştır?', 'coktan_secimli', 'D', '{\"A\":\"A) Kalemi masaya koydum.\",\"B\":\"B) Kitapları raftan aldı.\",\"C\":\"C) Öğretmen tahtaya yazdı.\",\"D\":\"D) O, bugün erken geldi.\",\"E\":\"E) Araba çok hızlıydı.\"}'),
(53, 16, '2. Aşağıdakilerden hangisinde sıfat kullanılmamıştır?', 'coktan_secimli', 'D', '{\"A\":\"A) Mavi kalemi bana ver.\",\"B\":\"B) Bu kitap çok güzeldi.\",\"C\":\"C) Yorgun adam oturuyordu.\",\"D\":\"D) Sınıfta sessizlik vardı.\",\"E\":\"E) Küçük çocuk ağlıyordu.\"}'),
(54, 16, '3. Aşağıdaki cümlelerin hangisinde fiilimsi yoktur?', 'coktan_secimli', 'E', '{\"A\":\"A) Gülerek içeri girdi.\",\"B\":\"B) Çalışmadan sınav kazanılmaz.\",\"C\":\"C) Gelirken ekmek almayı unutmuş.\",\"D\":\"D) Her sabah yürüyüş yaparım.\",\"E\":\"E) Öğrenciler sınıfa girdi.\"}'),
(55, 16, '4. Aşağıdakilerin hangisinde birleşik cümle vardır?', 'coktan_secimli', 'C', '{\"A\":\"A) Öğretmen sınıfa girdi.\",\"B\":\"B) Dersi dikkatle dinledim.\",\"C\":\"C) Ders çalışıyordum, zil çaldı.\",\"D\":\"D) Bugün çok çalışkanım.\",\"E\":\"E) Kalemini bana verir misin?\"}'),
(56, 16, '5. Aşağıdaki cümlelerin hangisinde dolaylı tümleç (yer tamlayıcısı) vardır?', 'coktan_secimli', 'C', '{\"A\":\"A) Kalemini unuttu.\",\"B\":\"B) Çocuk annesini özlemiş.\",\"C\":\"C) Kitabı arkadaşına verdi.\",\"D\":\"D) Sınıf çok kalabalıktı.\",\"E\":\"E) Eve geç geldim.\"}'),
(57, 16, '6. Aşağıdaki cümlelerin hangisinde çekimli fiil yoktur?\r\n', 'coktan_secimli', 'B', '{\"A\":\"A) Kitabı okuyorum.\",\"B\":\"B) Güzel bir akşamdı.\",\"C\":\"C) Bahçede oyun oynuyorlar.\",\"D\":\" D) Yolda yürüyordu.\",\"E\":\"E) Öğretmen tahtaya yazdı.\"}'),
(58, 16, '7. Aşağıdaki altı çizili sözcük türü bakımından diğerlerinden farklıdır?', 'coktan_secimli', 'E', '{\"A\":\"Bu kitabı çok beğendim.\",\"B\":\"Yeni elbise çok şık olmuş.\",\"C\":\"Güzel ev manzaralıydı.\",\"D\":\"Kırmızı araba dikkat çekiyordu.\",\"E\":\"Bahçede büyük oynadı.\"}'),
(59, 16, '1. Cümlede anlamı etkileyen öğeleri (özne, yüklem, nesne, dolaylı tümleç, zarf tümleci) açıklayarak örnek veriniz.', 'klasik', NULL, NULL),
(60, 16, '2. Fiilimsi nedir? Çeşitlerini açıklayıp her birine birer örnek veriniz.', 'klasik', NULL, NULL),
(61, 16, '3. Aşağıdaki cümlede geçen sıfatları ve türlerini yazınız:', 'klasik', NULL, NULL),
(62, 17, 'klasik soru 1\r\na diye cevapla', 'klasik', NULL, NULL),
(63, 17, 'klasik soru 2\r\nb diye cevapla', 'klasik', NULL, NULL),
(64, 17, 'klasik soru 3\r\nc diye cevapla', 'klasik', NULL, NULL),
(65, 17, 'klasik soru 4\r\nd diye cevapla', 'klasik', NULL, NULL),
(66, 17, 'klasik soru 5\r\ne diye cevapla', 'klasik', NULL, NULL),
(67, 19, 'demo', 'coktan_secimli', 'demo', '{\"A\":\"demo\",\"B\":\"0\",\"C\":\"0\",\"D\":\"0\"}'),
(68, 19, 'demo', 'klasik', 'demo', NULL),
(69, 19, 'demo', 'klasik', 'demo', NULL),
(70, 20, 'a', 'coktan_secimli', 'A', '{\"A\":\"a\",\"B\":\"f\",\"C\":\"f\",\"D\":\"f\",\"E\":\"f\"}'),
(71, 20, 'c', 'klasik', 'c', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `evaluated` tinyint(1) DEFAULT 0,
  `evaluation_time` datetime DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `results`
--

INSERT INTO `results` (`id`, `participant_id`, `score`, `evaluated`, `evaluation_time`, `exam_id`) VALUES
(1, 2, 150.00, 1, '2025-11-28 16:24:25', 20),
(2, 2, 100.00, 1, '2025-11-28 16:24:33', 20),
(3, 2, 93.33, 1, '2025-11-28 16:24:58', 20),
(4, 2, 60.00, 1, '2025-11-28 16:25:30', 20),
(5, 2, 32.00, 1, '2025-11-28 16:25:49', 17),
(6, 2, 58.53, 1, '2025-11-28 16:26:31', 16);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('ogrenci','ogretmen') DEFAULT 'ogrenci',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `phone`, `bio`, `password`, `role`, `created_at`, `profile_picture`) VALUES
(1, 'UMIT ALTUN', NULL, 'umuta1649@gmail.com', '05052947900', NULL, '$2y$10$lNx6HLsRtVM04Zv1CNcPeu6gfezcSeodCKO6IBXF6odZ.p7H.UjQi', 'ogretmen', '2025-05-08 15:01:29', 'uploads/profiles/pp_68403377b7e0b4.56558073.jpg'),
(2, 'Ramazan Perihan', NULL, 'rperihan12@gmail.com', '05373578681', NULL, '$2y$10$i5GPXOV6yWUbIxBzACiGL.LVCm2CcWokDvrCbdqcdYK80UaMluYCy', 'ogrenci', '2025-05-08 15:03:31', 'uploads/profiles/pp_684068d550de87.52907730.jpg'),
(3, 'sui', NULL, 'suatbaban6@gmail.com', '544520876', NULL, '$2y$10$YlUtJKKtqvTmLd1qJhZ3QeYlcVK6ltMzC2/Oo5dfPf8ER63dzVHwq', 'ogrenci', '2025-05-08 18:56:33', 'uploads/profiles/pp_684043cb19c526.57689591.jpg'),
(4, 'yas', NULL, 'yasinasuroglu21@gmail.com', '05340346091', NULL, '$2y$10$z.R6Ts5tant4M.I5VId0kefZt34FDnAihkSdMr7YkNz6hqJz/Iz32', 'ogretmen', '2025-05-08 18:56:55', NULL),
(5, 'ebo', NULL, 'bozkurtibrahim112@gmail.com', '5439107826', NULL, '$2y$10$4ujbvheH.D/9J8R7YfcsYOxPBORwU6TMcbuzKETuN80Bs88sS25RO', 'ogrenci', '2025-05-08 19:54:39', NULL),
(7, 'yusuf hoca', NULL, 'yusuf62@gmail.com', '05373578682', NULL, '$2y$10$R0zYdLKhRLZspK5Zekr7JelG9WdktxobUJUhv9NrCigvgfquR0Aam', 'ogretmen', '2025-05-09 08:35:44', NULL),
(8, 'hehehe', NULL, 'hehehe@gmail.com', '445654654', NULL, '$2y$10$yp3QfyA5b9ju89SKq5SmfeB0zf8nS7lhS8Fwj488veEfRzjrREO0m', 'ogrenci', '2025-06-04 22:52:09', NULL),
(9, 'deneme 123', NULL, 'deneme@ge.com', '5004002221', NULL, '$2y$10$ffKMhaxnvfASibj8vtwisO/7cDuo23sg9yUDs5WpzPB0GpH5dwCYe', 'ogretmen', '2025-06-08 10:15:09', NULL),
(10, 'Danieleroke', NULL, 'tou@bubuk.site', '87441868313', NULL, '$2y$10$4JR1Gz.aUs0jqtBjGAEbBu.EQ5HnEAN3zEvLcsgJTXXnCHk7E/nR.', 'ogretmen', '2025-06-08 23:17:14', NULL),
(12, 'ibrahim bozkurt', NULL, 'hackedbystylus02@gmail.com', '05439107826', NULL, '$2y$10$n3qtgK4hINCMUggTxJZABeEM9PNdbGL4bUpHfS7.RVxrB3jhpNLIi', 'ogrenci', '2025-06-11 20:06:00', NULL),
(16, 'ibrahim bozkurtt', NULL, 'asdasds2@gmail.com', '05439107824', NULL, '$2y$10$WoF0u/mHkjsXooFPJxOr5O.6vArkbirsqlVY4lg6iiVpfF.T.MuAe', 'ogretmen', '2025-06-11 20:09:30', NULL),
(17, 'ibrahimm bozkurt', NULL, 'asddsa@gmail.com', '05439107827', NULL, '$2y$10$SYvd0VaqA6MhMfZnajm5geT7ErdFwWMyCxg9zWPMKVJcso.cwzXDG', 'ogretmen', '2025-06-11 20:10:19', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_exam`
--

CREATE TABLE `user_exam` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `correct_answers` int(11) DEFAULT NULL,
  `total_questions` int(11) NOT NULL,
  `completion_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Tablo için indeksler `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Tablo için indeksler `exam_files`
--
ALTER TABLE `exam_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Tablo için indeksler `exam_materials`
--
ALTER TABLE `exam_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Tablo için indeksler `exam_participants`
--
ALTER TABLE `exam_participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Tablo için indeksler `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exam_id` (`exam_id`),
  ADD KEY `fk_participant_id` (`participant_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Tablo için indeksler `user_exam`
--
ALTER TABLE `user_exam`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Tablo için AUTO_INCREMENT değeri `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `exam_files`
--
ALTER TABLE `exam_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `exam_materials`
--
ALTER TABLE `exam_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `exam_participants`
--
ALTER TABLE `exam_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- Tablo için AUTO_INCREMENT değeri `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Tablo için AUTO_INCREMENT değeri `user_exam`
--
ALTER TABLE `user_exam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`),
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Tablo kısıtlamaları `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`);

--
-- Tablo kısıtlamaları `exam_files`
--
ALTER TABLE `exam_files`
  ADD CONSTRAINT `exam_files_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

--
-- Tablo kısıtlamaları `exam_materials`
--
ALTER TABLE `exam_materials`
  ADD CONSTRAINT `exam_materials_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `exam_participants`
--
ALTER TABLE `exam_participants`
  ADD CONSTRAINT `exam_participants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `exam_participants_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

--
-- Tablo kısıtlamaları `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

--
-- Tablo kısıtlamaları `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `fk_exam_id` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`),
  ADD CONSTRAINT `fk_participant_id` FOREIGN KEY (`participant_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `exam_participants` (`id`);

--
-- Tablo kısıtlamaları `user_exam`
--
ALTER TABLE `user_exam`
  ADD CONSTRAINT `user_exam_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_exam_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

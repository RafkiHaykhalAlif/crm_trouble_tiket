-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 18 Jun 2025 pada 06.42
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crm_retail_app`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `wo_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `message`, `wo_id`, `is_read`, `created_at`) VALUES
(1, 'Work Order baru dari Dispatch: WO-20250613-684BD48B31E30 - Test Customer Debug perlu dijadwalkan', 21, 0, '2025-06-13 07:35:15'),
(2, 'Work Order baru dari Dispatch: WO-20250613-684BD5A0B97D4 - perr perlu dijadwalkan', 22, 0, '2025-06-13 07:39:34'),
(3, 'Work Order baru dari Dispatch: WO-20250616-684FB6960E690 - acik perlu dijadwalkan', 23, 0, '2025-06-16 06:16:31'),
(4, 'Work Order baru dari Dispatch: WO-20250616-684FB69EF3DFF - rino perlu dijadwalkan', 24, 0, '2025-06-16 06:16:43'),
(5, 'Work Order baru dari Dispatch: WO-20250616-684FB6A305C05 - bujang perlu dijadwalkan', 25, 0, '2025-06-16 06:16:46'),
(6, 'Work Order baru dari Dispatch: WO-20250616-684FD081F3393 - kiki perlu dijadwalkan', 26, 0, '2025-06-16 08:06:38'),
(7, 'Work Order baru dari Dispatch: WO-20250617-6850CDE1D5990 - romi perlu dijadwalkan', 27, 0, '2025-06-17 02:07:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bor_notifications`
--

CREATE TABLE `bor_notifications` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `wo_id` int(11) DEFAULT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `priority` enum('Normal','High','Urgent') DEFAULT 'Normal',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bor_notifications`
--

INSERT INTO `bor_notifications` (`id`, `message`, `wo_id`, `ticket_id`, `priority`, `is_read`, `created_at`) VALUES
(1, 'Work Order WO-20250613-684BD48B31E30 telah selesai dan di-review Dispatch. Siap untuk penutupan ticket.', 21, 15, 'Normal', 0, '2025-06-15 07:32:24'),
(2, 'Work Order WO-20250610-6847EB06C31EC telah selesai dan di-review Dispatch. Siap untuk penutupan ticket.', 2, 6, 'Normal', 0, '2025-06-15 22:20:40'),
(3, 'Work Order WO-20250611-6848F9CD9BD33 telah selesai dan di-review Dispatch. Siap untuk penutupan ticket.', 4, 7, 'High', 0, '2025-06-15 22:36:10'),
(4, 'Work Order WO-20250616-684FB6960E690 telah selesai dan di-review Dispatch. Siap untuk penutupan ticket.', 23, 19, 'Normal', 0, '2025-06-16 02:15:43'),
(5, 'Work Order WO-20250611-6849019A31308 telah selesai dan di-review Dispatch. Siap untuk penutupan ticket.', 6, 9, 'Normal', 0, '2025-06-16 21:53:10'),
(6, 'Work Order WO-20250611-684929EA71B9B telah selesai dan di-review Dispatch. Siap untuk penutupan ticket.', 8, 10, 'High', 0, '2025-06-16 22:29:04'),
(7, 'Work Order WO-20250611-684929EE27723 telah selesai dan di-review Dispatch. Siap untuk penutupan ticket.', 9, 11, 'Normal', 0, '2025-06-16 22:41:46'),
(8, 'Work Order WO-20250613-684BD5A0B97D4 telah selesai dan di-review Dispatch. Siap untuk penutupan ticket.', 22, 16, 'Normal', 0, '2025-06-16 22:44:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ms_customers`
--

CREATE TABLE `ms_customers` (
  `id` int(11) NOT NULL,
  `customer_id_number` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ms_customers`
--

INSERT INTO `ms_customers` (`id`, `customer_id_number`, `full_name`, `address`, `phone_number`, `email`, `created_at`) VALUES
(1, '01', 'rafki', 'bekasi', '0812345678', 'rafki3663@gmail.com', '2025-06-09 09:59:16'),
(2, '02', 'fakhri', 'menteg', '0987654', 'fakhri01@gmail.com', '2025-06-09 10:05:35'),
(3, '03', 'rafli', 'jakarta', '08122111221', 'rafli12@gmail.com', '2025-06-09 10:15:35'),
(4, 'CUST-1749464792', 'joni', 'cikarang', '0899999999', 'joni1@gmail.com', '2025-06-09 10:26:32'),
(5, 'CUST-1749521545', 'adin', 'lampung', '081234567', 'adin12@gmail.com', '2025-06-10 02:12:25'),
(6, 'CUST-1749525416', 'daffa', 'tanjung senang', '08124536372', 'Muhammad.122140036@student.itera.ac.id', '2025-06-10 03:16:56'),
(7, 'CUST-1749543671', 'naufal', 'pringsewu', '082345322', 'naufal13@gmail.com', '2025-06-10 08:21:11'),
(8, 'CUST-1749612986', 'bintang', 'pekayon', '0975636336', 'star13@gmail.com', '2025-06-11 03:36:26'),
(9, 'CUST-1749613366', 'rehan', 'bandar lampung', '0899997777', 'rehan15@gmail.com', '2025-06-11 03:42:46'),
(10, 'CUST-1749614990', 'per', 'jaktim', '08463773221', 'fer12@gmail.com', '2025-06-11 04:09:50'),
(11, 'CUST-1749625169', 'asep', 'cikini', '0826266262', 'asepkeling@gmail.com', '2025-06-11 06:59:29'),
(12, 'CUST-1749625226', 'koko', 'kampung cina', '083644772232', 'kokocina@gmail.com', '2025-06-11 07:00:26'),
(13, 'CUST-1749625284', 'lulu', 'kalianda ', '0835373177311', 'lululalilalilu@gmail.com', '2025-06-11 07:01:24'),
(14, 'CUST-1749697160', 'dap', 'karang', '0987654344', 'dap112@gmail.com', '2025-06-12 02:59:20'),
(15, 'CUST-1749784998', 'lina', 'arab', '092312311', 'lina12@gmail.com', '2025-06-13 03:23:18'),
(16, 'CUST-TEST-1749799823', 'Test Customer Debug', 'Alamat Test', '081234567890', 'test@debug.com', '2025-06-13 07:30:23'),
(17, 'CUST-1749800332', 'perr', 'jaktim', '0822526621', 'perdana@gmail.com', '2025-06-13 07:38:52'),
(18, 'CUST-1750054426', 'rino', 'palembang', '08927272383', 'rino1@gmail.com', '2025-06-16 06:13:46'),
(19, 'CUST-1750054478', 'bujang', 'padang', '08877727272', 'bujang9@gmail.com', '2025-06-16 06:14:38'),
(20, 'CUST-1750054516', 'acik', 'siguhung', '081723123113', 'acciww13@gmail.com', '2025-06-16 06:15:16'),
(21, 'CUST-1750061160', 'kiki', 'papua', '08327232322', 'kiki112@gmail.com', '2025-06-16 08:06:00'),
(22, 'CUST-1750126021', 'romi', 'jakbar', '0826262121', 'romi17@gmail.com', '2025-06-17 02:07:01'),
(23, 'CUST-1750127671', 'popo', 'jabar', '08323231', 'popo12@gmail.com', '2025-06-17 02:34:31'),
(24, 'CUST-1750129382', 'ken', 'jaksel', '082612611', 'ken@gmail.com', '2025-06-17 03:03:02'),
(25, 'CUST-1750132359', 'ikoy', 'jabung', '08211122121', 'ikoy@gmail.com', '2025-06-17 03:52:39'),
(26, 'CUST-1750132391', 'lopi@gmail.com', 'bukit', '082112112', 'lopi1@gmail.com', '2025-06-17 03:53:11'),
(27, 'CUST-1750140157', 'jojo', 'tanggerang', '0812111', 'jojo1@gmail.com', '2025-06-17 06:02:37'),
(28, 'CUST-1750144003', 'utii', 'padang', '0832322332', 'utii12@gmail.com', '2025-06-17 07:06:43'),
(29, 'CUST-1750144340', 'keke', 'yaman', '09211121', 'kek@snsna', '2025-06-17 07:12:20'),
(30, 'CUST-1750144360', 'wewe', 'sasa', '08233123', 'sassa@gamas', '2025-06-17 07:12:40'),
(31, 'CUST-1750145419', 'saas', 'asa', '2311', 'asa@ds', '2025-06-17 07:30:19'),
(32, 'CUST-1750145431', 'saa', 'sasa', '1211', 'sasa@sasa', '2025-06-17 07:30:31'),
(33, 'CUST-1750155168', 'timothy', 'bsd', '0812112121', 'tim12@gmail.com', '2025-06-17 10:12:48'),
(34, 'CUST-1750213736', 'ginting', 'medan', '083232321', 'ginting@gmail.com', '2025-06-18 02:28:56'),
(35, 'CUST-1750214373', 'dedi', 'bintara', '08212114231', 'dedi@gmail.com', '2025-06-18 02:39:33'),
(36, 'CUST-1750214405', 'azka', 'bintara', '082342313', 'azka@gmail.com', '2025-06-18 02:40:05'),
(37, 'CUST-1750214537', 'johan', 'lampung', '082231112', 'johan@gmail.com', '2025-06-18 02:42:17'),
(38, 'CUST-1750221475', 'kayla', 'agam', '082462331', 'kayla@gmail.com', '2025-06-18 04:37:55'),
(39, 'CUST-1750221498', 'aziz', 'agam', '081231121', 'aziz12@gmail.com', '2025-06-18 04:38:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ms_users`
--

CREATE TABLE `ms_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('Customer Care','BOR','Dispatch','Vendor IKR','Admin IKR','Admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ms_users`
--

INSERT INTO `ms_users` (`id`, `username`, `password`, `full_name`, `role`) VALUES
(1, 'cc_satu', '$2y$10$vY/EbZVcfIORyY//I4043.ljBuYhP3DJKlnTZiZOi47XCf.GqpFoK', 'Staff Customer Care 01', 'Customer Care'),
(2, 'bor_admin', '$2y$10$VWPZSy9mOwiqP9jDS7P8L.YPPEA5rpJ8aIsQYawO1PtMOAQX3xNfm', 'Admin BOR', 'BOR'),
(3, 'dispatch_admin', '$2y$10$R3MZsTyZrbAontGoR8EUHuDZZ.s0ixRZln4JHCWPdl8YxYTb4yfnq', 'Admin Dispatch', 'Dispatch'),
(4, 'ikr_budi', '$2y$10$aWqWaUlhEdQcwDA1csnh1OjkZSijunvTT.dwIjw2dtqNYQxigy2nm', 'Budi Teknisi Senior', 'Vendor IKR'),
(5, 'ikr_sari', '$2y$10$aWqWaUlhEdQcwDA1csnh1OjkZSijunvTT.dwIjw2dtqNYQxigy2nm', 'Sari Teknisi Junior', 'Vendor IKR'),
(6, 'ikr_rudi', '$2y$10$aWqWaUlhEdQcwDA1csnh1OjkZSijunvTT.dwIjw2dtqNYQxigy2nm', 'Rudi Teknisi Expert', 'Vendor IKR'),
(7, 'ikr_lina', '$2y$10$aWqWaUlhEdQcwDA1csnh1OjkZSijunvTT.dwIjw2dtqNYQxigy2nm', 'Lina Teknisi Specialist', 'Vendor IKR'),
(8, 'admin_ikr', '$2y$10$vZ5OSOwHAPZocr5YWc1XUu9iYhuwzGdQZM7B8KKN5yGVBpEibm9Ky', 'Admin Back Office IKR', 'Admin IKR');

-- --------------------------------------------------------

--
-- Struktur dari tabel `technician_notifications`
--

CREATE TABLE `technician_notifications` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `wo_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `technician_notifications`
--

INSERT INTO `technician_notifications` (`id`, `vendor_id`, `wo_id`, `message`, `is_read`, `created_at`) VALUES
(1, 6, 21, 'Anda mendapat Work Order baru: WO-20250613-684BD48B31E30 - Test Customer Debug dijadwalkan untuk 15/06/2025 14:35', 0, '2025-06-13 07:35:55'),
(2, 6, 22, 'Anda mendapat Work Order baru: WO-20250613-684BD5A0B97D4 - perr dijadwalkan untuk 15/06/2025 14:39', 0, '2025-06-13 07:40:01'),
(3, 7, 23, 'Anda mendapat Work Order baru: WO-20250616-684FB6960E690 - acik dijadwalkan untuk 18/06/2025 13:17', 0, '2025-06-16 06:18:08'),
(4, 7, 24, 'Anda mendapat Work Order baru: WO-20250616-684FB69EF3DFF - rino dijadwalkan untuk 20/06/2025 13:18', 0, '2025-06-16 06:18:40'),
(5, 7, 25, 'Anda mendapat Work Order baru: WO-20250616-684FB6A305C05 - bujang dijadwalkan untuk 24/06/2025 13:18', 0, '2025-06-16 06:18:57'),
(6, 7, 26, 'Anda mendapat Work Order baru: WO-20250616-684FD081F3393 - kiki dijadwalkan untuk 19/06/2025 15:06', 0, '2025-06-16 08:06:59'),
(7, 7, 27, 'Anda mendapat Work Order baru: WO-20250617-6850CDE1D5990 - romi dijadwalkan untuk 20/06/2025 09:08', 0, '2025-06-17 02:08:33'),
(8, 7, 28, 'Anda mendapat Work Order baru: WO-20250617-6850D44785C8A - popo dijadwalkan untuk 19/06/2025 09:36', 0, '2025-06-17 02:36:40'),
(9, 7, 29, 'Anda mendapat Work Order baru: WO-20250617-6850DAFABCF16 - ken dijadwalkan untuk 19/06/2025 10:03', 0, '2025-06-17 03:03:54'),
(10, 7, 30, 'Anda mendapat Work Order baru: WO-20250617-6850E6C2B7721 - ikoy dijadwalkan untuk 20/06/2025 10:53', 0, '2025-06-17 03:54:05'),
(11, 7, 31, 'Anda mendapat Work Order baru: WO-20250617-685110CB99A42 - jojo dijadwalkan untuk 20/06/2025 13:53', 0, '2025-06-17 06:53:24'),
(12, 7, 32, 'Anda mendapat Work Order baru: WO-20250617-685114117BAB8 - utii dijadwalkan untuk 21/06/2025 14:07', 0, '2025-06-17 07:07:46'),
(13, 7, 33, 'Anda mendapat Work Order baru: WO-20250617-68511576C10FD - wewe dijadwalkan untuk 19/06/2025 14:13', 0, '2025-06-17 07:13:21'),
(14, 7, 34, 'Anda mendapat Work Order baru: WO-20250617-6851157979A2B - keke dijadwalkan untuk 19/06/2025 14:13', 0, '2025-06-17 07:13:30'),
(15, 7, 35, 'Anda mendapat Work Order baru: WO-20250617-685119A947574 - saa dijadwalkan untuk 19/06/2025 14:31', 0, '2025-06-17 07:31:15'),
(16, 7, 36, 'Anda mendapat Work Order baru: WO-20250617-685119AB6E7EB - saas dijadwalkan untuk 19/06/2025 14:31', 0, '2025-06-17 07:31:24'),
(17, 7, 37, 'Anda mendapat Work Order baru: WO-20250617-68513FACCA680 - timothy dijadwalkan untuk 19/06/2025 17:14', 0, '2025-06-17 10:14:15'),
(18, 7, 38, 'Anda mendapat Work Order baru: WO-20250618-6852247DA006A - ginting dijadwalkan untuk 20/06/2025 09:30', 0, '2025-06-18 02:30:40'),
(19, 7, 39, 'Anda mendapat Work Order baru: WO-20250618-68522721E2DB0 - azka dijadwalkan untuk 20/06/2025 09:46', 0, '2025-06-18 02:47:05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tr_bor_final_reviews`
--

CREATE TABLE `tr_bor_final_reviews` (
  `id` int(11) NOT NULL,
  `work_order_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `bor_user_id` int(11) NOT NULL,
  `decision` enum('approve','reject') NOT NULL,
  `final_ticket_status` varchar(50) NOT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tr_dispatch_reviews`
--

CREATE TABLE `tr_dispatch_reviews` (
  `id` int(11) NOT NULL,
  `work_order_id` int(11) NOT NULL,
  `dispatch_user_id` int(11) NOT NULL,
  `work_quality` enum('Excellent','Good','Satisfactory','Needs Improvement') NOT NULL,
  `ticket_resolution` enum('Fully Resolved','Partially Resolved','Not Resolved') NOT NULL,
  `dispatch_notes` text NOT NULL,
  `bor_summary` text NOT NULL,
  `customer_feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tr_dispatch_reviews`
--

INSERT INTO `tr_dispatch_reviews` (`id`, `work_order_id`, `dispatch_user_id`, `work_quality`, `ticket_resolution`, `dispatch_notes`, `bor_summary`, `customer_feedback`, `created_at`) VALUES
(1, 21, 3, 'Excellent', 'Fully Resolved', 'oke aman', 'aman bor', 'ga ada', '2025-06-15 07:32:24'),
(2, 2, 3, 'Excellent', 'Fully Resolved', 'ddaaa', 'saa', 'saas', '2025-06-15 22:20:40'),
(3, 4, 3, 'Excellent', 'Partially Resolved', 'asaa', 'saa', 'asaa', '2025-06-15 22:36:10'),
(4, 23, 3, 'Good', 'Fully Resolved', 'asa', 'saa', 'saa', '2025-06-16 02:15:43'),
(5, 28, 3, 'Excellent', 'Fully Resolved', 'saa', 'sas', 'saas', '2025-06-16 21:46:18'),
(6, 6, 3, 'Excellent', 'Fully Resolved', 'saaa', 'asa', 'saas', '2025-06-16 21:53:10'),
(7, 8, 3, 'Excellent', 'Partially Resolved', 'saa', 'sas', 'saas', '2025-06-16 22:29:04'),
(8, 9, 3, 'Excellent', 'Fully Resolved', 'saa', 'aa', 'asa', '2025-06-16 22:41:46'),
(9, 22, 3, 'Excellent', 'Fully Resolved', 'asasa', 'asa', 'saa', '2025-06-16 22:44:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tr_tickets`
--

CREATE TABLE `tr_tickets` (
  `id` int(11) NOT NULL,
  `ticket_code` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Open','On Progress - Customer Care','On Progress - BOR','Waiting for Dispatch','Waiting for Admin IKR','Waiting For BOR Review','Closed - Solved','Closed - Unsolved') NOT NULL DEFAULT 'Open',
  `created_by_user_id` int(11) NOT NULL,
  `current_owner_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tr_tickets`
--

INSERT INTO `tr_tickets` (`id`, `ticket_code`, `customer_id`, `title`, `description`, `status`, `created_by_user_id`, `current_owner_user_id`, `created_at`, `closed_at`) VALUES
(2, 'TICKET-20250609-6846B44774FB6', 3, 'jaringan putus', 'jaringan ngelag', 'Closed - Solved', 1, 1, '2025-06-09 10:15:35', '2025-06-09 21:37:11'),
(3, 'TICKET-20250609-6846B6D818442', 4, 'router mati', 'router tidak bisa hidup', 'Closed - Solved', 1, 2, '2025-06-09 10:26:32', '2025-06-09 22:14:16'),
(4, 'TICKET-20250610-6847948944398', 5, 'jaringan putus', 'jaringan hilang', 'Closed - Solved', 1, 2, '2025-06-10 02:12:25', '2025-06-10 02:28:23'),
(5, 'TICKET-20250610-6847A3A840AE3', 6, 'jaringan ngelag', 'jaringan tidak stabil', 'Waiting for Dispatch', 1, 3, '2025-06-10 03:16:56', NULL),
(6, 'TICKET-20250610-6847EAF760B6E', 7, 'jaringan ngelag', 'mati totall', 'Closed - Solved', 1, 2, '2025-06-10 08:21:11', '2025-06-17 01:28:59'),
(7, 'TICKET-20250611-6848F9BAA3D61', 8, 'router meledak', 'tiba-tiba router keluar asap', 'Closed - Solved', 1, 2, '2025-06-11 03:36:26', '2025-06-17 01:29:24'),
(8, 'TICKET-20250611-6848FB36857CC', 9, 'router tidak mau hidup', 'router tiba tiba mati dan tidah mau hidup lagi', 'Waiting for Dispatch', 1, 3, '2025-06-11 03:42:46', NULL),
(9, 'TICKET-20250611-6849018E177E3', 10, 'router tidak mau hidup', 'mati total', 'Closed - Solved', 1, 2, '2025-06-11 04:09:50', '2025-06-16 22:01:53'),
(10, 'TICKET-20250611-68492951CFAB0', 11, 'kabel putus', 'kabel terpukus karena anak anak main layangan', 'Closed - Solved', 1, 2, '2025-06-11 06:59:29', '2025-06-16 22:30:25'),
(11, 'TICKET-20250611-6849298A59F7E', 12, 'jaringan lambat', 'kecepatan jaringan tidak sesuai dengan yang dibeli', 'Closed - Solved', 1, 2, '2025-06-11 07:00:26', '2025-06-16 22:44:15'),
(12, 'TICKET-20250611-684929C44E681', 13, 'router meledak', 'duarrrrrr', 'Waiting for Dispatch', 1, 3, '2025-06-11 07:01:24', NULL),
(13, 'TICKET-20250612-684A42883ED55', 14, 'Modem/Router Bermasalah', 'router mati lagi', 'Waiting for Dispatch', 1, 3, '2025-06-12 02:59:20', NULL),
(14, 'TICKET-20250613-684B99A6E16B6', 15, 'Internet Mati Total', 'kabel putus jadi jaringan mati total', 'Closed - Solved', 1, 2, '2025-06-13 03:23:18', '2025-06-13 01:21:43'),
(15, 'TICKET-TEST-20250613093023', 16, 'Test Debug Issue', 'Test description for debugging', 'Waiting For BOR Review', 1, 3, '2025-06-13 07:30:23', NULL),
(16, 'TICKET-20250613-684BD58C288BB', 17, 'Internet Lambat', 'ngelag', 'Closed - Solved', 1, 2, '2025-06-13 07:38:52', '2025-06-16 22:45:00'),
(17, 'TICKET-20250616-684FB61A736EE', 18, 'WiFi Tidak Bisa Connect', 'wifi error', 'Closed - Solved', 1, 2, '2025-06-16 06:13:46', '2025-06-17 01:43:01'),
(18, 'TICKET-20250616-684FB64E23BDC', 19, 'pengen aja ', 'ga tauu', 'Waiting For BOR Review', 1, 3, '2025-06-16 06:14:38', NULL),
(19, 'TICKET-20250616-684FB674240EA', 20, 'Internet Mati ss', 'mati total', 'Closed - Solved', 1, 2, '2025-06-16 06:15:16', '2025-06-17 01:28:29'),
(20, 'TICKET-20250616-684FD068C6BE8', 21, 'Internet Lambat', 'sasaa', 'Waiting For BOR Review', 1, 3, '2025-06-16 08:06:00', NULL),
(21, 'TICKET-20250617-6850CDC54CC5E', 22, 'Internet Mati Total', 'matii', 'Waiting For BOR Review', 1, 3, '2025-06-17 02:07:01', NULL),
(22, 'TICKET-20250617-6850D43723E71', 23, 'Internet Mati Total', 'saasa', 'Closed - Solved', 1, 2, '2025-06-17 02:34:31', '2025-06-16 22:02:02'),
(23, 'TICKET-20250617-6850DAE6C3403', 24, 'router tidak mau hidup', 'asaa', 'Waiting For BOR Review', 1, 3, '2025-06-17 03:03:02', NULL),
(24, 'TICKET-20250617-6850E68750769', 25, 'Internet Mati Total', 'asaa', 'Waiting For BOR Review', 1, 3, '2025-06-17 03:52:39', NULL),
(25, 'TICKET-20250617-6850E6A7B07B3', 26, 'jaringan putus', 'sasq', 'Closed - Solved', 1, 2, '2025-06-17 03:53:11', '2025-06-16 22:53:31'),
(26, 'TICKET-20250617-685104FDCD79D', 27, 'Internet Mati Total', 'asaa', 'Waiting For BOR Review', 1, 3, '2025-06-17 06:02:37', NULL),
(27, 'TICKET-20250617-6851140348A54', 28, 'Internet Mati Total', 'saa', 'Waiting For BOR Review', 1, 3, '2025-06-17 07:06:43', NULL),
(28, 'TICKET-20250617-685115546B85A', 29, 'router meledak', 'aasa', 'Waiting For BOR Review', 1, 3, '2025-06-17 07:12:20', NULL),
(29, 'TICKET-20250617-68511568F20D0', 30, 'Internet Mati Total', 'asaa', 'Waiting For BOR Review', 1, 3, '2025-06-17 07:12:40', NULL),
(30, 'TICKET-20250617-6851198B6FA53', 31, 'Internet Mati Total', 'asa', 'Waiting For BOR Review', 1, 3, '2025-06-17 07:30:19', NULL),
(31, 'TICKET-20250617-68511997F3BDA', 32, 'Internet Mati Total', 'sasa', 'Waiting For BOR Review', 1, 3, '2025-06-17 07:30:31', NULL),
(32, 'TICKET-20250617-68513FA0C6F14', 33, 'Internet Lambat', 'asa', 'Closed - Solved', 1, 2, '2025-06-17 10:12:48', '2025-06-17 05:15:32'),
(33, 'TICKET-20250618-68522468E09B7', 34, 'Internet Mati Total', 'jaringan hilang', 'Closed - Solved', 1, 2, '2025-06-18 02:28:56', '2025-06-17 21:33:55'),
(34, 'TICKET-20250618-685226E558B3C', 35, 'Internet Mati Total', 'qwqsaa', 'Waiting for Dispatch', 1, 3, '2025-06-18 02:39:33', NULL),
(35, 'TICKET-20250618-68522705CC905', 36, 'Internet Mati Total', 'saaaaass', 'Closed - Solved', 1, 2, '2025-06-18 02:40:05', '2025-06-17 22:06:39'),
(36, 'TICKET-20250618-6852278978944', 37, 'Internet Lambat', 'ngelag', 'Waiting for Dispatch', 1, 3, '2025-06-18 02:42:17', NULL),
(37, 'TICKET-20250618-685242A33A0E5', 38, 'Internet Mati Total', 'saasa', 'On Progress - BOR', 1, 2, '2025-06-18 04:37:55', NULL),
(38, 'TICKET-20250618-685242BA7596A', 39, 'Internet Mati Total', 'asaas', 'On Progress - BOR', 1, 2, '2025-06-18 04:38:18', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tr_ticket_updates`
--

CREATE TABLE `tr_ticket_updates` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `update_type` enum('Comment','Status Change','Escalation','First Level Handling') NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tr_ticket_updates`
--

INSERT INTO `tr_ticket_updates` (`id`, `ticket_id`, `user_id`, `update_type`, `description`, `created_at`) VALUES
(1, 2, 1, 'Status Change', 'Ticket diselesaikan oleh Customer Care', '2025-06-10 02:37:11'),
(2, 4, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-10 03:13:20'),
(3, 3, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-10 03:13:58'),
(4, 3, 2, 'Status Change', 'Ticket diselesaikan oleh BOR - Masalah berhasil diperbaiki secara remote', '2025-06-10 03:14:16'),
(5, 5, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-10 03:17:01'),
(6, 4, 2, 'Status Change', 'Ticket diselesaikan oleh BOR - Masalah berhasil diperbaiki secara remote', '2025-06-10 07:28:23'),
(7, 5, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-10 07:46:44'),
(8, 5, 3, 'Status Change', 'Work Order dijadwalkan untuk kunjungan teknisi pada 10/06/2025 15:00 oleh Budi Teknisi Senior', '2025-06-10 08:00:17'),
(9, 6, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-10 08:21:17'),
(10, 6, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-10 08:21:26'),
(11, 6, 3, 'Status Change', 'Work Order dijadwalkan untuk kunjungan teknisi pada 07/06/2025 15:22 oleh Rudi Teknisi Expert', '2025-06-10 08:22:23'),
(12, 5, 3, 'Status Change', 'Work Order ditunda oleh Dispatch. Alasan: customer tidah bisa dihari ini', '2025-06-11 02:27:14'),
(13, 5, 3, 'Status Change', 'Work Order dijadwalkan untuk kunjungan teknisi pada 12/06/2025 09:28 oleh Rudi Teknisi Expert', '2025-06-11 02:29:05'),
(14, 5, 3, 'Status Change', 'Work Order dijadwalkan ulang untuk 07/06/2025 09:28 dan di-assign ke Rudi Teknisi Expert', '2025-06-11 02:29:22'),
(15, 5, 3, 'Status Change', 'Work Order dijadwalkan ulang untuk 07/06/2025 15:22 dan di-assign ke Rudi Teknisi Expert', '2025-06-11 02:29:41'),
(16, 5, 3, 'Status Change', 'Work Order dibatalkan oleh Dispatch. Alasan: tidak jadi jaringan sudah muncul kembali', '2025-06-11 02:30:04'),
(17, 6, 3, 'Status Change', 'Work Order dijadwalkan ulang untuk 12/06/2025 15:22 dan di-assign ke Rudi Teknisi Expert', '2025-06-11 02:55:54'),
(18, 5, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-11 02:56:41'),
(19, 5, 3, 'Status Change', 'Work Order dijadwalkan untuk kunjungan teknisi pada 13/06/2025 09:56 oleh Rudi Teknisi Expert', '2025-06-11 02:57:08'),
(20, 6, 6, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Masalah berhasil diperbaiki. Laporan: jaringan sudah diperbaiki...', '2025-06-11 02:57:54'),
(21, 5, 6, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: kaber fiber putus. Estimasi pengerjaan: 60 menit', '2025-06-11 03:32:31'),
(22, 7, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-11 03:36:34'),
(23, 7, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-11 03:36:45'),
(24, 7, 3, 'Status Change', 'Work Order dijadwalkan untuk kunjungan teknisi pada 12/06/2025 10:37 oleh Rudi Teknisi Expert', '2025-06-11 03:37:20'),
(25, 7, 6, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied', '2025-06-11 03:39:50'),
(26, 8, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-11 03:42:50'),
(27, 8, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-11 03:43:15'),
(28, 8, 3, 'Status Change', 'Work Order dijadwalkan untuk kunjungan teknisi pada 14/06/2025 10:44 oleh Rudi Teknisi Expert', '2025-06-11 03:44:25'),
(29, 8, 6, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: router mati totol. Estimasi pengerjaan: 10 menit', '2025-06-11 03:45:18'),
(30, 9, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-11 04:09:53'),
(31, 9, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-11 04:10:02'),
(32, 9, 3, 'Status Change', 'Work Order dijadwalkan untuk kunjungan teknisi pada 14/06/2025 11:10 oleh Rudi Teknisi Expert', '2025-06-11 04:10:28'),
(33, 9, 6, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: mau dikerjakan. Estimasi pengerjaan: 60 menit', '2025-06-11 04:42:18'),
(34, 12, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-11 07:01:36'),
(35, 11, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-11 07:01:40'),
(36, 10, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-11 07:01:43'),
(37, 12, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-11 07:01:58'),
(38, 10, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-11 07:02:02'),
(39, 11, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-11 07:02:06'),
(40, 11, 3, 'Status Change', 'Work Order dijadwalkan untuk kunjungan teknisi pada 12/06/2025 14:09 oleh Rudi Teknisi Expert', '2025-06-11 07:09:58'),
(41, 9, 6, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 156 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-11 07:18:30'),
(42, 9, 3, 'Comment', 'NOTIFICATION: Work Order WO-20250611-6849019A31308 telah diselesaikan teknisi dan siap untuk review BOR.', '2025-06-11 07:18:30'),
(43, 9, 2, 'Status Change', 'BOR telah mereview Work Order WO-20250611-6849019A31308 dan menyetujui bahwa masalah telah berhasil diselesaikan oleh teknisi Rudi Teknisi Expert. Ticket ditutup sebagai SOLVED.', '2025-06-11 07:19:52'),
(44, 11, 6, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: mati. Estimasi pengerjaan: 60 menit', '2025-06-11 07:25:11'),
(45, 11, 6, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 43 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-11 07:39:04'),
(46, 11, 2, 'Status Change', 'BOR telah mereview Work Order WO-20250611-684929EE27723 dan menyetujui bahwa masalah telah berhasil diselesaikan oleh teknisi Rudi Teknisi Expert. Ticket ditutup sebagai SOLVED.', '2025-06-11 07:39:59'),
(47, 13, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-12 02:59:39'),
(48, 13, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-12 03:01:07'),
(49, 10, 3, 'Status Change', 'Work Order dijadwalkan untuk kunjungan teknisi pada 13/06/2025 10:04 oleh Lina Teknisi Specialist', '2025-06-12 03:04:56'),
(50, 14, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-13 03:23:23'),
(58, 14, 2, 'Status Change', 'Ticket diselesaikan oleh BOR - Masalah berhasil diperbaiki secara remote', '2025-06-13 06:21:43'),
(59, 15, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-13 07:34:35'),
(60, 15, 3, 'Status Change', 'Work Order diteruskan dari Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi', '2025-06-13 07:35:15'),
(61, 15, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 15/06/2025 14:35 dan di-assign ke teknisi Rudi Teknisi Expert. Instruksi khusus: laksanakan', '2025-06-13 07:35:55'),
(62, 16, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-13 07:38:58'),
(63, 16, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-13 07:39:12'),
(64, 16, 3, 'Status Change', 'Work Order diteruskan dari Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi', '2025-06-13 07:39:34'),
(65, 16, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 15/06/2025 14:39 dan di-assign ke teknisi Rudi Teknisi Expert. Instruksi khusus: aasa', '2025-06-13 07:40:01'),
(66, 15, 6, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: wasas. Estimasi pengerjaan: 60 menit', '2025-06-13 08:13:35'),
(67, 15, 6, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 12 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-13 08:23:29'),
(68, 15, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch. Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Siap untuk dikirim ke BOR untuk penutupan ticket.', '2025-06-15 12:32:24'),
(69, 16, 6, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: test. Estimasi pengerjaan: 60 menit', '2025-06-16 02:37:36'),
(70, 16, 6, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 66 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-16 02:38:19'),
(71, 6, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch. Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Siap untuk dikirim ke BOR untuk penutupan ticket.', '2025-06-16 03:20:40'),
(72, 7, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch. Kualitas kerja: Excellent. Status penyelesaian: Partially Resolved. Siap untuk dikirim ke BOR untuk penutupan ticket.', '2025-06-16 03:36:10'),
(73, 19, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-16 06:15:25'),
(74, 18, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-16 06:15:29'),
(75, 17, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-16 06:15:32'),
(76, 19, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-16 06:15:50'),
(77, 17, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-16 06:15:58'),
(78, 18, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-16 06:16:03'),
(79, 19, 3, 'Status Change', 'Work Order diteruskan dari Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi', '2025-06-16 06:16:31'),
(80, 17, 3, 'Status Change', 'Work Order diteruskan dari Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi', '2025-06-16 06:16:43'),
(81, 18, 3, 'Status Change', 'Work Order diteruskan dari Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi', '2025-06-16 06:16:46'),
(82, 19, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 18/06/2025 13:17 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: tolong lina', '2025-06-16 06:18:08'),
(83, 17, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 20/06/2025 13:18 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: linaaa', '2025-06-16 06:18:40'),
(84, 18, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 24/06/2025 13:18 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: aaaa', '2025-06-16 06:18:57'),
(85, 10, 7, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: sorry yee. Estimasi pengerjaan: 60 menit', '2025-06-16 06:20:01'),
(86, 10, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Cannot Fix. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 10 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Neutral.', '2025-06-16 06:21:07'),
(87, 19, 7, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: mari kita cobaa. Estimasi pengerjaan: 60 menit', '2025-06-16 06:22:24'),
(88, 19, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 100 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Satisfied.', '2025-06-16 06:23:14'),
(89, 19, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch. Kualitas kerja: Good. Status penyelesaian: Fully Resolved. Siap untuk dikirim ke BOR untuk penutupan ticket.', '2025-06-16 07:15:43'),
(90, 17, 7, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: assa. Estimasi pengerjaan: 60 menit', '2025-06-16 07:41:23'),
(91, 17, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 322 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Satisfied.', '2025-06-16 07:42:07'),
(92, 18, 7, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: wwwwwwwq. Estimasi pengerjaan: 60 menit', '2025-06-16 07:59:20'),
(93, 18, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 32 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-16 07:59:52'),
(94, 20, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-16 08:06:06'),
(95, 20, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-16 08:06:25'),
(96, 20, 3, 'Status Change', 'Work Order diteruskan dari Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi', '2025-06-16 08:06:38'),
(97, 20, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 19/06/2025 15:06 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: asa', '2025-06-16 08:06:59'),
(98, 20, 7, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: asa. Estimasi pengerjaan: 60 menit', '2025-06-16 08:07:23'),
(99, 20, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 211 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Satisfied.', '2025-06-16 08:07:56'),
(100, 21, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 02:07:11'),
(101, 21, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-17 02:07:29'),
(102, 21, 3, 'Status Change', 'Work Order diteruskan dari Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi', '2025-06-17 02:07:57'),
(103, 21, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 20/06/2025 09:08 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: ayoo', '2025-06-17 02:08:33'),
(104, 21, 7, 'Status Change', 'Teknisi IKR mulai mengerjakan Work Order - Konfirmasi sudah sampai di lokasi customer. Catatan awal: asaa. Estimasi pengerjaan: 60 menit', '2025-06-17 02:09:28'),
(105, 21, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 212 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-17 02:10:08'),
(106, 22, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 02:34:35'),
(107, 22, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-17 02:34:47'),
(108, 22, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-17 02:36:05'),
(109, 22, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 19/06/2025 09:36 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: saaa', '2025-06-17 02:36:40'),
(110, 22, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: sasa', '2025-06-17 02:36:56'),
(111, 22, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 332 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-17 02:37:38'),
(112, 22, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch dengan hasil: Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Catatan Dispatch: saa... Dikirim ke BOR untuk penutupan ticket.', '2025-06-17 02:46:18'),
(113, 9, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch dengan hasil: Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Catatan Dispatch: saaa... Dikirim ke BOR untuk penutupan ticket.', '2025-06-17 02:53:10'),
(114, 9, 2, 'Status Change', 'Ticket ditutup oleh BOR sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja teknisi: Excellent. Status penyelesaian: Fully Resolved. Teknisi: Rudi Teknisi Expert. Reviewed by: Admin Dispatch.', '2025-06-17 03:01:53'),
(115, 22, 2, 'Status Change', 'Ticket ditutup oleh BOR sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja teknisi: Excellent. Status penyelesaian: Fully Resolved. Teknisi: Lina Teknisi Specialist. Reviewed by: Admin Dispatch.', '2025-06-17 03:02:02'),
(116, 23, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 03:03:07'),
(117, 23, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-17 03:03:22'),
(118, 23, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-17 03:03:37'),
(119, 23, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 19/06/2025 10:03 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: assa', '2025-06-17 03:03:54'),
(120, 23, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: saa', '2025-06-17 03:04:20'),
(121, 23, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 21 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-17 03:04:50'),
(122, 10, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch dengan hasil: Kualitas kerja: Excellent. Status penyelesaian: Partially Resolved. Catatan Dispatch: saa... Dikirim ke BOR untuk penutupan ticket.', '2025-06-17 03:29:04'),
(123, 10, 2, 'Status Change', 'Ticket ditutup oleh BOR sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja teknisi: Excellent. Status penyelesaian: Partially Resolved. Teknisi: Lina Teknisi Specialist. Reviewed by: Admin Dispatch.', '2025-06-17 03:30:25'),
(124, 11, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch dengan hasil: Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Catatan Dispatch: saa... Dikirim ke BOR untuk penutupan ticket.', '2025-06-17 03:41:46'),
(125, 11, 2, 'Status Change', 'Ticket ditutup oleh BOR sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja teknisi: Excellent. Status penyelesaian: Fully Resolved. Teknisi: Rudi Teknisi Expert. Reviewed by: Admin Dispatch.', '2025-06-17 03:44:15'),
(126, 16, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch dengan hasil: Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Catatan Dispatch: asasa... Dikirim ke BOR untuk penutupan ticket.', '2025-06-17 03:44:41'),
(127, 16, 2, 'Status Change', 'Ticket ditutup oleh BOR sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja teknisi: Excellent. Status penyelesaian: Fully Resolved. Teknisi: Rudi Teknisi Expert. Reviewed by: Admin Dispatch.', '2025-06-17 03:45:00'),
(128, 25, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 03:53:17'),
(129, 24, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 03:53:20'),
(130, 25, 2, 'Status Change', 'Ticket diselesaikan oleh BOR - Masalah berhasil diperbaiki secara remote', '2025-06-17 03:53:31'),
(131, 24, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-17 03:53:38'),
(132, 24, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-17 03:53:47'),
(133, 24, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 20/06/2025 10:53 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: asa', '2025-06-17 03:54:05'),
(134, 24, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: asa', '2025-06-17 03:54:18'),
(135, 24, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Partial. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 21 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-17 03:54:49'),
(136, 26, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 06:02:41'),
(137, 19, 2, 'Status Change', 'BOR APPROVED: Ticket ditutup sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja: Good. Status penyelesaian: Fully Resolved. Teknisi: Lina Teknisi Specialist. Reviewed by: Admin Dispatch.', '2025-06-17 06:28:29'),
(138, 6, 2, 'Status Change', 'BOR APPROVED: Ticket ditutup sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Teknisi: Rudi Teknisi Expert. Reviewed by: Admin Dispatch.', '2025-06-17 06:28:59'),
(139, 7, 2, 'Status Change', 'BOR APPROVED: Ticket ditutup sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja: Excellent. Status penyelesaian: Partially Resolved. Teknisi: Rudi Teknisi Expert. Reviewed by: Admin Dispatch.', '2025-06-17 06:29:24'),
(140, 17, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch dengan hasil: Kualitas kerja: Good. Status penyelesaian: Fully Resolved. Catatan Dispatch: aasaa... Dikirim ke BOR untuk penutupan ticket.', '2025-06-17 06:42:52'),
(141, 17, 2, 'Status Change', 'BOR APPROVED: Ticket ditutup sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja: Good. Status penyelesaian: Fully Resolved. Teknisi: Lina Teknisi Specialist. Reviewed by: Admin Dispatch.', '2025-06-17 06:43:01'),
(142, 26, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-17 06:52:59'),
(143, 26, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-17 06:53:11'),
(144, 26, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 20/06/2025 13:53 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: assasa', '2025-06-17 06:53:24'),
(145, 26, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: assa', '2025-06-17 06:53:36'),
(146, 26, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 22 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-17 06:54:10'),
(147, 27, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 07:06:48'),
(148, 27, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-17 07:06:57'),
(149, 27, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-17 07:07:29'),
(150, 27, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 21/06/2025 14:07 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: sasa', '2025-06-17 07:07:46'),
(151, 27, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: sasa', '2025-06-17 07:07:57'),
(152, 27, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 21 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-17 07:08:26'),
(153, 29, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 07:12:44'),
(154, 28, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 07:12:47'),
(155, 29, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-17 07:12:54'),
(156, 28, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-17 07:12:57'),
(157, 29, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-17 07:13:03'),
(158, 28, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-17 07:13:05'),
(159, 29, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 19/06/2025 14:13 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: asa', '2025-06-17 07:13:21'),
(160, 28, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 19/06/2025 14:13 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: asa', '2025-06-17 07:13:30'),
(161, 29, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: sasa', '2025-06-17 07:13:44'),
(162, 29, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 21 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-17 07:14:13'),
(163, 28, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: saa', '2025-06-17 07:26:01'),
(164, 28, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 12 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Satisfied.', '2025-06-17 07:26:30'),
(165, 31, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 07:30:37'),
(166, 30, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 07:30:40'),
(167, 31, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-17 07:30:49'),
(168, 30, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-17 07:30:51'),
(169, 31, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-17 07:31:00'),
(170, 30, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-17 07:31:03'),
(171, 31, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 19/06/2025 14:31 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: sasa', '2025-06-17 07:31:15'),
(172, 30, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 19/06/2025 14:31 dan di-assign ke teknisi Lina Teknisi Specialist', '2025-06-17 07:31:24'),
(173, 31, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: sa', '2025-06-17 07:31:38'),
(174, 31, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 12 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-17 07:32:09'),
(175, 30, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: aa', '2025-06-17 07:37:52'),
(176, 30, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 12 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-17 07:38:28'),
(177, 32, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-17 10:12:51'),
(178, 32, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-17 10:13:00'),
(179, 32, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-17 10:14:00'),
(180, 32, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 19/06/2025 17:14 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: saasa', '2025-06-17 10:14:15'),
(181, 32, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: sasaa', '2025-06-17 10:14:30'),
(182, 32, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 12 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-17 10:15:03'),
(183, 32, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch dengan hasil: Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Catatan Dispatch: sas... Dikirim ke BOR untuk penutupan ticket.', '2025-06-17 10:15:22'),
(184, 32, 2, 'Status Change', 'BOR APPROVED: Ticket ditutup sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Teknisi: Lina Teknisi Specialist. Reviewed by: Admin Dispatch.', '2025-06-17 10:15:32'),
(185, 33, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-18 02:29:00'),
(186, 33, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-18 02:29:17'),
(187, 33, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-18 02:29:52'),
(188, 33, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 20/06/2025 09:30 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: tolongg', '2025-06-18 02:30:40'),
(189, 33, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: wkwkwk', '2025-06-18 02:31:00'),
(190, 33, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 22 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-18 02:31:58'),
(191, 33, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch dengan hasil: Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Catatan Dispatch: saa... Dikirim ke BOR untuk penutupan ticket.', '2025-06-18 02:33:40'),
(192, 33, 2, 'Status Change', 'BOR APPROVED: Ticket ditutup sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Teknisi: Lina Teknisi Specialist. Reviewed by: Admin Dispatch.', '2025-06-18 02:33:55'),
(193, 35, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-18 02:40:09'),
(194, 34, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-18 02:40:13'),
(195, 35, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-18 02:40:33'),
(196, 34, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-18 02:40:36'),
(197, 36, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-18 02:43:20'),
(198, 35, 3, 'Status Change', 'Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.', '2025-06-18 02:46:08'),
(199, 35, 8, 'Status Change', 'Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada 20/06/2025 09:46 dan di-assign ke teknisi Lina Teknisi Specialist. Instruksi khusus: sasaa', '2025-06-18 02:47:05'),
(200, 35, 7, 'Status Change', 'Teknisi IKR telah memulai Work Order. Lokasi telah dikonfirmasi. Estimasi waktu pengerjaan: 60 menit. Catatan awal: sasaa', '2025-06-18 02:52:22'),
(201, 35, 7, 'Status Change', 'Work Order diselesaikan oleh teknisi IKR - Status: Solved. Laporan pekerjaan telah disubmit dan menunggu review dari BOR. Waktu pengerjaan: 32 menit (estimasi: 60 menit). Ada pergantian equipment. Menggunakan 1 material. Customer satisfaction: Very Satisfied.', '2025-06-18 02:57:36'),
(202, 35, 3, 'Status Change', 'Work Order telah di-review oleh Dispatch dengan hasil: Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Catatan Dispatch: sasas... Dikirim ke BOR untuk penutupan ticket.', '2025-06-18 03:01:11'),
(203, 35, 2, 'Status Change', 'BOR APPROVED: Ticket ditutup sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi. Kualitas kerja: Excellent. Status penyelesaian: Fully Resolved. Teknisi: Lina Teknisi Specialist. Reviewed by: Admin Dispatch.', '2025-06-18 03:06:39'),
(204, 36, 2, 'Escalation', 'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan', '2025-06-18 04:32:01'),
(205, 38, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-18 04:38:21'),
(206, 37, 1, 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR', '2025-06-18 04:38:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tr_work_completion_details`
--

CREATE TABLE `tr_work_completion_details` (
  `id` int(11) NOT NULL,
  `work_order_id` int(11) NOT NULL,
  `completion_status` enum('Solved','Partial','Cannot Fix','Customer Not Available') NOT NULL,
  `work_description` text NOT NULL,
  `equipment_replaced` text DEFAULT NULL,
  `cables_replaced` text DEFAULT NULL,
  `new_installations` text DEFAULT NULL,
  `signal_before` varchar(50) DEFAULT NULL,
  `signal_after` varchar(50) DEFAULT NULL,
  `speed_test_result` varchar(100) DEFAULT NULL,
  `materials_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`materials_used`)),
  `customer_satisfaction` enum('Very Satisfied','Satisfied','Neutral','Unsatisfied') DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tr_work_orders`
--

CREATE TABLE `tr_work_orders` (
  `id` int(11) NOT NULL,
  `wo_code` varchar(50) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `created_by_dispatch_id` int(11) NOT NULL,
  `forwarded_by_dispatch_id` int(11) DEFAULT NULL,
  `reviewed_by_dispatch_id` int(11) DEFAULT NULL,
  `closed_by_bor_id` int(11) DEFAULT NULL,
  `assigned_to_vendor_id` int(11) NOT NULL,
  `scheduled_by_admin_id` int(11) DEFAULT NULL,
  `scheduled_visit_date` datetime DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `forwarded_to_admin_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `bor_closed_at` datetime DEFAULT NULL,
  `bor_closed_by` int(11) DEFAULT NULL,
  `status` enum('Pending','Sent to Dispatch','Received by Admin IKR','Scheduled by Admin IKR','Scheduled','In Progress','Completed by Technician','Completed','Reviewed by Dispatch','Waiting For BOR Review','Closed by BOR','Cancelled') NOT NULL DEFAULT 'Pending',
  `priority_level` enum('Normal','Medium','High') DEFAULT 'Normal',
  `visit_report` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `dispatch_review_notes` text DEFAULT NULL,
  `work_quality_rating` enum('Excellent','Good','Satisfactory','Needs Improvement') DEFAULT NULL,
  `ticket_resolution_status` enum('Fully Resolved','Partially Resolved','Not Resolved') DEFAULT NULL,
  `bor_decision` enum('approve','reject') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `estimated_duration` int(11) DEFAULT NULL,
  `actual_duration` int(11) DEFAULT NULL,
  `pre_work_notes` text DEFAULT NULL,
  `priority_updated_by` int(11) DEFAULT NULL,
  `priority_updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tr_work_orders`
--

INSERT INTO `tr_work_orders` (`id`, `wo_code`, `ticket_id`, `created_by_dispatch_id`, `forwarded_by_dispatch_id`, `reviewed_by_dispatch_id`, `closed_by_bor_id`, `assigned_to_vendor_id`, `scheduled_by_admin_id`, `scheduled_visit_date`, `scheduled_at`, `forwarded_to_admin_at`, `reviewed_at`, `closed_at`, `bor_closed_at`, `bor_closed_by`, `status`, `priority_level`, `visit_report`, `admin_notes`, `dispatch_review_notes`, `work_quality_rating`, `ticket_resolution_status`, `bor_decision`, `created_at`, `started_at`, `completed_at`, `estimated_duration`, `actual_duration`, `pre_work_notes`, `priority_updated_by`, `priority_updated_at`) VALUES
(1, 'WO-20250610-6847E2E460C2B', 5, 3, NULL, NULL, NULL, 6, NULL, '2025-06-07 15:22:00', NULL, NULL, NULL, NULL, NULL, NULL, 'Cancelled', 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-10 07:48:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'WO-20250610-6847EB06C31EC', 6, 3, NULL, 3, NULL, 6, NULL, '2025-06-12 15:22:00', NULL, NULL, '2025-06-15 22:20:40', '2025-06-17 01:28:59', NULL, NULL, 'Closed by BOR', 'Normal', 'jaringan sudah diperbaiki', NULL, 'ddaaa', 'Excellent', 'Fully Resolved', NULL, '2025-06-10 08:21:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'WO-20250611-6848F0692BE52', 5, 3, NULL, NULL, NULL, 6, NULL, '2025-06-13 09:56:00', NULL, NULL, NULL, NULL, NULL, NULL, '', 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-11 02:56:41', '2025-06-11 05:32:31', NULL, 60, NULL, 'kaber fiber putus', NULL, NULL),
(4, 'WO-20250611-6848F9CD9BD33', 7, 3, NULL, 3, NULL, 6, NULL, '2025-06-12 10:37:00', NULL, NULL, '2025-06-15 22:36:10', '2025-06-17 01:29:24', NULL, NULL, 'Closed by BOR', 'Normal', 'pergantian router', NULL, 'asaa', 'Excellent', 'Partially Resolved', NULL, '2025-06-11 03:36:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'WO-20250611-6848FB54002FC', 8, 3, NULL, NULL, NULL, 6, NULL, '2025-06-14 10:44:00', NULL, NULL, NULL, NULL, NULL, NULL, '', 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-11 03:43:16', '2025-06-11 05:45:18', NULL, 10, NULL, 'router mati totol', NULL, NULL),
(6, 'WO-20250611-6849019A31308', 9, 3, NULL, 3, NULL, 6, NULL, '2025-06-14 11:10:00', NULL, NULL, '2025-06-16 21:53:10', '2025-06-16 22:01:53', '2025-06-17 05:01:53', 2, 'Closed by BOR', 'Normal', 'melakukan pergantian router\n\n--- BOR REVIEW ---\nApproved by BOR - Problem resolved', NULL, 'saaa', 'Excellent', 'Fully Resolved', 'approve', '2025-06-11 04:10:02', '2025-06-11 06:42:18', NULL, 60, 156, 'mau dikerjakan', NULL, NULL),
(7, 'WO-20250611-684929E643F05', 12, 3, NULL, NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-11 07:01:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'WO-20250611-684929EA71B9B', 10, 3, NULL, 3, NULL, 7, NULL, '2025-06-13 10:04:00', NULL, NULL, '2025-06-16 22:29:04', '2025-06-16 22:30:25', '2025-06-17 05:30:25', 2, 'Closed by BOR', 'Normal', 'ga ada', NULL, 'saa', 'Excellent', 'Partially Resolved', 'approve', '2025-06-11 07:02:02', '2025-06-16 08:20:01', NULL, 60, 10, 'sorry yee', NULL, NULL),
(9, 'WO-20250611-684929EE27723', 11, 3, NULL, 3, NULL, 6, NULL, '2025-06-12 14:09:00', NULL, NULL, '2025-06-16 22:41:46', '2025-06-16 22:44:15', '2025-06-17 05:44:15', 2, 'Closed by BOR', 'Normal', 'saa\n\n--- BOR REVIEW ---\nApproved by BOR - Problem resolved', NULL, 'saa', 'Excellent', 'Fully Resolved', 'approve', '2025-06-11 07:02:06', '2025-06-11 09:25:11', NULL, 60, 43, 'mati', NULL, NULL),
(10, 'WO-20250612-684A42F32504D', 13, 3, NULL, NULL, NULL, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-12 03:01:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'WO-20250613-684BD48B31E30', 15, 2, 3, 3, NULL, 6, 8, '2025-06-15 14:35:00', '2025-06-13 07:35:55', '2025-06-13 07:35:15', '2025-06-16 02:36:45', NULL, NULL, NULL, '', 'Normal', 'sasaa', 'laksanakan', 'oke aman', 'Excellent', 'Fully Resolved', NULL, '2025-06-13 07:34:35', '2025-06-13 10:13:35', NULL, 60, 12, 'wasas', NULL, NULL),
(22, 'WO-20250613-684BD5A0B97D4', 16, 2, 3, 3, NULL, 6, 8, '2025-06-15 14:39:00', '2025-06-13 07:40:01', '2025-06-13 07:39:34', '2025-06-16 22:44:41', '2025-06-16 22:45:00', '2025-06-17 05:45:00', 2, 'Closed by BOR', 'Normal', 'adasda', 'aasa', 'asasa', 'Excellent', 'Fully Resolved', 'approve', '2025-06-13 07:39:12', '2025-06-16 04:37:36', NULL, 60, 66, 'test', NULL, NULL),
(23, 'WO-20250616-684FB6960E690', 19, 2, 3, 3, NULL, 7, 8, '2025-06-18 13:17:00', '2025-06-16 06:18:08', '2025-06-16 06:16:31', '2025-06-16 02:15:43', '2025-06-17 01:28:29', NULL, NULL, 'Closed by BOR', 'Normal', 'ya', 'tolong lina', 'asa', 'Good', 'Fully Resolved', NULL, '2025-06-16 06:15:50', '2025-06-16 08:22:24', NULL, 60, 100, 'mari kita cobaa', NULL, NULL),
(24, 'WO-20250616-684FB69EF3DFF', 17, 2, 3, 3, NULL, 7, 8, '2025-06-20 13:18:00', '2025-06-16 06:18:40', '2025-06-16 06:16:43', '2025-06-17 01:42:52', '2025-06-17 01:43:01', NULL, NULL, 'Closed by BOR', 'Normal', 'saas', 'linaaa', 'aasaa', 'Good', 'Fully Resolved', NULL, '2025-06-16 06:15:58', '2025-06-16 09:41:23', NULL, 60, 322, 'assa', NULL, NULL),
(25, 'WO-20250616-684FB6A305C05', 18, 2, 3, NULL, NULL, 7, 8, '2025-06-24 13:18:00', '2025-06-16 06:18:57', '2025-06-16 06:16:46', NULL, NULL, NULL, NULL, 'Completed by Technician', 'Normal', 'saa', 'aaaa', NULL, NULL, NULL, NULL, '2025-06-16 06:16:03', '2025-06-16 09:59:20', NULL, 60, 32, 'wwwwwwwq', NULL, NULL),
(26, 'WO-20250616-684FD081F3393', 20, 2, 3, NULL, NULL, 7, 8, '2025-06-19 15:06:00', '2025-06-16 08:06:59', '2025-06-16 08:06:38', NULL, NULL, NULL, NULL, 'Completed by Technician', 'Normal', 'asa', 'asa', NULL, NULL, NULL, NULL, '2025-06-16 08:06:25', '2025-06-16 10:07:23', NULL, 60, 211, 'asa', NULL, NULL),
(27, 'WO-20250617-6850CDE1D5990', 21, 2, 3, NULL, NULL, 7, 8, '2025-06-20 09:08:00', '2025-06-17 02:08:33', '2025-06-17 02:07:57', NULL, NULL, NULL, NULL, 'Completed by Technician', 'Normal', 'asa', 'ayoo', NULL, NULL, NULL, NULL, '2025-06-17 02:07:29', '2025-06-17 04:09:28', NULL, 60, 212, 'asaa', NULL, NULL),
(28, 'WO-20250617-6850D44785C8A', 22, 2, NULL, 3, NULL, 7, 8, '2025-06-19 09:36:00', '2025-06-17 02:36:40', '2025-06-16 21:36:05', '2025-06-16 21:46:18', '2025-06-16 22:02:02', '2025-06-17 05:02:02', 2, 'Closed by BOR', 'Normal', 'sasaa', 'saaa', 'saa', 'Excellent', 'Fully Resolved', 'approve', '2025-06-17 02:34:47', '2025-06-17 04:36:56', NULL, 60, 332, 'sasa', NULL, NULL),
(29, 'WO-20250617-6850DAFABCF16', 23, 2, NULL, NULL, NULL, 7, 8, '2025-06-19 10:03:00', '2025-06-17 03:03:54', '2025-06-16 22:03:37', NULL, NULL, NULL, NULL, 'Completed', 'Normal', 'asaa', 'assa', NULL, NULL, NULL, NULL, '2025-06-17 03:03:22', '2025-06-17 05:04:20', NULL, 60, 21, 'saa', NULL, NULL),
(30, 'WO-20250617-6850E6C2B7721', 24, 2, NULL, NULL, NULL, 7, 8, '2025-06-20 10:53:00', '2025-06-17 03:54:05', '2025-06-16 22:53:47', NULL, NULL, NULL, NULL, 'Completed', 'Normal', 'qasa', 'asa', NULL, NULL, NULL, NULL, '2025-06-17 03:53:38', '2025-06-17 05:54:18', NULL, 60, 21, 'asa', NULL, NULL),
(31, 'WO-20250617-685110CB99A42', 26, 2, NULL, NULL, NULL, 7, 8, '2025-06-20 13:53:00', '2025-06-17 06:53:24', '2025-06-17 01:53:11', NULL, NULL, NULL, NULL, 'Completed', 'Normal', 'aasa', 'assasa', NULL, NULL, NULL, NULL, '2025-06-17 06:52:59', '2025-06-17 08:53:36', NULL, 60, 22, 'assa', NULL, NULL),
(32, 'WO-20250617-685114117BAB8', 27, 2, NULL, NULL, NULL, 7, 8, '2025-06-21 14:07:00', '2025-06-17 07:07:46', '2025-06-17 02:07:29', NULL, NULL, NULL, NULL, 'Completed', 'Normal', 'saaa', 'sasa', NULL, NULL, NULL, NULL, '2025-06-17 07:06:57', '2025-06-17 09:07:57', NULL, 60, 21, 'sasa', NULL, NULL),
(33, 'WO-20250617-68511576C10FD', 29, 2, NULL, NULL, NULL, 7, 8, '2025-06-19 14:13:00', '2025-06-17 07:13:21', '2025-06-17 02:13:03', NULL, NULL, NULL, NULL, 'Completed', 'Normal', 'asa', 'asa', NULL, NULL, NULL, NULL, '2025-06-17 07:12:54', '2025-06-17 09:13:44', NULL, 60, 21, 'sasa', NULL, NULL),
(34, 'WO-20250617-6851157979A2B', 28, 2, NULL, NULL, NULL, 7, 8, '2025-06-19 14:13:00', '2025-06-17 07:13:30', '2025-06-17 02:13:05', NULL, NULL, NULL, NULL, 'Completed', 'Normal', 'saa', 'asa', NULL, NULL, NULL, NULL, '2025-06-17 07:12:57', '2025-06-17 09:26:01', NULL, 60, 12, 'saa', NULL, NULL),
(35, 'WO-20250617-685119A947574', 31, 2, NULL, NULL, NULL, 7, 8, '2025-06-19 14:31:00', '2025-06-17 07:31:15', '2025-06-17 02:31:00', NULL, NULL, NULL, NULL, 'Completed', 'Normal', 'assa', 'sasa', NULL, NULL, NULL, NULL, '2025-06-17 07:30:49', '2025-06-17 09:31:38', NULL, 60, 12, 'sa', NULL, NULL),
(36, 'WO-20250617-685119AB6E7EB', 30, 2, NULL, NULL, NULL, 7, 8, '2025-06-19 14:31:00', '2025-06-17 07:31:24', '2025-06-17 02:31:03', NULL, NULL, NULL, NULL, 'Completed', 'Normal', 'aasaa', '', NULL, NULL, NULL, NULL, '2025-06-17 07:30:51', '2025-06-17 09:37:52', NULL, 60, 12, 'aa', NULL, NULL),
(37, 'WO-20250617-68513FACCA680', 32, 2, NULL, 3, NULL, 7, 8, '2025-06-19 17:14:00', '2025-06-17 10:14:15', '2025-06-17 05:14:00', '2025-06-17 05:15:22', '2025-06-17 05:15:32', NULL, NULL, 'Closed by BOR', 'Normal', 'saa', 'saasa', 'sas', 'Excellent', 'Fully Resolved', NULL, '2025-06-17 10:13:00', '2025-06-17 12:14:30', NULL, 60, 12, 'sasaa', NULL, NULL),
(38, 'WO-20250618-6852247DA006A', 33, 2, NULL, 3, NULL, 7, 8, '2025-06-20 09:30:00', '2025-06-18 02:30:40', '2025-06-17 21:29:52', '2025-06-17 21:33:40', '2025-06-17 21:33:55', NULL, NULL, 'Closed by BOR', 'Normal', 'asasa', 'tolongg', 'saa', 'Excellent', 'Fully Resolved', NULL, '2025-06-18 02:29:17', '2025-06-18 04:31:00', NULL, 60, 22, 'wkwkwk', NULL, NULL),
(39, 'WO-20250618-68522721E2DB0', 35, 2, NULL, 3, NULL, 7, 8, '2025-06-20 09:46:00', '2025-06-18 02:47:05', '2025-06-17 21:46:08', '2025-06-17 22:01:11', '2025-06-17 22:06:39', NULL, NULL, 'Closed by BOR', 'Normal', 'saa', 'sasaa', 'sasas', 'Excellent', 'Fully Resolved', NULL, '2025-06-18 02:40:33', '2025-06-18 04:52:22', NULL, 60, 32, 'sasaa', NULL, NULL),
(40, 'WO-20250618-685227245B206', 34, 2, NULL, NULL, NULL, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Sent to Dispatch', 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-18 02:40:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(41, 'WO-20250618-68524141BD1EE', 36, 2, NULL, NULL, NULL, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Sent to Dispatch', 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-18 04:32:01', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tr_work_reports`
--

CREATE TABLE `tr_work_reports` (
  `id` int(11) NOT NULL,
  `work_order_id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `equipment_replaced` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`equipment_replaced`)),
  `cables_replaced` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cables_replaced`)),
  `new_installations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_installations`)),
  `signal_before` varchar(50) DEFAULT NULL,
  `signal_after` varchar(50) DEFAULT NULL,
  `speed_test_result` varchar(100) DEFAULT NULL,
  `materials_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`materials_used`)),
  `customer_satisfaction` enum('Very Satisfied','Satisfied','Neutral','Unsatisfied') DEFAULT NULL,
  `customer_present` varchar(50) DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `before_photos` text DEFAULT NULL,
  `after_photos` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_draft` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tr_work_reports`
--

INSERT INTO `tr_work_reports` (`id`, `work_order_id`, `technician_id`, `equipment_replaced`, `cables_replaced`, `new_installations`, `signal_before`, `signal_after`, `speed_test_result`, `materials_used`, `customer_satisfaction`, `customer_present`, `customer_notes`, `before_photos`, `after_photos`, `created_at`, `updated_at`, `is_draft`) VALUES
(1, 4, 6, '{\"equipment_replaced\":\"router\",\"cables_replaced\":\"-\",\"new_installations\":\"pasang router baru\"}', NULL, NULL, NULL, '150', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'terimakasih ikr', NULL, NULL, '2025-06-11 03:39:50', NULL, 1),
(2, 6, 6, '{\"equipment_replaced\":\"router435\",\"cables_replaced\":\"-\",\"new_installations\":\"pasang router baru\"}', NULL, NULL, NULL, '236', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'okee', NULL, NULL, '2025-06-11 07:18:30', NULL, 1),
(3, 9, 6, '{\"equipment_replaced\":\"router \",\"cables_replaced\":\"tidak ada\",\"new_installations\":\"pemasangan router baru\"}', NULL, NULL, '2', '100', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'mantap', NULL, NULL, '2025-06-11 02:38:19', '2025-06-11 02:39:04', 1),
(4, 21, 6, '{\"equipment_replaced\":\"sasasa\",\"cables_replaced\":\"asassa\",\"new_installations\":\"asaas\"}', NULL, NULL, '22', '22', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'qwqq', NULL, NULL, '2025-06-13 03:16:20', '2025-06-13 03:23:29', 1),
(5, 22, 6, '{\"equipment_replaced\":\"asa\",\"cables_replaced\":\"asa\",\"new_installations\":\"asa\"}', NULL, NULL, '22', '150', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'saaaa', NULL, NULL, '2025-06-15 21:38:19', NULL, 1),
(6, 8, 7, '{\"equipment_replaced\":\"ga ada\",\"cables_replaced\":\"ga\",\"new_installations\":\"ga\"}', NULL, NULL, '22', '236', '100mbps', '[{\"name\":\"ga\",\"quantity\":1,\"unit\":\"ga\",\"notes\":\"ga\"}]', 'Neutral', NULL, 'ga', NULL, NULL, '2025-06-16 01:21:07', NULL, 1),
(7, 23, 7, '{\"equipment_replaced\":\"ya\",\"cables_replaced\":\"ya\",\"new_installations\":\"ya\"}', NULL, NULL, '22', '100', '90mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"ga\"}]', 'Satisfied', NULL, 'ya', NULL, NULL, '2025-06-16 01:23:14', NULL, 1),
(8, 24, 7, '{\"equipment_replaced\":\"saa\",\"cables_replaced\":\"saa\",\"new_installations\":\"asa\"}', NULL, NULL, '22', '332', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Satisfied', NULL, 'saasa', NULL, NULL, '2025-06-16 02:42:07', NULL, 1),
(9, 25, 7, '{\"equipment_replaced\":\"asa\",\"cables_replaced\":\"assa\",\"new_installations\":\"sasa\"}', NULL, NULL, '22', '100', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'saa', NULL, NULL, '2025-06-16 02:59:52', NULL, 1),
(10, 26, 7, '{\"equipment_replaced\":\"asa\",\"cables_replaced\":\"saas\",\"new_installations\":\"saa\"}', NULL, NULL, '22', '100', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Satisfied', NULL, 'aasa', NULL, NULL, '2025-06-16 03:07:56', NULL, 1),
(11, 27, 7, '{\"equipment_replaced\":\"asaa\",\"cables_replaced\":\"saa\",\"new_installations\":\"assa\"}', NULL, NULL, '22', '332', '90mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'saaa', NULL, NULL, '2025-06-16 21:10:08', NULL, 1),
(12, 28, 7, '{\"equipment_replaced\":\"sa\",\"cables_replaced\":\"sas\",\"new_installations\":\"aas\"}', NULL, NULL, '22', '100', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'asa', NULL, NULL, '2025-06-16 21:37:38', NULL, 1),
(13, 29, 7, '{\"equipment_replaced\":\"saas\",\"cables_replaced\":\"asa\",\"new_installations\":\"asa\"}', NULL, NULL, '22', '100', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'aasa', NULL, NULL, '2025-06-16 22:04:50', NULL, 1),
(14, 30, 7, '{\"equipment_replaced\":\"asa\",\"cables_replaced\":\"asa\",\"new_installations\":\"saa\"}', NULL, NULL, '22', '332', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"a\",\"notes\":\"asa\"}]', 'Very Satisfied', NULL, 'saa', NULL, NULL, '2025-06-16 22:54:49', NULL, 1),
(15, 31, 7, '{\"equipment_replaced\":\"sasa\",\"cables_replaced\":\"aa\",\"new_installations\":\"asa\"}', NULL, NULL, '22', '236', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'asa', NULL, NULL, '2025-06-17 01:54:10', NULL, 1),
(16, 32, 7, '{\"equipment_replaced\":\"asa\",\"cables_replaced\":\"sas\",\"new_installations\":\"asa\"}', NULL, NULL, '22', '100', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'saa', NULL, NULL, '2025-06-17 02:08:26', NULL, 1),
(17, 33, 7, '{\"equipment_replaced\":\"saa\",\"cables_replaced\":\"asa\",\"new_installations\":\"asa\"}', NULL, NULL, '22', '100', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"ds\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'asa', NULL, NULL, '2025-06-17 02:14:13', NULL, 1),
(18, 34, 7, '{\"equipment_replaced\":\"saa\",\"cables_replaced\":\"sas\",\"new_installations\":\"saas\"}', NULL, NULL, '22', '100', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"aas\",\"notes\":\"router berjalan dengan lancar\"}]', 'Satisfied', NULL, 'saa', NULL, NULL, '2025-06-17 02:26:30', NULL, 1),
(19, 35, 7, '{\"equipment_replaced\":\"sasa\",\"cables_replaced\":\"saas\",\"new_installations\":\"asa\"}', NULL, NULL, '22', '100', '90mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'saa', NULL, NULL, '2025-06-17 02:32:09', NULL, 1),
(20, 36, 7, '{\"equipment_replaced\":\"asasa\",\"cables_replaced\":\"assa\",\"new_installations\":\"saa\"}', NULL, NULL, '22', '100', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'assasa', NULL, NULL, '2025-06-17 02:38:28', NULL, 1),
(21, 37, 7, '{\"equipment_replaced\":\"sas\",\"cables_replaced\":\"sasa\",\"new_installations\":\"sasa\"}', NULL, NULL, '22', '100', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'asaa', NULL, NULL, '2025-06-17 05:15:03', NULL, 1),
(22, 38, 7, '{\"equipment_replaced\":\"saa\",\"cables_replaced\":\"sasa\",\"new_installations\":\"saasaa\"}', NULL, NULL, '22', '150', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'saaaas', NULL, NULL, '2025-06-17 21:31:58', NULL, 1),
(23, 39, 7, '{\"equipment_replaced\":\"saas\",\"cables_replaced\":\"saa\",\"new_installations\":\"sasa\"}', NULL, NULL, '22', '100', '100mbps', '[{\"name\":\"router oqygen\",\"quantity\":1,\"unit\":\"router baru\",\"notes\":\"router berjalan dengan lancar\"}]', 'Very Satisfied', NULL, 'saaa', NULL, NULL, '2025-06-17 21:54:51', '2025-06-17 21:57:36', 1);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wo_id` (`wo_id`);

--
-- Indeks untuk tabel `bor_notifications`
--
ALTER TABLE `bor_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wo_id` (`wo_id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indeks untuk tabel `ms_customers`
--
ALTER TABLE `ms_customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id_number` (`customer_id_number`);

--
-- Indeks untuk tabel `ms_users`
--
ALTER TABLE `ms_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `technician_notifications`
--
ALTER TABLE `technician_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `wo_id` (`wo_id`);

--
-- Indeks untuk tabel `tr_bor_final_reviews`
--
ALTER TABLE `tr_bor_final_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `work_order_id` (`work_order_id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `bor_user_id` (`bor_user_id`);

--
-- Indeks untuk tabel `tr_dispatch_reviews`
--
ALTER TABLE `tr_dispatch_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `work_order_id` (`work_order_id`),
  ADD KEY `dispatch_user_id` (`dispatch_user_id`);

--
-- Indeks untuk tabel `tr_tickets`
--
ALTER TABLE `tr_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_code` (`ticket_code`),
  ADD KEY `tr_tickets_ibfk_1` (`customer_id`),
  ADD KEY `tr_tickets_ibfk_3` (`created_by_user_id`),
  ADD KEY `tr_tickets_ibfk_4` (`current_owner_user_id`),
  ADD KEY `idx_ticket_status_owner` (`status`,`current_owner_user_id`);

--
-- Indeks untuk tabel `tr_ticket_updates`
--
ALTER TABLE `tr_ticket_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tr_ticket_updates_ibfk_1` (`ticket_id`),
  ADD KEY `tr_ticket_updates_ibfk_2` (`user_id`);

--
-- Indeks untuk tabel `tr_work_completion_details`
--
ALTER TABLE `tr_work_completion_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `work_order_id` (`work_order_id`);

--
-- Indeks untuk tabel `tr_work_orders`
--
ALTER TABLE `tr_work_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wo_code` (`wo_code`),
  ADD KEY `tr_work_orders_ibfk_1` (`ticket_id`),
  ADD KEY `tr_work_orders_ibfk_2` (`created_by_dispatch_id`),
  ADD KEY `tr_work_orders_ibfk_3` (`assigned_to_vendor_id`),
  ADD KEY `fk_wo_scheduled_by_admin` (`scheduled_by_admin_id`),
  ADD KEY `fk_wo_forwarded_by_dispatch` (`forwarded_by_dispatch_id`),
  ADD KEY `fk_wo_reviewed_by_dispatch` (`reviewed_by_dispatch_id`),
  ADD KEY `fk_wo_closed_by_bor` (`closed_by_bor_id`),
  ADD KEY `idx_wo_status_priority` (`status`,`priority_level`),
  ADD KEY `idx_wo_scheduled_date` (`scheduled_visit_date`);

--
-- Indeks untuk tabel `tr_work_reports`
--
ALTER TABLE `tr_work_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_work_reports_wo` (`work_order_id`),
  ADD KEY `fk_work_reports_technician` (`technician_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `bor_notifications`
--
ALTER TABLE `bor_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `ms_customers`
--
ALTER TABLE `ms_customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT untuk tabel `ms_users`
--
ALTER TABLE `ms_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `technician_notifications`
--
ALTER TABLE `technician_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `tr_bor_final_reviews`
--
ALTER TABLE `tr_bor_final_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tr_dispatch_reviews`
--
ALTER TABLE `tr_dispatch_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `tr_tickets`
--
ALTER TABLE `tr_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `tr_ticket_updates`
--
ALTER TABLE `tr_ticket_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;

--
-- AUTO_INCREMENT untuk tabel `tr_work_completion_details`
--
ALTER TABLE `tr_work_completion_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tr_work_orders`
--
ALTER TABLE `tr_work_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT untuk tabel `tr_work_reports`
--
ALTER TABLE `tr_work_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD CONSTRAINT `fk_admin_notif_wo` FOREIGN KEY (`wo_id`) REFERENCES `tr_work_orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `bor_notifications`
--
ALTER TABLE `bor_notifications`
  ADD CONSTRAINT `fk_bor_notif_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tr_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bor_notif_wo` FOREIGN KEY (`wo_id`) REFERENCES `tr_work_orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `technician_notifications`
--
ALTER TABLE `technician_notifications`
  ADD CONSTRAINT `fk_tech_notif_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `ms_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tech_notif_wo` FOREIGN KEY (`wo_id`) REFERENCES `tr_work_orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tr_bor_final_reviews`
--
ALTER TABLE `tr_bor_final_reviews`
  ADD CONSTRAINT `fk_bor_reviews_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tr_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bor_reviews_user` FOREIGN KEY (`bor_user_id`) REFERENCES `ms_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bor_reviews_wo` FOREIGN KEY (`work_order_id`) REFERENCES `tr_work_orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tr_dispatch_reviews`
--
ALTER TABLE `tr_dispatch_reviews`
  ADD CONSTRAINT `fk_dispatch_reviews_user` FOREIGN KEY (`dispatch_user_id`) REFERENCES `ms_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dispatch_reviews_wo` FOREIGN KEY (`work_order_id`) REFERENCES `tr_work_orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tr_tickets`
--
ALTER TABLE `tr_tickets`
  ADD CONSTRAINT `tr_tickets_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `ms_customers` (`id`),
  ADD CONSTRAINT `tr_tickets_ibfk_3` FOREIGN KEY (`created_by_user_id`) REFERENCES `ms_users` (`id`),
  ADD CONSTRAINT `tr_tickets_ibfk_4` FOREIGN KEY (`current_owner_user_id`) REFERENCES `ms_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tr_ticket_updates`
--
ALTER TABLE `tr_ticket_updates`
  ADD CONSTRAINT `tr_ticket_updates_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tr_tickets` (`id`),
  ADD CONSTRAINT `tr_ticket_updates_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tr_work_completion_details`
--
ALTER TABLE `tr_work_completion_details`
  ADD CONSTRAINT `tr_work_completion_details_ibfk_1` FOREIGN KEY (`work_order_id`) REFERENCES `tr_work_orders` (`id`);

--
-- Ketidakleluasaan untuk tabel `tr_work_orders`
--
ALTER TABLE `tr_work_orders`
  ADD CONSTRAINT `fk_wo_closed_by_bor` FOREIGN KEY (`closed_by_bor_id`) REFERENCES `ms_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_wo_forwarded_by_dispatch` FOREIGN KEY (`forwarded_by_dispatch_id`) REFERENCES `ms_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_wo_reviewed_by_dispatch` FOREIGN KEY (`reviewed_by_dispatch_id`) REFERENCES `ms_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_wo_scheduled_by_admin` FOREIGN KEY (`scheduled_by_admin_id`) REFERENCES `ms_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tr_work_orders_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tr_tickets` (`id`),
  ADD CONSTRAINT `tr_work_orders_ibfk_2` FOREIGN KEY (`created_by_dispatch_id`) REFERENCES `ms_users` (`id`),
  ADD CONSTRAINT `tr_work_orders_ibfk_3` FOREIGN KEY (`assigned_to_vendor_id`) REFERENCES `ms_users` (`id`);

--
-- Ketidakleluasaan untuk tabel `tr_work_reports`
--
ALTER TABLE `tr_work_reports`
  ADD CONSTRAINT `fk_work_reports_technician` FOREIGN KEY (`technician_id`) REFERENCES `ms_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_work_reports_wo` FOREIGN KEY (`work_order_id`) REFERENCES `tr_work_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tr_work_reports_ibfk_1` FOREIGN KEY (`work_order_id`) REFERENCES `tr_work_orders` (`id`),
  ADD CONSTRAINT `tr_work_reports_ibfk_2` FOREIGN KEY (`technician_id`) REFERENCES `ms_users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Menonaktifkan cek foreign key sementara untuk menghindari error saat pembuatan tabel
SET FOREIGN_KEY_CHECKS=0;

-- =============================
-- BAGIAN A: TABEL-TABEL MASTER
-- =============================

-- Tabel 1: Menyimpan data pelanggan
CREATE TABLE `ms_customers` (
  `id` int(11) NOT NULL,
  `customer_id_number` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id_number` (`customer_id_number`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone_number` (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel 2: Menyimpan data pengguna sistem (staf)
CREATE TABLE `ms_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('Customer Care','BOR','Dispatch','Vendor IKR','Admin') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
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
  `closed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_code` (`ticket_code`),
  KEY `customer_id` (`customer_id`),
  KEY `created_by_user_id` (`created_by_user_id`),
  KEY `current_owner_user_id` (`current_owner_user_id`)
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
  `assigned_to_vendor_id` int(11) NOT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `wo_code` (`wo_code`),
  KEY `ticket_id` (`ticket_id`),
  KEY `created_by_dispatch_id` (`created_by_dispatch_id`),
  KEY `assigned_to_vendor_id` (`assigned_to_vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel 5: Menyimpan semua riwayat dan komentar pada tiket
CREATE TABLE `tr_ticket_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `update_type` enum('Comment','Status Change','Escalation','First Level Handling') NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_draft` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================
-- BAGIAN C: PENGATURAN RELASI (FOREIGN KEY)
-- =============================================

-- Relasi untuk tabel tr_tickets
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

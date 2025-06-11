-- Menonaktifkan cek foreign key sementara untuk menghindari error saat pembuatan tabel
SET FOREIGN_KEY_CHECKS=0;

-- =============================
-- BAGIAN A: TABEL-TABEL MASTER
-- =============================

-- Tabel 1: Menyimpan data pelanggan
CREATE TABLE `ms_customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('Customer Care','BOR','Dispatch','Vendor IKR','Admin') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =================================
-- BAGIAN B: TABEL-TABEL TRANSAKSI
-- =================================

-- Tabel 3: Menyimpan data tiket gangguan (Versi Final)
CREATE TABLE `tr_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_code` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Open','On Progress - Customer Care','On Progress - BOR','Waiting for Dispatch','Closed - Solved','Closed - Unsolved') NOT NULL DEFAULT 'Open',
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

-- Tabel 4: Menyimpan data Work Order (WO)
CREATE TABLE `tr_work_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wo_code` varchar(50) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `created_by_dispatch_id` int(11) NOT NULL,
  `assigned_to_vendor_id` int(11) NOT NULL,
  `scheduled_visit_date` datetime DEFAULT NULL,
  `status` enum('Pending','Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
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
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================
-- BAGIAN C: PENGATURAN RELASI (FOREIGN KEY)
-- =============================================

-- Relasi untuk tabel tr_tickets
ALTER TABLE `tr_tickets`
  ADD CONSTRAINT `tr_tickets_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `ms_customers` (`id`),
  ADD CONSTRAINT `tr_tickets_ibfk_3` FOREIGN KEY (`created_by_user_id`) REFERENCES `ms_users` (`id`),
  ADD CONSTRAINT `tr_tickets_ibfk_4` FOREIGN KEY (`current_owner_user_id`) REFERENCES `ms_users` (`id`);

-- Relasi untuk tabel tr_work_orders
ALTER TABLE `tr_work_orders`
  ADD CONSTRAINT `tr_work_orders_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tr_tickets` (`id`),
  ADD CONSTRAINT `tr_work_orders_ibfk_2` FOREIGN KEY (`created_by_dispatch_id`) REFERENCES `ms_users` (`id`),
  ADD CONSTRAINT `tr_work_orders_ibfk_3` FOREIGN KEY (`assigned_to_vendor_id`) REFERENCES `ms_users` (`id`);

-- Relasi untuk tabel tr_ticket_updates
ALTER TABLE `tr_ticket_updates`
  ADD CONSTRAINT `tr_ticket_updates_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tr_tickets` (`id`),
  ADD CONSTRAINT `tr_ticket_updates_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `ms_users` (`id`);

-- Mengaktifkan kembali cek foreign key
SET FOREIGN_KEY_CHECKS=1;

-- Таблица для хранения чеков налоговой инспекции Украины
-- Структура БД для tax_receipts

CREATE TABLE IF NOT EXISTS `tax_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `firma` int unsigned DEFAULT 0,
  `receipt_number` varchar(100) UNIQUE NOT NULL,
  `document_id` varchar(100) NULLABLE,
  `document_type` enum('PO','RO','OTHER') DEFAULT 'PO',
  `taxpayer_id` varchar(50) NULLABLE,
  `cashier_name` varchar(255) NULLABLE,
  `amount` decimal(12,2) DEFAULT 0,
  `goods_description` longtext NULLABLE,
  `registration_status` varchar(50) DEFAULT 'pending',
  `tax_office_receipt_id` varchar(100) NULLABLE,
  `tax_office_response` longtext NULLABLE,
  `error_message` longtext NULLABLE,
  `registered_at` timestamp NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  KEY `idx_firma` (`firma`),
  KEY `idx_document_id` (`document_id`),
  KEY `idx_registration_status` (`registration_status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

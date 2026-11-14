<?php
declare(strict_types=1);

/**
 * Met à jour automatiquement la base d'une boutique déjà installée.
 * Version 2 : frais de livraison par produit + avis clients.
 * Les produits, commandes et photos existants ne sont pas touchés.
 */
const DB_VERSION = 4;

function run_migrations(PDO $pdo): void
{
    try {
        $cur = (int)$pdo->query("SELECT v FROM settings WHERE k = 'db_version'")->fetchColumn();
    } catch (PDOException $e) {
        return; // la base n'est pas encore installée
    }
    if ($cur >= DB_VERSION) {
        return;
    }
    if ($cur < 2) {
        $has = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'shipping_fee'")->fetchColumn();
        if (!$has) {
            $pdo->exec('ALTER TABLE products ADD COLUMN shipping_fee DECIMAL(10,3) NULL AFTER position');
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(120) NOT NULL,
          city VARCHAR(80) NULL,
          rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
          comment TEXT NOT NULL,
          photo VARCHAR(255) NULL,
          active TINYINT(1) NOT NULL DEFAULT 1,
          position INT NOT NULL DEFAULT 0,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          KEY idx_active (active, position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if ($cur < 3) {
        // Version 3 : limite des tentatives de connexion par adresse IP (hachée).
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
          ip_hash CHAR(64) NOT NULL PRIMARY KEY,
          fails INT UNSIGNED NOT NULL DEFAULT 0,
          last_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if ($cur < 4) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS catalogue_categories (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(80) NOT NULL,
          parent_key VARCHAR(40) NULL,
          position INT NOT NULL DEFAULT 1,
          active TINYINT(1) NOT NULL DEFAULT 1,
          KEY idx_parent (parent_key, position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
          product_id INT UNSIGNED NOT NULL,
          category_id INT UNSIGNED NOT NULL,
          PRIMARY KEY (product_id, category_id),
          KEY idx_category (category_id),
          CONSTRAINT fk_pc_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
          CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES catalogue_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $pdo->prepare("INSERT INTO settings (k, v) VALUES ('db_version', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)")->execute([(string)DB_VERSION]);
}

<?php
declare(strict_types=1);

/**
 * Met à jour automatiquement la base d'une boutique déjà installée.
 * Version 2 : frais de livraison par produit + avis clients.
 * Les produits, commandes et photos existants ne sont pas touchés.
 */
const DB_VERSION = 8;

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
    if ($cur < 5) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS pot_colors (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          product_id INT UNSIGNED NOT NULL,
          name VARCHAR(60) NOT NULL,
          hex CHAR(7) NOT NULL DEFAULT '#B6B2A9',
          position INT NOT NULL DEFAULT 1,
          CONSTRAINT fk_pot_color_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pot_color_photos (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          color_id INT UNSIGNED NOT NULL,
          file VARCHAR(255) NOT NULL,
          position INT NOT NULL DEFAULT 0,
          CONSTRAINT fk_pot_photo_color FOREIGN KEY (color_id) REFERENCES pot_colors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $has = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='variants' AND COLUMN_NAME='color_id'")->fetchColumn();
        if (!$has) $pdo->exec('ALTER TABLE variants ADD COLUMN color_id INT UNSIGNED NULL, ADD CONSTRAINT fk_variant_color FOREIGN KEY (color_id) REFERENCES pot_colors(id) ON DELETE SET NULL');
    }
    if ($cur < 6) {
        // Keep full size + color snapshots on past and future orders.
        $pdo->exec('ALTER TABLE order_items MODIFY COLUMN variant_label VARCHAR(200) NOT NULL');
    }
    if ($cur < 7) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(120) NOT NULL,
          email VARCHAR(190) NOT NULL UNIQUE,
          password_hash VARCHAR(255) NOT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if ($cur < 8) {
        foreach (['usage_place' => 'VARCHAR(20) NULL', 'material_text' => 'VARCHAR(120) NULL', 'care_text' => 'VARCHAR(200) NULL', 'included_text' => 'VARCHAR(200) NULL'] as $column => $definition) {
            $query = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME=?");
            $query->execute([$column]);
            if (!$query->fetchColumn()) $pdo->exec('ALTER TABLE products ADD COLUMN ' . $column . ' ' . $definition);
        }
    }
    $pdo->prepare("INSERT INTO settings (k, v) VALUES ('db_version', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)")->execute([(string)DB_VERSION]);
}

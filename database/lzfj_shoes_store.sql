-- =========================================================
--  LZFJ Shoes Store - Database (MySQL / MariaDB)
--  Import lewat phpMyAdmin: buat database `lzfj_shoes_store`
--  lalu tab "Import" -> pilih file ini -> Go.
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `lzfj_shoes_store`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lzfj_shoes_store`;

DROP TABLE IF EXISTS `wishlists`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `promo_codes`;
DROP TABLE IF EXISTS `subscribers`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;

-- ---------------------------------------------------------
-- Users (login email/password & login Google)
-- ---------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(120) NOT NULL,
  `username`      VARCHAR(60)  NOT NULL,
  `email`         VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NULL,
  `google_id`     VARCHAR(64)  NULL,
  `avatar_url`    VARCHAR(500) NULL,
  `phone`         VARCHAR(30)  NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_google` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Products
--   gender   : men | women | kids | unisex
--   category : running | casual | lifestyle
--   sizes    : daftar ukuran dipisah koma
--   colors   : Nama:#HEX dipisah koma
--   rank_all : urutan di halaman Shop (semua produk)
--   position : urutan di halaman Men / Women / Kids
-- ---------------------------------------------------------
CREATE TABLE `products` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(120) NOT NULL,
  `name`        VARCHAR(160) NOT NULL,
  `brand`       VARCHAR(60)  NOT NULL,
  `gender`      ENUM('men','women','kids','unisex') NOT NULL DEFAULT 'unisex',
  `category`    ENUM('running','casual','lifestyle') NOT NULL DEFAULT 'lifestyle',
  `price`       INT UNSIGNED NOT NULL,
  `old_price`   INT UNSIGNED NULL,
  `badge`       VARCHAR(30)  NULL,
  `rating`      DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  `reviews`     INT UNSIGNED NOT NULL DEFAULT 0,
  `sold`        INT UNSIGNED NOT NULL DEFAULT 0,
  `description` TEXT NOT NULL,
  `image`       VARCHAR(190) NOT NULL,
  `sizes`       VARCHAR(120) NOT NULL,
  `colors`      VARCHAR(255) NOT NULL,
  `stock`       INT UNSIGNED NOT NULL DEFAULT 50,
  `is_featured` TINYINT(1)   NOT NULL DEFAULT 0,
  `rank_all`    INT NOT NULL DEFAULT 100,
  `position`    INT NOT NULL DEFAULT 100,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_gender` (`gender`),
  KEY `idx_products_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Orders
--   status: pending | paid | failed | expired | cancelled | refunded
-- ---------------------------------------------------------
CREATE TABLE `orders` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code`       VARCHAR(40)  NOT NULL,
  `user_id`          INT UNSIGNED NOT NULL,
  `full_name`        VARCHAR(120) NOT NULL,
  `email`            VARCHAR(190) NOT NULL,
  `phone`            VARCHAR(30)  NOT NULL,
  `city`             VARCHAR(80)  NOT NULL,
  `address`          VARCHAR(255) NOT NULL,
  `postal_code`      VARCHAR(12)  NOT NULL,
  `delivery_method`  ENUM('regular','express') NOT NULL DEFAULT 'regular',
  `payment_method`   ENUM('bank_transfer','ewallet','card') NOT NULL,
  `payment_channel`  VARCHAR(30)  NOT NULL,
  `subtotal`         INT UNSIGNED NOT NULL,
  `shipping_cost`    INT UNSIGNED NOT NULL,
  `discount`         INT UNSIGNED NOT NULL DEFAULT 0,
  `promo_code`       VARCHAR(30)  NULL,
  `total`            INT UNSIGNED NOT NULL,
  `status`           VARCHAR(20)  NOT NULL DEFAULT 'pending',
  `snap_token`       VARCHAR(80)  NULL,
  `snap_redirect_url` VARCHAR(255) NULL,
  `midtrans_transaction_id` VARCHAR(80) NULL,
  `midtrans_payment_type`   VARCHAR(40) NULL,
  `va_number`        VARCHAR(60)  NULL,
  `stock_restored`   TINYINT(1)   NOT NULL DEFAULT 0,
  `paid_at`          DATETIME NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_code` (`order_code`),
  KEY `idx_orders_user` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`     INT UNSIGNED NOT NULL,
  `product_id`   INT UNSIGNED NULL,
  `product_name` VARCHAR(160) NOT NULL,
  `image`        VARCHAR(190) NOT NULL,
  `size`         VARCHAR(10)  NOT NULL,
  `color`        VARCHAR(40)  NOT NULL,
  `qty`          INT UNSIGNED NOT NULL,
  `price`        INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_items_order` (`order_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promo_codes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`       VARCHAR(30) NOT NULL,
  `type`       ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `value`      INT UNSIGNED NOT NULL,
  `min_subtotal` INT UNSIGNED NOT NULL,
  `starts_on`  DATE NOT NULL,
  `ends_on`    DATE NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_promo_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Wishlist (tombol love). Satu baris = satu sepatu yang disimpan pembeli.
-- ---------------------------------------------------------
CREATE TABLE `wishlists` (
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`product_id`),
  KEY `idx_wish_product` (`product_id`),
  CONSTRAINT `fk_wish_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wish_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscribers` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(190) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscriber_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- Data produk (sesuai desain Figma)
-- ---------------------------------------------------------
INSERT INTO `products`
(`slug`,`name`,`brand`,`gender`,`category`,`price`,`old_price`,`badge`,`rating`,`reviews`,`sold`,`description`,`image`,`sizes`,`colors`,`stock`,`is_featured`,`rank_all`,`position`) VALUES
('nike-air-max','Nike Air Max','Nike','men','running',1899000,NULL,'NEW',4.8,124,320,
 'Stand out and stay comfortable all day long with the Nike Air Max. Combining legendary Visible Air cushioning with an iconic, futuristic design, these sneakers are engineered to deliver maximum impact protection while elevating your everyday streetwear look.',
 'nike-air-max.webp','39,40,41,42,43','Black:#111111,White:#FFFFFF,Grey:#9AA0AC',40,1,1,1),
('adidas-ultraboost','Adidas Ultraboost','Adidas','men','running',2299000,2874000,'SALE -20%',4.7,98,320,
 'Combining a flexible PRIMEKNIT upper with responsive BOOST cushioning, the adidas Ultraboost delivers maximum energy return and supreme comfort for both running and daily wear.',
 'adidas-ultraboost.webp','39,40,41,42,43','Black:#111111,White:#FFFFFF,Grey:#9AA0AC',35,2,2,2),
('new-balance-530','New Balance 530','New Balance','unisex','lifestyle',1499000,NULL,NULL,4.6,210,540,
 'A retro running silhouette reimagined for everyday wear. The New Balance 530 pairs a breathable mesh upper with ABZORB cushioning for lightweight comfort from morning to night.',
 'new-balance-530.webp','39,40,41,42,43','White:#FFFFFF,Grey:#9AA0AC,Black:#111111',50,3,3,100),
('puma-speedcat-ballet','Puma Speedcat Ballet','Puma','women','lifestyle',1699000,1999000,'SALE -15%',4.5,76,150,
 'Motorsport heritage meets ballet-inspired elegance. The PUMA Speedcat Ballet features a soft suede upper, crossover straps and a slim rubber outsole for an effortless, feminine everyday look.',
 'puma-speedcat-ballet.webp','37,38,39,40,41','Black:#111111,White:#FFFFFF,Grey:#9AA0AC',30,0,4,4),
('converse-chuck-taylor','Converse','Converse','men','lifestyle',899000,NULL,NULL,4.7,110,410,
 'The timeless Converse Chuck Taylor All Star. A durable canvas upper, classic rubber toe cap and cushioned insole make it the go-to sneaker for any outfit, any day.',
 'converse-chuck-taylor.webp','39,40,41,42,43','Black:#111111,White:#FFFFFF,Grey:#9AA0AC',60,0,5,3),
('puma-speedcat','Puma Speedcat','Puma','men','lifestyle',1699000,1999000,'SALE -15%',4.5,76,150,
 'Born on the racetrack, the PUMA Speedcat brings a low-profile suede upper and a grippy rubber outsole for a sleek, motorsport-inspired look that works on and off the street.',
 'puma-speedcat.webp','39,40,41,42,43','Black:#111111,White:#FFFFFF,Grey:#9AA0AC',30,4,6,4),
('adidas-gazelle-indoor','Adidas Gazelle Indoor','Adidas','women','running',1999000,NULL,'NEW',4.8,124,320,
 'Crafted with a premium suede upper and its iconic translucent gum sole, the adidas Gazelle Indoor delivers timeless retro style and low-profile comfort for your everyday streetwear look.',
 'adidas-gazelle-indoor.webp','37,38,39,40,41','Grey:#9AA0AC,Black:#111111,White:#FFFFFF',40,0,7,1),
('nike-v2k-run','Nike V2K Run','Nike','women','running',2299000,2874000,NULL,4.7,98,260,
 'A Y2K-inspired runner with a chunky, layered upper and plush foam midsole. The Nike V2K Run blends early-2000s tech style with modern comfort for all-day wear.',
 'nike-v2k-run.webp','37,38,39,40,41','Grey:#9AA0AC,Black:#111111,White:#FFFFFF',35,0,8,2),
('new-balance-530-women','New Balance 530','New Balance','women','lifestyle',1499000,NULL,NULL,4.6,210,540,
 'A retro running silhouette reimagined for everyday wear. The New Balance 530 pairs a breathable mesh upper with ABZORB cushioning for lightweight comfort from morning to night.',
 'new-balance-530-women.webp','37,38,39,40,41','White:#FFFFFF,Grey:#9AA0AC,Black:#111111',45,0,9,3),
('puma-suede-xl-toddlers','Suede XL Toddlers'' Sneakers','Puma','kids','running',1799000,NULL,'NEW',4.8,124,320,
 'Featuring a playful Y2K-inspired oversized design, the Puma Suede XL Toddlers'' Sneakers combine a soft suede upper, extra padding, and a durable rubber outsole for all-day comfort and stability for little feet.',
 'puma-suede-xl-toddlers.webp','23,24,25,26,27','Blue:#1F4FFF,White:#FFFFFF,Grey:#9AA0AC',40,0,10,1),
('puma-ca-pro-kids','Puma CA Pro Kids Shoes','Puma','kids','running',1299000,2874000,NULL,4.7,98,190,
 'Clean court style for little ones. The PUMA CA Pro Kids Shoes feature a soft leather-look upper, playful graphic details and a cushioned sole that keeps up with every adventure.',
 'puma-ca-pro-kids.webp','23,24,25,26,27','Pink:#F4B8D1,White:#FFFFFF,Grey:#9AA0AC',40,0,11,2),
('adidas-vl-court-kids','Adidas VL Court 3.0 Shoes Kids','Adidas','kids','lifestyle',1599000,NULL,NULL,4.6,210,280,
 'Skate-inspired style made easy. The adidas VL Court 3.0 Kids comes with hook-and-loop straps for quick on and off, a soft upper and a gum rubber outsole for steady steps.',
 'adidas-vl-court-kids.webp','23,24,25,26,27','White:#FFFFFF,Pink:#F4B8D1,Grey:#9AA0AC',40,0,12,3),
('adidas-performance-court-kids','Adidas Performance Court','Adidas','kids','lifestyle',1899000,1999000,'SALE -15%',4.5,76,150,
 'Bright colours, big fun. The adidas Performance Court for kids pairs a durable upper with an easy strap closure and a grippy gum sole built for the playground.',
 'adidas-performance-court-kids.webp','23,24,25,26,27','Blue:#3A8EE6,White:#FFFFFF,Grey:#9AA0AC',40,0,13,4);

-- Produk tambahan (Men / Women / Kids)
INSERT INTO `products`
(`slug`,`name`,`brand`,`gender`,`category`,`price`,`old_price`,`badge`,`rating`,`reviews`,`sold`,`description`,`image`,`sizes`,`colors`,`stock`,`is_featured`,`rank_all`,`position`) VALUES
('nike-air-max-midnight','Nike Air Max Midnight','Nike','men','running',1949000,NULL,'NEW',4.8,86,140,
 'The Air Max silhouette in a deep midnight-navy colourway. Visible Air cushioning, a breathable layered upper and a sculpted fit keep every step light and sharp.',
 'nike-air-max-midnight.webp','39,40,41,42,43','Navy:#1E2F73',45,0,14,5),
('nike-air-max-crimson','Nike Air Max Crimson','Nike','men','running',1949000,2199000,'SALE -10%',4.7,102,210,
 'A bold crimson take on the Air Max look. Responsive cushioning underfoot and a structured overlay design make it a statement pair for daily runs and city streets.',
 'nike-air-max-crimson.webp','39,40,41,42,43','Crimson:#AF1C2A',38,0,15,6),
('adidas-ultraboost-royal','Adidas Ultraboost Royal','Adidas','men','running',2399000,2999000,'SALE -20%',4.7,134,260,
 'Royal blue PRIMEKNIT upper wrapped around responsive BOOST cushioning. A sock-like fit and energy-returning midsole built for long distances and all-day comfort.',
 'adidas-ultraboost-royal.webp','39,40,41,42,43','Royal:#1E55CD',32,0,16,7),
('new-balance-530-sand','New Balance 530 Sand','New Balance','men','lifestyle',1549000,NULL,NULL,4.6,88,175,
 'The retro 530 runner in a soft sand tone. Breathable mesh, ABZORB cushioning and a layered silhouette that pairs with almost any outfit.',
 'new-balance-530-sand.webp','39,40,41,42,43','Sand:#D6BC94',50,0,17,8),
('converse-chuck-taylor-red','Converse Chuck Taylor Red','Converse','men','lifestyle',899000,NULL,NULL,4.7,95,330,
 'The classic Chuck Taylor low-top in a rich red canvas. Rubber toe cap, cushioned insole and the timeless look that never goes out of style.',
 'converse-chuck-taylor-red.webp','39,40,41,42,43','Red:#C81E2A',60,0,18,9),
('puma-speedcat-burgundy','Puma Speedcat Burgundy','Puma','men','lifestyle',1749000,1999000,'SALE -10%',4.5,64,120,
 'Motorsport-inspired suede in deep burgundy. A low-profile upper, slim sole and bold formstrip give the Speedcat its unmistakable racing character.',
 'puma-speedcat-burgundy.webp','39,40,41,42,43','Burgundy:#7D1630',30,0,19,10),
('puma-speedcat-ballet-wine','Puma Speedcat Ballet Wine','Puma','women','lifestyle',1749000,1999000,'SALE -10%',4.5,58,110,
 'Ballet-flat elegance meets racing heritage. A soft wine-coloured suede upper with a criss-cross strap, flexible sole and cat logo detail.',
 'puma-speedcat-ballet-wine.webp','37,38,39,40,41','Wine:#871C40',28,0,20,5),
('adidas-gazelle-indoor-pink','Adidas Gazelle Indoor Pink','Adidas','women','lifestyle',2049000,NULL,'NEW',4.8,92,150,
 'The iconic Gazelle Indoor in a soft pink suede. Classic 3-Stripes, a gum rubber sole and a clean low profile for effortless everyday style.',
 'adidas-gazelle-indoor-pink.webp','37,38,39,40,41','Pink:#E296B4',36,0,21,6),
('nike-v2k-run-rose','Nike V2K Run Rose','Nike','women','running',2299000,NULL,'NEW',4.7,71,95,
 'A Y2K-inspired runner in a muted rose colourway. A chunky layered upper and plush foam midsole bring early-2000s tech style to your daily rotation.',
 'nike-v2k-run-rose.webp','37,38,39,40,41','Rose:#CD7D96',34,0,22,7),
('new-balance-530-women-sage','New Balance 530 Sage','New Balance','women','lifestyle',1549000,NULL,NULL,4.6,80,160,
 'The retro 530 in calming sage green. A breathable mesh upper, ABZORB cushioning and a lightweight feel from morning errands to evening plans.',
 'new-balance-530-women-sage.webp','37,38,39,40,41','Sage:#96B698',42,0,23,8),
('converse-chuck-taylor-lilac','Converse Chuck Taylor Lilac','Converse','women','lifestyle',899000,NULL,NULL,4.7,77,205,
 'The everyday Chuck Taylor low-top in soft lilac canvas. A durable upper, classic rubber toe cap and a cushioned insole for all-day wear.',
 'converse-chuck-taylor-lilac.webp','37,38,39,40,41','Lilac:#7858AC',55,0,24,9),
('adidas-performance-court-kids-green','Adidas Performance Court Kids Green','Adidas','kids','lifestyle',1899000,1999000,'SALE -15%',4.5,69,120,
 'Bright green and big fun. A durable upper with an easy strap closure and a grippy gum sole built for the playground.',
 'adidas-performance-court-kids-green.webp','23,24,25,26,27','Green:#3BC24A',40,0,25,5),
('adidas-performance-court-kids-pink','Adidas Performance Court Kids Pink','Adidas','kids','lifestyle',1899000,NULL,'NEW',4.6,54,90,
 'A playful pink colourway with sunny yellow stripes. Easy strap closure, a cushioned insole and a grippy gum sole for kids who never sit still.',
 'adidas-performance-court-kids-pink.webp','23,24,25,26,27','Pink:#E0408C',40,0,26,6),
('adidas-vl-court-kids-blue','Adidas VL Court Kids Blue','Adidas','kids','lifestyle',1599000,NULL,NULL,4.5,48,85,
 'A clean white court shoe with sky-blue stripes. Hook-and-loop straps make it easy for little feet to get in and out.',
 'adidas-vl-court-kids-blue.webp','23,24,25,26,27','Blue:#5B9BE6',45,0,27,7),
('puma-suede-xl-toddlers-red','Puma Suede XL Toddlers Red','Puma','kids','running',1799000,NULL,'NEW',4.8,73,130,
 'The oversized Suede XL in classic red. A soft suede upper, fat laces and the signature formstrip, sized for toddlers.',
 'puma-suede-xl-toddlers-red.webp','23,24,25,26,27','Red:#D61F2B',35,0,28,8),
('converse-chuck-taylor-kids-navy','Converse Chuck Taylor Kids Navy','Converse','kids','lifestyle',699000,NULL,NULL,4.7,62,175,
 'The classic Chuck Taylor low-top in navy canvas for kids. Durable, easy to style and ready for every adventure.',
 'converse-chuck-taylor-kids-navy.webp','23,24,25,26,27','Navy:#193A7D',50,0,29,9);

-- Sepuluh promo harian tanpa batas penggunaan atau pembatasan per pengguna.
-- Mulai berlaku saat import dan berulang setiap hari hingga ends_on (NULL = tanpa tanggal akhir).
INSERT INTO `promo_codes` (`code`,`type`,`value`,`min_subtotal`,`starts_on`,`ends_on`,`is_active`) VALUES
('BELANJA30K','fixed',30000,300000,CURDATE(),NULL,1),
('HEMAT50K','fixed',50000,500000,CURDATE(),NULL,1),
('POTONG75K','fixed',75000,750000,CURDATE(),NULL,1),
('LZFJ10','percent',10,500000,CURDATE(),NULL,1),
('HEMAT100K','fixed',100000,1000000,CURDATE(),NULL,1),
('WELCOME15','percent',15,1000000,CURDATE(),NULL,1),
('WEEKEND12','percent',12,1500000,CURDATE(),NULL,1),
('SNEAKER20','percent',20,2000000,CURDATE(),NULL,1),
('GAJIAN20','percent',20,2500000,CURDATE(),NULL,1),
('FLASH25','percent',25,3000000,CURDATE(),NULL,1);

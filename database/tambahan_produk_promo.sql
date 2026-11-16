-- =========================================================
--  PATCH untuk database yang SUDAH berjalan (tidak menghapus data / pesanan)
--  Menambah 16 produk, mengganti katalog promo menjadi tepat 10 kode harian,
--  dan menyiapkan tabel wishlists.
--  Cara: phpMyAdmin -> pilih database lzfj_shoes_store -> tab Import -> pilih file ini -> Go.
--  Aman dijalankan berulang kali (data yang sudah ada dilewati).
-- =========================================================
SET NAMES utf8mb4;
USE `lzfj_shoes_store`;

-- ---------------------------------------------------------
-- Wishlist (tombol love). Satu baris = satu sepatu yang disimpan pembeli.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wishlists` (
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`product_id`),
  KEY `idx_wish_product` (`product_id`),
  CONSTRAINT `fk_wish_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wish_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT IGNORE INTO `products`
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

-- Migrasi kolom promo lama. Pemeriksaan metadata menjaga patch tetap aman di-import ulang.
SET @promo_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'promo_codes' AND column_name = 'min_subtotal') = 0,
  'ALTER TABLE `promo_codes` ADD COLUMN `min_subtotal` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `value`',
  'SELECT 1'
);
PREPARE promo_stmt FROM @promo_sql;
EXECUTE promo_stmt;
DEALLOCATE PREPARE promo_stmt;

SET @promo_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'promo_codes' AND column_name = 'starts_on') = 0,
  'ALTER TABLE `promo_codes` ADD COLUMN `starts_on` DATE NULL AFTER `min_subtotal`',
  'SELECT 1'
);
PREPARE promo_stmt FROM @promo_sql;
EXECUTE promo_stmt;
DEALLOCATE PREPARE promo_stmt;

SET @promo_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'promo_codes' AND column_name = 'ends_on') = 0,
  'ALTER TABLE `promo_codes` ADD COLUMN `ends_on` DATE NULL AFTER `starts_on`',
  'SELECT 1'
);
PREPARE promo_stmt FROM @promo_sql;
EXECUTE promo_stmt;
DEALLOCATE PREPARE promo_stmt;

UPDATE `promo_codes` SET `starts_on` = CURDATE() WHERE `starts_on` IS NULL;
ALTER TABLE `promo_codes` MODIFY COLUMN `starts_on` DATE NOT NULL;

SET @promo_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'promo_codes' AND column_name = 'max_discount') > 0,
  'ALTER TABLE `promo_codes` DROP COLUMN `max_discount`',
  'SELECT 1'
);
PREPARE promo_stmt FROM @promo_sql;
EXECUTE promo_stmt;
DEALLOCATE PREPARE promo_stmt;

-- Hanya sepuluh promo ini yang disediakan. Penggunaan tidak dibatasi dan tidak ditumpuk.
DELETE FROM `promo_codes`;
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

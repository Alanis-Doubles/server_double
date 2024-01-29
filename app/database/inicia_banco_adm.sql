-- --------------------------------------------------------
-- Servidor:                     srv1183.hstgr.io
-- Versão do servidor:           10.6.15-MariaDB-cll-lve - MariaDB Server
-- OS do Servidor:               Linux
-- HeidiSQL Versão:              12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Copiando estrutura para tabela u485635095_admin.system_access_log
CREATE TABLE IF NOT EXISTS `system_access_log` (
  `id` int(11) NOT NULL,
  `sessionid` varchar(256) DEFAULT NULL,
  `login` varchar(256) DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `login_year` varchar(4) DEFAULT NULL,
  `login_month` varchar(2) DEFAULT NULL,
  `login_day` varchar(2) DEFAULT NULL,
  `logout_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `impersonated` char(1) DEFAULT NULL,
  `access_ip` varchar(45) DEFAULT NULL,
  `impersonated_by` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_access_log_login_idx` (`login`),
  KEY `sys_access_log_year_idx` (`login_year`),
  KEY `sys_access_log_month_idx` (`login_month`),
  KEY `sys_access_log_day_idx` (`login_day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_access_log: ~2 rows (aproximadamente)
INSERT INTO `system_access_log` (`id`, `sessionid`, `login`, `login_time`, `login_year`, `login_month`, `login_day`, `logout_time`, `impersonated`, `access_ip`, `impersonated_by`) VALUES
	(1, 'qi956b8mmlspijfresstshl1jf', 'admin', '2024-01-26 16:42:31', '2024', '01', '26', '2024-01-28 04:23:53', 'N', '177.74.142.57', NULL),
	(2, 'q8f2vp5osdom4gh77s7bvhemsj', 'admin', '2024-01-28 04:24:18', '2024', '01', '28', '0000-00-00 00:00:00', 'N', '177.74.142.57', NULL);

-- Copiando estrutura para tabela u485635095_admin.system_access_notification_log
CREATE TABLE IF NOT EXISTS `system_access_notification_log` (
  `id` int(11) NOT NULL,
  `login` varchar(256) DEFAULT NULL,
  `email` varchar(256) DEFAULT NULL,
  `ip_address` varchar(256) DEFAULT NULL,
  `login_time` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_access_notification_log_login_idx` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_access_notification_log: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_change_log
CREATE TABLE IF NOT EXISTS `system_change_log` (
  `id` int(11) NOT NULL,
  `logdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `login` varchar(256) DEFAULT NULL,
  `tablename` varchar(256) DEFAULT NULL,
  `primarykey` varchar(256) DEFAULT NULL,
  `pkvalue` varchar(256) DEFAULT NULL,
  `operation` varchar(256) DEFAULT NULL,
  `columnname` varchar(256) DEFAULT NULL,
  `oldvalue` text DEFAULT NULL,
  `newvalue` text DEFAULT NULL,
  `access_ip` varchar(256) DEFAULT NULL,
  `transaction_id` varchar(256) DEFAULT NULL,
  `log_trace` text DEFAULT NULL,
  `session_id` varchar(256) DEFAULT NULL,
  `class_name` varchar(256) DEFAULT NULL,
  `php_sapi` varchar(256) DEFAULT NULL,
  `log_year` varchar(4) DEFAULT NULL,
  `log_month` varchar(2) DEFAULT NULL,
  `log_day` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_change_log_login_idx` (`login`),
  KEY `sys_change_log_date_idx` (`logdate`),
  KEY `sys_change_log_year_idx` (`log_year`),
  KEY `sys_change_log_month_idx` (`log_month`),
  KEY `sys_change_log_day_idx` (`log_day`),
  KEY `sys_change_log_class_idx` (`class_name`),
  KEY `sys_change_log_table_idx` (`tablename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_change_log: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_document
CREATE TABLE IF NOT EXISTS `system_document` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `title` varchar(256) DEFAULT NULL,
  `description` varchar(4096) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `submission_date` date DEFAULT NULL,
  `archive_date` date DEFAULT NULL,
  `filename` varchar(512) DEFAULT NULL,
  `in_trash` char(1) DEFAULT NULL,
  `system_folder_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_document_user_idx` (`system_user_id`),
  KEY `sys_document_category_idx` (`category_id`),
  KEY `sys_document_folder_idx` (`system_folder_id`),
  CONSTRAINT `system_document_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `system_document_category` (`id`),
  CONSTRAINT `system_document_ibfk_2` FOREIGN KEY (`system_folder_id`) REFERENCES `system_folder` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_document: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_document_bookmark
CREATE TABLE IF NOT EXISTS `system_document_bookmark` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `system_document_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_document_bookmark_user_idx` (`system_user_id`),
  KEY `sys_document_bookmark_document_idx` (`system_document_id`),
  CONSTRAINT `system_document_bookmark_ibfk_1` FOREIGN KEY (`system_document_id`) REFERENCES `system_document` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_document_bookmark: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_document_category
CREATE TABLE IF NOT EXISTS `system_document_category` (
  `id` int(11) NOT NULL,
  `name` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_document_category_name_idx` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_document_category: ~1 rows (aproximadamente)
INSERT INTO `system_document_category` (`id`, `name`) VALUES
	(1, 'Documentos');

-- Copiando estrutura para tabela u485635095_admin.system_document_group
CREATE TABLE IF NOT EXISTS `system_document_group` (
  `id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `system_group_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_document_group_document_idx` (`document_id`),
  KEY `sys_document_group_group_idx` (`system_group_id`),
  CONSTRAINT `system_document_group_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `system_document` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_document_group: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_document_user
CREATE TABLE IF NOT EXISTS `system_document_user` (
  `id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_document_user_document_idx` (`document_id`),
  KEY `sys_document_user_user_idx` (`system_user_id`),
  CONSTRAINT `system_document_user_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `system_document` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_document_user: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_folder
CREATE TABLE IF NOT EXISTS `system_folder` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `name` varchar(256) NOT NULL,
  `in_trash` char(1) DEFAULT NULL,
  `system_folder_parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_folder_user_id_idx` (`system_user_id`),
  KEY `sys_folder_name_idx` (`name`),
  KEY `sys_folder_parend_id_idx` (`system_folder_parent_id`),
  CONSTRAINT `system_folder_ibfk_1` FOREIGN KEY (`system_folder_parent_id`) REFERENCES `system_folder` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_folder: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_folder_bookmark
CREATE TABLE IF NOT EXISTS `system_folder_bookmark` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `system_folder_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_folder_bookmark_user_idx` (`system_user_id`),
  KEY `sys_folder_bookmark_folder_idx` (`system_folder_id`),
  CONSTRAINT `system_folder_bookmark_ibfk_1` FOREIGN KEY (`system_folder_id`) REFERENCES `system_folder` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_folder_bookmark: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_folder_group
CREATE TABLE IF NOT EXISTS `system_folder_group` (
  `id` int(11) NOT NULL,
  `system_folder_id` int(11) DEFAULT NULL,
  `system_group_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_folder_group_folder_idx` (`system_folder_id`),
  KEY `sys_folder_group_group_idx` (`system_group_id`),
  CONSTRAINT `system_folder_group_ibfk_1` FOREIGN KEY (`system_folder_id`) REFERENCES `system_folder` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_folder_group: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_folder_user
CREATE TABLE IF NOT EXISTS `system_folder_user` (
  `id` int(11) NOT NULL,
  `system_folder_id` int(11) DEFAULT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_folder_user_folder_idx` (`system_folder_id`),
  KEY `sys_folder_user_user_idx` (`system_user_id`),
  CONSTRAINT `system_folder_user_ibfk_1` FOREIGN KEY (`system_folder_id`) REFERENCES `system_folder` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_folder_user: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_group
CREATE TABLE IF NOT EXISTS `system_group` (
  `id` int(11) NOT NULL,
  `name` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_group_name_idx` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_group: ~2 rows (aproximadamente)
INSERT INTO `system_group` (`id`, `name`) VALUES
	(1, 'Admin'),
	(2, 'Double-Administrador');

-- Copiando estrutura para tabela u485635095_admin.system_group_program
CREATE TABLE IF NOT EXISTS `system_group_program` (
  `id` int(11) NOT NULL,
  `system_group_id` int(11) DEFAULT NULL,
  `system_program_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_group_program_program_idx` (`system_program_id`),
  KEY `sys_group_program_group_idx` (`system_group_id`),
  CONSTRAINT `system_group_program_ibfk_1` FOREIGN KEY (`system_group_id`) REFERENCES `system_group` (`id`),
  CONSTRAINT `system_group_program_ibfk_2` FOREIGN KEY (`system_program_id`) REFERENCES `system_program` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_group_program: ~82 rows (aproximadamente)
INSERT INTO `system_group_program` (`id`, `system_group_id`, `system_program_id`) VALUES
	(1, 1, 1),
	(2, 1, 2),
	(3, 1, 3),
	(4, 1, 4),
	(5, 1, 5),
	(6, 1, 6),
	(7, 1, 8),
	(8, 1, 9),
	(9, 1, 11),
	(10, 1, 14),
	(11, 1, 15),
	(20, 1, 21),
	(25, 1, 26),
	(26, 1, 27),
	(27, 1, 28),
	(28, 1, 29),
	(30, 1, 31),
	(31, 1, 32),
	(32, 1, 33),
	(33, 1, 34),
	(34, 1, 35),
	(36, 1, 36),
	(37, 1, 37),
	(38, 1, 38),
	(39, 1, 39),
	(40, 1, 40),
	(41, 1, 41),
	(42, 1, 42),
	(43, 1, 43),
	(44, 1, 44),
	(45, 1, 45),
	(46, 1, 46),
	(47, 1, 47),
	(48, 1, 48),
	(49, 1, 49),
	(52, 1, 52),
	(53, 1, 53),
	(54, 1, 54),
	(55, 1, 55),
	(56, 1, 56),
	(57, 1, 57),
	(58, 1, 58),
	(59, 1, 59),
	(60, 1, 60),
	(61, 1, 61),
	(74, 1, 62),
	(75, 1, 63),
	(76, 2, 12),
	(77, 2, 13),
	(78, 2, 16),
	(79, 2, 17),
	(80, 2, 18),
	(81, 2, 19),
	(82, 2, 20),
	(83, 2, 21),
	(84, 2, 30),
	(85, 2, 43),
	(86, 2, 44),
	(87, 2, 45),
	(88, 2, 46),
	(89, 2, 47),
	(90, 2, 48),
	(91, 2, 49),
	(92, 2, 52),
	(93, 2, 53),
	(94, 2, 54),
	(95, 2, 55),
	(96, 2, 56),
	(97, 2, 58),
	(98, 2, 59),
	(99, 2, 60),
	(100, 2, 61),
	(101, 2, 64),
	(103, 2, 66),
	(104, 2, 65),
	(105, 2, 67),
	(106, 2, 68),
	(107, 2, 69),
	(108, 2, 70),
	(109, 2, 71),
	(110, 2, 72),
	(111, 2, 73);

-- Copiando estrutura para tabela u485635095_admin.system_message
CREATE TABLE IF NOT EXISTS `system_message` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `system_user_to_id` int(11) DEFAULT NULL,
  `subject` varchar(256) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `dt_message` varchar(256) DEFAULT NULL,
  `checked` char(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_message_user_id_idx` (`system_user_id`),
  KEY `sys_message_user_to_idx` (`system_user_to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_message: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_notification
CREATE TABLE IF NOT EXISTS `system_notification` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `system_user_to_id` int(11) DEFAULT NULL,
  `subject` varchar(256) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `dt_message` varchar(256) DEFAULT NULL,
  `action_url` varchar(4096) DEFAULT NULL,
  `action_label` varchar(256) DEFAULT NULL,
  `icon` varchar(256) DEFAULT NULL,
  `checked` char(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_notification_user_id_idx` (`system_user_id`),
  KEY `sys_notification_user_to_idx` (`system_user_to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_notification: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_post
CREATE TABLE IF NOT EXISTS `system_post` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `title` varchar(256) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active` char(1) NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`id`),
  KEY `sys_post_user_idx` (`system_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_post: ~2 rows (aproximadamente)
INSERT INTO `system_post` (`id`, `system_user_id`, `title`, `content`, `created_at`, `active`) VALUES
	(1, 1, 'Primeira noticia', '<p style="text-align: justify; "><span style="font-family: &quot;Source Sans Pro&quot;; font-size: 18px;">﻿</span><span style="font-family: &quot;Source Sans Pro&quot;; font-size: 18px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Id cursus metus aliquam eleifend mi in nulla posuere sollicitudin. Tincidunt nunc pulvinar sapien et ligula ullamcorper. Odio pellentesque diam volutpat commodo sed egestas egestas. Eget egestas purus viverra accumsan in nisl nisi scelerisque. Habitant morbi tristique senectus et netus et malesuada. Vitae ultricies leo integer malesuada nunc vel risus commodo viverra. Vehicula ipsum a arcu cursus. Rhoncus est pellentesque elit ullamcorper dignissim. Faucibus in ornare quam viverra orci sagittis eu. Nisi scelerisque eu ultrices vitae auctor. Tellus cras adipiscing enim eu turpis egestas. Eget lorem dolor sed viverra ipsum nunc aliquet. Neque convallis a cras semper auctor neque. Bibendum ut tristique et egestas. Amet nisl suscipit adipiscing bibendum.</span></p><p style="text-align: justify;"><span style="font-family: &quot;Source Sans Pro&quot;; font-size: 18px;">Mattis nunc sed blandit libero volutpat sed cras ornare. Leo duis ut diam quam nulla. Tempus imperdiet nulla malesuada pellentesque elit eget gravida cum sociis. Non quam lacus suspendisse faucibus. Enim nulla aliquet porttitor lacus luctus accumsan tortor posuere ac. Dignissim enim sit amet venenatis urna. Elit sed vulputate mi sit. Sit amet nisl suscipit adipiscing bibendum est. Maecenas accumsan lacus vel facilisis. Orci phasellus egestas tellus rutrum tellus pellentesque eu tincidunt tortor. Aenean pharetra magna ac placerat vestibulum lectus mauris ultrices eros. Augue lacus viverra vitae congue eu consequat ac felis. Bibendum neque egestas congue quisque egestas diam. Facilisis magna etiam tempor orci eu lobortis elementum. Rhoncus est pellentesque elit ullamcorper dignissim cras tincidunt lobortis. Pellentesque adipiscing commodo elit at imperdiet dui accumsan sit amet. Nullam eget felis eget nunc. Nec ullamcorper sit amet risus nullam eget felis. Lacus vel facilisis volutpat est velit egestas dui id.</span></p>', '2022-11-03 14:59:39', 'Y'),
	(2, 1, 'Segunda noticia', '<p style="text-align: justify; "><span style="font-size: 18px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ac orci phasellus egestas tellus rutrum. Pretium nibh ipsum consequat nisl vel pretium lectus quam. Faucibus scelerisque eleifend donec pretium vulputate sapien. Mattis molestie a iaculis at erat pellentesque adipiscing commodo elit. Ultricies mi quis hendrerit dolor magna eget. Quam id leo in vitae turpis massa sed elementum tempus. Eget arcu dictum varius duis at consectetur lorem. Quis varius quam quisque id diam. Consequat interdum varius sit amet mattis vulputate. Purus non enim praesent elementum facilisis leo vel fringilla. Nulla facilisi nullam vehicula ipsum a arcu. Habitant morbi tristique senectus et netus et malesuada fames. Risus commodo viverra maecenas accumsan lacus. Mattis molestie a iaculis at erat pellentesque adipiscing commodo elit. Imperdiet proin fermentum leo vel orci porta non pulvinar neque. Massa massa ultricies mi quis hendrerit. Vel turpis nunc eget lorem dolor sed viverra ipsum nunc. Quisque egestas diam in arcu cursus euismod quis.</span></p><p style="text-align: justify; "><span style="font-size: 18px;">Posuere morbi leo urna molestie at elementum eu facilisis. Dolor morbi non arcu risus quis varius quam. Fermentum posuere urna nec tincidunt praesent semper feugiat nibh. Consectetur adipiscing elit ut aliquam purus sit. Gravida cum sociis natoque penatibus et magnis. Sollicitudin aliquam ultrices sagittis orci. Tortor consequat id porta nibh venenatis cras sed felis. Dictumst quisque sagittis purus sit amet volutpat consequat mauris nunc. Arcu dictum varius duis at consectetur. Mauris commodo quis imperdiet massa tincidunt nunc pulvinar. At tellus at urna condimentum mattis pellentesque. Tellus mauris a diam maecenas sed.</span></p>', '2022-11-03 15:03:31', 'Y');

-- Copiando estrutura para tabela u485635095_admin.system_post_comment
CREATE TABLE IF NOT EXISTS `system_post_comment` (
  `id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `system_user_id` int(11) NOT NULL,
  `system_post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sys_post_comment_user_idx` (`system_user_id`),
  KEY `sys_post_comment_post_idx` (`system_post_id`),
  CONSTRAINT `system_post_comment_ibfk_1` FOREIGN KEY (`system_post_id`) REFERENCES `system_post` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_post_comment: ~3 rows (aproximadamente)
INSERT INTO `system_post_comment` (`id`, `comment`, `system_user_id`, `system_post_id`, `created_at`) VALUES
	(1, 'My first comment', 1, 2, '2022-11-03 15:22:11'),
	(2, 'Another comment', 1, 2, '2022-11-03 15:22:17'),
	(3, 'The best comment', 2, 2, '2022-11-03 15:23:11');

-- Copiando estrutura para tabela u485635095_admin.system_post_like
CREATE TABLE IF NOT EXISTS `system_post_like` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `system_post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sys_post_like_user_idx` (`system_user_id`),
  KEY `sys_post_like_post_idx` (`system_post_id`),
  CONSTRAINT `system_post_like_ibfk_1` FOREIGN KEY (`system_post_id`) REFERENCES `system_post` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_post_like: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_post_share_group
CREATE TABLE IF NOT EXISTS `system_post_share_group` (
  `id` int(11) NOT NULL,
  `system_group_id` int(11) DEFAULT NULL,
  `system_post_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_post_share_group_group_idx` (`system_group_id`),
  KEY `sys_post_share_group_post_idx` (`system_post_id`),
  CONSTRAINT `system_post_share_group_ibfk_1` FOREIGN KEY (`system_post_id`) REFERENCES `system_post` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_post_share_group: ~4 rows (aproximadamente)
INSERT INTO `system_post_share_group` (`id`, `system_group_id`, `system_post_id`) VALUES
	(1, 1, 1),
	(2, 2, 1),
	(3, 1, 2),
	(4, 2, 2);

-- Copiando estrutura para tabela u485635095_admin.system_post_tag
CREATE TABLE IF NOT EXISTS `system_post_tag` (
  `id` int(11) NOT NULL,
  `system_post_id` int(11) NOT NULL,
  `tag` varchar(256) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_post_tag_post_idx` (`system_post_id`),
  CONSTRAINT `system_post_tag_ibfk_1` FOREIGN KEY (`system_post_id`) REFERENCES `system_post` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_post_tag: ~2 rows (aproximadamente)
INSERT INTO `system_post_tag` (`id`, `system_post_id`, `tag`) VALUES
	(1, 1, 'novidades'),
	(2, 2, 'novidades');

-- Copiando estrutura para tabela u485635095_admin.system_preference
CREATE TABLE IF NOT EXISTS `system_preference` (
  `id` varchar(256) DEFAULT NULL,
  `value` text DEFAULT NULL,
  KEY `sys_preference_id_idx` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_preference: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_program
CREATE TABLE IF NOT EXISTS `system_program` (
  `id` int(11) NOT NULL,
  `name` varchar(256) DEFAULT NULL,
  `controller` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_program_name_idx` (`name`),
  KEY `sys_program_controller_idx` (`controller`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_program: ~67 rows (aproximadamente)
INSERT INTO `system_program` (`id`, `name`, `controller`) VALUES
	(1, 'System Group Form', 'SystemGroupForm'),
	(2, 'System Group List', 'SystemGroupList'),
	(3, 'System Program Form', 'SystemProgramForm'),
	(4, 'System Program List', 'SystemProgramList'),
	(5, 'System User Form', 'SystemUserForm'),
	(6, 'System User List', 'SystemUserList'),
	(7, 'Common Page', 'CommonPage'),
	(8, 'System PHP Info', 'SystemPHPInfoView'),
	(9, 'System ChangeLog View', 'SystemChangeLogView'),
	(10, 'Welcome View', 'WelcomeView'),
	(11, 'System Sql Log', 'SystemSqlLogList'),
	(12, 'System Profile View', 'SystemProfileView'),
	(13, 'System Profile Form', 'SystemProfileForm'),
	(14, 'System SQL Panel', 'SystemSQLPanel'),
	(15, 'System Access Log', 'SystemAccessLogList'),
	(16, 'System Message Form', 'SystemMessageForm'),
	(17, 'System Message List', 'SystemMessageList'),
	(18, 'System Message Form View', 'SystemMessageFormView'),
	(19, 'System Notification List', 'SystemNotificationList'),
	(20, 'System Notification Form View', 'SystemNotificationFormView'),
	(21, 'System Document Category List', 'SystemDocumentCategoryFormList'),
	(26, 'System Unit Form', 'SystemUnitForm'),
	(27, 'System Unit List', 'SystemUnitList'),
	(28, 'System Access stats', 'SystemAccessLogStats'),
	(29, 'System Preference form', 'SystemPreferenceForm'),
	(30, 'System Support form', 'SystemSupportForm'),
	(31, 'System PHP Error', 'SystemPHPErrorLogView'),
	(32, 'System Database Browser', 'SystemDatabaseExplorer'),
	(33, 'System Table List', 'SystemTableList'),
	(34, 'System Data Browser', 'SystemDataBrowser'),
	(35, 'System Menu Editor', 'SystemMenuEditor'),
	(36, 'System Request Log', 'SystemRequestLogList'),
	(37, 'System Request Log View', 'SystemRequestLogView'),
	(38, 'System Administration Dashboard', 'SystemAdministrationDashboard'),
	(39, 'System Log Dashboard', 'SystemLogDashboard'),
	(40, 'System Session vars', 'SystemSessionVarsView'),
	(41, 'System Information', 'SystemInformationView'),
	(42, 'System files diff', 'SystemFilesDiff'),
	(43, 'System Documents', 'SystemDriveList'),
	(44, 'System Folder form', 'SystemFolderForm'),
	(45, 'System Share folder', 'SystemFolderShareForm'),
	(46, 'System Share document', 'SystemDocumentShareForm'),
	(47, 'System Document properties', 'SystemDocumentFormWindow'),
	(48, 'System Folder properties', 'SystemFolderFormView'),
	(49, 'System Document upload', 'SystemDriveDocumentUploadForm'),
	(52, 'System Post list', 'SystemPostList'),
	(53, 'System Post form', 'SystemPostForm'),
	(54, 'Post View list', 'SystemPostFeedView'),
	(55, 'Post Comment form', 'SystemPostCommentForm'),
	(56, 'Post Comment list', 'SystemPostCommentList'),
	(57, 'System Contacts list', 'SystemContactsList'),
	(58, 'System Wiki list', 'SystemWikiList'),
	(59, 'System Wiki form', 'SystemWikiForm'),
	(60, 'System Wiki search', 'SystemWikiSearchList'),
	(61, 'System Wiki view', 'SystemWikiView'),
	(62, 'System Role List', 'SystemRoleList'),
	(63, 'System Role Form', 'SystemRoleForm'),
	(64, 'System Profile 2FA Form', 'SystemProfile2FAForm'),
	(65, '[Double] Plataformas', 'TDoublePlataformaList'),
	(66, '[Double] Plataforma - Detalhes', 'TDoublePlataformaForm'),
	(67, '[Double] Usuários', 'TDoubleUsuarioList'),
	(68, '[Double] Usuário - Form', 'TDoubleUsuarioForm'),
	(69, '[Double] Usuário - Histórico', 'TDoubleUsuarioHistoricoForm'),
	(70, '[Double] Usuário - Pagamento', 'TDoubleUsuarioPagamentoForm'),
	(71, '[Double] Canal', 'TDoubleCanalList'),
	(72, '[Double] Canal - Form', 'TDoubleCanalForm'),
	(73, '[Double] Dashboard', 'TDoubleDashboard');

-- Copiando estrutura para tabela u485635095_admin.system_program_method_role
CREATE TABLE IF NOT EXISTS `system_program_method_role` (
  `id` int(11) NOT NULL,
  `system_program_id` int(11) DEFAULT NULL,
  `system_role_id` int(11) DEFAULT NULL,
  `method_name` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_program_method_role_program_idx` (`system_program_id`),
  KEY `sys_program_method_role_role_idx` (`system_role_id`),
  CONSTRAINT `system_program_method_role_ibfk_1` FOREIGN KEY (`system_program_id`) REFERENCES `system_program` (`id`),
  CONSTRAINT `system_program_method_role_ibfk_2` FOREIGN KEY (`system_role_id`) REFERENCES `system_role` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_program_method_role: ~4 rows (aproximadamente)
INSERT INTO `system_program_method_role` (`id`, `system_program_id`, `system_role_id`, `method_name`) VALUES
	(1, 66, 1, 'onInsert'),
	(2, 65, 1, 'onDelete'),
	(3, 67, 1, 'onDelete'),
	(4, 71, 2, 'onDelete');

-- Copiando estrutura para tabela u485635095_admin.system_request_log
CREATE TABLE IF NOT EXISTS `system_request_log` (
  `id` int(11) NOT NULL,
  `endpoint` varchar(4096) DEFAULT NULL,
  `logdate` varchar(256) DEFAULT NULL,
  `log_year` varchar(4) DEFAULT NULL,
  `log_month` varchar(2) DEFAULT NULL,
  `log_day` varchar(2) DEFAULT NULL,
  `session_id` varchar(256) DEFAULT NULL,
  `login` varchar(256) DEFAULT NULL,
  `access_ip` varchar(256) DEFAULT NULL,
  `class_name` varchar(256) DEFAULT NULL,
  `class_method` varchar(256) DEFAULT NULL,
  `http_host` varchar(256) DEFAULT NULL,
  `server_port` varchar(256) DEFAULT NULL,
  `request_uri` text DEFAULT NULL,
  `request_method` varchar(256) DEFAULT NULL,
  `query_string` text DEFAULT NULL,
  `request_headers` text DEFAULT NULL,
  `request_body` text DEFAULT NULL,
  `request_duration` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_request_log_login_idx` (`login`),
  KEY `sys_request_log_date_idx` (`logdate`),
  KEY `sys_request_log_year_idx` (`log_year`),
  KEY `sys_request_log_month_idx` (`log_month`),
  KEY `sys_request_log_day_idx` (`log_day`),
  KEY `sys_request_log_class_idx` (`class_name`),
  KEY `sys_request_log_method_idx` (`class_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_request_log: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_role
CREATE TABLE IF NOT EXISTS `system_role` (
  `id` int(11) NOT NULL,
  `name` varchar(256) DEFAULT NULL,
  `custom_code` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_role_name_idx` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_role: ~3 rows (aproximadamente)
INSERT INTO `system_role` (`id`, `name`, `custom_code`) VALUES
	(1, 'Administrador', 'admin'),
	(2, 'Administrador Plataforma', 'plataform_admin'),
	(3, 'Jogador', 'player');

-- Copiando estrutura para tabela u485635095_admin.system_sql_log
CREATE TABLE IF NOT EXISTS `system_sql_log` (
  `id` int(11) NOT NULL,
  `logdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `login` varchar(256) DEFAULT NULL,
  `database_name` varchar(256) DEFAULT NULL,
  `sql_command` text DEFAULT NULL,
  `statement_type` varchar(256) DEFAULT NULL,
  `access_ip` varchar(45) DEFAULT NULL,
  `transaction_id` varchar(256) DEFAULT NULL,
  `log_trace` text DEFAULT NULL,
  `session_id` varchar(256) DEFAULT NULL,
  `class_name` varchar(256) DEFAULT NULL,
  `php_sapi` varchar(256) DEFAULT NULL,
  `request_id` varchar(256) DEFAULT NULL,
  `log_year` varchar(4) DEFAULT NULL,
  `log_month` varchar(2) DEFAULT NULL,
  `log_day` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_sql_log_login_idx` (`login`),
  KEY `sys_sql_log_date_idx` (`logdate`),
  KEY `sys_sql_log_database_idx` (`database_name`),
  KEY `sys_sql_log_class_idx` (`class_name`),
  KEY `sys_sql_log_year_idx` (`log_year`),
  KEY `sys_sql_log_month_idx` (`log_month`),
  KEY `sys_sql_log_day_idx` (`log_day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_sql_log: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_unit
CREATE TABLE IF NOT EXISTS `system_unit` (
  `id` int(11) NOT NULL,
  `name` varchar(256) DEFAULT NULL,
  `connection_name` varchar(256) DEFAULT NULL,
  `custom_code` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_unit_name_idx` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_unit: ~1 rows (aproximadamente)
INSERT INTO `system_unit` (`id`, `name`, `connection_name`, `custom_code`) VALUES
	(1, '[Double]', 'double', NULL);

-- Copiando estrutura para tabela u485635095_admin.system_users
CREATE TABLE IF NOT EXISTS `system_users` (
  `id` int(11) NOT NULL,
  `name` varchar(256) DEFAULT NULL,
  `login` varchar(256) DEFAULT NULL,
  `password` varchar(256) DEFAULT NULL,
  `email` varchar(256) DEFAULT NULL,
  `accepted_term_policy` char(1) DEFAULT NULL,
  `phone` varchar(256) DEFAULT NULL,
  `address` varchar(256) DEFAULT NULL,
  `function_name` varchar(256) DEFAULT NULL,
  `about` varchar(4096) DEFAULT NULL,
  `accepted_term_policy_at` varchar(256) DEFAULT NULL,
  `accepted_term_policy_data` text DEFAULT NULL,
  `frontpage_id` int(11) DEFAULT NULL,
  `system_unit_id` int(11) DEFAULT NULL,
  `active` char(1) DEFAULT NULL,
  `custom_code` varchar(256) DEFAULT NULL,
  `otp_secret` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_unit_id` (`system_unit_id`),
  KEY `sys_user_program_idx` (`frontpage_id`),
  KEY `sys_users_name_idx` (`name`),
  CONSTRAINT `system_users_ibfk_1` FOREIGN KEY (`system_unit_id`) REFERENCES `system_unit` (`id`),
  CONSTRAINT `system_users_ibfk_2` FOREIGN KEY (`frontpage_id`) REFERENCES `system_program` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_users: ~1 rows (aproximadamente)
INSERT INTO `system_users` (`id`, `name`, `login`, `password`, `email`, `accepted_term_policy`, `phone`, `address`, `function_name`, `about`, `accepted_term_policy_at`, `accepted_term_policy_data`, `frontpage_id`, `system_unit_id`, `active`, `custom_code`, `otp_secret`) VALUES
	(1, 'Administrator', 'admin', '$2y$10$xuR3XEc3J6tpv7myC9gPj.Ab5GacSeHSZoYUTYtOg.cEc22G.iBwa', 'admin@admin.net', 'Y', '+123 456 789', 'Admin Street, 123', 'Administrator', 'I\'m the administrator', NULL, NULL, 73, 1, 'Y', NULL, NULL);

-- Copiando estrutura para tabela u485635095_admin.system_user_group
CREATE TABLE IF NOT EXISTS `system_user_group` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `system_group_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_user_group_group_idx` (`system_group_id`),
  KEY `sys_user_group_user_idx` (`system_user_id`),
  CONSTRAINT `system_user_group_ibfk_1` FOREIGN KEY (`system_user_id`) REFERENCES `system_users` (`id`),
  CONSTRAINT `system_user_group_ibfk_2` FOREIGN KEY (`system_group_id`) REFERENCES `system_group` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_user_group: ~2 rows (aproximadamente)
INSERT INTO `system_user_group` (`id`, `system_user_id`, `system_group_id`) VALUES
	(1, 1, 1),
	(2, 1, 2);

-- Copiando estrutura para tabela u485635095_admin.system_user_old_password
CREATE TABLE IF NOT EXISTS `system_user_old_password` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `password` varchar(256) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sys_user_old_password_user_idx` (`system_user_id`),
  CONSTRAINT `system_user_old_password_ibfk_1` FOREIGN KEY (`system_user_id`) REFERENCES `system_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Copiando estrutura para tabela u485635095_admin.system_user_program
CREATE TABLE IF NOT EXISTS `system_user_program` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `system_program_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_user_program_program_idx` (`system_program_id`),
  KEY `sys_user_program_user_idx` (`system_user_id`),
  CONSTRAINT `system_user_program_ibfk_1` FOREIGN KEY (`system_user_id`) REFERENCES `system_users` (`id`),
  CONSTRAINT `system_user_program_ibfk_2` FOREIGN KEY (`system_program_id`) REFERENCES `system_program` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_user_program: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_user_role
CREATE TABLE IF NOT EXISTS `system_user_role` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `system_role_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_user_role_user_idx` (`system_user_id`),
  KEY `sys_user_role_role_idx` (`system_role_id`),
  CONSTRAINT `system_user_role_ibfk_1` FOREIGN KEY (`system_user_id`) REFERENCES `system_users` (`id`),
  CONSTRAINT `system_user_role_ibfk_2` FOREIGN KEY (`system_role_id`) REFERENCES `system_role` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_user_role: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela u485635095_admin.system_user_unit
CREATE TABLE IF NOT EXISTS `system_user_unit` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `system_unit_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_user_unit_user_idx` (`system_user_id`),
  KEY `sys_user_unit_unit_idx` (`system_unit_id`),
  CONSTRAINT `system_user_unit_ibfk_1` FOREIGN KEY (`system_user_id`) REFERENCES `system_users` (`id`),
  CONSTRAINT `system_user_unit_ibfk_2` FOREIGN KEY (`system_unit_id`) REFERENCES `system_unit` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_user_unit: ~1 rows (aproximadamente)
INSERT INTO `system_user_unit` (`id`, `system_user_id`, `system_unit_id`) VALUES
	(1, 1, 1);

-- Copiando estrutura para tabela u485635095_admin.system_wiki_page
CREATE TABLE IF NOT EXISTS `system_wiki_page` (
  `id` int(11) NOT NULL,
  `system_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `title` varchar(256) NOT NULL,
  `description` varchar(4096) NOT NULL,
  `content` text NOT NULL,
  `active` char(1) NOT NULL DEFAULT 'Y',
  `searchable` char(1) NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`id`),
  KEY `sys_wiki_page_user_idx` (`system_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_wiki_page: ~4 rows (aproximadamente)
INSERT INTO `system_wiki_page` (`id`, `system_user_id`, `created_at`, `updated_at`, `title`, `description`, `content`, `active`, `searchable`) VALUES
	(1, 1, '2022-11-02 15:33:58', '2022-11-02 15:35:10', 'Manual de operacoes', 'Este manual explica os procedimentos basicos de operacao', '<p style="text-align: justify; "><span style="font-size: 18px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Sapien nec sagittis aliquam malesuada bibendum arcu vitae. Quisque egestas diam in arcu cursus euismod quis. Risus nec feugiat in fermentum posuere urna nec tincidunt praesent. At imperdiet dui accumsan sit amet. Est pellentesque elit ullamcorper dignissim cras tincidunt lobortis. Elementum facilisis leo vel fringilla est ullamcorper. Id porta nibh venenatis cras. Viverra orci sagittis eu volutpat odio facilisis mauris sit. Senectus et netus et malesuada fames ac turpis. Sociis natoque penatibus et magnis dis parturient montes. Vel turpis nunc eget lorem dolor sed viverra ipsum nunc. Sed viverra tellus in hac habitasse. Tellus id interdum velit laoreet id donec ultrices tincidunt arcu. Pharetra et ultrices neque ornare aenean euismod elementum. Volutpat blandit aliquam etiam erat velit scelerisque in. Neque aliquam vestibulum morbi blandit cursus risus. Id consectetur purus ut faucibus pulvinar elementum.</span></p><p style="text-align: justify; "><br></p>', 'Y', 'Y'),
	(2, 1, '2022-11-02 15:35:04', '2022-11-02 15:37:49', 'Instrucoes de lancamento', 'Este manual explica as instrucoes de lancamento de produto', '<p><span style="font-size: 18px;">Non curabitur gravida arcu ac tortor dignissim convallis. Nunc scelerisque viverra mauris in aliquam sem fringilla ut morbi. Nunc eget lorem dolor sed viverra. Et odio pellentesque diam volutpat commodo sed egestas. Enim lobortis scelerisque fermentum dui faucibus in ornare quam viverra. Faucibus et molestie ac feugiat. Erat velit scelerisque in dictum non consectetur a erat nam. Quis risus sed vulputate odio ut enim blandit volutpat. Pharetra vel turpis nunc eget lorem dolor sed viverra. Nisl tincidunt eget nullam non nisi est sit. Orci phasellus egestas tellus rutrum tellus pellentesque eu. Et tortor at risus viverra adipiscing at in tellus integer. Risus ultricies tristique nulla aliquet enim. Ac felis donec et odio pellentesque diam volutpat commodo sed. Ut morbi tincidunt augue interdum. Morbi tempus iaculis urna id volutpat.</span></p><p><a href="index.php?class=SystemWikiView&amp;method=onLoad&amp;key=3" generator="adianti">Sub pagina de instrucoes 1</a></p><p><a href="index.php?class=SystemWikiView&amp;method=onLoad&amp;key=4" generator="adianti">Sub pagina de instrucoes 2</a><br><span style="font-size: 18px;"><br></span><br></p>', 'Y', 'Y'),
	(3, 1, '2022-11-02 15:36:59', '2022-11-02 15:37:21', 'Instrucoes - sub pagina 1', 'Instrucoes - sub pagina 1', '<p><span style="font-size: 18px;">Follow these steps:</span></p><ol><li><span style="font-size: 18px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</span></li><li><span style="font-size: 18px;">Sapien nec sagittis aliquam malesuada bibendum arcu vitae.</span></li><li><span style="font-size: 18px;">Quisque egestas diam in arcu cursus euismod quis.</span><br></li></ol>', 'Y', 'N'),
	(4, 1, '2022-11-02 15:37:17', '2022-11-02 15:37:22', 'Instrucoes - sub pagina 2', 'Instrucoes - sub pagina 2', '<p><span style="font-size: 18px;">Follow these steps:</span></p><ol><li><span style="font-size: 18px;">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</span></li><li><span style="font-size: 18px;">Sapien nec sagittis aliquam malesuada bibendum arcu vitae.</span></li><li><span style="font-size: 18px;">Quisque egestas diam in arcu cursus euismod quis.</span></li></ol>', 'Y', 'N');

-- Copiando estrutura para tabela u485635095_admin.system_wiki_share_group
CREATE TABLE IF NOT EXISTS `system_wiki_share_group` (
  `id` int(11) NOT NULL,
  `system_group_id` int(11) DEFAULT NULL,
  `system_wiki_page_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_wiki_share_group_group_idx` (`system_group_id`),
  KEY `sys_wiki_share_group_page_idx` (`system_wiki_page_id`),
  CONSTRAINT `system_wiki_share_group_ibfk_1` FOREIGN KEY (`system_wiki_page_id`) REFERENCES `system_wiki_page` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_wiki_share_group: ~8 rows (aproximadamente)
INSERT INTO `system_wiki_share_group` (`id`, `system_group_id`, `system_wiki_page_id`) VALUES
	(1, 1, 1),
	(2, 2, 1),
	(3, 1, 2),
	(4, 2, 2),
	(5, 1, 3),
	(6, 2, 3),
	(7, 1, 4),
	(8, 2, 4);

-- Copiando estrutura para tabela u485635095_admin.system_wiki_tag
CREATE TABLE IF NOT EXISTS `system_wiki_tag` (
  `id` int(11) NOT NULL,
  `system_wiki_page_id` int(11) NOT NULL,
  `tag` varchar(256) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_wiki_tag_page_idx` (`system_wiki_page_id`),
  CONSTRAINT `system_wiki_tag_ibfk_1` FOREIGN KEY (`system_wiki_page_id`) REFERENCES `system_wiki_page` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela u485635095_admin.system_wiki_tag: ~4 rows (aproximadamente)
INSERT INTO `system_wiki_tag` (`id`, `system_wiki_page_id`, `tag`) VALUES
	(3, 1, 'manual'),
	(5, 4, 'manual'),
	(6, 3, 'manual'),
	(7, 2, 'manual');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

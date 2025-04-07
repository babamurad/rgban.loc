SET NAMES 'utf8';

DROP DATABASE IF EXISTS rgbandb;

CREATE DATABASE rgbandb
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

--
-- Set default database
--
USE rgbandb;

--
-- Create table `matches`
--
CREATE TABLE matches (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  current_player_id int(11) DEFAULT NULL,
  last_action_time timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 4,
AVG_ROW_LENGTH = 5461,
CHARACTER SET utf8mb4,
COLLATE utf8mb4_unicode_ci,
ROW_FORMAT = DYNAMIC;

--
-- Create table `maps`
--
CREATE TABLE maps (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 8,
AVG_ROW_LENGTH = 2340,
CHARACTER SET utf8mb4,
COLLATE utf8mb4_unicode_ci,
ROW_FORMAT = DYNAMIC;

--
-- Create index `name` on table `maps`
--
ALTER TABLE maps
ADD UNIQUE INDEX name (name);

--
-- Create table `bans`
--
CREATE TABLE bans (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  match_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  map_name varchar(255) NOT NULL,
  banned_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 14,
AVG_ROW_LENGTH = 2340,
CHARACTER SET utf8mb4,
COLLATE utf8mb4_unicode_ci,
ROW_FORMAT = DYNAMIC;

-- 
-- Dumping data for table matches
--
INSERT INTO matches VALUES
(1, 2, '2025-04-08 00:32:28'),
(2, 1, '2025-04-08 00:32:50'),
(3, 2, '2025-04-08 00:31:58');

-- 
-- Dumping data for table maps
--
INSERT INTO maps VALUES
(5, 'Ancient'),
(7, 'Anubis'),
(3, 'Dust2'),
(1, 'Inferno'),
(2, 'Mirage'),
(4, 'Nuke'),
(6, 'Vertigo');

-- 
-- Dumping data for table bans
--
INSERT INTO bans VALUES
(6, 2, 2, 'Nuke', '2025-04-08 00:28:49'),
(8, 1, 1, 'Dust2', '2025-04-08 00:32:12'),
(9, 1, 2, 'Mirage', '2025-04-08 00:32:18'),
(10, 1, 1, 'Vertigo', '2025-04-08 00:32:22'),
(11, 1, 2, 'Ancient', '2025-04-08 00:32:25'),
(12, 1, 1, 'Anubis', '2025-04-08 00:32:28'),
(13, 2, 2, 'Ancient', '2025-04-08 00:32:50');

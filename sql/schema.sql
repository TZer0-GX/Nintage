-- Nin-tage schema. Import into an EMPTY database via phpMyAdmin.
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(15) NOT NULL UNIQUE,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('utilisateur','limite','admin') NOT NULL DEFAULT 'utilisateur',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS tags (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NOT NULL UNIQUE,
  slug VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS games (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  platform ENUM('NES','Game Boy','Game Boy Color','Super Nintendo','Nintendo 64','PlayStation 1') NOT NULL,
  description_markdown MEDIUMTEXT NOT NULL,
  rom_path VARCHAR(255) NOT NULL,
  rom_original_name VARCHAR(255) NOT NULL,
  thumbnail_path VARCHAR(255) DEFAULT NULL,
  manual_path VARCHAR(255) DEFAULT NULL,
  manual_url VARCHAR(2048) DEFAULT NULL,
  created_by BIGINT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_games_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_games_platform (platform), INDEX idx_games_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS game_tags (
  game_id BIGINT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (game_id, tag_id),
  CONSTRAINT fk_gt_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  CONSTRAINT fk_gt_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ratings (
  user_id BIGINT UNSIGNED NOT NULL,
  game_id BIGINT UNSIGNED NOT NULL,
  rating DECIMAL(2,1) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, game_id),
  CONSTRAINT fk_ratings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ratings_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  CONSTRAINT chk_rating CHECK (rating >= 0.5 AND rating <= 5.0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS favorites (
  user_id BIGINT UNSIGNED NOT NULL,
  game_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, game_id),
  CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_favorites_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO tags (name, slug) VALUES
('Action','action'),('Aventure','aventure'),('Plateforme','plateforme'),('RPG','rpg'),('Puzzle','puzzle'),('Course','course'),('Combat','combat'),('Sport','sport'),('Shoot-em-up','shoot-em-up'),('Horreur','horreur'),('Difficile','difficile'),('Classique','classique'),('Mauvais','mauvais'),('Sous-coté','sous-cote'),('Très difficile','tres-difficile'),('Facile','facile');

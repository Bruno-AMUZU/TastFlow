-- =============================================================
-- TaskFlow — schéma de la base de données
-- Exécuté automatiquement par MySQL au PREMIER démarrage du
-- conteneur (dossier /docker-entrypoint-initdb.d), puis 002_labels.sql.
-- =============================================================

SET NAMES utf8mb4;

-- Utilisateurs ------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(80)  NOT NULL,
    email         VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,          -- jamais le mot de passe en clair !
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tableaux (un "projet" façon Trello) --------------------------
CREATE TABLE IF NOT EXISTS boards (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    title      VARCHAR(120) NOT NULL,
    color      VARCHAR(20)  NOT NULL DEFAULT 'jaune',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_boards_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Colonnes d'un tableau (À faire, En cours, Terminé…) ----------
-- "columns" est un mot-clé SQL, d'où le nom board_columns.
CREATE TABLE IF NOT EXISTS board_columns (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    board_id INT UNSIGNED NOT NULL,
    title    VARCHAR(60)  NOT NULL,
    color    VARCHAR(20)  NOT NULL DEFAULT 'jaune',
    position INT UNSIGNED NOT NULL DEFAULT 0,
    is_done  TINYINT(1)   NOT NULL DEFAULT 0,      -- 1 = colonne "Terminé"
    CONSTRAINT fk_columns_board FOREIGN KEY (board_id)
        REFERENCES boards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tâches -------------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    column_id   INT UNSIGNED NOT NULL,
    title       VARCHAR(160) NOT NULL,
    description TEXT         NULL,
    tag         VARCHAR(20)  NULL,                 -- design, back, front, docs…
    due_date    DATE         NULL,
    position    INT UNSIGNED NOT NULL DEFAULT 0,
    created_by  INT UNSIGNED NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_column FOREIGN KEY (column_id)
        REFERENCES board_columns(id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_user FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tasks_column_position (column_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Données de démo
-- Compte : demo@taskflow.local / demo1234
-- =============================================================
INSERT INTO users (id, name, email, password_hash) VALUES
(1, 'Bruno Amuzu', 'demo@taskflow.local', '$2y$10$bEjkY8WfYmlclFmz2Ay3jeWjVDWuGeQr4F3xQo55eLGUH3P8liRHC');

INSERT INTO boards (id, user_id, title, color) VALUES
(1, 1, 'Projet site web', 'violet'),
(2, 1, 'Révisions examens', 'vert');

INSERT INTO board_columns (id, board_id, title, color, position, is_done) VALUES
(1, 1, 'À faire',  'jaune',  1, 0),
(2, 1, 'En cours', 'violet', 2, 0),
(3, 1, 'Terminé',  'vert',   3, 1),
(4, 2, 'À faire',  'jaune',  1, 0),
(5, 2, 'En cours', 'violet', 2, 0),
(6, 2, 'Terminé',  'vert',   3, 1);

INSERT INTO tasks (column_id, title, description, tag, due_date, position, created_by) VALUES
(1, 'Maquetter la page d''accueil', 'Reprendre le design « Ludique ».', 'design', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 1, 1),
(1, 'Créer la table tasks en MySQL', NULL, 'back', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 2, 1),
(2, 'Formulaire de connexion en PHP', 'password_hash + sessions.', 'back', CURDATE(), 1, 1),
(2, 'Rédiger le README', NULL, 'docs', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 2, 1),
(3, 'Installer Docker + MySQL', NULL, 'autre', NULL, 1, 1),
(3, 'Définir le schéma de la base', NULL, 'back', NULL, 2, 1),
(4, 'Fiches SQL : les jointures', NULL, 'docs', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 1, 1);

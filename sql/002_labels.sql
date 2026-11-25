-- =============================================================
-- TaskFlow — migration 002 : étiquettes multiples
--
-- Avant : une seule étiquette par tâche (colonne tasks.tag, liste figée).
-- Après : chaque tableau a ses propres étiquettes (table labels),
--         et une tâche peut en avoir plusieurs (table task_labels).
--
-- Installation neuve : exécuté automatiquement après 001_init.sql.
-- Base existante : à exécuter UNE fois (voir README, « Mettre à jour la base »).
-- =============================================================

SET NAMES utf8mb4;

-- Les étiquettes d'un tableau ----------------------------------
CREATE TABLE IF NOT EXISTS labels (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    board_id INT UNSIGNED NOT NULL,
    name     VARCHAR(30)  NOT NULL,
    color    VARCHAR(20)  NOT NULL DEFAULT 'gris',
    CONSTRAINT fk_labels_board FOREIGN KEY (board_id)
        REFERENCES boards(id) ON DELETE CASCADE,
    UNIQUE KEY uq_labels_board_name (board_id, name)   -- pas deux fois "Design" dans un tableau
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de liaison "plusieurs à plusieurs" ---------------------
-- Une tâche a plusieurs étiquettes, une étiquette est sur plusieurs tâches.
CREATE TABLE IF NOT EXISTS task_labels (
    task_id  INT UNSIGNED NOT NULL,
    label_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (task_id, label_id),
    CONSTRAINT fk_task_labels_task FOREIGN KEY (task_id)
        REFERENCES tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_task_labels_label FOREIGN KEY (label_id)
        REFERENCES labels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pas d'étiquettes imposées : chaque utilisateur crée les siennes.
-- On transforme seulement les anciennes étiquettes RÉELLEMENT utilisées
-- par des tâches, pour ne rien perdre.
INSERT IGNORE INTO labels (board_id, name, color)
SELECT DISTINCT c.board_id,
       CASE t.tag
           WHEN 'design' THEN 'Design'
           WHEN 'front'  THEN 'Front-end'
           WHEN 'back'   THEN 'Back-end'
           WHEN 'docs'   THEN 'Docs'
       END,
       CASE t.tag
           WHEN 'design' THEN 'orange'
           WHEN 'front'  THEN 'vert'
           WHEN 'back'   THEN 'bleu'
           WHEN 'docs'   THEN 'violet'
       END
FROM tasks t
JOIN board_columns c ON c.id = t.column_id
WHERE t.tag IN ('design', 'front', 'back', 'docs');

-- On repose l'ancienne étiquette unique sur chaque tâche -------
INSERT IGNORE INTO task_labels (task_id, label_id)
SELECT t.id, l.id
FROM tasks t
JOIN board_columns c ON c.id = t.column_id
JOIN labels l        ON l.board_id = c.board_id
                    AND l.name = CASE t.tag
                                     WHEN 'design' THEN 'Design'
                                     WHEN 'front'  THEN 'Front-end'
                                     WHEN 'back'   THEN 'Back-end'
                                     WHEN 'docs'   THEN 'Docs'
                                 END;

-- L'ancienne colonne ne sert plus
ALTER TABLE tasks DROP COLUMN tag;

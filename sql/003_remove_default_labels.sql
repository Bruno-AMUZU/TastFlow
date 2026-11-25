-- =============================================================
-- TaskFlow — migration 003 : retrait des étiquettes prédéfinies
--
-- La première version de 002_labels.sql créait d'office Design,
-- Front-end, Back-end et Docs dans chaque tableau. On ne veut plus
-- d'étiquettes imposées : on supprime celles qui ne sont posées
-- sur AUCUNE tâche. Celles qui servent sont conservées.
--
-- Sans effet sur une installation neuve (rien à supprimer).
-- =============================================================

DELETE l
FROM labels l
LEFT JOIN task_labels tl ON tl.label_id = l.id
WHERE tl.label_id IS NULL
  AND l.name IN ('Design', 'Front-end', 'Back-end', 'Docs');

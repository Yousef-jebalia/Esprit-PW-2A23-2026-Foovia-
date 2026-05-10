ALTER TABLE `work_categorie`
  MODIFY `name_cat` VARCHAR(120) NOT NULL;

ALTER TABLE `workout`
  MODIFY `id_work` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

UPDATE `work_categorie`
SET `name_cat` = 'Custom by AI'
WHERE `name_cat` = '0' OR TRIM(`name_cat`) = '';

INSERT INTO `work_categorie` (`name_cat`)
SELECT 'Custom by AI'
WHERE NOT EXISTS (
  SELECT 1
  FROM `work_categorie`
  WHERE LOWER(TRIM(`name_cat`)) = 'custom by ai'
);

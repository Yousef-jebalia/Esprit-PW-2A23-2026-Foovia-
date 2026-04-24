-- Add per-user login usage counter for statistics
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user'
      AND COLUMN_NAME = 'login_count_user'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE user ADD COLUMN login_count_user INT NOT NULL DEFAULT 0 AFTER duration_user',
    'SELECT "login_count_user already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

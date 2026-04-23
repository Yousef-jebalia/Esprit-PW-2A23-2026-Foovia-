<?php
require 'model/config.php';
$db = config::getConnexion();
$db->exec('ALTER TABLE user ADD COLUMN reset_token VARCHAR(64) NULL, ADD COLUMN reset_token_expires_at DATETIME NULL');
echo "Columns added";

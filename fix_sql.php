<?php
$sql = file_get_contents('filtered_maha.sql');
// Remove INSERT statements for comp table which might have duplicate keys
// We'll just let it create the tables and structure
$sql = preg_replace('/INSERT IGNORE INTO `comp`.*?;/is', '', $sql);
$sql = preg_replace('/INSERT INTO `comp`.*?;/is', '', $sql);
file_put_contents('filtered_maha_no_data.sql', $sql);

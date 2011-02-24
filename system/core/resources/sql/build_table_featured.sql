CREATE TABLE IF NOT EXISTS `<?php echo DB_NAME; ?>`.`<?php echo DB_PREFIX; ?>featured`
(
    `featured_id` INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
    `entry_id`    INT UNSIGNED NOT NULL
) ENGINE=MYISAM CHARACTER SET <?php echo DEFAULT_CHARACTER_SET; ?>
    COLLATE <?php echo DEFAULT_COLLATION; ?>;

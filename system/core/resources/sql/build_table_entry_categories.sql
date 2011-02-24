CREATE TABLE IF NOT EXISTS `<?php echo DB_NAME; ?>`.`<?php echo DB_PREFIX; ?>entry_categories`
(
    `category_id` INT UNSIGNED NOT NULL PRIMARY KEY,
    `entry_id`    INT UNSIGNED NOT NULL
) ENGINE=MYISAM CHARACTER SET <?php echo DEFAULT_CHARACTER_SET; ?>
    COLLATE <?php echo DEFAULT_COLLATION; ?>;

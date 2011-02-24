CREATE TABLE IF NOT EXISTS `<?php echo DB_NAME; ?>`.`<?php echo DB_PREFIX; ?>categories`
(
    `category_id`   INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
    `category_name` VARCHAR( 128 ) UNIQUE NOT NULL,
    `category_slug` VARCHAR( 128 ) UNIQUE NOT NULL
) ENGINE=MYISAM CHARACTER SET <?php echo DEFAULT_CHARACTER_SET; ?>
    COLLATE <?php echo DEFAULT_COLLATION; ?>;

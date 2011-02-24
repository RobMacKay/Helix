CREATE TABLE IF NOT EXISTS `<?php echo DB_NAME; ?>`.`<?php echo DB_PREFIX; ?>users`
(
    `user_id`   INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
    `email`     VARCHAR( 128 ) UNIQUE NOT NULL,
    `username`  VARCHAR( 20 ) UNIQUE DEFAULT NULL,
    `display`   VARCHAR( 32 ) DEFAULT NULL,
    `vcode`     VARCHAR( 40 ) NOT NULL,
    `password`  VARCHAR( 54 ) DEFAULT NULL,
    `clearance` TINYINT DEFAULT 1,
    `active`    TINYINT DEFAULT 0,
    INDEX(`password`),
    INDEX(`clearance`)
) ENGINE=MYISAM CHARACTER SET <?php echo DEFAULT_CHARACTER_SET; ?>
    COLLATE <?php echo DEFAULT_COLLATION; ?>;

CREATE TABLE IF NOT EXISTS `<?php echo DB_NAME; ?>`.`<?php echo DB_PREFIX; ?>entries`
(
    `entry_id` INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
    `page_id`  INT UNSIGNED NOT NULL,
    `title`    VARCHAR( 128 ) DEFAULT NULL,
    `entry`    TEXT DEFAULT NULL,
    `excerpt`  TEXT DEFAULT NULL,
    `slug`     VARCHAR( 128 ) UNIQUE NOT NULL,
    `tags`     VARCHAR( 256 ) DEFAULT NULL,
    `order`    INT( 3 ) DEFAULT 1,
    `extra`    TEXT DEFAULT NULL,
    `author`   VARCHAR( 64 ) DEFAULT '<?php echo SITE_CONTACT_NAME; ?>',
    `created`  INT( 12 ),
    INDEX(`page_id`),
    INDEX(`created`),
    INDEX(`title`),
    FULLTEXT(`title`,`entry`,`excerpt`,`tags`)
) ENGINE=MYISAM CHARACTER SET <?php echo DEFAULT_CHARACTER_SET; ?>
    COLLATE <?php echo DEFAULT_COLLATION; ?>;

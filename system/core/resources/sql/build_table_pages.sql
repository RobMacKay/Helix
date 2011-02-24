CREATE TABLE IF NOT EXISTS `<?php echo DB_NAME; ?>`.`<?php echo DB_PREFIX; ?>pages`
(
    `page_id`      INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
    `page_name`    VARCHAR(64) NOT NULL,
    `page_slug`    VARCHAR(64) UNIQUE NOT NULL,
    `type`         VARCHAR(64) NOT NULL,
    `menu_order`   INT UNSIGNED NOT NULL DEFAULT 0,
    `show_full`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `hide_in_menu` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `is_default`   TINYINT(1) DEFAULT 0,
    `parent_id`    VARCHAR(128) DEFAULT 0,
    `extra`        TEXT DEFAULT NULL,
    INDEX( `show_full` ),
    INDEX( `menu_order` ),
    INDEX( `parent_id` )
) ENGINE=MYISAM CHARACTER SET <?php echo DEFAULT_CHARACTER_SET; ?>
    COLLATE <?php echo DEFAULT_COLLATION; ?>;

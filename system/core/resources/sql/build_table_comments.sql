CREATE TABLE IF NOT EXISTS `<?php echo DB_NAME; ?>`.`<?php echo DB_PREFIX; ?>comments`
(
    `comment_id`        INT( 10 ) UNSIGNED NOT NULL PRIMARY KEY auto_increment,
    `entry_id`          INT( 10 ) UNSIGNED NOT NULL,
    `name`              VARCHAR( 64 ) NOT NULL,
    `email`             VARCHAR( 128 ) NOT NULL,
    `url`               VARCHAR( 128 ) NOT NULL,
    `remote_address`    VARCHAR( 15 ) NOT NULL,
    `comment`           TEXT NOT NULL,
    `flagged`           TINYINT( 1 ) UNSIGNED DEFAULT 0,
    `subscribed`        TINYINT( 1 ) UNSIGNED DEFAULT 0,
    `thread_id`         INT( 10 ) UNSIGNED DEFAULT 0,
    `created`           INT( 12 ),
    INDEX( `entry_id` ),
    INDEX( `created` ),
    INDEX( `remote_address` ),
    INDEX( `flagged` )
) ENGINE=MYISAM CHARACTER SET <?php echo DEFAULT_CHARACTER_SET; ?>
    COLLATE <?php echo DEFAULT_COLLATION; ?>;

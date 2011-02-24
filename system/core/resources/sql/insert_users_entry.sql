INSERT INTO `<?php echo DB_NAME; ?>`.`<?php echo DB_PREFIX; ?>users`
(
    `email`, `username`, `display`, `vcode`, `password`, `clearance`
)
VALUES
(
    '<?php echo $email; ?>',
    '<?php echo $username; ?>',
    '<?php echo $display; ?>',
    '<?php echo $vcode; ?>',
    '<?php echo $password; ?>',
    <?php echo $clearance; ?>
)
ON DUPLICATE KEY UPDATE `clearance`=<?php echo $clearance; ?>;
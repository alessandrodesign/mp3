CREATE TABLE `stream_keys`
(
    `id`         INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user`       VARCHAR(255)        NULL     DEFAULT NULL,
    `stream_key` VARCHAR(255)        NOT NULL,
    `active`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
    `created_at` TIMESTAMP           NULL     DEFAULT current_timestamp(),
    `updated_at` TIMESTAMP           NULL     DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `stream_key` (`stream_key`) USING BTREE
);

CREATE TABLE `stream_logs`
(
    `id`          INT(11) UNSIGNED      NOT NULL AUTO_INCREMENT,
    `stream_key`  VARCHAR(255)          NULL DEFAULT NULL,
    `action_name` ENUM ('start','stop') NULL DEFAULT NULL,
    `source_ip`   VARCHAR(45)           NULL DEFAULT NULL,
    `created_at`  TIMESTAMP             NULL DEFAULT current_timestamp(),
    `updated_at`  TIMESTAMP             NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`) USING BTREE
);

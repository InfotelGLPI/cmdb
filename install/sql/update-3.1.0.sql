DROP TABLE IF EXISTS `glpi_plugin_cmdb_impacticons`;
CREATE TABLE `glpi_plugin_cmdb_impacticons`
(
    `id`        int unsigned NOT NULL auto_increment,
    `itemtype`  varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `criteria`  varchar(255) COLLATE utf8mb4_unicode_ci,
    `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE(`itemtype`, `criteria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

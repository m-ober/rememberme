CREATE TABLE IF NOT EXISTS `tokens` (
  `credential` varchar(255) NOT NULL DEFAULT '',
  `token` varchar(255) NOT NULL DEFAULT '',
  `persistent_token` varchar(255) NOT NULL DEFAULT '',
  `expires` datetime NOT NULL,
  KEY `credential` (`credential`,`persistent_token`,`expires`)
);

CREATE TABLE IF NOT EXISTS `#__seers_cookie_consent` (
  `id` int(11)  NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NULL,
  `email` varchar(255) NULL,
  `apikey` varchar(255) NULL,
  `hitcount` int(11) NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
--
-- Install Script for SHPS 3.0 U2 [MYSQL]
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_browser`
--

CREATE TABLE IF NOT EXISTS `HP_browser` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `company` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_browserAddon`
--

CREATE TABLE IF NOT EXISTS `HP_browserAddon` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_browserEngine`
--

CREATE TABLE IF NOT EXISTS `HP_browserEngine` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_browserInfoCache`
--

CREATE TABLE IF NOT EXISTS `HP_browserInfoCache` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `btoken` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` int(11) NOT NULL,
  `browser` int(11) NOT NULL,
  `browserVersion` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `browserEngine` int(11) NOT NULL,
  `engineVersion` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `browserAddon` int(11) NOT NULL,
  `os` int(11) NOT NULL,
  `osBit` int(11) NOT NULL,
  `isMozilla` tinyint(1) NOT NULL,
  `mozillaVersion` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `safariVersion` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `likeGekko` tinyint(1) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `btoken` (`btoken`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_content`
--

CREATE TABLE IF NOT EXISTS `HP_content` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `namespace` int(11) DEFAULT '0',
  `eval` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE `keys` (`name`,`namespace`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_cs`
--

CREATE TABLE IF NOT EXISTS `HP_cs`
(
   `ID`          int(11) NOT NULL AUTO_INCREMENT,
   `name`        varchar(30) NOT NULL,
   `content`     text NOT NULL,
   `evaluate`    tinyint(1) NOT NULL DEFAULT '0',
   `namespace`   int(11) DEFAULT '0',
   PRIMARY KEY  ( `ID` ),
   UNIQUE `keys` ( `name`,`namespace` )
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = 'CoffeeScript';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_css`
--

CREATE TABLE IF NOT EXISTS `HP_css` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `namespace` int(11) DEFAULT '0',
  `evaluate` tinyint(1) NOT NULL DEFAULT '0',
  `mediaquery` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_fileType`
--

CREATE TABLE IF NOT EXISTS `HP_fileType` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_group`
--

CREATE TABLE IF NOT EXISTS `HP_group` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_group_user`
--

CREATE TABLE IF NOT EXISTS `HP_group_user` (
  `uid` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  `role` tinyint(1) NOT NULL COMMENT '0: leader, 1: user',
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_include`
--

CREATE TABLE IF NOT EXISTS `HP_include` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `type` int(11) NOT NULL,
  `namespace` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE `keys` ( `file`,`namespace` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_js`
--

CREATE TABLE IF NOT EXISTS `HP_js` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `evaluate` tinyint(1) NOT NULL DEFAULT '0',
  `namespace` int(11) DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_key`
--

CREATE TABLE IF NOT EXISTS `HP_key` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `accesskey` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_language`
--

CREATE TABLE IF NOT EXISTS `HP_language` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(2) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_lang_group`
--

CREATE TABLE IF NOT EXISTS `HP_lang_group` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_log`
--

CREATE TABLE IF NOT EXISTS `HP_log` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(11) NOT NULL,
  `entry` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_loginQuery`
--

CREATE TABLE IF NOT EXISTS `HP_loginQuery` (
  `uid` int(11) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_mediaquery`
--

CREATE TABLE IF NOT EXISTS `HP_mediaquery` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `query` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_memory`
--

CREATE TABLE IF NOT EXISTS `HP_memory` (
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_namespace`
--

CREATE TABLE IF NOT EXISTS `HP_namespace` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO `HP_namespace` (`ID`, `name`) VALUES (0, 'default');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_operatingSystem`
--

CREATE TABLE IF NOT EXISTS `HP_operatingSystem` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `codeName` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `uaRegex` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_php`
--

CREATE TABLE IF NOT EXISTS `HP_php` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `head` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'function head',
  `content` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_plugin`
--

CREATE TABLE IF NOT EXISTS `HP_plugin` (
  `GUID` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL PRIMARY KEY,
  `namespace` int(11) NOT NULL DEFAULT '0',
  `plugin` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:unins, 2:inactive, 3:active',
  `order` int(11) NOT NULL DEFAULT '1',
  UNIQUE KEY `keys` (`namespace`,`plugin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_request`
--

CREATE TABLE IF NOT EXISTS `HP_request` (
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `script` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `accessKey` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `namespace` int(11) DEFAULT '0',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_security`
--

CREATE TABLE IF NOT EXISTS `HP_security` (
  `uid` int(11) NOT NULL COMMENT 'user ID or group ID',
  `key` int(11) NOT NULL,
  `from` int(11) NOT NULL,
  `to` int(11) NOT NULL,
  `authorizer` int(11) NOT NULL COMMENT 'uid',
  `isgroup` tinyint(1) NOT NULL DEFAULT '0',
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_setting`
--

CREATE TABLE IF NOT EXISTS `HP_setting` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `setting` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_string`
--

CREATE TABLE IF NOT EXISTS `HP_string` (
  `langID` int(11) DEFAULT '0',
  `namespace` int(11) NOT NULL,
  `group` int(11) NOT NULL,
  `key` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE `keys` (`langID`,`namespace`,`group`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_template`
--

CREATE TABLE IF NOT EXISTS `HP_template` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `evaluate` tinyint(1) NOT NULL DEFAULT '0',
  `content` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `namespace` int(11) DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_upload`
--

CREATE TABLE IF NOT EXISTS `HP_upload` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `filename` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `upload_time` int(11) NOT NULL,
  `mimetype` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'image/png',
  `cache` tinyint(1) NOT NULL DEFAULT '1',
  `file` longblob DEFAULT NULL,
  `ttc` int(11) NOT NULL DEFAULT '604800' COMMENT 'time to cache',
  PRIMARY KEY (`ID`),
  UNIQUE `keys` ( `name` )
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_user`
--

CREATE TABLE IF NOT EXISTS `HP_user` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `pass` varchar(129) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `host` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `regdate` int(11) NOT NULL,
  `token` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `lastActive` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `btoken` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `xforward` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `uaInfo` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

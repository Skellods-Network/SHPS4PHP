-- phpMyAdmin SQL Dump
-- version 4.3.8
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 02. Mrz 2015 um 15:52
-- Server-Version: 10.0.16-MariaDB-1~wheezy-log
-- PHP-Version: 5.6.6-1~dotdeb.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `SHPS_INSTALL_base`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_accessKey`
--

CREATE TABLE IF NOT EXISTS `HP_accessKey` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1 TRANSACTIONAL=1;

--
-- Daten für Tabelle `HP_accessKey`
--

INSERT INTO `HP_accessKey` (`ID`, `name`, `description`) VALUES
(0, 'SYS_NULL', 'Every user/guest has this access key. Always.');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_browser`
--

CREATE TABLE IF NOT EXISTS `HP_browser` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_browserAddon`
--

CREATE TABLE IF NOT EXISTS `HP_browserAddon` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_browserEngine`
--

CREATE TABLE IF NOT EXISTS `HP_browserEngine` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_browserInfoCache`
--

CREATE TABLE IF NOT EXISTS `HP_browserInfoCache` (
  `ID` int(11) unsigned NOT NULL,
  `btoken` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` int(11) unsigned NOT NULL,
  `browser` int(11) unsigned NOT NULL,
  `browserVersion` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `browserEngine` int(11) unsigned NOT NULL,
  `engineVersion` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `browserAddon` int(11) unsigned NOT NULL,
  `os` int(11) unsigned NOT NULL,
  `osBit` int(11) unsigned NOT NULL,
  `isMozilla` tinyint(1) unsigned NOT NULL,
  `mozillaVersion` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `safariVersion` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `likeGekko` tinyint(1) unsigned NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_browserScript`
--

CREATE TABLE IF NOT EXISTS `HP_browserScript` (
  `ID` int(11) unsigned NOT NULL,
  `scriptLang` int(11) unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `eval` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `namespace` int(11) unsigned NOT NULL DEFAULT '0'
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_browserScriptLanguage`
--

CREATE TABLE IF NOT EXISTS `HP_browserScriptLanguage` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fileType` int(11) unsigned NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_content`
--

CREATE TABLE IF NOT EXISTS `HP_content` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `namespace` int(11) unsigned NOT NULL DEFAULT '0',
  `eval` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `accessKey` int(11) unsigned NOT NULL DEFAULT '0',
  `secure` tinyint(1) DEFAULT '1'
) ENGINE=Aria AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

--
-- Daten für Tabelle `HP_content`
--

INSERT INTO `HP_content` (`ID`, `name`, `content`, `namespace`, `eval`, `accessKey`, `secure`) VALUES
(1, 'index', 'return ''\r\n<p>\r\n  <strong>This is a demonstrational test content.</strong><br>\r\n  If you can see this message, SHPS should be operational :)\r\n</p>\r\n'';', 0, 1, 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_css`
--

CREATE TABLE IF NOT EXISTS `HP_css` (
  `ID` int(11) unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `namespace` int(11) unsigned NOT NULL DEFAULT '0',
  `eval` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `mediaQuery` int(11) unsigned NOT NULL DEFAULT '0'
) ENGINE=Aria AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

--
-- Daten für Tabelle `HP_css`
--

INSERT INTO `HP_css` (`ID`, `name`, `content`, `namespace`, `eval`, `mediaQuery`) VALUES
(1, 'html, body', 'min-width: 100%;\r\nmin-height: 100%;', 0, 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_fileType`
--

CREATE TABLE IF NOT EXISTS `HP_fileType` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mimeType` int(10) unsigned NOT NULL
) ENGINE=Aria AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

--
-- Daten für Tabelle `HP_fileType`
--

INSERT INTO `HP_fileType` (`ID`, `name`, `mimeType`) VALUES
(1, 'css', 0),
(2, 'js', 0),
(3, 'coffee', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_group`
--

CREATE TABLE IF NOT EXISTS `HP_group` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_groupSecurity`
--

CREATE TABLE IF NOT EXISTS `HP_groupSecurity` (
  `gid` int(11) unsigned NOT NULL COMMENT 'group ID',
  `key` int(11) unsigned NOT NULL,
  `from` int(10) unsigned NOT NULL DEFAULT '0',
  `to` int(10) unsigned NOT NULL DEFAULT '4294967295',
  `authorizer` int(11) unsigned NOT NULL COMMENT 'uid'
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_groupUser`
--

CREATE TABLE IF NOT EXISTS `HP_groupUser` (
  `uid` int(11) unsigned NOT NULL,
  `gid` int(11) unsigned NOT NULL,
  `from` int(10) unsigned NOT NULL DEFAULT '0',
  `to` int(10) unsigned NOT NULL DEFAULT '4294967295'
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_include`
--

CREATE TABLE IF NOT EXISTS `HP_include` (
  `ID` int(11) unsigned NOT NULL,
  `file` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fileType` int(11) unsigned NOT NULL,
  `namespace` int(11) unsigned NOT NULL DEFAULT '0',
  `accessKey` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_language`
--

CREATE TABLE IF NOT EXISTS `HP_language` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1 TRANSACTIONAL=1;

--
-- Daten für Tabelle `HP_language`
--

INSERT INTO `HP_language` (`ID`, `name`) VALUES
(1, 'en');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_log`
--

CREATE TABLE IF NOT EXISTS `HP_log` (
  `ID` int(11) unsigned NOT NULL,
  `time` int(11) unsigned NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_loginQuery`
--

CREATE TABLE IF NOT EXISTS `HP_loginQuery` (
  `uid` int(11) unsigned NOT NULL,
  `time` int(11) unsigned NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_logLevel`
--

CREATE TABLE IF NOT EXISTS `HP_logLevel` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int(11) unsigned NOT NULL
) ENGINE=Aria AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

--
-- Daten für Tabelle `HP_logLevel`
--

INSERT INTO `HP_logLevel` (`ID`, `name`, `level`) VALUES
(1, 'LOG_ALL', 0),
(3, 'LOG_NOTICE', 100),
(4, 'LOG_WARN', 200),
(5, 'LOG_ERROR', 300),
(6, 'LOG_FATAL', 400),
(2, 'LOG_TRACE', 10);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_mediaquery`
--

CREATE TABLE IF NOT EXISTS `HP_mediaquery` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `query` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_memory`
--

CREATE TABLE IF NOT EXISTS `HP_memory` (
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_mimeType`
--

CREATE TABLE IF NOT EXISTS `HP_mimeType` (
  `ID` int(10) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_namespace`
--

CREATE TABLE IF NOT EXISTS `HP_namespace` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

--
-- Daten für Tabelle `HP_namespace`
--

INSERT INTO `HP_namespace` (`ID`, `name`) VALUES
(0, 'default');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_oldPassword`
--

CREATE TABLE IF NOT EXISTS `HP_oldPassword` (
  `ID` int(10) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `timestamp` int(10) unsigned NOT NULL,
  `salt` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pass` varchar(129) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_operatingSystem`
--

CREATE TABLE IF NOT EXISTS `HP_operatingSystem` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codeName` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uaRegex` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_php`
--

CREATE TABLE IF NOT EXISTS `HP_php` (
  `ID` int(11) unsigned NOT NULL,
  `head` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'function head',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_plugin`
--

CREATE TABLE IF NOT EXISTS `HP_plugin` (
  `GUID` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plugin` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `namespace` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1:unins, 2:inactive, 3:active',
  `accessKey` int(10) unsigned NOT NULL DEFAULT '0',
  `order` int(11) unsigned NOT NULL DEFAULT '1'
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_request`
--

CREATE TABLE IF NOT EXISTS `HP_request` (
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `accessKey` int(10) unsigned NOT NULL DEFAULT '0',
  `namespace` int(11) unsigned NOT NULL DEFAULT '0',
  `secure` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_session`
--

CREATE TABLE IF NOT EXISTS `HP_session` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(45) NOT NULL,
  `timeStamp` int(11) unsigned NOT NULL
) ENGINE=Aria DEFAULT CHARSET=ascii PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_sessionContent`
--

CREATE TABLE IF NOT EXISTS `HP_sessionContent` (
  `sid` int(11) unsigned NOT NULL COMMENT 'Session ID',
  `key` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_setting`
--

CREATE TABLE IF NOT EXISTS `HP_setting` (
  `ID` int(11) unsigned NOT NULL,
  `setting` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_string`
--

CREATE TABLE IF NOT EXISTS `HP_string` (
  `ID` int(11) unsigned NOT NULL,
  `langID` int(11) unsigned NOT NULL DEFAULT '0',
  `namespace` int(11) unsigned NOT NULL,
  `group` int(11) unsigned NOT NULL,
  `key` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_stringGroup`
--

CREATE TABLE IF NOT EXISTS `HP_stringGroup` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_template`
--

CREATE TABLE IF NOT EXISTS `HP_template` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `eval` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `namespace` int(11) unsigned NOT NULL DEFAULT '0',
  `accessKey` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=Aria AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

--
-- Daten für Tabelle `HP_template`
--

INSERT INTO `HP_template` (`ID`, `name`, `eval`, `content`, `namespace`, `accessKey`) VALUES
(1, 'site', 0, '<!DOCTYPE HTML>\r\n<html>\r\n\r\n{$default:head} <!-- This variable is part of the namespace `default` and contains the result of the template called `head` in said namespace -->\r\n\r\n<body>\r\n\r\n  <!--[if lt IE 9]><p class=chromeframe>Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Please upgrade to a more current browser</a> to fully experience this site.</p><![endif]-->\r\n\r\n  <h1>Welcome to SHPS!</h1>\r\n\r\n  {$body} <!-- This variable contains the dynamic content -->\r\n\r\n</body>\r\n</html>', 0, 0),
(2, 'head', 1, 'return ''\r\n<head>\r\n  <meta charset="UTF-8">\r\n  <meta http-equiv="X-UA-Compatible" content="IE=edge">\r\n  <title>SHPS</title>\r\n  <meta name="description" content="Welcome to SHPS">\r\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\r\n  <link rel="canonical" href="'' . SHPS_main::getHPConfig(''generalConfig'',''URL'') . ''">\r\n\r\n  <!--[if gte IE 9]><style type="text/css">.gradient{filter: none;}</style><![endif]-->\r\n</head>\r\n'';', 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_upload`
--

CREATE TABLE IF NOT EXISTS `HP_upload` (
  `ID` int(11) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fileName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploadTime` int(11) unsigned NOT NULL,
  `mimeType` int(10) unsigned NOT NULL,
  `cache` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `ttc` int(11) unsigned NOT NULL DEFAULT '604800' COMMENT 'time to cache',
  `accessKey` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_user`
--

CREATE TABLE IF NOT EXISTS `HP_user` (
  `ID` int(11) unsigned NOT NULL,
  `user` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pass` varchar(129) COLLATE utf8mb4_unicode_ci NOT NULL,
  `salt` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `host` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regDate` int(11) unsigned NOT NULL,
  `token` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastActive` int(11) unsigned NOT NULL,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `bToken` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `xForward` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uaInfo` int(11) unsigned NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `HP_userSecurity`
--

CREATE TABLE IF NOT EXISTS `HP_userSecurity` (
  `uid` int(11) unsigned NOT NULL COMMENT 'user ID or group ID',
  `key` int(11) unsigned NOT NULL,
  `from` int(11) unsigned NOT NULL,
  `to` int(11) unsigned NOT NULL,
  `authorizer` int(11) unsigned NOT NULL COMMENT 'uid'
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=0 TRANSACTIONAL=1;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `HP_accessKey`
--
ALTER TABLE `HP_accessKey`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_browser`
--
ALTER TABLE `HP_browser`
  ADD PRIMARY KEY (`ID`), ADD KEY `name` (`name`);

--
-- Indizes für die Tabelle `HP_browserAddon`
--
ALTER TABLE `HP_browserAddon`
  ADD PRIMARY KEY (`ID`), ADD KEY `name` (`name`);

--
-- Indizes für die Tabelle `HP_browserEngine`
--
ALTER TABLE `HP_browserEngine`
  ADD PRIMARY KEY (`ID`), ADD KEY `name` (`name`);

--
-- Indizes für die Tabelle `HP_browserInfoCache`
--
ALTER TABLE `HP_browserInfoCache`
  ADD PRIMARY KEY (`ID`), ADD KEY `btoken` (`btoken`);

--
-- Indizes für die Tabelle `HP_browserScript`
--
ALTER TABLE `HP_browserScript`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_browserScriptLanguage`
--
ALTER TABLE `HP_browserScriptLanguage`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_content`
--
ALTER TABLE `HP_content`
  ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `keys` (`name`,`namespace`);

--
-- Indizes für die Tabelle `HP_css`
--
ALTER TABLE `HP_css`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_fileType`
--
ALTER TABLE `HP_fileType`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_group`
--
ALTER TABLE `HP_group`
  ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `name` (`name`);

--
-- Indizes für die Tabelle `HP_groupSecurity`
--
ALTER TABLE `HP_groupSecurity`
  ADD UNIQUE KEY `UNIQUE` (`gid`,`key`,`from`,`to`), ADD KEY `uid` (`gid`);

--
-- Indizes für die Tabelle `HP_groupUser`
--
ALTER TABLE `HP_groupUser`
  ADD KEY `uid` (`uid`);

--
-- Indizes für die Tabelle `HP_include`
--
ALTER TABLE `HP_include`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_language`
--
ALTER TABLE `HP_language`
  ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `name` (`name`);

--
-- Indizes für die Tabelle `HP_log`
--
ALTER TABLE `HP_log`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_logLevel`
--
ALTER TABLE `HP_logLevel`
  ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `level` (`level`);

--
-- Indizes für die Tabelle `HP_mediaquery`
--
ALTER TABLE `HP_mediaquery`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_memory`
--
ALTER TABLE `HP_memory`
  ADD PRIMARY KEY (`key`);

--
-- Indizes für die Tabelle `HP_mimeType`
--
ALTER TABLE `HP_mimeType`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_namespace`
--
ALTER TABLE `HP_namespace`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_oldPassword`
--
ALTER TABLE `HP_oldPassword`
  ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `UNIQUE` (`uid`,`timestamp`);

--
-- Indizes für die Tabelle `HP_operatingSystem`
--
ALTER TABLE `HP_operatingSystem`
  ADD PRIMARY KEY (`ID`), ADD KEY `name` (`name`);

--
-- Indizes für die Tabelle `HP_php`
--
ALTER TABLE `HP_php`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_plugin`
--
ALTER TABLE `HP_plugin`
  ADD PRIMARY KEY (`GUID`), ADD UNIQUE KEY `keys` (`namespace`,`plugin`);

--
-- Indizes für die Tabelle `HP_request`
--
ALTER TABLE `HP_request`
  ADD PRIMARY KEY (`name`);

--
-- Indizes für die Tabelle `HP_session`
--
ALTER TABLE `HP_session`
  ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `name` (`name`);

--
-- Indizes für die Tabelle `HP_sessionContent`
--
ALTER TABLE `HP_sessionContent`
  ADD UNIQUE KEY `UNIQUE` (`sid`,`key`);

--
-- Indizes für die Tabelle `HP_setting`
--
ALTER TABLE `HP_setting`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_string`
--
ALTER TABLE `HP_string`
  ADD PRIMARY KEY (`ID`), ADD KEY `keys` (`langID`,`namespace`,`group`,`key`);

--
-- Indizes für die Tabelle `HP_stringGroup`
--
ALTER TABLE `HP_stringGroup`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_template`
--
ALTER TABLE `HP_template`
  ADD PRIMARY KEY (`ID`), ADD KEY `name` (`name`);

--
-- Indizes für die Tabelle `HP_upload`
--
ALTER TABLE `HP_upload`
  ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `keys` (`name`);

--
-- Indizes für die Tabelle `HP_user`
--
ALTER TABLE `HP_user`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `HP_userSecurity`
--
ALTER TABLE `HP_userSecurity`
  ADD UNIQUE KEY `UNIQUE` (`uid`,`key`,`from`,`to`), ADD KEY `uid` (`uid`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `HP_accessKey`
--
ALTER TABLE `HP_accessKey`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_browser`
--
ALTER TABLE `HP_browser`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_browserAddon`
--
ALTER TABLE `HP_browserAddon`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_browserEngine`
--
ALTER TABLE `HP_browserEngine`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_browserInfoCache`
--
ALTER TABLE `HP_browserInfoCache`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_browserScript`
--
ALTER TABLE `HP_browserScript`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_browserScriptLanguage`
--
ALTER TABLE `HP_browserScriptLanguage`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_content`
--
ALTER TABLE `HP_content`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT für Tabelle `HP_css`
--
ALTER TABLE `HP_css`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT für Tabelle `HP_fileType`
--
ALTER TABLE `HP_fileType`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT für Tabelle `HP_group`
--
ALTER TABLE `HP_group`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_include`
--
ALTER TABLE `HP_include`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_language`
--
ALTER TABLE `HP_language`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT für Tabelle `HP_log`
--
ALTER TABLE `HP_log`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_logLevel`
--
ALTER TABLE `HP_logLevel`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT für Tabelle `HP_mediaquery`
--
ALTER TABLE `HP_mediaquery`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_mimeType`
--
ALTER TABLE `HP_mimeType`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_namespace`
--
ALTER TABLE `HP_namespace`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_oldPassword`
--
ALTER TABLE `HP_oldPassword`
  MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_operatingSystem`
--
ALTER TABLE `HP_operatingSystem`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_php`
--
ALTER TABLE `HP_php`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_setting`
--
ALTER TABLE `HP_setting`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_string`
--
ALTER TABLE `HP_string`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_stringGroup`
--
ALTER TABLE `HP_stringGroup`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_template`
--
ALTER TABLE `HP_template`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT für Tabelle `HP_upload`
--
ALTER TABLE `HP_upload`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `HP_user`
--
ALTER TABLE `HP_user`
  MODIFY `ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

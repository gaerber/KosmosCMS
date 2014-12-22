-- phpMyAdmin SQL Dump
-- version 2.11.9.6
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Erstellungszeit: 14. Dez 2014 um 13:00
-- Server Version: 5.5.30-cll
-- PHP-Version: 5.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `KosmosCMS`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_access_group`
--

CREATE TABLE IF NOT EXISTS `cms_access_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_str` varchar(20) NOT NULL,
  `name` varchar(80) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cms_access_group`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_access_user`
--

CREATE TABLE IF NOT EXISTS `cms_access_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id_str` varchar(20) NOT NULL,
  `user_login` varchar(20) NOT NULL,
  `user_password` varchar(42) NOT NULL,
  `user_name` varchar(50) NOT NULL COMMENT 'Namen',
  `user_email` varchar(50) NOT NULL,
  `user_email_show` tinyint(1) NOT NULL DEFAULT '0',
  `user_allow_newsletter` tinyint(1) NOT NULL DEFAULT '0',
  `user_tel` varchar(20) NOT NULL,
  `user_website` varchar(150) NOT NULL,
  `user_description` varchar(255) NOT NULL,
  `user_points` int(11) NOT NULL DEFAULT '0',
  `user_access` bigint(20) NOT NULL DEFAULT '1',
  `user_lastlogin` int(11) NOT NULL DEFAULT '0',
  `user_regist` int(11) NOT NULL,
  `user_locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cms_access_user`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_admin`
--

CREATE TABLE IF NOT EXISTS `cms_admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(20) NOT NULL,
  `password` varchar(42) NOT NULL,
  `access` smallint(6) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `last_login` int(11) NOT NULL,
  `ip_adress` varchar(16) NOT NULL,
  `locked` tinyint(1) NOT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Daten für Tabelle `cms_admin`
--

INSERT INTO `cms_admin` (`admin_id`, `login`, `password`, `access`, `name`, `email`, `last_login`, `ip_adress`, `locked`) VALUES
(1, 'admin', '40bd001563085fc35165329ea1ff5c5ecbdbbeef', 32767, 'Initial Admin', 'cms@swiss-webdesign.ch', 0, '', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_content`
--

CREATE TABLE IF NOT EXISTS `cms_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `html` longtext NOT NULL,
  `content` longtext NOT NULL,
  `writer` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Daten für Tabelle `cms_content`
--

INSERT INTO `cms_content` (`id`, `page_id`, `html`, `content`, `writer`, `timestamp`) VALUES
(1, 1, '<h1 class="first">Keine Berechtigung</h1>\r\n<p>Sie besitzen keine Rechte um diese Datei / diesen Ordner anzusehen!</p>\r\n<p>{MODULE}</p>', '', 1, 0),
(2, 2, '<h1 class="first">Datei oder Verzeichnis wurde nicht gefunden</h1>\r\n<p>Die von Ihnen gesuchte Seite wurde eventuell entfernt, die Seite ist vorübergehend nicht verfügbar, oder Sie haben die Websiteadresse nicht richtig geschrieben.</p>\r\n<p>Falls Sie über einen Hyperlink auf diese Seite gekommen sind, informieren Sie bitte den <a href="/ueber-mich/kontakt.html">Administrator</a>.</p>\r\n<p>Falls Sie etwas Bestimmtes suchen, finden Sie auf der <a href="/portal/seitenuebersicht.html">Seitenübersicht</a> eine vollständige Liste mit allen Seiten dieser Website.</p>', '', 1, 0),
(3, 3, '<div class="box_error">\r\n<h1>Anmeldungsdaten sind fehlerhaft</h1>\r\n<p>Ihr angegebener Benutzernamen und/oder das Passwort sind falsch!</p></div>\r\n<p>{MODUL}</p>', '', 1, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_menu`
--

CREATE TABLE IF NOT EXISTS `cms_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_str` varchar(20) NOT NULL,
  `label` varchar(20) NOT NULL,
  `menu_is_categorie` tinyint(1) NOT NULL DEFAULT '0',
  `menu_sub` int(11) NOT NULL,
  `menu_order` int(11) NOT NULL,
  `menu_view` tinyint(1) NOT NULL DEFAULT '1',
  `caption` varchar(50) NOT NULL,
  `slogan` varchar(255) NOT NULL,
  `image` varchar(50) NOT NULL,
  `tags` varchar(255) NOT NULL,
  `plugin` int(11) NOT NULL DEFAULT '0',
  `writer` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `access` bigint(20) NOT NULL DEFAULT '0',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Daten für Tabelle `cms_menu`
--

INSERT INTO `cms_menu` (`id`, `id_str`, `label`, `menu_is_categorie`, `menu_sub`, `menu_order`, `menu_view`, `caption`, `slogan`, `image`, `tags`, `plugin`, `writer`, `timestamp`, `access`, `locked`) VALUES
(1, '403', 'Error 403', 0, 0, 1, 0, 'HTTP Error 403', 'Keine Berechtigung', '', '', 0, 1, 0, 0, 1),
(2, '404', 'Error 404', 0, 0, 2, 0, 'HTTP Error 404', 'Seite existiert nicht', '', '', 0, 1, 0, 0, 1),
(3, '550', 'Error 550', 0, 0, 3, 0, 'HTTP Error 550', 'Anmeldung fehlgeschlagen', '', '', 0, 1, 0, 0, 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin`
--

CREATE TABLE IF NOT EXISTS `cms_plugin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(20) NOT NULL,
  `description` tinytext NOT NULL,
  `path` varchar(50) NOT NULL,
  `path_menu` varchar(20) NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- Daten für Tabelle `cms_plugin`
--

INSERT INTO `cms_plugin` (`id`, `label`, `description`, `path`, `path_menu`, `locked`) VALUES
(1, 'Kontaktformular', 'Kontaktformular mit dem die Besucher der Administrator Email Adresse Nachrichten schicken können.', 'contact/contact.php', '', 1),
(2, 'Gästebuch', '', 'guestbook/guestbook.php', '', 1),
(3, 'Neuigkeiten', '', 'news/news.php', 'news/list.php', 1),
(4, 'Fotoalbum', '', 'photos/album.php', 'photos/list.php', 1),
(5, 'Gästebuch Formular', '', 'guestbook/entry.php', '', 1),
(6, 'Search', '', 'search/search.php', '', 1),
(7, 'Sitemap', '', 'sitemap/sitemap.php', '', 1),
(8, 'Login Formular', '', 'access/loginform.php', '', 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin_guestbook`
--

CREATE TABLE IF NOT EXISTS `cms_plugin_guestbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `writer_id` int(11) NOT NULL DEFAULT '0',
  `writer_name` varchar(50) NOT NULL,
  `writer_email` varchar(50) NOT NULL,
  `writer_website` varchar(255) NOT NULL,
  `comment` longtext NOT NULL,
  `admin_id` int(11) NOT NULL DEFAULT '0',
  `admin_comment` longtext NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cms_plugin_guestbook`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin_news`
--

CREATE TABLE IF NOT EXISTS `cms_plugin_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_str` varchar(20) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `caption` varchar(50) NOT NULL,
  `news_short` tinytext NOT NULL,
  `news_long` longtext NOT NULL,
  `writer` int(11) NOT NULL,
  `newsletter` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL,
  `access` bigint(20) NOT NULL DEFAULT '0',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cms_plugin_news`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin_news_categorie`
--

CREATE TABLE IF NOT EXISTS `cms_plugin_news_categorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_str` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cms_plugin_news_categorie`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin_news_comment`
--

CREATE TABLE IF NOT EXISTS `cms_plugin_news_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `news_id` int(11) NOT NULL,
  `writer_id` int(11) NOT NULL DEFAULT '0',
  `writer_name` varchar(50) NOT NULL,
  `writer_email` varchar(50) NOT NULL,
  `writer_website` varchar(150) NOT NULL,
  `comment` longtext NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cms_plugin_news_comment`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin_photoalbum`
--

CREATE TABLE IF NOT EXISTS `cms_plugin_photoalbum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_str` varchar(255) NOT NULL,
  `menu_sub` int(11) NOT NULL DEFAULT '0',
  `menu_order` int(11) NOT NULL,
  `caption` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `writer` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `access` bigint(20) NOT NULL DEFAULT '0',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cms_plugin_news_comment`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin_photoalbum_photo`
--

CREATE TABLE IF NOT EXISTS `cms_plugin_photoalbum_photo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `album_id` int(11) NOT NULL,
  `file_name` varchar(80) NOT NULL,
  `file_timestamp` int(11) NOT NULL,
  `caption` varchar(128) NOT NULL,
  `writer` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cms_plugin_news_comment`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin_spy`
--

CREATE TABLE IF NOT EXISTS `cms_plugin_spy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor` int(11) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `host` varchar(255) CHARACTER SET ucs2 NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `object` varchar(255) NOT NULL,
  `day` date NOT NULL,
  `time` time NOT NULL,
  `page` varchar(255) NOT NULL,
  `get` mediumtext NOT NULL,
  `post` mediumtext NOT NULL,
  `session` mediumtext NOT NULL,
  `comment` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cms_plugin_spy`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin_stats_day`
--

CREATE TABLE IF NOT EXISTS `cms_plugin_stats_day` (
  `day` date NOT NULL,
  `visitors` int(11) NOT NULL,
  `views` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cms_plugin_stats_day`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin_stats_ip`
--

CREATE TABLE IF NOT EXISTS `cms_plugin_stats_ip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(16) NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cms_plugin_stats_ip`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin_stats_page`
--

CREATE TABLE IF NOT EXISTS `cms_plugin_stats_page` (
  `page_id` int(11) NOT NULL,
  `visitors` int(11) NOT NULL DEFAULT '0',
  `views` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cms_plugin_stats_page`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_plugin_stats_views`
--

CREATE TABLE IF NOT EXISTS `cms_plugin_stats_views` (
  `hour` int(4) NOT NULL,
  `views` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`hour`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cms_plugin_stats_views`
--

INSERT INTO `cms_plugin_stats_views` (`hour`, `views`) VALUES
(0, 0),
(1, 0),
(2, 0),
(3, 0),
(4, 0),
(5, 0),
(6, 0),
(7, 0),
(8, 0),
(9, 0),
(10, 0),
(11, 0),
(12, 0),
(13, 0),
(14, 0),
(15, 0),
(16, 0),
(17, 0),
(18, 0),
(19, 0),
(20, 0),
(21, 0),
(22, 0),
(23, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_register`
--

CREATE TABLE IF NOT EXISTS `cms_register` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `number` int(11) NOT NULL,
  `string` varchar(255) NOT NULL,
  `area` text NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Daten für Tabelle `cms_register`
--

INSERT INTO `cms_register` (`id`, `name`, `number`, `string`, `area`, `description`) VALUES
(1, 'Stats_CtrVisitors', 0, '', '', 'Zähler aller Besucher'),
(2, 'Stats_CtrBots', 0, '', '', 'Zähler aller Schuchautomaten'),
(3, 'Stats_CtrSpamBlock', 0, '', '', 'Anzahl abgewehrter Spamvesuche');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cms_setting`
--

CREATE TABLE IF NOT EXISTS `cms_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company` varchar(50) NOT NULL,
  `header` varchar(255) NOT NULL,
  `description` tinytext NOT NULL,
  `admin_email` varchar(50) NOT NULL,
  `newsletter_sender` varchar(20) NOT NULL,
  `newsletter_email` varchar(50) NOT NULL,
  `online` tinyint(1) NOT NULL,
  `offlinemessage` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Daten für Tabelle `cms_setting`
--

INSERT INTO `cms_setting` (`id`, `company`, `header`, `description`, `admin_email`, `newsletter_sender`, `newsletter_email`, `online`, `offlinemessage`) VALUES
(1, 'CMS 2.1 Kosmos', 'Content Management System von Swiss-Webdesign', '', 'cms@swiss-webdesign.ch', '', '', 1, '');

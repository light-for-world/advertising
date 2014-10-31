-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Окт 31 2014 г., 22:54
-- Версия сервера: 5.5.38-0ubuntu0.14.04.1
-- Версия PHP: 5.5.9-1ubuntu4.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `advertising`
--

-- --------------------------------------------------------

--
-- Структура таблицы `area`
--

CREATE TABLE IF NOT EXISTS `area` (
  `codeid` int(11) NOT NULL AUTO_INCREMENT,
  `name_area` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`codeid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `classifieds`
--

CREATE TABLE IF NOT EXISTS `classifieds` (
  `codeid` bigint(20) NOT NULL AUTO_INCREMENT,
  `title_classifieds` text NOT NULL,
  `codeid_sub_category` int(11) NOT NULL,
  `codeid_gen_category` int(11) NOT NULL,
  `codeid_user` bigint(20) NOT NULL,
  PRIMARY KEY (`codeid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `gen_category`
--

CREATE TABLE IF NOT EXISTS `gen_category` (
  `codeid` int(11) NOT NULL AUTO_INCREMENT,
  `name_gen_category` varchar(50) NOT NULL,
  PRIMARY KEY (`codeid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sub_category`
--

CREATE TABLE IF NOT EXISTS `sub_category` (
  `codeid` int(11) NOT NULL AUTO_INCREMENT,
  `title_sub_category` varchar(50) NOT NULL,
  `codeid_gen_category` int(11) NOT NULL,
  PRIMARY KEY (`codeid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `towns`
--

CREATE TABLE IF NOT EXISTS `towns` (
  `codeid` int(11) NOT NULL AUTO_INCREMENT,
  `name_town` varchar(50) DEFAULT NULL,
  `codeid_area` int(11) NOT NULL,
  PRIMARY KEY (`codeid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `codeid` bigint(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `fio` varchar(60) NOT NULL,
  PRIMARY KEY (`codeid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

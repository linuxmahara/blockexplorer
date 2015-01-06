-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               5.5.38 - Source distribution
-- Server OS:                    FreeBSD9.2
-- HeidiSQL Version:             9.1.0.4867
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table explorer.blocks
CREATE TABLE IF NOT EXISTS `blocks` (
  `hash` blob NOT NULL,
  `prev` blob NOT NULL,
  `number` int(11) NOT NULL,
  `root` blob NOT NULL,
  `bits` bigint(20) NOT NULL,
  `nonce` bigint(20) NOT NULL,
  `raw` text NOT NULL,
  `time` datetime NOT NULL,
  `totalvalue` decimal(16,8) DEFAULT NULL,
  `transactions` bigint(20) DEFAULT NULL,
  `size` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table explorer.inputs
CREATE TABLE IF NOT EXISTS `inputs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tx` blob NOT NULL,
  `prev` blob NOT NULL,
  `index` bigint(20) NOT NULL,
  `value` decimal(16,8) NOT NULL,
  `scriptsig` text NOT NULL,
  `hash160` blob NOT NULL,
  `type` text NOT NULL,
  `block` blob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table explorer.keys
CREATE TABLE IF NOT EXISTS `keys` (
  `hash160` blob NOT NULL,
  `address` text NOT NULL,
  `pubkey` blob,
  `firstseen` blob NOT NULL,
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table explorer.outputs
CREATE TABLE IF NOT EXISTS `outputs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tx` blob NOT NULL,
  `index` bigint(20) NOT NULL,
  `value` decimal(16,8) NOT NULL,
  `scriptpubkey` text NOT NULL,
  `hash160` blob,
  `type` text NOT NULL,
  `block` blob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table explorer.special
CREATE TABLE IF NOT EXISTS `special` (
  `tx` blob NOT NULL,
  `block` blob NOT NULL,
  `subtype` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table explorer.transactions
CREATE TABLE IF NOT EXISTS `transactions` (
  `hash` blob NOT NULL,
  `block` blob NOT NULL,
  `raw` text NOT NULL,
  `fee` decimal(16,8) DEFAULT NULL,
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `size` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

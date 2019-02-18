/*
Navicat MySQL Data Transfer

Source Server         : LOCAL MySQL Homestead
Source Server Version : 50721
Source Host           : 127.0.0.1:33060
Source Database       : ex

Target Server Type    : MYSQL
Target Server Version : 50721
File Encoding         : 65001

Date: 2019-02-18 22:24:38
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for currencies
-- ----------------------------
DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of currencies
-- ----------------------------
INSERT INTO `currencies` VALUES ('1', 'USD');
INSERT INTO `currencies` VALUES ('2', 'RUR');
INSERT INTO `currencies` VALUES ('3', 'EUR');
INSERT INTO `currencies` VALUES ('4', 'CAD');
INSERT INTO `currencies` VALUES ('5', 'VEF');

-- ----------------------------
-- Table structure for currency_rates
-- ----------------------------
DROP TABLE IF EXISTS `currency_rates`;
CREATE TABLE `currency_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `currency_id` int(11) NOT NULL,
  `rate` bigint(20) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `currency_id` (`currency_id`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of currency_rates
-- ----------------------------
INSERT INTO `currency_rates` VALUES ('86', '2019-02-17', '2', '65481400');
INSERT INTO `currency_rates` VALUES ('87', '2019-02-17', '3', '885317');
INSERT INTO `currency_rates` VALUES ('88', '2019-02-17', '4', '1324670');
INSERT INTO `currency_rates` VALUES ('89', '2019-02-17', '5', '248487640000');
INSERT INTO `currency_rates` VALUES ('114', '2019-02-18', '2', '66264500');
INSERT INTO `currency_rates` VALUES ('115', '2019-02-18', '3', '886155');
INSERT INTO `currency_rates` VALUES ('116', '2019-02-18', '4', '1323900');
INSERT INTO `currency_rates` VALUES ('117', '2019-02-18', '5', '248487640000');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `country` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES ('1', 'Sergey', 'Russia', 'Ufa');
INSERT INTO `users` VALUES ('18', 'Aleksei', 'Cyprus', 'Limassol');

-- ----------------------------
-- Table structure for wallets
-- ----------------------------
DROP TABLE IF EXISTS `wallets`;
CREATE TABLE `wallets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `balance` bigint(20) NOT NULL DEFAULT '0',
  `currency_id` int(10) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of wallets
-- ----------------------------
INSERT INTO `wallets` VALUES ('14', 'New CAD Wallet', '15929', '4', '18');
INSERT INTO `wallets` VALUES ('15', 'New USD Wallet', '16491', '1', '18');
INSERT INTO `wallets` VALUES ('16', 'New EUR Wallet', '15886', '3', '1');
INSERT INTO `wallets` VALUES ('17', 'New RUR Wallet', '4869401', '2', '1');

-- ----------------------------
-- Table structure for wallets_actions_log
-- ----------------------------
DROP TABLE IF EXISTS `wallets_actions_log`;
CREATE TABLE `wallets_actions_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(255) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) unsigned NOT NULL,
  `wallet_id` int(10) unsigned NOT NULL,
  `balance` bigint(20) NOT NULL DEFAULT '0',
  `amount` bigint(20) NOT NULL DEFAULT '0',
  `amount_usd` bigint(20) NOT NULL DEFAULT '0',
  `secondary_user_id` int(11) unsigned DEFAULT NULL,
  `secondary_wallet_id` int(10) unsigned DEFAULT NULL,
  `delta` bigint(20) DEFAULT '0',
  `extra` text,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `date` (`date`)
) ENGINE=MyISAM AUTO_INCREMENT=269 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of wallets_actions_log
-- ----------------------------
INSERT INTO `wallets_actions_log` VALUES ('268', 'transferCredit', '2019-02-18 09:41:19', '1', '16', '15886', '886', '1000', '18', '15', '155', null);
INSERT INTO `wallets_actions_log` VALUES ('267', 'transferCredit', '2019-02-18 09:41:19', '18', '15', '16491', '-1000', '-1000', '1', '16', '0', null);
INSERT INTO `wallets_actions_log` VALUES ('266', 'transferCredit', '2019-02-18 09:41:18', '18', '14', '15929', '2033', '1535', '1', '17', '0', null);
INSERT INTO `wallets_actions_log` VALUES ('265', 'transferCredit', '2019-02-18 09:41:18', '1', '17', '4869401', '-101756', '-1535', '18', '14', '960', null);
INSERT INTO `wallets_actions_log` VALUES ('264', 'transferCredit', '2019-02-18 09:41:18', '1', '17', '4971157', '100000', '1509', '18', '15', '0', null);
INSERT INTO `wallets_actions_log` VALUES ('263', 'transferCredit', '2019-02-18 09:41:18', '18', '15', '17491', '-1509', '-1509', '1', '17', '686', null);
INSERT INTO `wallets_actions_log` VALUES ('262', 'transferCredit', '2019-02-18 09:41:17', '1', '17', '4871157', '66264', '1000', '18', '15', '500', null);
INSERT INTO `wallets_actions_log` VALUES ('261', 'transferCredit', '2019-02-18 09:41:17', '18', '15', '19000', '-1000', '-1000', '1', '17', '0', null);
INSERT INTO `wallets_actions_log` VALUES ('260', 'transferCredit', '2019-02-18 09:41:17', '18', '14', '13896', '1000', '755', '1', '17', '0', null);
INSERT INTO `wallets_actions_log` VALUES ('259', 'transferCredit', '2019-02-18 09:41:17', '1', '17', '4804893', '-50052', '-755', '18', '14', '657', null);
INSERT INTO `wallets_actions_log` VALUES ('248', 'applyCredit', '2019-02-18 09:41:10', '18', '14', '10000', '10000', '7553', null, null, '0', null);
INSERT INTO `wallets_actions_log` VALUES ('249', 'applyCredit', '2019-02-18 09:41:11', '18', '15', '20000', '20000', '20000', null, null, '0', null);
INSERT INTO `wallets_actions_log` VALUES ('250', 'applyCredit', '2019-02-18 09:41:13', '1', '16', '10000', '10000', '11284', null, null, '0', null);
INSERT INTO `wallets_actions_log` VALUES ('251', 'applyCredit', '2019-02-18 09:41:14', '1', '16', '15000', '5000', '5642', null, null, '0', null);
INSERT INTO `wallets_actions_log` VALUES ('252', 'applyCredit', '2019-02-18 09:41:14', '1', '17', '5000000', '5000000', '75455', null, null, '0', null);
INSERT INTO `wallets_actions_log` VALUES ('253', 'transferCredit', '2019-02-18 09:41:15', '1', '17', '4965000', '-35000', '-528', '18', '14', '0', null);
INSERT INTO `wallets_actions_log` VALUES ('254', 'transferCredit', '2019-02-18 09:41:15', '18', '14', '10699', '699', '528', '1', '17', '176', null);
INSERT INTO `wallets_actions_log` VALUES ('255', 'transferCredit', '2019-02-18 09:41:15', '1', '17', '4865000', '-100000', '-1509', '18', '14', '0', null);
INSERT INTO `wallets_actions_log` VALUES ('256', 'transferCredit', '2019-02-18 09:41:15', '18', '14', '12696', '1997', '1509', '1', '17', '597', null);
INSERT INTO `wallets_actions_log` VALUES ('257', 'transferCredit', '2019-02-18 09:41:16', '1', '17', '4854945', '-10055', '-151', '18', '14', '0', null);
INSERT INTO `wallets_actions_log` VALUES ('258', 'transferCredit', '2019-02-18 09:41:16', '18', '14', '12896', '200', '151', '1', '17', '589', null);
SET FOREIGN_KEY_CHECKS=1;

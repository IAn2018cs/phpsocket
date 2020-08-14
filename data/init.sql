
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for group_member
-- ----------------------------
DROP TABLE IF EXISTS `group_member`;
CREATE TABLE `group_member` (
  `group_id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`group_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for msg
-- ----------------------------
DROP TABLE IF EXISTS `msg`;
CREATE TABLE `msg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) DEFAULT NULL,
  `encrypt_key` varchar(255) DEFAULT NULL,
  `encrypt_msg` text,
  `send_time` bigint(20) DEFAULT NULL,
  `send_ip` varchar(255) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `from_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for public_key
-- ----------------------------
DROP TABLE IF EXISTS `public_key`;
CREATE TABLE `public_key` (
  `id` int(11) NOT NULL,
  `securetoken` text NOT NULL,
  `update_timestemp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for user_group
-- ----------------------------
DROP TABLE IF EXISTS `user_group`;
CREATE TABLE `user_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `share_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for user_key
-- ----------------------------
DROP TABLE IF EXISTS `user_key`;
CREATE TABLE `user_key` (
  `id` varchar(255) NOT NULL,
  `publicKey` varchar(512) DEFAULT NULL,
  `fd` int(11) DEFAULT '0',
  `online` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS = 1;

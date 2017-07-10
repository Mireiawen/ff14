CREATE TABLE IF NOT EXISTS `Group` (
  `ID` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Group ID',
  `Name` varchar(64) NOT NULL COMMENT 'Group human readable name',
  PRIMARY KEY (`ID`),
  UNIQUE(`Name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `User` (
  `ID` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'User ID',
  `Username` varchar(64) NOT NULL COMMENT 'Login name',
  `Name` varchar(64) NOT NULL COMMENT 'Real name',
  `Password` varchar(255) NOT NULL COMMENT 'Password hash',
  `Email` varchar(255) NOT NULL COMMENT 'email address',
  `Active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Active/Enabled',
  PRIMARY KEY (`ID`), 
  UNIQUE(`Username`),
  UNIQUE(`Email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `Log` (
  `ID` int(12) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Log record number',
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'UNIX timestamp',
  `UID` int(8) unsigned NOT NULL COMMENT 'User ID of the person causing entry',
  `Setby` varchar(64) NOT NULL COMMENT 'Class or Page that added the record', 
  `Remote` varchar(48) NOT NULL COMMENT 'Remote host IP address', 
  `Location` varchar(64) NOT NULL COMMENT 'GPS coordinates or locality name',
  `Message` text NOT NULL COMMENT 'The action that happened',
  PRIMARY KEY (`ID`),
  CONSTRAINT `fk_log_uid` FOREIGN KEY (`UID`) REFERENCES `User` (`ID`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `UserGroup` (
  `ID` int(12) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Assignment ID number',
  `UID` int(8) unsigned NOT NULL COMMENT 'User ID',
  `GID` int(8) unsigned NOT NULL COMMENT 'Group ID',
  PRIMARY KEY (`ID`),
  UNIQUE (`UID`,`GID`),
  CONSTRAINT `fk_ug_uid` FOREIGN KEY (`UID`) REFERENCES `User` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ug_gid` FOREIGN KEY (`GID`) REFERENCES `Group` (`ID`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Create some default values
-- 
INSERT INTO `Group` (`ID`, `Name`) VALUES
(1, 'Admin'),
(2, 'User');

-- default password: toor
INSERT INTO `User` (`ID`, `Username`, `Name`, `Password`, `Email`) VALUES
(1, 'all', 'All', '', 'root+all@localhost'),
(2, 'unknown', 'Unknown', '', 'root+unknown@localhost'),
(3, 'admin', 'Default Admin User', '{sha256#16}OGQ3NTRmMTIwMWI1NTYxMs5cpnPROzYRjVSnzxOusMoBI4O/dx5xNCG00f2EH1Oa', 'root@localhost');

-- set the admin in admin group
INSERT INTO `UserGroup` (`UID`, `GID`) VALUES
(3, 1);

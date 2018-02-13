SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `Category`;
DROP TABLE IF EXISTS `Skill`;
DROP TABLE IF EXISTS `Job`;
CREATE TABLE `Job` (
	`ID` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Job ID',
	`XIVDB_ID` int(8) unsigned NOT NULL COMMENT 'XIVDB Job ID',
	`Icon` varchar(64) NULL DEFAULT NULL COMMENT 'Icon name',
	`Name_EN` varchar(64) NOT NULL COMMENT 'Job name, English',
	`Name_JP` varchar(64) NOT NULL COMMENT 'Job name, Japanese',
	`Name_DE` varchar(64) NOT NULL COMMENT 'Job name, German',
	`Name_FR` varchar(64) NOT NULL COMMENT 'Job name, French',
	`Abbr_EN` varchar(8) NOT NULL COMMENT 'Abbreviation of the job, English',
	`Abbr_JP` varchar(8) NOT NULL COMMENT 'Abbreviation of the job, Japanese',
	`Abbr_DE` varchar(8) NOT NULL COMMENT 'Abbreviation of the job, German',
	`Abbr_FR` varchar(8) NOT NULL COMMENT 'Abbreviation of the job, French',
	PRIMARY KEY (`ID`),
	UNIQUE (`XIVDB_ID`),
	UNIQUE (`Name_EN`),
	UNIQUE (`Name_JP`),
	UNIQUE (`Name_DE`),
	UNIQUE (`Name_FR`),
	UNIQUE (`Abbr_EN`),
	UNIQUE (`Abbr_JP`),
	UNIQUE (`Abbr_DE`),
	UNIQUE (`Abbr_FR`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `Category` (
	`ID` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Category ID',
	`Name` varchar(64) NOT NULL COMMENT 'Category name',
	PRIMARY KEY (`ID`),
	UNIQUE (`Name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `Skill` (
	`ID` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Skill ID',
	`Category` int(8) unsigned NOT NULL COMMENT 'Skill category',
	`XIVDB_ID` int(8) unsigned NOT NULL COMMENT 'XIVDB Skill ID',
	`Name_EN` varchar(64) NOT NULL COMMENT 'Skill name, English',
	`Name_JP` varchar(64) NOT NULL COMMENT 'Skill name, Japanese',
	`Name_DE` varchar(64) NOT NULL COMMENT 'Skill name, German',
	`Name_FR` varchar(64) NOT NULL COMMENT 'Skill name, French',
	`Icon` varchar(64) NULL DEFAULT NULL COMMENT 'Icon name',
	`Cost` int(4) NOT NULL COMMENT 'Skill CP cost',
	`Restore` int(4) NOT NULL DEFAULT 0 COMMENT 'Skill CP restore',
	`Buff` int(1) NOT NULL COMMENT 'Is the skill buff',
	PRIMARY KEY (`ID`),
	UNIQUE (`XIVDB_ID`),
	UNIQUE (`Name_EN`),
	UNIQUE (`Name_JP`),
	UNIQUE (`Name_DE`),
	UNIQUE (`Name_FR`),
	CONSTRAINT `fk_skill_category` FOREIGN KEY (`Category`) REFERENCES `Category` (`ID`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `ZoneWeather`;
DROP TABLE IF EXISTS `Weather`;
DROP TABLE IF EXISTS `Zone`;
DROP TABLE IF EXISTS `Region`;

CREATE TABLE `Region` (
	`ID` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Region ID',
	`XIVDB_ID` int(8) unsigned NOT NULL COMMENT 'XIVDB Placename ID',
	`Name_EN` varchar(64) NOT NULL COMMENT 'Region name, English',
	`Name_JP` varchar(64) NOT NULL COMMENT 'Region name, Japanese',
	`Name_DE` varchar(64) NOT NULL COMMENT 'Region name, German',
	`Name_FR` varchar(64) NOT NULL COMMENT 'Region name, French',
	PRIMARY KEY (`ID`),
	UNIQUE (`XIVDB_ID`),
	UNIQUE (`Name_EN`),
	UNIQUE (`Name_JP`),
	UNIQUE (`Name_DE`),
	UNIQUE (`Name_FR`)
) Engine=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `Zone` (
	`ID` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Zone ID',
	`Region` int(8) unsigned NOT NULL COMMENT 'Parent Region ID',
	`XIVDB_ID` int(8) unsigned NOT NULL COMMENT 'XIVDB Placename ID',
	`Name_EN` varchar(64) NOT NULL COMMENT 'Zone name, English',
	`Name_JP` varchar(64) NOT NULL COMMENT 'Zone name, Japanese',
	`Name_DE` varchar(64) NOT NULL COMMENT 'Zone name, German',
	`Name_FR` varchar(64) NOT NULL COMMENT 'Zone name, French',
	PRIMARY KEY (`ID`),
	UNIQUE (`XIVDB_ID`),
	UNIQUE (`Name_EN`),
	UNIQUE (`Name_JP`),
	UNIQUE (`Name_DE`),
	UNIQUE (`Name_FR`),
	CONSTRAINT `fk_zone_region` FOREIGN KEY (`Region`) REFERENCES `Region` (`ID`) ON UPDATE CASCADE ON DELETE RESTRICT
) Engine=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `Weather` (
	`ID` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Weather ID',
	`XIVDB_ID` int(8) unsigned NOT NULL COMMENT 'XIVDB Weather ID',
	`Name_EN` varchar(64) NOT NULL COMMENT 'Weather name, English',
	`Name_JP` varchar(64) NOT NULL COMMENT 'Weather name, Japanese',
	`Name_DE` varchar(64) NOT NULL COMMENT 'Weather name, German',
	`Name_FR` varchar(64) NOT NULL COMMENT 'Weather name, French',
	PRIMARY KEY (`ID`),
	UNIQUE (`XIVDB_ID`),
	UNIQUE (`Name_EN`),
	UNIQUE (`Name_JP`),
	UNIQUE (`Name_DE`),
	UNIQUE (`Name_FR`)
) Engine=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `ZoneWeather` (
	`ID` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Weather Zone ID',
	`Zone` int(8) unsigned NOT NULL COMMENT 'Zone ID',
	`Weather` int(8) unsigned NOT NULL COMMENT 'Weather ID',
	`Min` int(8) unsigned NOT NULL COMMENT 'Weather Minimum Chance',
	`Max` int(8) unsigned NOT NULL COMMENT 'Weather Maximum Chance',
	PRIMARY KEY (`ID`),
	UNIQUE (`Zone`, `Weather`, `Min`, `Max`),
	CONSTRAINT `fk_zoneweather_zone` FOREIGN KEY (`Zone`) REFERENCES `Zone` (`ID`) ON UPDATE CASCADE ON DELETE RESTRICT,
	CONSTRAINT `fk_zoneweather_weather` FOREIGN KEY (`Weather`) REFERENCES `Weather` (`ID`) ON UPDATE CASCADE ON DELETE RESTRICT
) Engine=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `Macro`;
CREATE TABLE `Macro` (
	`ID` int(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The macro ID',
	`Hash` varchar(32) NULL DEFAULT NULL COMMENT 'The macro URL hash',
	`Data` text NOT NULL COMMENT 'The macro data',
	PRIMARY KEY (`ID`),
	UNIQUE (`Hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

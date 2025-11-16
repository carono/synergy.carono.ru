CREATE TABLE `auth_assignment` (
  `item_name` varchar(64) NOT NULL,
  `user_id` varchar(64) NOT NULL,
  `created_at` int DEFAULT null,
  PRIMARY KEY (`item_name`, `user_id`)
);

CREATE TABLE `auth_item` (
  `name` varchar(64) PRIMARY KEY NOT NULL,
  `type` smallint NOT NULL,
  `description` text,
  `rule_name` varchar(64) DEFAULT null,
  `data` blob,
  `created_at` int DEFAULT null,
  `updated_at` int DEFAULT null
);

CREATE TABLE `auth_item_child` (
  `parent` varchar(64) NOT NULL,
  `child` varchar(64) NOT NULL,
  PRIMARY KEY (`parent`, `child`)
);

CREATE TABLE `auth_rule` (
  `name` varchar(64) PRIMARY KEY NOT NULL,
  `data` blob,
  `created_at` int DEFAULT null,
  `updated_at` int DEFAULT null
);

CREATE TABLE `comment` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `source_id` int DEFAULT null,
  `user_id` int DEFAULT null,
  `message` text,
  `created_at` datetime DEFAULT null,
  `updated_at` datetime DEFAULT null,
  `deleted_at` datetime DEFAULT null
);

CREATE TABLE `course` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT null,
  `pos` int NOT NULL DEFAULT 10,
  `module` varchar(255) DEFAULT null,
  `created_at` datetime DEFAULT null,
  `deleted_at` datetime DEFAULT null
);

CREATE TABLE `migration` (
  `version` varchar(180) PRIMARY KEY NOT NULL,
  `apply_time` int DEFAULT null
);

CREATE TABLE `semester` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT null,
  `course_id` int DEFAULT null,
  `controller` varchar(255) DEFAULT null,
  `start_at` date DEFAULT null,
  `end_at` date DEFAULT null,
  `pos` int NOT NULL DEFAULT 10,
  `created_at` datetime DEFAULT null,
  `deleted_at` datetime DEFAULT null
);

CREATE TABLE `source` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT null,
  `description` text,
  `view` varchar(255) DEFAULT null,
  `pos` int NOT NULL DEFAULT 10,
  `task_id` int DEFAULT null,
  `class` varchar(255) DEFAULT null,
  `method` varchar(255) DEFAULT null,
  `file` varchar(255) DEFAULT null,
  `deleted_at` datetime DEFAULT null,
  `image` varchar(255) DEFAULT null
);

CREATE TABLE `task` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT null,
  `semester_id` int DEFAULT null,
  `description` text,
  `action` varchar(255) DEFAULT null,
  `deleted_at` datetime DEFAULT null,
  `comment` text
);

CREATE TABLE `user` (
  `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT null,
  `password_hash` varchar(255) DEFAULT null,
  `deleted_at` datetime DEFAULT null
);

CREATE INDEX `idx-auth_assignment-user_id` ON `auth_assignment` (`user_id`) USING BTREE;

CREATE INDEX `rule_name` ON `auth_item` (`rule_name`) USING BTREE;

CREATE INDEX `idx-auth_item-type` ON `auth_item` (`type`) USING BTREE;

CREATE INDEX `child` ON `auth_item_child` (`child`) USING BTREE;

CREATE INDEX `comment[source_id]_source[id]_fk` ON `comment` (`source_id`) USING BTREE;

CREATE INDEX `comment[user_id]_user[id]_fk` ON `comment` (`user_id`) USING BTREE;

CREATE INDEX `semester[course_id]_course[id]_fk` ON `semester` (`course_id`) USING BTREE;

CREATE INDEX `source[task_id]_task[id]_fk` ON `source` (`task_id`) USING BTREE;

CREATE INDEX `task[semester_id]_semester[id]_fk` ON `task` (`semester_id`) USING BTREE;

ALTER TABLE `auth_assignment` ADD CONSTRAINT `auth_assignment_ibfk_1` FOREIGN KEY (`item_name`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `auth_item` ADD CONSTRAINT `auth_item_ibfk_1` FOREIGN KEY (`rule_name`) REFERENCES `auth_rule` (`name`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `auth_item_child` ADD CONSTRAINT `auth_item_child_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `auth_item_child` ADD CONSTRAINT `auth_item_child_ibfk_2` FOREIGN KEY (`child`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `comment` ADD CONSTRAINT `comment[source_id]_source[id]_fk` FOREIGN KEY (`source_id`) REFERENCES `source` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `comment` ADD CONSTRAINT `comment[user_id]_user[id]_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `semester` ADD CONSTRAINT `semester[course_id]_course[id]_fk` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `source` ADD CONSTRAINT `source[task_id]_task[id]_fk` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `task` ADD CONSTRAINT `task[semester_id]_semester[id]_fk` FOREIGN KEY (`semester_id`) REFERENCES `semester` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

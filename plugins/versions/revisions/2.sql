ALTER TABLE `todoitem` ADD `list_id` INT( 11 ) NOT NULL AFTER `id`;

CREATE TABLE `cowl`.`todolist` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` INT( 75 ) NOT NULL
) ENGINE = MYISAM;
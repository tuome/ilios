<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Breaks up the "ilm_session_facet_instructor" table into two separate join tables.
 */
class Migration_Break_up_ilm_session_facet_instructor_table extends CI_Migration
{

    /**
     * @see CI_Migration::up()
     */
    public function up ()
    {
        $this->db->trans_start();
        // create new join tables
        $sql =<<<EOL
CREATE TABLE `ilm_session_facet_x_instructor` (
    `ilm_session_facet_id` INT(14) UNSIGNED NOT NULL,
    `user_id` INT(14) UNSIGNED NOT NULL,
    PRIMARY KEY(`ilm_session_facet_id`, `user_id`),
    CONSTRAINT `fkey_ilm_session_facet_x_instructor_ilm_session_facet_id`
        FOREIGN KEY (`ilm_session_facet_id`)
        REFERENCES `ilm_session_facet` (`ilm_session_facet_id`)
        ON DELETE CASCADE,
    CONSTRAINT `fkey_ilm_session_facet_x_instructor_user_id`
        FOREIGN KEY (`user_id`)
        REFERENCES `user` (`user_id`)
        ON DELETE CASCADE
) DEFAULT CHARSET='utf8'
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB
EOL;
        $this->db->query($sql);
        $sql =<<<EOL
CREATE TABLE `ilm_session_facet_x_instructor_group` (
    `ilm_session_facet_id` INT(14) UNSIGNED NOT NULL,
    `instructor_group_id` INT(14) UNSIGNED NOT NULL,
    PRIMARY KEY(`ilm_session_facet_id`, `instructor_group_id`),
    CONSTRAINT `fkey_ilm_session_facet_x_instructor_group_ilm_session_facet_id`
        FOREIGN KEY (`ilm_session_facet_id`)
        REFERENCES `ilm_session_facet` (`ilm_session_facet_id`)
        ON DELETE CASCADE,
    CONSTRAINT `fkey_ilm_session_facet_x_instructor_group_instructor_group_id`
        FOREIGN KEY (`instructor_group_id`)
        REFERENCES `instructor_group` (`instructor_group_id`)
        ON DELETE CASCADE
) DEFAULT CHARSET='utf8'
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB
EOL;
        $this->db->query($sql);

        // copy data out of "ilm_session_facet_instructor" into the appropriate join table.
        $sql =<<<EOL
INSERT INTO `ilm_session_facet_x_instructor` (`ilm_session_facet_id`, `user_id`) (
    SELECT DISTINCT `ilm_session_facet_id`, `user_id` FROM `ilm_session_facet_instructor`
    WHERE `user_id` IS NOT NULL
)
EOL;
        $this->db->query($sql);
        $sql =<<<EOL
INSERT INTO `ilm_session_facet_x_instructor_group` (`ilm_session_facet_id`, `instructor_group_id`) (
    SELECT DISTINCT `ilm_session_facet_id`, `instructor_group_id` FROM `ilm_session_facet_instructor`
    WHERE `instructor_group_id` IS NOT NULL
)
EOL;
        $this->db->query($sql);

        // create triggers on new join tables

        $sql =<<<EOL
CREATE TRIGGER `trig_ilm_session_facet_x_instructor_post_delete` AFTER DELETE ON `ilm_session_facet_x_instructor`
FOR EACH ROW BEGIN
    UPDATE `session` SET `session`.`last_updated_on` = NOW()
    WHERE `session`.`ilm_session_facet_id` = OLD.`ilm_session_facet_id`;
END
EOL;
        $this->db->query($sql);
        $sql =<<<EOL
CREATE TRIGGER `trig_ilm_session_facet_x_instructor_post_insert` AFTER INSERT ON `ilm_session_facet_x_instructor`
FOR EACH ROW BEGIN
    UPDATE `session` SET `session`.`last_updated_on` = NOW()
    WHERE `session`.`ilm_session_facet_id` = NEW.`ilm_session_facet_id`;
END
EOL;
        $this->db->query($sql);
        $sql =<<<EOL
CREATE TRIGGER `trig_ilm_session_facet_x_instructor_group_post_delete` AFTER DELETE ON `ilm_session_facet_x_instructor_group`
FOR EACH ROW BEGIN
    UPDATE `session` SET `session`.`last_updated_on` = NOW()
    WHERE `session`.`ilm_session_facet_id` = OLD.`ilm_session_facet_id`;
END
EOL;
        $this->db->query($sql);
        $sql =<<<EOL
CREATE TRIGGER `trig_ilm_session_facet_x_instructor_group_post_insert` AFTER INSERT ON `ilm_session_facet_x_instructor_group`
FOR EACH ROW BEGIN
    UPDATE `session` SET `session`.`last_updated_on` = NOW()
    WHERE `session`.`ilm_session_facet_id` = NEW.`ilm_session_facet_id`;
END
EOL;
        $this->db->query($sql);

        // drop triggers on "ilm_session_facet_instructor" table
        $this->db->query("DROP TRIGGER `ilm_session_facet_instructor_post_delete`");
        $this->db->query("DROP TRIGGER `ilm_session_facet_instructor_post_insert`");

        // nuke "ilm_session_facet_instructor"
        $sql = "DROP TABLE `ilm_session_facet_instructor`";
        $this->db->query($sql);

        // delete and re-create relevant stored procedures
        $sql = "DROP PROCEDURE `nuke_session`";
        $this->db->query($sql);
        $sql =<<< EOL
CREATE PROCEDURE nuke_session (in_session_id INT)
READS SQL DATA
BEGIN
    DECLARE out_of_rows INT DEFAULT 0;
    DECLARE ilm_id INT DEFAULT 0;
    DECLARE oid INT DEFAULT 0;
    DECLARE offering_cursor CURSOR FOR SELECT offering_id FROM offering WHERE session_id = in_session_id;
    DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET out_of_rows = 1;

    DELETE FROM session_description WHERE session_id = in_session_id;
    DELETE FROM session_x_discipline WHERE session_id = in_session_id;
    DELETE FROM session_x_mesh WHERE session_id = in_session_id;

    CALL nuke_learning_material_associations(in_session_id, 'session');

    CALL nuke_objective_associations(in_session_id, 'session');

    SELECT ilm_session_facet_id FROM session WHERE session_id = in_session_id INTO ilm_id;


    OPEN offering_cursor;

    REPEAT
        SET out_of_rows = 0;
        FETCH offering_cursor INTO oid;

        IF NOT out_of_rows THEN
            CALL nuke_offering(oid);
        END IF;

    UNTIL out_of_rows END REPEAT;

    CLOSE offering_cursor;

    DELETE FROM session WHERE session_id = in_session_id;


    IF ilm_id IS NOT NULL THEN
        DELETE FROM ilm_session_facet WHERE ilm_session_facet_id = ilm_id;
    END IF;
END
EOL;
        $this->db->query($sql);
        $sql = "DROP FUNCTION `copy_ilm_session_attributes_to_ilm_session`";
        $this->db->query($sql);
        $sql =<<< EOL
CREATE FUNCTION copy_ilm_session_attributes_to_ilm_session (in_original_ilmsfid INT, in_new_ilmsfid INT)
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE out_of_rows INT DEFAULT 0;
    DECLARE uid INT DEFAULT 0;
    DECLARE gid INT DEFAULT 0;
    DECLARE rows_added INT DEFAULT 0;
    DECLARE i_cursor CURSOR FOR SELECT user_id FROM ilm_session_facet_x_instructor WHERE ilm_session_facet_id = in_original_ilmsfid;
    DECLARE j_cursor CURSOR FOR SELECT instructor_group_id FROM ilm_session_facet_x_instructor_group WHERE ilm_session_facet_id = in_original_ilmsfid;
    DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET out_of_rows = 1;

    OPEN i_cursor;

    REPEAT
        FETCH i_cursor INTO uid;

        IF NOT out_of_rows THEN
            INSERT INTO ilm_session_facet_x_instructor (ilm_session_facet_id, user_id) VALUES (in_new_ilmsfid, uid);

            SET rows_added = rows_added + 1;
        END IF;

    UNTIL out_of_rows END REPEAT;

    CLOSE i_cursor;

    SET out_of_rows = 0;

    OPEN j_cursor;

    REPEAT
        FETCH j_cursor INTO gid;

        IF NOT out_of_rows THEN
            INSERT INTO ilm_session_facet_x_instructor_group (ilm_session_facet_id, instructor_group_id) VALUES (in_new_ilmsfid, gid);

            SET rows_added = rows_added + 1;
        END IF;

    UNTIL out_of_rows END REPEAT;

    CLOSE j_cursor;

    RETURN rows_added;
END
EOL;

        $this->db->query($sql);
        $this->db->trans_complete();
    }

    /**
     * @see CI_Migration::down()
     */
    public function down ()
    {
        $this->db->trans_start();

        // re-create "ilm_session_facet_instructor" table
        $sql =<<< EOL
CREATE TABLE `ilm_session_facet_instructor` (
    `ilm_session_facet_id` INT(14) UNSIGNED NOT NULL,
    `user_id` INT(14) UNSIGNED NULL DEFAULT NULL,
    `instructor_group_id` INT(14) UNSIGNED NULL DEFAULT NULL,
    INDEX `fkey_ilm_instructor_ilm_session_facet_id` (`ilm_session_facet_id`),
    INDEX `fkey_ilm_instructor_user_id` (`user_id`),
    INDEX `fkey_ilm_instructor_group_id` (`instructor_group_id`),
    CONSTRAINT `fkey_ilm_instructor_group_id`
        FOREIGN KEY (`instructor_group_id`)
        REFERENCES `instructor_group` (`instructor_group_id`)
        ON DELETE CASCADE,
    CONSTRAINT `fkey_ilm_instructor_ilm_session_facet_id`
        FOREIGN KEY (`ilm_session_facet_id`)
        REFERENCES `ilm_session_facet` (`ilm_session_facet_id`)
        ON DELETE CASCADE,
    CONSTRAINT `fkey_ilm_instructor_user_id`
        FOREIGN KEY (`user_id`)
        REFERENCES `user` (`user_id`)
        ON DELETE CASCADE
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
EOL;
        $this->db->query($sql);

        // copy data from join tables into "ilm_session_facet_instructor"
        $sql =<<<EOL
INSERT INTO `ilm_session_facet_instructor` (`ilm_session_facet_id`, `user_id`) (
    SELECT `ilm_session_facet_id`, `user_id` FROM `ilm_session_facet_x_instructor`
)
EOL;
        $this->db->query($sql);
        $sql =<<<EOL
INSERT INTO `ilm_session_facet_instructor` (`ilm_session_facet_id`, `instructor_group_id`) (
    SELECT `ilm_session_facet_id`, `instructor_group_id` FROM `ilm_session_facet_x_instructor_group`
)
EOL;
        $this->db->query($sql);

        // re-create triggers on "ilm_session_facet_instructor"
        $sql =<<<EOL
CREATE TRIGGER `trig_ilm_session_facet_instructor_post_delete` AFTER DELETE ON `ilm_session_facet_instructor`
FOR EACH ROW BEGIN
    UPDATE `session` SET `session`.`last_updated_on` = NOW()
    WHERE `session`.`ilm_session_facet_id` = OLD.`ilm_session_facet_id`;
END
EOL;
        $this->db->query($sql);
        $sql=<<<EOL
CREATE TRIGGER `trig_ilm_session_facet_instructor_post_insert` AFTER INSERT ON `ilm_session_facet_instructor`
FOR EACH ROW BEGIN
    UPDATE `session` SET `session`.`last_updated_on` = NOW()
    WHERE `session`.`ilm_session_facet_id` = NEW.`ilm_session_facet_id`;
END
EOL;
        $this->db->query($sql);

        // drop triggers on ilm_session_facet_x_instructor and ilm_session_facet_x_instructor_group
        $this->db->query("DROP TRIGGER `trig_ilm_session_facet_x_instructor_post_delete`");
        $this->db->query("DROP TRIGGER `trig_ilm_session_facet_x_instructor_post_insert`");
        $this->db->query("DROP TRIGGER `trig_ilm_session_facet_x_instructor_group_post_delete`");
        $this->db->query("DROP TRIGGER `trig_ilm_session_facet_x_instructor_group_post_insert`");

        // nuke ilm_session_facet_x_instructor and ilm_session_facet_x_instructor_group
        $sql = "DROP TABLE `ilm_session_facet_x_instructor`";
        $this->db->query($sql);
        $sql = "DROP TABLE `ilm_session_facet_x_instructor_group`";
        $this->db->query($sql);

        // delete and re-create relevant stored procedures
        $sql = "DROP PROCEDURE `nuke_session`";
        $this->db->query($sql);
        $sql =<<<EOL
CREATE PROCEDURE nuke_session (in_session_id INT)
READS SQL DATA
BEGIN
    DECLARE out_of_rows INT DEFAULT 0;
    DECLARE ilm_id INT DEFAULT 0;
    DECLARE oid INT DEFAULT 0;
    DECLARE offering_cursor CURSOR FOR SELECT offering_id FROM offering WHERE session_id = in_session_id;
    DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET out_of_rows = 1;

    DELETE FROM session_description WHERE session_id = in_session_id;
    DELETE FROM session_x_discipline WHERE session_id = in_session_id;
    DELETE FROM session_x_mesh WHERE session_id = in_session_id;

    CALL nuke_learning_material_associations(in_session_id, 'session');

    CALL nuke_objective_associations(in_session_id, 'session');

    SELECT ilm_session_facet_id FROM session WHERE session_id = in_session_id INTO ilm_id;


    OPEN offering_cursor;

    REPEAT
        SET out_of_rows = 0;
        FETCH offering_cursor INTO oid;

        IF NOT out_of_rows THEN
            CALL nuke_offering(oid);
        END IF;

    UNTIL out_of_rows END REPEAT;

    CLOSE offering_cursor;

    DELETE FROM session WHERE session_id = in_session_id;

    IF ilm_id IS NOT NULL THEN
        DELETE FROM ilm_session_facet WHERE ilm_session_facet_id = ilm_id;
        DELETE FROM ilm_session_facet_instructor WHERE ilm_session_facet_id = ilm_id;
    END IF;
END
EOL;
        $this->db->query($sql);
        $sql = "DROP FUNCTION `copy_ilm_session_attributes_to_ilm_session`";
        $this->db->query($sql);
        $sql =<<< EOL
CREATE FUNCTION copy_ilm_session_attributes_to_ilm_session (in_original_ilmsfid INT, in_new_ilmsfid INT)
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE out_of_rows INT DEFAULT 0;
    DECLARE uid INT DEFAULT 0;
    DECLARE gid INT DEFAULT 0;
    DECLARE rows_added INT DEFAULT 0;
    DECLARE i_cursor CURSOR FOR SELECT user_id, instructor_group_id FROM ilm_session_facet_instructor WHERE ilm_session_facet_id = in_original_ilmsfid;
    DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET out_of_rows = 1;

    OPEN i_cursor;

    REPEAT
        FETCH i_cursor INTO uid, gid;

        IF NOT out_of_rows THEN
            INSERT INTO ilm_session_facet_instructor (ilm_session_facet_id, user_id, instructor_group_id) VALUES (in_new_ilmsfid, uid, gid);

            SET rows_added = rows_added + 1;
        END IF;

    UNTIL out_of_rows END REPEAT;

    CLOSE i_cursor;

    RETURN rows_added;
END
EOL;
        $this->db->query($sql);
        $this->db->trans_complete();
    }
}

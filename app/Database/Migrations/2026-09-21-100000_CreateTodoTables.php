<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates every table the Todo App needs, but ONLY when it does not exist yet,
 * so it is safe to run on a database that was imported from the original
 * app's SQL files. For tables that already exist it brings the three columns
 * the newer code depends on up to date:
 *
 *   tasks.board_id      added when missing
 *   tasks.progress      gets the "Pending" value when missing
 *   tasks.is_completed  0/1 numbers become 'Incomplete' / 'Completed' text
 *
 * No data is deleted.
 */
class CreateTodoTables extends Migration
{
    public function up()
    {
        // ------------------------------------------------------------ users
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `users` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL,
              `email` varchar(255) NOT NULL,
              `password` varchar(255) NOT NULL,
              `reset_token` varchar(255) DEFAULT NULL,
              `reset_token_expiry` datetime DEFAULT NULL,
              `role` varchar(20) NOT NULL DEFAULT 'user',
              PRIMARY KEY (`id`),
              UNIQUE KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // ----------------------------------------------------------- boards
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `boards` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `description` text DEFAULT NULL,
              `created_by` int(11) DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // ------------------------------------------------------------ tasks
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `tasks` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `board_id` int(11) DEFAULT NULL,
              `task` varchar(255) NOT NULL,
              `description` text DEFAULT NULL,
              `status` tinyint(4) NOT NULL DEFAULT 1,
              `is_completed` varchar(20) NOT NULL DEFAULT 'Incomplete',
              `priority` enum('High','Medium','Low') NOT NULL DEFAULT 'Medium',
              `progress` enum('Todo','Pending','In Progress','Review','Done') NOT NULL DEFAULT 'Todo',
              `addedDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `editedDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_board_id` (`board_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // --------------------------------------------------- task_comments
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `task_comments` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `task_id` int(11) NOT NULL,
              `user_id` int(11) NOT NULL,
              `comment` text NOT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_task_id` (`task_id`),
              KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // ------------------------------------------------ task_attachments
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `task_attachments` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `task_id` int(11) NOT NULL,
              `user_id` int(11) NOT NULL,
              `original_name` varchar(255) NOT NULL,
              `stored_name` varchar(255) NOT NULL,
              `file_path` varchar(500) NOT NULL,
              `file_type` varchar(150) DEFAULT NULL,
              `file_size` bigint(20) DEFAULT 0,
              `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_task_id` (`task_id`),
              KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // ------------------------------------------------------ login_logs
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `login_logs` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `emailaddress` varchar(255) NOT NULL,
              `ipaddress` varchar(45) DEFAULT NULL,
              `latlang` varchar(100) DEFAULT NULL,
              `location` varchar(255) DEFAULT NULL,
              `last_attempted_time` datetime NOT NULL,
              `status` varchar(20) NOT NULL,
              `addedDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $this->upgradeExistingTasksTable();
    }

    /**
     * Only needed when the tasks table came from an older version of the app.
     */
    private function upgradeExistingTasksTable(): void
    {
        // ---- board_id ----------------------------------------------------
        if (! $this->db->fieldExists('board_id', 'tasks')) {
            $this->db->query('ALTER TABLE `tasks` ADD COLUMN `board_id` int(11) DEFAULT NULL AFTER `id`');
            $this->db->query('ALTER TABLE `tasks` ADD KEY `idx_board_id` (`board_id`)');
        }

        // ---- progress must know "Pending" -------------------------------
        $progress = $this->db->query("SHOW COLUMNS FROM `tasks` LIKE 'progress'")->getRowArray();

        if ($progress && stripos((string) $progress['Type'], 'Pending') === false) {
            $this->db->query(
                "ALTER TABLE `tasks` MODIFY `progress` "
                . "enum('Todo','Pending','In Progress','Review','Done') NOT NULL DEFAULT 'Todo'"
            );
        }

        // ---- is_completed: numbers -> text labels ------------------------
        $completed = $this->db->query("SHOW COLUMNS FROM `tasks` LIKE 'is_completed'")->getRowArray();

        if ($completed && preg_match('/int/i', (string) $completed['Type'])) {
            $this->db->query(
                "ALTER TABLE `tasks` MODIFY `is_completed` varchar(20) NOT NULL DEFAULT 'Incomplete'"
            );
            $this->db->query("UPDATE `tasks` SET `is_completed` = 'Completed'  WHERE `is_completed` = '1'");
            $this->db->query("UPDATE `tasks` SET `is_completed` = 'Incomplete' WHERE `is_completed` = '0'");
        }
    }

    public function down()
    {
        // Intentionally empty: rolling back must never drop the user's tables.
    }
}

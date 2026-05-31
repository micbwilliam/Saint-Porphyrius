<?php
/**
 * Migration: Create Profile Edit Requests Table
 *
 * Stores profile edit requests submitted by approved members. After a member is
 * approved they can no longer change their own profile directly — every change is
 * stored here as a pending request that an admin must review and approve before it
 * is applied to the member's account.
 */

class SP_Migration_Create_Profile_Edit_Requests_Table {

    public function up() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sp_profile_edit_requests';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            changes longtext NOT NULL,
            status enum('pending','approved','rejected') DEFAULT 'pending',
            admin_id bigint(20) DEFAULT NULL,
            admin_notes text DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function down() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_profile_edit_requests';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}

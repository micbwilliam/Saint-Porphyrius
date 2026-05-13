<?php
/**
 * Saint Porphyrius - Admin Lesson Edit
 * Redirects to the create wizard with lesson_id context
 *
 * @since 6.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// This template is identical to lesson-prep-create but with the lesson_id set.
// Simply include the create template which handles both create and edit modes.
include SP_PLUGIN_DIR . 'templates/unified/admin/lesson-prep-create.php';

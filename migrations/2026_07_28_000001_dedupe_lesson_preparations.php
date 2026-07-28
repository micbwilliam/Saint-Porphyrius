<?php
/**
 * Migration: one lesson preparation per (member, lesson)
 *
 * The wizard only ever put the row id into the form when the preparation was a
 * `draft` or `needs_revision`. Reopening a `submitted`/`under_review`/`approved`
 * one therefore rendered an empty form with no id, and saving it took the INSERT
 * branch -- a second row for the same (user, lesson). Two autosaves in flight at
 * once did the same thing. `sp_lesson_preparations` only had a plain
 * `KEY user_lesson`, so nothing stopped it.
 *
 * The duplicates were not harmless. The max-submissions gate sums
 * `submission_count` across every row for the pair, so members were told they had
 * used up all 3 attempts long before they had; and the approval dedupe key is
 * keyed on the row id, so approving two duplicates awarded the lesson twice.
 *
 * This migration:
 *   1. adds `ai_detection_status`, so detection can move off the submit request;
 *   2. collapses each duplicate group down to one row, salvaging any section text
 *      that only exists on a row being deleted;
 *   3. puts a UNIQUE key on (user_id, lesson_id) so the database -- not a
 *      check-then-act guard -- is what rejects the next one;
 *   4. strips the backslashes that accumulated because save_preparation() ran
 *      wp_kses_post() on slashed $_POST data without unslashing first.
 */

class SP_Migration_Dedupe_Lesson_Preparations {

    /**
     * Which row survives a duplicate group. A row that has been through review
     * outranks one that has not, because points were awarded against its id.
     */
    private $status_rank = array(
        'approved'       => 5,
        'needs_revision' => 4,
        'under_review'   => 3,
        'submitted'      => 2,
        'draft'          => 1,
    );

    private function section_columns() {
        $sections = array(
            'lesson_name', 'objective', 'verse_ayah', 'training_exercises',
            'explanation_means', 'lesson_introduction', 'lesson_writing',
        );

        $columns = array();
        foreach ($sections as $section) {
            $columns[] = 'section_' . $section;
            $columns[] = 'section_' . $section . '_notes';
        }

        return $columns;
    }

    public function up() {
        global $wpdb;

        $table = $wpdb->prefix . 'sp_lesson_preparations';

        if (!$wpdb->get_var("SHOW TABLES LIKE '$table'")) {
            return;
        }

        $this->add_ai_detection_status($table);
        $this->collapse_duplicates($table);
        $this->add_unique_key($table);
        $this->strip_accumulated_slashes($table);
        $this->widen_ai_log_action_type();
    }

    /**
     * sp_lesson_ai_log.action_type was an enum of three values, so any other action --
     * the new prep_save_failed / ai_detect_failed diagnostics among them -- would be
     * silently coerced to ''. Same trap sp_points_log.type fell into in 6.4.5.
     * preparation_id was NOT NULL while the logger passes null for lesson-level events.
     */
    private function widen_ai_log_action_type() {
        global $wpdb;

        $log_table = $wpdb->prefix . 'sp_lesson_ai_log';

        if (!$wpdb->get_var("SHOW TABLES LIKE '$log_table'")) {
            return;
        }

        $wpdb->query("ALTER TABLE $log_table MODIFY COLUMN action_type varchar(40) NOT NULL DEFAULT 'ai_detection'");
        $wpdb->query("ALTER TABLE $log_table MODIFY COLUMN preparation_id bigint(20) unsigned DEFAULT NULL");
    }

    /**
     * Submission no longer waits on OpenAI; it parks the row as `pending` and a
     * single-event cron fills in the score. `none` covers drafts and every row
     * written before this migration.
     */
    private function add_ai_detection_status($table) {
        global $wpdb;

        $has_column = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'ai_detection_status'",
            DB_NAME,
            $table
        ));

        if ($has_column) {
            return;
        }

        $wpdb->query(
            "ALTER TABLE $table
             ADD COLUMN ai_detection_status enum('none','pending','done','failed')
             NOT NULL DEFAULT 'none' AFTER ai_penalty_applied"
        );

        // Anything already submitted has a score (detection used to run inline), so
        // mark it done rather than leaving it looking un-checked in the review queue.
        $wpdb->query(
            "UPDATE $table SET ai_detection_status = 'done'
             WHERE ai_detection_score IS NOT NULL"
        );
    }

    private function collapse_duplicates($table) {
        global $wpdb;

        $groups = $wpdb->get_results(
            "SELECT user_id, lesson_id FROM $table
             GROUP BY user_id, lesson_id HAVING COUNT(*) > 1"
        );

        if (empty($groups)) {
            return;
        }

        $section_columns = $this->section_columns();

        foreach ($groups as $group) {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d AND lesson_id = %d",
                $group->user_id,
                $group->lesson_id
            ));

            if (count($rows) < 2) {
                continue;
            }

            $keeper = $this->pick_keeper($rows, $section_columns);

            $update = array();

            // Salvage anything the keeper is missing. A duplicate was usually created
            // from a blank form, but the member may have typed into it before the
            // real row was found again -- losing that text would be the one
            // unrecoverable outcome here.
            foreach ($section_columns as $column) {
                if (trim((string) $keeper->{$column}) !== '') {
                    continue;
                }
                foreach ($rows as $row) {
                    if ($row->id != $keeper->id && trim((string) $row->{$column}) !== '') {
                        $update[$column] = $row->{$column};
                        break;
                    }
                }
            }

            // MAX, not SUM. Every duplicate row counted its own submissions, and
            // summing them is exactly the inflation that locked members out of the
            // 3-attempt budget they had barely touched. The highest single row is the
            // honest count of how many times this preparation was really submitted.
            $max_count = 0;
            foreach ($rows as $row) {
                $max_count = max($max_count, (int) $row->submission_count);
            }
            $formats = array_fill(0, count($update), '%s');

            if ($max_count !== (int) $keeper->submission_count) {
                $update['submission_count'] = $max_count;
                $formats[] = '%d';
            }

            if (!empty($update)) {
                $wpdb->update(
                    $table,
                    $update,
                    array('id' => $keeper->id),
                    $formats,
                    array('%d')
                );
            }

            $doomed = array();
            foreach ($rows as $row) {
                if ($row->id != $keeper->id) {
                    $doomed[] = (int) $row->id;
                }
            }

            if (!empty($doomed)) {
                $wpdb->query(
                    "DELETE FROM $table WHERE id IN (" . implode(',', $doomed) . ")"
                );
            }
        }
    }

    /**
     * Reviewed status first, then whichever row actually holds the member's work,
     * then the most recently touched.
     */
    private function pick_keeper($rows, $section_columns) {
        $keeper = null;
        $best = null;

        foreach ($rows as $row) {
            $length = 0;
            foreach ($section_columns as $column) {
                $length += strlen((string) $row->{$column});
            }

            $score = array(
                isset($this->status_rank[$row->status]) ? $this->status_rank[$row->status] : 0,
                $length,
                strtotime($row->updated_at ?: $row->created_at ?: '1970-01-01'),
                (int) $row->id,
            );

            if ($best === null || $score > $best) {
                $best = $score;
                $keeper = $row;
            }
        }

        return $keeper;
    }

    private function add_unique_key($table) {
        global $wpdb;

        $leftover = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM (
                SELECT 1 FROM $table GROUP BY user_id, lesson_id HAVING COUNT(*) > 1
             ) d"
        );

        if ($leftover > 0) {
            // Adding the index would fail anyway. Stop rather than half-applying; the
            // runner catches this and reports it as a failed migration.
            throw new Exception(
                "sp_lesson_preparations still has $leftover duplicate (user_id, lesson_id) group(s); "
                . 'the UNIQUE key was not added.'
            );
        }

        $has_unique = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'user_lesson_unique'",
            DB_NAME,
            $table
        ));

        if (!$has_unique) {
            $wpdb->query("ALTER TABLE $table ADD UNIQUE KEY user_lesson_unique (user_id, lesson_id)");
        }

        // The old non-unique key is now redundant -- the unique one covers the same
        // (user_id, lesson_id) prefix.
        $has_old = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'user_lesson'",
            DB_NAME,
            $table
        ));

        if ($has_old) {
            $wpdb->query("ALTER TABLE $table DROP INDEX user_lesson");
        }
    }

    /**
     * save_preparation() ran wp_kses_post() straight on slashed $_POST data, then the
     * template rendered the result back into the textarea and the next autosave -- two
     * seconds later -- posted it again. So every apostrophe grew one more backslash
     * roughly every two seconds of typing.
     *
     * Only sequences WordPress' own slashing produces are unwound (\' \" \\), and only
     * while the string keeps changing, so ordinary text is left alone.
     */
    private function strip_accumulated_slashes($table) {
        global $wpdb;

        $columns = $this->section_columns();
        $columns[] = 'admin_notes';

        // Matching the escape sequences in SQL means fighting three layers of quoting;
        // there is at most one row per member per lesson, so walk them in PHP instead.
        $select = 'id, ' . implode(', ', $columns);
        $offset = 0;
        $limit = 200;

        while (true) {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT $select FROM $table ORDER BY id ASC LIMIT %d OFFSET %d",
                $limit,
                $offset
            ));

            if (empty($rows)) {
                return;
            }

            foreach ($rows as $row) {
                $update = array();

                foreach ($columns as $column) {
                    $value = (string) $row->{$column};
                    $clean = $this->unslash_repeatedly($value);
                    if ($clean !== $value) {
                        $update[$column] = $clean;
                    }
                }

                if (!empty($update)) {
                    $wpdb->update(
                        $table,
                        $update,
                        array('id' => $row->id),
                        array_fill(0, count($update), '%s'),
                        array('%d')
                    );
                }
            }

            $offset += $limit;
        }
    }

    private function unslash_repeatedly($value) {
        // Bounded so a pathological value cannot spin here.
        for ($i = 0; $i < 10; $i++) {
            if (!preg_match('/\\\\[\'"\\\\]/', $value)) {
                break;
            }
            $stripped = stripslashes($value);
            if ($stripped === $value) {
                break;
            }
            $value = $stripped;
        }

        return $value;
    }

    public function down() {
        global $wpdb;

        $table = $wpdb->prefix . 'sp_lesson_preparations';

        // The collapsed rows and the stripped slashes are not recoverable; only the
        // schema changes are undone.
        $wpdb->query("ALTER TABLE $table DROP INDEX user_lesson_unique");
        $wpdb->query("ALTER TABLE $table ADD KEY user_lesson (user_id, lesson_id)");
        $wpdb->query("ALTER TABLE $table DROP COLUMN ai_detection_status");
    }
}

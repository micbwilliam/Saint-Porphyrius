<?php
/**
 * Migration: Normalize sp_gender user meta
 *
 * Cleans up dirty / inconsistent gender values that previously caused the
 * bus seat-pair validator to either reject same-gender bookings (e.g. a
 * stored value of 'Male' / 'M' / 'ذكر' didn't strict-match 'male') or to
 * silently allow mixed-gender pairs (empty meta was being defaulted to
 * 'male' in PHP via `?: 'male'`, hiding the missing data).
 *
 * After this migration:
 *   - Every recognizable variant becomes 'male' or 'female'.
 *   - Unrecognized / empty values are LEFT EMPTY (the validator now refuses
 *     bookings for users with empty gender, prompting them to update their
 *     profile, instead of mis-assigning them to 'male').
 */

class SP_Migration_Normalize_Sp_Gender_User_Meta {
    
    public function up() {
        global $wpdb;
        
        $rows = $wpdb->get_results(
            "SELECT umeta_id, user_id, meta_value
             FROM {$wpdb->usermeta}
             WHERE meta_key = 'sp_gender'"
        );
        
        if (empty($rows)) {
            return true;
        }
        
        // Use the global helper if available; otherwise inline the same logic
        $normalize = function ($raw) {
            if (function_exists('sp_normalize_gender')) {
                return sp_normalize_gender($raw);
            }
            if (!is_scalar($raw)) {
                return '';
            }
            $v = strtolower(trim((string) $raw));
            if ($v === '') return '';
            $male   = array('male', 'm', 'man', 'boy', '1', 'ذكر', 'ولد', 'رجل');
            $female = array('female', 'f', 'woman', 'girl', '2', 'أنثى', 'انثى', 'بنت', 'سيدة', 'امرأة', 'إمرأة');
            if (in_array($v, $male, true))   return 'male';
            if (in_array($v, $female, true)) return 'female';
            return '';
        };
        
        $normalized   = 0;
        $unrecognized = array();
        
        foreach ($rows as $row) {
            $clean = $normalize($row->meta_value);
            $current = (string) $row->meta_value;
            
            if ($clean === '') {
                // Unrecognized — leave the meta as-is so admins can audit it.
                // The bus validator will refuse bookings until it's fixed.
                if (trim($current) !== '') {
                    $unrecognized[] = array(
                        'user_id'    => (int) $row->user_id,
                        'meta_value' => $current,
                    );
                }
                continue;
            }
            
            if ($clean !== $current) {
                $wpdb->update(
                    $wpdb->usermeta,
                    array('meta_value' => $clean),
                    array('umeta_id' => $row->umeta_id),
                    array('%s'),
                    array('%d')
                );
                $normalized++;
            }
        }
        
        // Store an audit report so admins can see who needs follow-up
        update_option('sp_gender_normalization_report', array(
            'ran_at'       => current_time('mysql'),
            'normalized'   => $normalized,
            'unrecognized' => $unrecognized,
        ), false);
        
        return true;
    }
    
    public function down() {
        // Non-reversible: original dirty values are not stored.
        return true;
    }
}

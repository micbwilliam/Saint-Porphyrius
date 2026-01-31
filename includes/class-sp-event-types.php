<?php
/**
 * Saint Porphyrius - Event Types Handler
 * Manages event types with configurable rewards/penalties
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Event_Types {
    
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sp_event_types';
    }
    
    /**
     * Get all event types
     */
    public function get_all($active_only = false) {
        global $wpdb;
        
        $where = $active_only ? "WHERE is_active = 1" : "";
        return $wpdb->get_results("SELECT * FROM {$this->table_name} $where ORDER BY name_ar ASC");
    }
    
    /**
     * Get event type by ID
     */
    public function get($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Get event type by slug
     */
    public function get_by_slug($slug) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE slug = %s",
            $slug
        ));
    }
    
    /**
     * Create event type
     */
    public function create($data) {
        global $wpdb;
        
        $defaults = array(
            'name_ar' => '',
            'name_en' => '',
            'slug' => '',
            'description' => '',
            'icon' => 'calendar',
            'color' => '#6C9BCF',
            'attendance_points' => 10,
            'absence_penalty' => 5,
            'is_active' => 1,
            'excuse_points_7plus' => 2,
            'excuse_points_6' => 3,
            'excuse_points_5' => 4,
            'excuse_points_4' => 5,
            'excuse_points_3' => 6,
            'excuse_points_2' => 7,
            'excuse_points_1' => 8,
            'excuse_points_0' => 10,
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Generate slug if empty
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name_en'] ?: $data['name_ar']);
        }
        
        // Check for duplicate slug
        $exists = $this->get_by_slug($data['slug']);
        if ($exists) {
            return new WP_Error('duplicate_slug', __('Event type with this slug already exists.', 'saint-porphyrius'));
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'name_ar' => sanitize_text_field($data['name_ar']),
                'name_en' => sanitize_text_field($data['name_en']),
                'slug' => sanitize_title($data['slug']),
                'description' => sanitize_textarea_field($data['description']),
                'icon' => sanitize_text_field($data['icon']),
                'color' => sanitize_hex_color($data['color']) ?: '#6C9BCF',
                'attendance_points' => absint($data['attendance_points']),
                'absence_penalty' => absint($data['absence_penalty']),
                'is_active' => (int) $data['is_active'],
                'excuse_points_7plus' => absint($data['excuse_points_7plus']),
                'excuse_points_6' => absint($data['excuse_points_6']),
                'excuse_points_5' => absint($data['excuse_points_5']),
                'excuse_points_4' => absint($data['excuse_points_4']),
                'excuse_points_3' => absint($data['excuse_points_3']),
                'excuse_points_2' => absint($data['excuse_points_2']),
                'excuse_points_1' => absint($data['excuse_points_1']),
                'excuse_points_0' => absint($data['excuse_points_0']),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create event type.', 'saint-porphyrius'));
        }
        
        return array(
            'success' => true,
            'id' => $wpdb->insert_id,
            'message' => __('Event type created successfully.', 'saint-porphyrius')
        );
    }
    
    /**
     * Update event type
     */
    public function update($id, $data) {
        global $wpdb;
        
        $existing = $this->get($id);
        if (!$existing) {
            return new WP_Error('not_found', __('Event type not found.', 'saint-porphyrius'));
        }
        
        $update_data = array();
        $format = array();
        
        if (isset($data['name_ar'])) {
            $update_data['name_ar'] = sanitize_text_field($data['name_ar']);
            $format[] = '%s';
        }
        
        if (isset($data['name_en'])) {
            $update_data['name_en'] = sanitize_text_field($data['name_en']);
            $format[] = '%s';
        }
        
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $format[] = '%s';
        }
        
        if (isset($data['icon'])) {
            $update_data['icon'] = sanitize_text_field($data['icon']);
            $format[] = '%s';
        }
        
        if (isset($data['color'])) {
            $update_data['color'] = sanitize_hex_color($data['color']) ?: '#6C9BCF';
            $format[] = '%s';
        }
        
        if (isset($data['attendance_points'])) {
            $update_data['attendance_points'] = absint($data['attendance_points']);
            $format[] = '%d';
        }
        
        if (isset($data['absence_penalty'])) {
            $update_data['absence_penalty'] = absint($data['absence_penalty']);
            $format[] = '%d';
        }
        
        if (isset($data['is_active'])) {
            $update_data['is_active'] = (int) $data['is_active'];
            $format[] = '%d';
        }
        
        // Excuse points fields
        $excuse_fields = array(
            'excuse_points_7plus',
            'excuse_points_6',
            'excuse_points_5',
            'excuse_points_4',
            'excuse_points_3',
            'excuse_points_2',
            'excuse_points_1',
            'excuse_points_0',
        );
        
        foreach ($excuse_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = absint($data[$field]);
                $format[] = '%d';
            }
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', __('No data to update.', 'saint-porphyrius'));
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update event type.', 'saint-porphyrius'));
        }
        
        return array(
            'success' => true,
            'message' => __('Event type updated successfully.', 'saint-porphyrius')
        );
    }
    
    /**
     * Delete event type
     */
    public function delete($id) {
        global $wpdb;
        
        // Check if event type is in use
        $events_table = $wpdb->prefix . 'sp_events';
        $in_use = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $events_table WHERE event_type_id = %d",
            $id
        ));
        
        if ($in_use > 0) {
            return new WP_Error('in_use', __('Cannot delete event type that is in use by events.', 'saint-porphyrius'));
        }
        
        $result = $wpdb->delete($this->table_name, array('id' => $id), array('%d'));
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete event type.', 'saint-porphyrius'));
        }
        
        return array(
            'success' => true,
            'message' => __('Event type deleted successfully.', 'saint-porphyrius')
        );
    }
    
    /**
     * Get available icons
     */
    public function get_icons() {
        return array(
            'calendar' => __('Calendar', 'saint-porphyrius'),
            'church' => __('Church', 'saint-porphyrius'),
            'book' => __('Book', 'saint-porphyrius'),
            'heart' => __('Heart', 'saint-porphyrius'),
            'users' => __('Users', 'saint-porphyrius'),
            'star' => __('Star', 'saint-porphyrius'),
            'music' => __('Music', 'saint-porphyrius'),
            'sun' => __('Sun', 'saint-porphyrius'),
            'gift' => __('Gift', 'saint-porphyrius'),
            'flag' => __('Flag', 'saint-porphyrius'),
        );
    }
}

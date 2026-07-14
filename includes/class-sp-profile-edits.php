<?php
/**
 * Saint Porphyrius - Profile Edit Requests Handler
 *
 * After a member is approved they can no longer change their own profile data
 * directly. Instead every change is submitted here as a pending request that an
 * admin must review and approve. Only on approval are the changes applied to the
 * member's account (via SP_Registration::update_user_profile in admin mode).
 *
 * Modeled on the appeals request/approve flow (see SP_Appeals).
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Profile_Edits {

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
        $this->table_name = $wpdb->prefix . 'sp_profile_edit_requests';
    }

    /**
     * Editable fields, their Arabic label and value type.
     * Keys match the field names submitted by the profile edit form and the keys
     * accepted by SP_Registration::update_user_profile().
     */
    public static function get_editable_fields() {
        return array(
            'first_name'             => array('label' => 'الاسم الأول',        'type' => 'text'),
            'middle_name'            => array('label' => 'الاسم الأوسط',       'type' => 'text'),
            'last_name'              => array('label' => 'اسم العائلة',         'type' => 'text'),
            'gender'                 => array('label' => 'النوع',              'type' => 'gender'),
            'birth_date'             => array('label' => 'تاريخ الميلاد',       'type' => 'date'),
            'phone'                  => array('label' => 'رقم الهاتف',          'type' => 'phone'),
            'whatsapp_same_as_phone' => array('label' => 'الواتساب نفس الهاتف', 'type' => 'bool'),
            'whatsapp_number'        => array('label' => 'رقم الواتساب',        'type' => 'phone'),
            'job_or_college'         => array('label' => 'الوظيفة / الكلية',    'type' => 'text'),
            'address_area'           => array('label' => 'المنطقة / الحي',      'type' => 'text'),
            'address_street'         => array('label' => 'الشارع',             'type' => 'text'),
            'address_building'       => array('label' => 'العقار',             'type' => 'text'),
            'address_floor'          => array('label' => 'الدور',              'type' => 'text'),
            'address_apartment'      => array('label' => 'الشقة',              'type' => 'text'),
            'address_landmark'       => array('label' => 'علامة مميزة',         'type' => 'text'),
            'address_maps_url'       => array('label' => 'رابط خرائط جوجل',     'type' => 'maps_url'),
            'church_name'            => array('label' => 'اسم الكنيسة',         'type' => 'text'),
            'confession_father'      => array('label' => 'أب الاعتراف',         'type' => 'text'),
            'church_family'          => array('label' => 'الأسرة بالكنيسة',     'type' => 'text'),
            'church_family_servant'  => array('label' => 'خادم الأسرة',         'type' => 'text'),
            'current_church_service' => array('label' => 'الخدمة الحالية',      'type' => 'text'),
            'facebook_link'          => array('label' => 'حساب فيسبوك',         'type' => 'url'),
            'instagram_link'         => array('label' => 'حساب انستجرام',       'type' => 'url'),
        );
    }

    /**
     * Read the current stored value for a profile field.
     */
    public function get_current_value($user_id, $field) {
        if ($field === 'first_name') {
            return (string) get_user_meta($user_id, 'first_name', true);
        }
        if ($field === 'last_name') {
            return (string) get_user_meta($user_id, 'last_name', true);
        }
        return (string) get_user_meta($user_id, 'sp_' . $field, true);
    }

    /**
     * Submit a new profile edit request for a member.
     *
     * Validates the incoming values, computes the diff against the member's
     * current data and stores only the changed fields as a pending request.
     */
    public function submit($user_id, $data) {
        global $wpdb;

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return array('success' => false, 'message' => __('المستخدم غير موجود', 'saint-porphyrius'));
        }

        // Only one pending request per member at a time.
        if ($this->get_pending_for_user($user_id)) {
            return array(
                'success' => false,
                'message' => __('لديك بالفعل طلب تعديل قيد المراجعة. انتظر مراجعة الإدارة قبل إرسال طلب جديد.', 'saint-porphyrius'),
            );
        }

        $changes = $this->build_changes($user_id, $data);
        if (is_wp_error($changes)) {
            return array('success' => false, 'message' => $changes->get_error_message());
        }

        if (empty($changes)) {
            return array('success' => false, 'message' => __('لم تقم بتغيير أي بيانات.', 'saint-porphyrius'));
        }

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id'    => $user_id,
                'changes'    => wp_json_encode($changes, JSON_UNESCAPED_UNICODE),
                'status'     => 'pending',
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s')
        );

        if ($result === false) {
            return array('success' => false, 'message' => __('حدث خطأ أثناء إرسال الطلب', 'saint-porphyrius'));
        }

        $this->notify_admin_new_request($user_id, count($changes));

        return array(
            'success' => true,
            'message' => __('ابن/بنت برفوريوس! تم إرسال طلب التعديل بنجاح. سيتم تطبيقه بعد مراجعة الإدارة 🙏', 'saint-porphyrius'),
        );
    }

    /**
     * Build the validated diff (changed fields only).
     *
     * @return array|WP_Error  field => array('label','old','new'), or WP_Error on invalid input.
     */
    private function build_changes($user_id, $data) {
        $fields = self::get_editable_fields();
        $registration = SP_Registration::get_instance();

        // Determine the requested WhatsApp "same as phone" state up front so we can
        // skip a spurious whatsapp_number diff when the member chose to mirror the phone.
        $whatsapp_same = !empty($data['whatsapp_same_as_phone']);

        $changes = array();

        foreach ($fields as $field => $meta) {
            // whatsapp_number is irrelevant (and intentionally cleared in the form)
            // when the member opts to use the same number as the phone.
            if ($field === 'whatsapp_number' && $whatsapp_same) {
                continue;
            }

            // Checkbox fields are always present in intent; others must be submitted.
            if ($meta['type'] !== 'bool' && !isset($data[$field])) {
                continue;
            }

            $raw = isset($data[$field]) ? $data[$field] : '';

            switch ($meta['type']) {
                case 'phone':
                    $new = trim((string) $raw);
                    if ($new !== '') {
                        $valid = $registration->validate_egyptian_phone($new);
                        if (!$valid) {
                            return new WP_Error(
                                'invalid_phone',
                                sprintf(__('%s غير صحيح. يجب أن يكون رقم مصري (01xxxxxxxxx)', 'saint-porphyrius'), $meta['label'])
                            );
                        }
                        $new = $valid;
                    }
                    break;

                case 'maps_url':
                    $new = trim((string) $raw);
                    if ($new !== '') {
                        if (!preg_match('/^https?:\/\/(www\.)?(google\.com\/maps|maps\.google\.com|goo\.gl\/maps|maps\.app\.goo\.gl)/i', $new)) {
                            return new WP_Error('invalid_maps_url', __('رابط خرائط جوجل غير صحيح', 'saint-porphyrius'));
                        }
                        $new = esc_url_raw($new);
                    }
                    break;

                case 'url':
                    $new = esc_url_raw(trim((string) $raw));
                    break;

                case 'bool':
                    $new = !empty($raw) ? '1' : '0';
                    break;

                case 'gender':
                    $new = sp_normalize_gender($raw) ?: sanitize_text_field($raw);
                    break;

                case 'date':
                    $new = sanitize_text_field($raw);
                    break;

                default:
                    $new = sanitize_text_field($raw);
                    break;
            }

            $old = $this->get_current_value($user_id, $field);

            // Normalize the stored bool ('' counts as '0') for an accurate comparison.
            if ($meta['type'] === 'bool') {
                $old = !empty($old) ? '1' : '0';
            }

            if ((string) $old !== (string) $new) {
                $changes[$field] = array(
                    'label' => $meta['label'],
                    'old'   => (string) $old,
                    'new'   => (string) $new,
                );
            }
        }

        return $changes;
    }

    /**
     * Approve or reject a pending request (admin action).
     *
     * @param int    $request_id
     * @param string $decision    'approved' | 'rejected'
     * @param int    $admin_id
     * @param string $admin_notes
     */
    public function process($request_id, $decision, $admin_id, $admin_notes = '') {
        global $wpdb;

        $request = $this->get($request_id);
        if (!$request) {
            return array('success' => false, 'message' => __('الطلب غير موجود', 'saint-porphyrius'));
        }

        if ($request->status !== 'pending') {
            return array('success' => false, 'message' => __('تم مراجعة هذا الطلب مسبقاً', 'saint-porphyrius'));
        }

        if (!in_array($decision, array('approved', 'rejected'), true)) {
            return array('success' => false, 'message' => __('قرار غير صالح', 'saint-porphyrius'));
        }

        $changes = json_decode($request->changes, true);
        if (!is_array($changes)) {
            $changes = array();
        }

        if ($decision === 'approved') {
            $apply = array();
            foreach ($changes as $field => $info) {
                $apply[$field] = isset($info['new']) ? $info['new'] : '';
            }

            // update_user_profile only applies a WhatsApp number change when the
            // "same as phone" flag is also present, so include it for consistency.
            if (array_key_exists('whatsapp_number', $apply) && !array_key_exists('whatsapp_same_as_phone', $apply)) {
                $apply['whatsapp_same_as_phone'] = '0';
            }

            $registration = SP_Registration::get_instance();
            $apply_result = $registration->update_user_profile($request->user_id, $apply, true);

            if (is_wp_error($apply_result)) {
                return array('success' => false, 'message' => $apply_result->get_error_message());
            }
        }

        $wpdb->update(
            $this->table_name,
            array(
                'status'      => $decision,
                'admin_id'    => $admin_id,
                'admin_notes' => sanitize_textarea_field($admin_notes),
                'reviewed_at' => current_time('mysql'),
            ),
            array('id' => $request_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );

        $this->notify_user_result($request, $decision, count($changes));

        return array(
            'success' => true,
            'message' => $decision === 'approved'
                ? __('تم قبول طلب التعديل وتحديث بيانات العضو', 'saint-porphyrius')
                : __('تم رفض طلب التعديل', 'saint-porphyrius'),
        );
    }

    /**
     * Get a single request.
     */
    public function get($request_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $request_id
        ));
    }

    /**
     * Get the member's current pending request, if any.
     */
    public function get_pending_for_user($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
            $user_id
        ));
    }

    /**
     * Get all requests with optional filters.
     */
    public function get_all($args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => null,
            'user_id' => null,
            'limit' => 50,
            'offset' => 0,
        );
        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $params = array();

        if ($args['status']) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }
        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $params[] = $args['user_id'];
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->table_name} WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Count pending requests.
     */
    public function count_pending() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'");
    }

    /**
     * Format a stored field value for display.
     */
    public static function format_value($field, $value) {
        $fields = self::get_editable_fields();
        $type = isset($fields[$field]['type']) ? $fields[$field]['type'] : 'text';

        if ($value === '' || $value === null) {
            return '—';
        }

        switch ($type) {
            case 'gender':
                $labels = array('male' => __('ذكر', 'saint-porphyrius'), 'female' => __('أنثى', 'saint-porphyrius'));
                return $labels[$value] ?? $value;
            case 'bool':
                return !empty($value) ? __('نعم', 'saint-porphyrius') : __('لا', 'saint-porphyrius');
            case 'date':
                $ts = strtotime($value);
                return $ts ? date_i18n('j F Y', $ts) : $value;
            default:
                return $value;
        }
    }

    public static function get_field_label($field) {
        $fields = self::get_editable_fields();
        return isset($fields[$field]['label']) ? $fields[$field]['label'] : $field;
    }

    public static function get_status_label($status) {
        $labels = array(
            'pending'  => __('قيد المراجعة', 'saint-porphyrius'),
            'approved' => __('مقبول', 'saint-porphyrius'),
            'rejected' => __('مرفوض', 'saint-porphyrius'),
        );
        return $labels[$status] ?? $status;
    }

    public static function get_status_color($status) {
        $colors = array(
            'pending'  => '#F59E0B',
            'approved' => '#10B981',
            'rejected' => '#EF4444',
        );
        return $colors[$status] ?? '#6B7280';
    }

    /**
     * Notify admins about a new edit request.
     */
    private function notify_admin_new_request($user_id, $change_count) {
        $notifications = SP_Notifications::get_instance();
        $user = get_user_by('id', $user_id);
        $user_name = $user ? ($user->first_name ?: $user->display_name) : __('عضو', 'saint-porphyrius');

        $title = '✏️ طلب تعديل ملف شخصي';
        $message = sprintf(
            __('%s طلب تعديل %d من بيانات ملفه الشخصي', 'saint-porphyrius'),
            $user_name,
            $change_count
        );
        $url = home_url('/app/admin/profile-edits?filter=pending');

        $admin_ids = get_users(array('role' => 'administrator', 'fields' => 'ID'));

        if (!empty($admin_ids)) {
            $notifications->create_inbox_for_users($admin_ids, array(
                'title'   => $title,
                'message' => $message,
                'icon'    => '✏️',
                'type'    => 'system',
                'url'     => $url,
            ));

            if ($notifications->is_configured()) {
                $notifications->queue_to_users($admin_ids, $title, $message, $url, 'auto_profile_edit');
            }
        }
    }

    /**
     * Notify the member about the result of their request.
     */
    private function notify_user_result($request, $decision, $change_count) {
        $notifications = SP_Notifications::get_instance();

        if ($decision === 'approved') {
            $title = '✅ ابن/بنت برفوريوس! تم قبول طلب التعديل';
            $message = __('تم قبول طلب تعديل ملفك الشخصي وتحديث بياناتك بنجاح 🙏', 'saint-porphyrius');
            $icon = '✅';
        } else {
            $title = '❌ ابن/بنت برفوريوس! تم رفض طلب التعديل';
            $message = __('تم رفض طلب تعديل ملفك الشخصي. يمكنك إرسال طلب جديد.', 'saint-porphyrius');
            if (!empty($request->admin_notes)) {
                $message .= ' (' . $request->admin_notes . ')';
            }
            $icon = '❌';
        }

        $url = home_url('/app/profile');

        $notifications->create_inbox_notification(array(
            'user_id' => $request->user_id,
            'title'   => $title,
            'message' => $message,
            'icon'    => $icon,
            'type'    => 'system',
            'url'     => $url,
        ));

        if ($notifications->is_configured()) {
            $notifications->queue_to_users(array($request->user_id), $title, $message, $url, 'auto_profile_edit');
        }
    }
}

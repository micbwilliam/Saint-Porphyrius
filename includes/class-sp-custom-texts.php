<?php
/**
 * Saint Porphyrius - Custom Texts Handler
 * Manages all customizable user-facing text strings with gender variants and variable interpolation
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Custom_Texts {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // No hooks needed — this is a passive data store
    }

    /**
     * Get the full default text registry.
     * Each key has 'male' and 'female' variants, a description, and a list of available variables.
     */
    public function get_default_texts() {
        return array(
            // ── Dashboard: Hero Card ──
            'hero_greeting' => array(
                'male'       => 'ابن برفوريوس الغالي، {name}!',
                'female'     => 'بنت برفوريوس الغالية، {name}!',
                'section'    => 'dashboard_hero',
                'label'      => 'التحية في بطاقة الترحيب',
                'variables'  => array('name' => 'اسم العضو'),
            ),
            'hero_subtitle' => array(
                'male'       => 'منور أسرة برفوريوس 😇',
                'female'     => 'منورة أسرة برفوريوس 😇',
                'section'    => 'dashboard_hero',
                'label'      => 'النص الفرعي في بطاقة الترحيب',
                'variables'  => array(),
            ),
            'hero_points_label' => array(
                'male'       => 'نقطة',
                'female'     => 'نقطة',
                'section'    => 'dashboard_hero',
                'label'      => 'كلمة "نقطة" تحت رصيد النقاط',
                'variables'  => array(),
            ),

            // ── Dashboard: Birthday Card ──
            'birthday_reward_msg' => array(
                'male'       => 'حصلت على {points} نقطة هدية عيد ميلادك!',
                'female'     => 'حصلتِ على {points} نقطة هدية عيد ميلادك!',
                'section'    => 'dashboard_birthday',
                'label'      => 'رسالة مكافأة عيد الميلاد',
                'variables'  => array('points' => 'عدد النقاط'),
            ),
            'birthday_today' => array(
                'male'       => 'عيد ميلاده النهاردة! 🎉',
                'female'     => 'عيد ميلادها النهاردة! 🎉',
                'section'    => 'dashboard_birthday',
                'label'      => 'نص "عيد ميلاد اليوم" (عن عضو آخر)',
                'variables'  => array(),
            ),
            'birthday_soon' => array(
                'male'       => 'عيد ميلاده قريب! 🎈',
                'female'     => 'عيد ميلادها قريب! 🎈',
                'section'    => 'dashboard_birthday',
                'label'      => 'نص "عيد ميلاد قريب" (عن عضو آخر)',
                'variables'  => array(),
            ),
            'birthday_congrat_label' => array(
                'male'       => 'هنئه بهدية نقاط! ⭐',
                'female'     => 'هنئيها بهدية نقاط! ⭐',
                'section'    => 'dashboard_birthday',
                'label'      => 'دعوة لتهنئة عضو بعيد ميلاده',
                'variables'  => array(),
            ),
            'birthday_congrat_sent' => array(
                'male'       => 'تم إرسال تهنئتك',
                'female'     => 'تم إرسال تهنئتك',
                'section'    => 'dashboard_birthday',
                'label'      => 'نص "تم إرسال التهنئة"',
                'variables'  => array(),
            ),
            'birthday_gift_claimed_title' => array(
                'male'       => 'اخترت هديتك!',
                'female'     => 'اخترتِ هديتك!',
                'section'    => 'dashboard_birthday',
                'label'      => 'عنوان "تم اختيار الهدية"',
                'variables'  => array(),
            ),
            'birthday_gift_claimed_desc' => array(
                'male'       => 'اختيارك: {icon} {title}',
                'female'     => 'اختيارك: {icon} {title}',
                'section'    => 'dashboard_birthday',
                'label'      => 'وصف الهدية المختارة',
                'variables'  => array('icon' => 'أيقونة الهدية', 'title' => 'اسم الهدية'),
            ),
            'birthday_gift_points_added' => array(
                'male'       => 'تمت إضافة {points} نقطة لرصيدك',
                'female'     => 'تمت إضافة {points} نقطة لرصيدك',
                'section'    => 'dashboard_birthday',
                'label'      => 'نص إضافة نقاط الهدية للرصيد',
                'variables'  => array('points' => 'عدد النقاط'),
            ),
            'birthday_gift_money_added' => array(
                'male'       => 'هديتك {value} جنيه - تواصل مع الخدام',
                'female'     => 'هديتك {value} جنيه - تواصل مع الخدام',
                'section'    => 'dashboard_birthday',
                'label'      => 'نص الهدية المالية',
                'variables'  => array('value' => 'قيمة الهدية'),
            ),
            'birthday_gift_other_added' => array(
                'male'       => 'تواصل مع الخدام لاستلام هديتك',
                'female'     => 'تواصل مع الخدام لاستلام هديتك',
                'section'    => 'dashboard_birthday',
                'label'      => 'نص الهدية الأخرى (غير نقاط/مال)',
                'variables'  => array(),
            ),
            'birthday_gift_choose_title' => array(
                'male'       => 'اختار هديتك! 🎉',
                'female'     => 'اختاري هديتك! 🎉',
                'section'    => 'dashboard_birthday',
                'label'      => 'عنوان "اختيار هدية عيد الميلاد"',
                'variables'  => array(),
            ),
            'birthday_gift_choose_desc' => array(
                'male'       => 'اختار هدية واحدة من الهدايا المتاحة',
                'female'     => 'اختاري هدية واحدة من الهدايا المتاحة',
                'section'    => 'dashboard_birthday',
                'label'      => 'وصف شاشة اختيار الهدية',
                'variables'  => array(),
            ),

            // ── Dashboard: Profile Completion ──
            'profile_complete_praise' => array(
                'male'       => 'أحسنت!',
                'female'     => 'أحسنتِ!',
                'section'    => 'dashboard_profile',
                'label'      => 'كلمة مدح عند إكمال الملف',
                'variables'  => array(),
            ),
            'profile_complete_msg' => array(
                'male'       => 'ملفك الشخصي مكتمل',
                'female'     => 'ملفك الشخصي مكتمل',
                'section'    => 'dashboard_profile',
                'label'      => 'رسالة "الملف مكتمل"',
                'variables'  => array(),
            ),
            'profile_complete_reward' => array(
                'male'       => 'حصلت على {points} نقطة مكافأة إكمال الملف!',
                'female'     => 'حصلتِ على {points} نقطة مكافأة إكمال الملف!',
                'section'    => 'dashboard_profile',
                'label'      => 'رسالة نقاط مكافأة إكمال الملف',
                'variables'  => array('points' => 'عدد النقاط'),
            ),
            'profile_incomplete_title' => array(
                'male'       => 'أكمل ملفك الشخصي',
                'female'     => 'أكملي ملفك الشخصي',
                'section'    => 'dashboard_profile',
                'label'      => 'عنوان "الملف غير مكتمل"',
                'variables'  => array(),
            ),
            'profile_incomplete_desc' => array(
                'male'       => 'أكمل بياناتك واحصل على {points} نقطة!',
                'female'     => 'أكملي بياناتك واحصلي على {points} نقطة!',
                'section'    => 'dashboard_profile',
                'label'      => 'وصف بطاقة إكمال الملف',
                'variables'  => array('points' => 'عدد النقاط'),
            ),
            'profile_incomplete_btn' => array(
                'male'       => 'إكمال الملف الشخصي',
                'female'     => 'إكمال الملف الشخصي',
                'section'    => 'dashboard_profile',
                'label'      => 'زر "إكمال الملف الشخصي"',
                'variables'  => array(),
            ),

            // ── Dashboard: Discipline Status ──
            'discipline_blocked_title' => array(
                'male'       => 'حسابك محظور',
                'female'     => 'حسابك محظور',
                'section'    => 'dashboard_discipline',
                'label'      => 'عنوان "الحساب محظور"',
                'variables'  => array(),
            ),
            'discipline_blocked_msg' => array(
                'male'       => 'ابن برفوريوس! تم إيقاف حسابك بسبب تكرار الغياب — أسرة برفوريوس مستنياك ترجع! تواصل مع المسؤول لإعادة التفعيل 🙏',
                'female'     => 'بنت برفوريوس! تم إيقاف حسابك بسبب تكرار الغياب — أسرة برفوريوس مستنياكي ترجعي! تواصل مع المسؤول لإعادة التفعيل 🙏',
                'section'    => 'dashboard_discipline',
                'label'      => 'رسالة الحظر الكامل',
                'variables'  => array(),
            ),
            'discipline_absences' => array(
                'male'       => 'الغيابات: {current} من {max}',
                'female'     => 'الغيابات: {current} من {max}',
                'section'    => 'dashboard_discipline',
                'label'      => 'نص عدد الغيابات',
                'variables'  => array('current' => 'عدد الغيابات الحالي', 'max' => 'الحد الأقصى'),
            ),
            'discipline_remaining_yellow' => array(
                'male'       => '{count} متبقي للكارت الأصفر',
                'female'     => '{count} متبقي للكارت الأصفر',
                'section'    => 'dashboard_discipline',
                'label'      => 'نص "متبقي للكارت الأصفر"',
                'variables'  => array('count' => 'عدد مرات الغياب المتبقية'),
            ),
            'discipline_remaining_red' => array(
                'male'       => '{count} متبقي للكارت الأحمر',
                'female'     => '{count} متبقي للكارت الأحمر',
                'section'    => 'dashboard_discipline',
                'label'      => 'نص "متبقي للكارت الأحمر"',
                'variables'  => array('count' => 'عدد مرات الغياب المتبقية'),
            ),
            'discipline_forbidden' => array(
                'male'       => 'أنت محروم من {count} فعاليات قادمة',
                'female'     => 'أنتِ محرومة من {count} فعاليات قادمة',
                'section'    => 'dashboard_discipline',
                'label'      => 'نص "محروم من فعاليات"',
                'variables'  => array('count' => 'عدد الفعاليات'),
            ),

            // ── Dashboard: Learning Cards ──
            'story_quiz_incomplete' => array(
                'male'       => 'اكتشف قصة حياة شفيع أسرتنا واحصل على {points} نقاط',
                'female'     => 'اكتشفي قصة حياة شفيع أسرتنا واحصلي على {points} نقاط',
                'section'    => 'dashboard_learning',
                'label'      => 'نص اختبار القصة (غير مكتمل)',
                'variables'  => array('points' => 'عدد النقاط'),
            ),
            'story_quiz_complete' => array(
                'male'       => 'اطلعت على هذه القصة الملهمة ✓',
                'female'     => 'اطلعتِ على هذه القصة الملهمة ✓',
                'section'    => 'dashboard_learning',
                'label'      => 'نص اختبار القصة (مكتمل)',
                'variables'  => array(),
            ),
            'service_instr_incomplete' => array(
                'male'       => 'تعرّف على نظام الخدمة والنقاط واحصل على {points} نقاط',
                'female'     => 'تعرّفي على نظام الخدمة والنقاط واحصلي على {points} نقاط',
                'section'    => 'dashboard_learning',
                'label'      => 'نص تعليمات الخدمة (غير مكتمل)',
                'variables'  => array('points' => 'عدد النقاط'),
            ),
            'service_instr_retry' => array(
                'male'       => 'يمكنك إعادة الاختبار والحصول على {points} نقطة إضافية',
                'female'     => 'يمكنكِ إعادة الاختبار والحصول على {points} نقطة إضافية',
                'section'    => 'dashboard_learning',
                'label'      => 'نص إعادة اختبار تعليمات الخدمة',
                'variables'  => array('points' => 'عدد النقاط'),
            ),
            'service_instr_complete' => array(
                'male'       => 'تمت مراجعتك لهذا الموضوع ✓',
                'female'     => 'تمت مراجعتك لهذا الموضوع ✓',
                'section'    => 'dashboard_learning',
                'label'      => 'نص تعليمات الخدمة (مكتمل)',
                'variables'  => array(),
            ),
            'quizzes_available' => array(
                'male'       => 'اقرأ وزود معلوماتك واكسب النقاط ({count} اختبار متاح)',
                'female'     => 'اقرأي وزودي معلوماتك واكسبي النقاط ({count} اختبار متاح)',
                'section'    => 'dashboard_learning',
                'label'      => 'نص الاختبارات المتاحة',
                'variables'  => array('count' => 'عدد الاختبارات'),
            ),

            // ── Events ──
            'events_forbidden_remaining' => array(
                'male'       => 'متبقي {count} فعاليات للرجوع',
                'female'     => 'متبقي {count} فعاليات للرجوع',
                'section'    => 'events',
                'label'      => 'نص "متبقي فعاليات للرجوع" (الحرمان)',
                'variables'  => array('count' => 'عدد الفعاليات المتبقية'),
            ),
            'events_forbidden_overlay' => array(
                'male'       => 'محروم',
                'female'     => 'محرومة',
                'section'    => 'events',
                'label'      => 'نص "محروم" على كارت الفعالية',
                'variables'  => array(),
            ),
            'events_draft_badge' => array(
                'male'       => 'مسودة',
                'female'     => 'مسودة',
                'section'    => 'events',
                'label'      => 'نص شارة "مسودة"',
                'variables'  => array(),
            ),

            // ── Leaderboard ──
            'leaderboard_empty' => array(
                'male'       => 'كن أول ابن/بنت برفوريوس يكسب النقاط! 🏆',
                'female'     => 'كوني أول بنت برفوريوس تكسب النقاط! 🏆',
                'section'    => 'leaderboard',
                'label'      => 'نص "لا يوجد متصدرين"',
                'variables'  => array(),
            ),

            // ── Community ──
            'community_member_count' => array(
                'male'       => '{count} عضو في الأسرة',
                'female'     => '{count} عضو في الأسرة',
                'section'    => 'community',
                'label'      => 'نص عدد الأعضاء في الأسرة',
                'variables'  => array('count' => 'عدد الأعضاء'),
            ),

            // ── Share Points ──
            'share_fee_fixed' => array(
                'male'       => 'يتم خصم {fee} نقطة رسوم على كل عملية مشاركة',
                'female'     => 'يتم خصم {fee} نقطة رسوم على كل عملية مشاركة',
                'section'    => 'share_points',
                'label'      => 'نص رسوم المشاركة (ثابتة)',
                'variables'  => array('fee' => 'قيمة الرسم'),
            ),
            'share_fee_percent' => array(
                'male'       => 'يتم خصم {fee}% رسوم على كل عملية مشاركة',
                'female'     => 'يتم خصم {fee}% رسوم على كل عملية مشاركة',
                'section'    => 'share_points',
                'label'      => 'نص رسوم المشاركة (نسبة مئوية)',
                'variables'  => array('fee' => 'النسبة المئوية'),
            ),
            'share_balance' => array(
                'male'       => 'رصيدك المتاح: {balance} نقطة',
                'female'     => 'رصيدك المتاح: {balance} نقطة',
                'section'    => 'share_points',
                'label'      => 'نص الرصيد المتاح للمشاركة',
                'variables'  => array('balance' => 'الرصيد'),
            ),

            // ── Quiz Results ──
            'quiz_pass_praise' => array(
                'male'       => 'أحسنت!',
                'female'     => 'أحسنتِ!',
                'section'    => 'quiz',
                'label'      => 'كلمة مدح عند النجاح في الاختبار',
                'variables'  => array(),
            ),
            'quiz_fail_msg' => array(
                'male'       => 'حاول مرة أخرى',
                'female'     => 'حاولي مرة أخرى',
                'section'    => 'quiz',
                'label'      => 'نص "الرسوب في الاختبار"',
                'variables'  => array(),
            ),
            'quiz_result_pass' => array(
                'male'       => 'لقد أجبت على {correct} من {total} إجابات صحيحة وحصلت على {points} نقطة!',
                'female'     => 'لقد أجبتِ على {correct} من {total} إجابات صحيحة وحصلتِ على {points} نقطة!',
                'section'    => 'quiz',
                'label'      => 'نص نتيجة الاختبار (ناجح)',
                'variables'  => array('correct' => 'عدد الإجابات الصحيحة', 'total' => 'إجمالي الأسئلة', 'points' => 'النقاط المكتسبة'),
            ),

            // ── Notifications ──
            'notif_time_minute' => array(
                'male'       => '{count} دقيقة',
                'female'     => '{count} دقيقة',
                'section'    => 'notifications',
                'label'      => 'نص الوقت: دقائق',
                'variables'  => array('count' => 'عدد الدقائق'),
            ),
            'notif_time_hour' => array(
                'male'       => '{count} ساعة',
                'female'     => '{count} ساعة',
                'section'    => 'notifications',
                'label'      => 'نص الوقت: ساعات',
                'variables'  => array('count' => 'عدد الساعات'),
            ),
            'notif_time_day' => array(
                'male'       => '{count} أيام',
                'female'     => '{count} أيام',
                'section'    => 'notifications',
                'label'      => 'نص الوقت: أيام',
                'variables'  => array('count' => 'عدد الأيام'),
            ),
            'notif_time_week' => array(
                'male'       => '{count} أسبوع',
                'female'     => '{count} أسبوع',
                'section'    => 'notifications',
                'label'      => 'نص الوقت: أسابيع',
                'variables'  => array('count' => 'عدد الأسابيع'),
            ),

            // ── General / Shared ──
            'general_points_singular' => array(
                'male'       => 'نقطة',
                'female'     => 'نقطة',
                'section'    => 'general',
                'label'      => 'كلمة "نقطة" (مفرد)',
                'variables'  => array(),
            ),
            'general_points_plural' => array(
                'male'       => 'نقاط',
                'female'     => 'نقاط',
                'section'    => 'general',
                'label'      => 'كلمة "نقاط" (جمع)',
                'variables'  => array(),
            ),
            'general_pound' => array(
                'male'       => 'جنيه',
                'female'     => 'جنيه',
                'section'    => 'general',
                'label'      => 'كلمة "جنيه"',
                'variables'  => array(),
            ),
            'general_member' => array(
                'male'       => 'عضو',
                'female'     => 'عضوة',
                'section'    => 'general',
                'label'      => 'كلمة "عضو" (مفرد)',
                'variables'  => array(),
            ),
            'general_members' => array(
                'male'       => 'أعضاء',
                'female'     => 'عضوات',
                'section'    => 'general',
                'label'      => 'كلمة "أعضاء" (جمع)',
                'variables'  => array(),
            ),
        );
    }

    /**
     * Get merged settings (saved overrides + defaults).
     */
    public function get_settings() {
        $defaults = $this->get_default_texts();
        $saved    = get_option('sp_custom_texts', array());

        // Deep merge: saved values override defaults per-key per-gender
        $merged = $defaults;
        foreach ($saved as $key => $genders) {
            if (isset($merged[$key]) && is_array($genders)) {
                foreach ($genders as $gender => $text) {
                    if (in_array($gender, array('male', 'female'), true) && !empty($text)) {
                        $merged[$key][$gender] = $text;
                    }
                }
            }
        }

        return $merged;
    }

    /**
     * Update custom texts settings.
     *
     * @param array $data  Nested array: [key => ['male' => '...', 'female' => '...']]
     * @return array       The new merged settings.
     */
    public function update_settings($data) {
        $defaults = $this->get_default_texts();
        $saved    = array();

        foreach ($data as $key => $genders) {
            // Only save keys that exist in defaults
            if (!isset($defaults[$key]) || !is_array($genders)) {
                continue;
            }

            foreach (array('male', 'female') as $gender) {
                if (isset($genders[$gender])) {
                    $text = sanitize_text_field($genders[$gender]);
                    // Only save if different from default
                    if ($text !== '' && $text !== $defaults[$key][$gender]) {
                        if (!isset($saved[$key])) {
                            $saved[$key] = array();
                        }
                        $saved[$key][$gender] = $text;
                    }
                }
            }
        }

        update_option('sp_custom_texts', $saved);
        return $this->get_settings();
    }

    /**
     * Reset all custom texts to defaults.
     */
    public function reset_settings() {
        delete_option('sp_custom_texts');
        return $this->get_settings();
    }

    /**
     * Get a single text string by key and gender, with variable interpolation.
     *
     * @param string $key    The text key from the registry.
     * @param string $gender 'male' or 'female'.
     * @param array  $vars   Associative array of variables to interpolate, e.g. ['name' => 'مايكل'].
     * @return string        The final text string.
     */
    public function get_text($key, $gender = 'male', $vars = array()) {
        $settings = $this->get_settings();

        // Get the gendered text, fall back to male if not set
        $text = '';
        if (isset($settings[$key][$gender])) {
            $text = $settings[$key][$gender];
        } elseif (isset($settings[$key]['male'])) {
            $text = $settings[$key]['male'];
        } else {
            return ''; // Key not found
        }

        // Interpolate variables: {name}, {points}, {count}, etc.
        foreach ($vars as $var_key => $var_value) {
            $text = str_replace('{' . $var_key . '}', (string) $var_value, $text);
        }

        return $text;
    }

    /**
     * Get all text keys grouped by section, for the admin UI.
     *
     * @return array  Nested array: [section_id => ['label' => '...', 'keys' => [...]]]
     */
    public function get_keys_by_section() {
        $defaults = $this->get_default_texts();
        $sections = array();

        $section_labels = array(
            'dashboard_hero'       => '📱 بطاقة الترحيب',
            'dashboard_birthday'   => '🎂 بطاقة عيد الميلاد',
            'dashboard_profile'    => '📝 إكمال الملف الشخصي',
            'dashboard_discipline' => '⛔ حالة الحضور والحرمان',
            'dashboard_learning'   => '📚 بطاقات التعلم',
            'events'               => '📅 الفعاليات',
            'leaderboard'          => '🏆 لوحة المتصدرين',
            'community'            => '👥 أعضاء الأسرة',
            'share_points'         => '💰 مشاركة النقاط',
            'quiz'                 => '📝 الاختبارات',
            'notifications'        => '🔔 الإشعارات',
            'general'              => '🌐 كلمات عامة',
        );

        foreach ($defaults as $key => $def) {
            $section = isset($def['section']) ? $def['section'] : 'general';
            if (!isset($sections[$section])) {
                $sections[$section] = array(
                    'label' => isset($section_labels[$section]) ? $section_labels[$section] : $section,
                    'keys'  => array(),
                );
            }
            $sections[$section]['keys'][$key] = $def;
        }

        return $sections;
    }
}

/**
 * Global helper: Get a customizable text string.
 *
 * @param string $key    The text key from the registry.
 * @param string $gender 'male' or 'female'.
 * @param array  $vars   Associative array of variables to interpolate.
 * @return string        The final text string, safe for echo.
 */
function sp_custom_text($key, $gender = 'male', $vars = array()) {
    $handler = SP_Custom_Texts::get_instance();
    return $handler->get_text($key, $gender, $vars);
}

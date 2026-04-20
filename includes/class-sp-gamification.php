<?php
/**
 * Saint Porphyrius - Gamification Handler
 * Manages birthday rewards, profile completion, and story quiz
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Gamification {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into user login to check birthday and profile completion
        add_action('wp_login', array($this, 'check_rewards_on_login'), 10, 2);
        // Hook into profile update to check for profile completion
        add_action('profile_update', array($this, 'check_profile_completion_on_update'), 10, 3);
        // Hook into user meta update to check for profile completion when meta is changed
        add_action('update_user_meta', array($this, 'check_profile_on_meta_update'), 10, 4);
    }
    
    /**
     * Get gamification settings
     */
    public function get_settings() {
        $defaults = array(
            'profile_completion_points' => 50,
            'birthday_points' => 20,
            'story_quiz_points' => 25,
            'feast_day_points' => 100,
            'service_instructions_points' => 10,
            'profile_completion_enabled' => 1,
            'birthday_reward_enabled' => 1,
            'story_quiz_enabled' => 1,
            'feast_day_reward_enabled' => 1,
            'service_instructions_enabled' => 1,
        );
        
        $settings = get_option('sp_gamification_settings', array());
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Update gamification settings
     */
    public function update_settings($settings) {
        $current = $this->get_settings();
        $settings = wp_parse_args($settings, $current);
        
        // Sanitize
        $settings['profile_completion_points'] = absint($settings['profile_completion_points']);
        $settings['birthday_points'] = absint($settings['birthday_points']);
        $settings['story_quiz_points'] = absint($settings['story_quiz_points']);
        $settings['feast_day_points'] = absint($settings['feast_day_points']);
        $settings['service_instructions_points'] = absint($settings['service_instructions_points']);
        $settings['profile_completion_enabled'] = !empty($settings['profile_completion_enabled']) ? 1 : 0;
        $settings['birthday_reward_enabled'] = !empty($settings['birthday_reward_enabled']) ? 1 : 0;
        $settings['story_quiz_enabled'] = !empty($settings['story_quiz_enabled']) ? 1 : 0;
        $settings['feast_day_reward_enabled'] = !empty($settings['feast_day_reward_enabled']) ? 1 : 0;
        $settings['service_instructions_enabled'] = !empty($settings['service_instructions_enabled']) ? 1 : 0;
        
        update_option('sp_gamification_settings', $settings);
        return $settings;
    }
    
    /**
     * Check if it's user's birthday period (day before, day of, day after)
     */
    public function is_birthday_period($user_id) {
        $birth_date = get_user_meta($user_id, 'sp_birth_date', true);
        if (empty($birth_date)) {
            return false;
        }
        
        $today = new DateTime();
        $current_year = $today->format('Y');
        
        // Get birthday for current year
        $birth = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$birth) {
            return false;
        }
        
        $birthday_this_year = new DateTime($current_year . '-' . $birth->format('m-d'));
        
        // Calculate day before and day after
        $day_before = clone $birthday_this_year;
        $day_before->modify('-1 day');
        
        $day_after = clone $birthday_this_year;
        $day_after->modify('+1 day');
        
        $today_string = $today->format('Y-m-d');
        
        if ($today_string === $day_before->format('Y-m-d')) {
            return 'day_before';
        } elseif ($today_string === $birthday_this_year->format('Y-m-d')) {
            return 'birthday';
        } elseif ($today_string === $day_after->format('Y-m-d')) {
            return 'day_after';
        }
        
        return false;
    }
    
    /**
     * Get birthday message based on period and gender
     */
    public function get_birthday_message($user_id) {
        $period = $this->is_birthday_period($user_id);
        if (!$period) {
            return null;
        }
        
        $gender = get_user_meta($user_id, 'sp_gender', true) ?: 'male';
        $is_female = ($gender === 'female');
        
        $messages = array(
            'day_before' => array(
                'male' => '🎂 ابن برفوريوس! بكرة عيد ميلادك! كل سنة وانت طيب وعقبال 100 سنة سعيدة مع يسوع — بركة صلوات القديس برفوريوس تكون معاك ⛪',
                'female' => '🎂 بنت برفوريوس! بكرة عيد ميلادك! كل سنة وانتي طيبة وعقبال 100 سنة سعيدة مع يسوع — بركة صلوات القديس برفوريوس تكون معاكي ⛪',
            ),
            'birthday' => array(
                'male' => '🎉🎂 ابن برفوريوس الغالي! كل سنة وانت طيب وعقبال 100 سنة جديدة سعيدة مع يسوع وكل سنة جاية فيهم تكون احلى من اللي قبلها — بركة صلوات القديس برفوريوس تكون معاك ⛪',
                'female' => '🎉🎂 بنت برفوريوس الغالية! كل سنة وانتي طيبة وعقبال 100 سنة جديدة سعيدة مع يسوع وكل سنة جاية فيهم تكون احلى من اللي قبلها — بركة صلوات القديس برفوريوس تكون معاكي ⛪',
            ),
            'day_after' => array(
                'male' => '🎂 ابن برفوريوس الغالي! عقبال 100 سنة جديدة سعيدة مع يسوع! وكل سنة جاية تكون احلى من اللي قبلها — بركة صلوات القديس برفوريوس تكون معاك ⛪',
                'female' => '🎂 بنت برفوريوس الغالية! عقبال 100 سنة جديدة سعيدة مع يسوع! وكل سنة جاية تكون احلى من اللي قبلها — بركة صلوات القديس برفوريوس تكون معاكي ⛪',
            ),
        );
        
        $gender_key = $is_female ? 'female' : 'male';
        
        return array(
            'message' => $messages[$period][$gender_key],
            'period' => $period,
            'is_birthday' => ($period === 'birthday'),
        );
    }
    
    /**
     * Award birthday points (once per year)
     */
    public function award_birthday_points($user_id) {
        $settings = $this->get_settings();
        
        if (!$settings['birthday_reward_enabled']) {
            return false;
        }
        
        $period = $this->is_birthday_period($user_id);
        if ($period !== 'birthday') {
            return false;
        }
        
        // Check if already awarded this year
        $current_year = date('Y');
        $last_awarded_year = get_user_meta($user_id, 'sp_birthday_rewarded_year', true);
        
        if ($last_awarded_year === $current_year) {
            return false; // Already awarded
        }
        
        // Award points
        $points_handler = SP_Points::get_instance();
        $result = $points_handler->add(
            $user_id,
            $settings['birthday_points'],
            'birthday_reward',
            null,
            __('هدية عيد ميلاد من أسرة برفوريوس 🎂⛪', 'saint-porphyrius')
        );
        
        if (!is_wp_error($result)) {
            update_user_meta($user_id, 'sp_birthday_rewarded_year', $current_year);

            // Broadcast birthday announcement to all members (once per user per year)
            $this->broadcast_birthday_announcement($user_id);

            return $result;
        }
        
        return false;
    }
    
    /**
     * Check rewards on login (birthday + profile completion + feast day)
     */
    public function check_rewards_on_login($user_login, $user) {
        $this->award_birthday_points($user->ID);
        $this->award_profile_completion($user->ID);
        $this->award_feast_day_points($user->ID);
    }
    
    /**
     * Check if it's the feast day of Saint Porphyrius (28 September)
     * Returns true on the feast day, day before, or day after
     */
    public function is_feast_day_period() {
        $today = new DateTime();
        $current_year = $today->format('Y');
        
        // Feast day is September 28 (18 Tout)
        $feast_day = new DateTime($current_year . '-09-28');
        
        // Calculate day before and day after
        $day_before = clone $feast_day;
        $day_before->modify('-1 day');
        
        $day_after = clone $feast_day;
        $day_after->modify('+1 day');
        
        $today_string = $today->format('Y-m-d');
        
        if ($today_string === $day_before->format('Y-m-d')) {
            return 'day_before';
        } elseif ($today_string === $feast_day->format('Y-m-d')) {
            return 'feast_day';
        } elseif ($today_string === $day_after->format('Y-m-d')) {
            return 'day_after';
        }
        
        return false;
    }
    
    /**
     * Get feast day message based on period and gender
     */
    public function get_feast_day_message($user_id) {
        $period = $this->is_feast_day_period();
        if (!$period) {
            return null;
        }
        
        $gender = get_user_meta($user_id, 'sp_gender', true) ?: 'male';
        $is_female = ($gender === 'female');
        
        $messages = array(
            'day_before' => array(
                'male' => '⛪ ابن برفوريوس! بكرة عيد شفيعنا الغالي القديس برفوريوس البهلوان! كل سنة وانت طيب — بركته وصلواته تكون معاك! 🏆',
                'female' => '⛪ بنت برفوريوس! بكرة عيد شفيعنا الغالي القديس برفوريوس البهلوان! كل سنة وانتي طيبة — بركته وصلواته تكون معاكي! 🏆',
            ),
            'feast_day' => array(
                'male' => '🎉⛪ ابن برفوريوس! كل سنة وانت طيب! النهاردة عيد شفيعنا الغالي القديس برفوريوس البهلوان 🏆 — بركته وصلواته تكون معاك!',
                'female' => '🎉⛪ بنت برفوريوس! كل سنة وانتي طيبة! النهاردة عيد شفيعنا الغالي القديس برفوريوس البهلوان 🏆 — بركته وصلواته تكون معاكي!',
            ),
            'day_after' => array(
                'male' => '⛪ ابن برفوريوس! بركة صلوات القديس برفوريوس البهلوان تكون معاك دايماً 🙏',
                'female' => '⛪ بنت برفوريوس! بركة صلوات القديس برفوريوس البهلوان تكون معاكي دايماً 🙏',
            ),
        );
        
        $gender_key = $is_female ? 'female' : 'male';
        
        return array(
            'message' => $messages[$period][$gender_key],
            'period' => $period,
            'is_feast_day' => ($period === 'feast_day'),
        );
    }
    
    /**
     * Award feast day points (once per year)
     */
    public function award_feast_day_points($user_id) {
        $settings = $this->get_settings();
        
        if (!$settings['feast_day_reward_enabled']) {
            return false;
        }
        
        $period = $this->is_feast_day_period();
        if ($period !== 'feast_day') {
            return false;
        }
        
        // Check if already awarded this year
        $current_year = date('Y');
        $last_awarded_year = get_user_meta($user_id, 'sp_feast_day_rewarded_year', true);
        
        if ($last_awarded_year === $current_year) {
            return false; // Already awarded
        }
        
        // Award points
        $points_handler = SP_Points::get_instance();
        $result = $points_handler->add(
            $user_id,
            $settings['feast_day_points'],
            'feast_day_reward',
            null,
            __('هدية عيد شفيعنا لكل أبناء وبنات برفوريوس ⛪🎁', 'saint-porphyrius')
        );
        
        if (!is_wp_error($result)) {
            update_user_meta($user_id, 'sp_feast_day_rewarded_year', $current_year);
            return $result;
        }
        
        return false;
    }
    
    /**
     * Check profile completion when user meta is updated
     */
    public function check_profile_completion_on_update($user_id, $old_user_data, $new_user_data) {
        // Check if profile became complete and award if it did
        $this->award_profile_completion($user_id);
    }
    
    /**
     * Check profile completion when user meta is updated
     */
    public function check_profile_on_meta_update($meta_id, $user_id, $meta_key, $meta_value) {
        // Check profile completion when certain meta keys are updated
        $tracked_fields = array('sp_middle_name', 'sp_gender', 'sp_birth_date', 'sp_phone', 
                              'sp_address_area', 'sp_church_name', 'sp_confession_father');
        
        if (in_array($meta_key, $tracked_fields)) {
            // Delay check to ensure all updates are complete
            wp_schedule_single_event(time() + 2, 'sp_check_profile_completion', array($user_id));
            add_action('sp_check_profile_completion', array($this, 'award_profile_completion'));
        }
    }
    
    /**
     * Calculate profile completion percentage
     */
    public function get_profile_completion($user_id) {
        $required_fields = array(
            'first_name' => get_user_by('id', $user_id)->first_name,
            'last_name' => get_user_by('id', $user_id)->last_name,
            'sp_middle_name' => get_user_meta($user_id, 'sp_middle_name', true),
            'sp_gender' => get_user_meta($user_id, 'sp_gender', true),
            'sp_birth_date' => get_user_meta($user_id, 'sp_birth_date', true),
            'sp_phone' => get_user_meta($user_id, 'sp_phone', true),
            'sp_address_area' => get_user_meta($user_id, 'sp_address_area', true),
            'sp_address_street' => get_user_meta($user_id, 'sp_address_street', true),
            'sp_address_building' => get_user_meta($user_id, 'sp_address_building', true),
            'sp_address_floor' => get_user_meta($user_id, 'sp_address_floor', true),
            'sp_address_apartment' => get_user_meta($user_id, 'sp_address_apartment', true),
            'sp_address_landmark' => get_user_meta($user_id, 'sp_address_landmark', true),
            'sp_address_maps_url' => get_user_meta($user_id, 'sp_address_maps_url', true),
            'sp_church_name' => get_user_meta($user_id, 'sp_church_name', true),
            'sp_confession_father' => get_user_meta($user_id, 'sp_confession_father', true),
            'sp_job_or_college' => get_user_meta($user_id, 'sp_job_or_college', true),
            'sp_current_church_service' => get_user_meta($user_id, 'sp_current_church_service', true),
            'sp_church_family' => get_user_meta($user_id, 'sp_church_family', true),
            'sp_church_family_servant' => get_user_meta($user_id, 'sp_church_family_servant', true),
        );
        
        $filled = 0;
        $total = count($required_fields);
        $missing = array();
        
        foreach ($required_fields as $key => $value) {
            if (!empty($value)) {
                $filled++;
            } else {
                $missing[] = $key;
            }
        }
        
        $percentage = (int) round(($filled / $total) * 100);
        
        return array(
            'percentage' => $percentage,
            'filled' => $filled,
            'total' => $total,
            'missing' => $missing,
            'is_complete' => ($filled === $total),
        );
    }
    
    /**
     * Award profile completion points (once only)
     */
    public function award_profile_completion($user_id) {
        $settings = $this->get_settings();
        
        if (!$settings['profile_completion_enabled']) {
            return false;
        }
        
        // Check if already awarded
        $already_awarded = get_user_meta($user_id, 'sp_profile_completion_rewarded', true);
        if ($already_awarded) {
            return false;
        }
        
        // Check if profile is complete
        $completion = $this->get_profile_completion($user_id);
        if (!$completion['is_complete']) {
            return false;
        }
        
        // Award points
        $points_handler = SP_Points::get_instance();
        $result = $points_handler->add(
            $user_id,
            $settings['profile_completion_points'],
            'profile_completion',
            null,
            __('مكافأة إكمال ملفك في أسرة برفوريوس 🏆⛪', 'saint-porphyrius')
        );
        
        if (!is_wp_error($result)) {
            update_user_meta($user_id, 'sp_profile_completion_rewarded', 1);
            return $result;
        }
        
        return false;
    }
    
    /**
     * Check if user has completed the story quiz
     */
    public function has_completed_story_quiz($user_id) {
        return (bool) get_user_meta($user_id, 'sp_story_quiz_completed', true);
    }
    
    /**
     * Award story quiz points
     */
    public function award_story_quiz($user_id) {
        $settings = $this->get_settings();
        
        if (!$settings['story_quiz_enabled']) {
            return false;
        }
        
        // Check if already completed
        if ($this->has_completed_story_quiz($user_id)) {
            return false;
        }
        
        // Award points
        $points_handler = SP_Points::get_instance();
        $result = $points_handler->add(
            $user_id,
            $settings['story_quiz_points'],
            'story_quiz',
            null,
            __('مكافأة معرفة قصة شفيعك القديس برفوريوس البهلوان 📖⛪', 'saint-porphyrius')
        );
        
        if (!is_wp_error($result)) {
            update_user_meta($user_id, 'sp_story_quiz_completed', 1);
            update_user_meta($user_id, 'sp_story_quiz_completed_at', current_time('mysql'));
            return $result;
        }
        
        return false;
    }
    
    /**
     * Check if user has fully completed the service instructions quiz (max 2 attempts)
     */
    public function has_completed_service_instructions($user_id) {
        return $this->get_service_instructions_completion_count($user_id) >= 2;
    }

    /**
     * Get the number of times user has completed the service instructions quiz
     */
    public function get_service_instructions_completion_count($user_id) {
        return (int) get_user_meta($user_id, 'sp_service_instructions_completed', true);
    }

    /**
     * Award service instructions quiz points
     */
    public function award_service_instructions($user_id) {
        $settings = $this->get_settings();

        if (!$settings['service_instructions_enabled']) {
            return false;
        }

        // Check if max attempts reached (max 2)
        $completion_count = $this->get_service_instructions_completion_count($user_id);
        if ($completion_count >= 2) {
            return false;
        }

        // Award points
        $points_handler = SP_Points::get_instance();
        $result = $points_handler->add(
            $user_id,
            $settings['service_instructions_points'],
            'service_instructions',
            null,
            __('مكافأة قراءة نظام أسرة برفوريوس 📋⛪', 'saint-porphyrius')
        );

        if (!is_wp_error($result)) {
            update_user_meta($user_id, 'sp_service_instructions_completed', $completion_count + 1);
            update_user_meta($user_id, 'sp_service_instructions_completed_at', current_time('mysql'));
            return $result;
        }

        return false;
    }
    
    /**
     * Get service instructions content
     */
    public function get_service_instructions() {
        return array(
            'title' => 'تعليمات الخدمة والنظام',
            'subtitle' => 'كل ما تحتاج معرفته عن نظام الحضور والنقاط',
            'content' => $this->get_service_instructions_content(),
        );
    }
    
    /**
     * Get service instructions content HTML
     */
    private function get_service_instructions_content() {
        return <<<INSTRUCTIONS
<h3>📝 نظام الخدمة:</h3>
<p>نظام الحضور تم تصميمه لمساعدتنا على متابعة ارتباطك بالكنيسة و قلة النقاط أو كثرة الغياب معناه ابتعادك عن الكنيسة بشكل عام مش عن الخدمة فقط .<br/>علشان كده بنطلب نقاط اكثر للأعتذار أو نلفت نظرك لاحتمال إنذار أو حرمان .. الهدف من الانذار او الحرمان انك تبجي مش انه يتنفذ .<br/><strong>(هدفنا دايماً حضورك ومشاركتك مش العقوبة).</strong></p>

<hr style="margin: 24px 0; border: none; border-top: 1px solid var(--sp-border-light);">

<h3>⭐️ نظام النقاط</h3>
<p>كل حاجة بتتعمل كتحضير ليوم الخدمة بتاخد عليها نقاط محددة حسب نوعها:</p>
<ul>
    <li><strong>الحضور في الوقت:</strong> بتاخد النقاط الكاملة.</li>
    <li><strong>الحضور متأخر:</strong> بتاخد نقاط أقل.</li>
    <li><strong>الغياب:</strong> لو التحضيرات إلزامية وماجتش بتخسر نقاط.</li>
    <li><strong>العذر المقبول:</strong> وده عن يوم القرية فقط .. لو عندك عذر ومتقدم قبل يوم الخدمة بأكثر من اسبوع وقتها مش هاتخسر نقاط ولا هاتاخد حرمان.</li>
</ul>

<hr style="margin: 24px 0; border: none; border-top: 1px solid var(--sp-border-light);">

<h3>🙋 تسجيل الحضور المتوقع</h3>
<ul>
    <li>قبل أي تحضيرات أو فعاليات زي القداس أو التسبحة .. تقدر تسجل إنك ناوي تحضر .. ده بيساعدنا نعرف العدد المتوقع خصوصاً في الفعاليات اللي محتاجة تجهيزات خاصة زي اجتماع الصلاة.</li>
    <li>لحجز يوم القرية هاتعمل ده عن طريق الابليكشن وتسجيل رغبتك  من الفاعليات وده هايكون من خلال رصيد نقاطك ( والحجز لكل قرية هايكون بمبلغ نقاط مختلف )</li>
</ul>

<hr style="margin: 24px 0; border: none; border-top: 1px solid var(--sp-border-light);">

<h3>📱 تسجيل الحضور بـ QR Code</h3>
<ul>
    <li>يوم الخدمة أو التحضيرات أو الفعاليات هيظهر QR Code خاص بالفعالية.</li>
    <li>الخادم بيعمله Scan علشان يثبت حضورك وتنزلك النقاط.</li>
    <li>الكود بيبقى صالح لمدة 5 دقايق بس وبعدها بيتغير، علشان الأمان.</li>
</ul>

<hr style="margin: 24px 0; border: none; border-top: 1px solid var(--sp-border-light);">

<h3>✍️ تقديم الأعذار</h3>
<p>لو مش هتقدر تحضر يوم خدمة (فعالية إلزامية):</p>
<ul>
    <li>قدم العذر من صفحة الفعالية قبلها بأكتر من أسبوع.</li>
    <li>اكتب السبب بشكل واضح.</li>
    <li>الخادم هايراجع العذر ويرد عليك بالقبول أو الرفض ( في حالة الرفض لو ماجتش بتكون معرض لحرمان )</li>
    <li>العذر المقبول بيحميك من خصم النقاط ومن الحرمان.</li>
</ul>

<hr style="margin: 24px 0; border: none; border-top: 1px solid var(--sp-border-light);">

<h3>⛔ نظام الحرمان</h3>
<p>يوم الخدمة هو أهم يوم وبينطبق عليه نظام الحرمان في الحالات دي:</p>
<ul>
    <li><strong>غياب مفاجئ يوم الخدمة:</strong> حرمان من الخدمة مرتين ورا بعض.</li>
    <li><strong>اعتذار في الأسبوع الأخير قبل يوم الخدمة:</strong> حرمان مرة واحدة.</li>
    <li><strong>تجاوز كبير:</strong> مدة الحرمان بتكون حسب تقدير أبونا والخادم المسؤول.</li>
</ul>
<ul>
    <li>لو غيبت ٣ غيابات متتالية = كارت اصفر ( تحذير )</li>
    <li>بعد ٦ غيابات متتالية = كارت احمر ( حظر من التطبيق )</li>
    <li>حضورك مرة وسط الغيابات بيصفر العداد</li>
    <li>في حالة حصلت على حرمان مرتين ( لا يحتسب من الغيابات )</li>
</ul>

<hr style="margin: 24px 0; border: none; border-top: 1px solid var(--sp-border-light);">

<h3>🏆 المتصدرين</h3>
<p>صفحة المتصدرين بتوضح أكتر الخدام التزاماً.. كل ما تحضر أكتر، ترتيبك يعلى 🔝</p>

<hr style="margin: 24px 0; border: none; border-top: 1px solid var(--sp-border-light);">

<h3>📷 التصوير</h3>
<ul>
    <li>صورنا مع الاطفال اثناء الخدمة مش بنعملها شير على السوشيال ميديا .. بنشاركها مع بعض في جروب الواتساب الخاص بالأسرة فقط .</li>
    <li>لو حابب تشير عالسوشيال ميديا ( بيكون صورك انت واصحابك افراد اسرة برفوريوس) ..</li>
</ul>
<p><strong>س/ ليه بنعمل كده ؟؟</strong><br/>ج/ علشان الخدمة اللي بتقدمها هي عشور من وقتك / مجهودك / فلوسك … الخ<br/>وطالما هي عشور يبقى حلو انك تطبق الاية دي 👇<br/><em>"احترزوا من أن تصنعوا صدقتكم قدام الناس لكي ينظروكم، وإلا فليس لكم أجر عند أبيكم الذي في السماوات ، فمتى صنعت صدقة فلا تصوت قدامك بالبوق، كما يفعل المراؤون في المجامع وفي الأزقة، لكي يمجدوا من الناس. الحق أقول لكم: إنهم قد استوفوا أجرهم!" (مت 6: 1-2).</em></p>

<hr style="margin: 24px 0; border: none; border-top: 1px solid var(--sp-border-light);">

<h3>💡 نصايح مهمّة</h3>
<ul>
    <li>سجل حضورك المتوقع قبل أي فعالية.</li>
    <li>حاول تيجي في الميعاد علشان تاخد النقاط كاملة.</li>
    <li>لو هتعتذر عن يوم الخدمة، اعمل ده قبلها بأكتر من أسبوع.</li>
    <li>تابع صفحة النقاط علشان تبقى عارف رصيدك.</li>
</ul>
INSTRUCTIONS;
    }
    
    /**
     * Get service instructions quiz questions
     */
    public function get_service_instructions_questions() {
        $questions = array(
            array(
                'id' => 101,
                'question' => 'لما تحضر فعالية في الوقت، بتاخد نقاط ايه؟',
                'options' => array('نقاط أقل', 'النقاط الكاملة', 'مافيش نقاط', 'بتخسر نقاط'),
                'correct' => 1,
            ),
            array(
                'id' => 102,
                'question' => 'لو حضرت فعالية متأخر، ايه اللي بيحصل؟',
                'options' => array('بتاخد النقاط الكاملة', 'بتاخد نقاط أقل', 'مابتاخدش نقاط', 'بتتحرم'),
                'correct' => 1,
            ),
            array(
                'id' => 103,
                'question' => 'امتى بتخسر نقاط لما تغيب؟',
                'options' => array('أي فعالية', 'الفعاليات الإلزامية بس', 'مابتخسرش أبداً', 'الفعاليات الاختيارية'),
                'correct' => 1,
            ),
            array(
                'id' => 104,
                'question' => 'نظام المحروم بيتطبق على ايه؟',
                'options' => array('كل الفعاليات', 'الفعاليات المهمة جداً', 'الفعاليات الاختيارية', 'مافيش نظام محروم'),
                'correct' => 1,
            ),
            array(
                'id' => 105,
                'question' => 'لو اتحرمت، ازاي ترجع؟',
                'options' => array('بتدفع فلوس', 'لازم تحضر فعاليات تانية', 'بتستنى شهر', 'مافيش طريقة'),
                'correct' => 1,
            ),
            array(
                'id' => 106,
                'question' => 'امتى لازم تقدم العذر؟',
                'options' => array('بعد الفعالية', 'قبل الفعالية', 'في أي وقت', 'مش مهم'),
                'correct' => 1,
            ),
            array(
                'id' => 107,
                'question' => 'رمز QR للحضور صالح لمدة قد ايه؟',
                'options' => array('يوم كامل', 'ساعة', '5 دقايق', 'أسبوع'),
                'correct' => 2,
            ),
            array(
                'id' => 108,
                'question' => 'ليه رمز QR بيتغير؟',
                'options' => array('عشان الشكل', 'عشان الأمان', 'مابيتغيرش', 'عشان النظام'),
                'correct' => 1,
            ),
            array(
                'id' => 109,
                'question' => 'ايه فايدة تسجيل الحضور المتوقع؟',
                'options' => array('مافيش فايدة', 'بيساعد في تجهيز المكان', 'بيزود النقاط', 'إلزامي'),
                'correct' => 1,
            ),
            array(
                'id' => 110,
                'question' => 'صفحة المتصدرين بتعرض ايه؟',
                'options' => array('أقدم الأعضاء', 'أكتر الأعضاء نشاطاً وحضوراً', 'الخدام بس', 'مافيش حاجة'),
                'correct' => 1,
            ),
        );
        
        return $questions;
    }
    
    /**
     * Get random service instructions quiz questions
     */
    public function get_random_service_instructions_quiz($count = 5) {
        $all_questions = $this->get_service_instructions_questions();
        shuffle($all_questions);
        return array_slice($all_questions, 0, $count);
    }
    
    /**
     * Validate service instructions quiz answers
     */
    public function validate_service_instructions_answers($user_id, $answers) {
        $all_questions = $this->get_service_instructions_questions();
        $questions_map = array();
        foreach ($all_questions as $q) {
            $questions_map[$q['id']] = $q['correct'];
        }
        
        $correct = 0;
        $total = count($answers);
        
        foreach ($answers as $question_id => $answer) {
            if (isset($questions_map[$question_id]) && $questions_map[$question_id] == $answer) {
                $correct++;
            }
        }
        
        // Need at least 3 out of 5 correct (60%)
        $passed = ($correct >= 3);
        
        return array(
            'correct' => $correct,
            'total' => $total,
            'passed' => $passed,
            'percentage' => round(($correct / $total) * 100),
        );
    }
    
    /**
     * Get the story of Saint Porphyrius
     */
    public function get_saint_story() {
        return array(
            'title' => 'القديس برفوريوس البهلوان',
            'feast_date' => '18 توت / 28 سبتمبر',
            'content' => $this->get_story_content(),
            'image' => SP_PLUGIN_URL . 'media/saint-porphyrius.jpg',
        );
    }
    
    /**
     * Get story content
     */
    private function get_story_content() {
        return <<<STORY
<h3>🌟 نشأته</h3>
<p>القديس برفوريوس اتولد في مدينة افسس في اسيا الصغرى وكان شغله بهلوان وممثل في المسارح الرومانية وكان شاطر جدا في التمثيل والحركات الاستعراضية لدرجة انه بقى مشهور في اماكن كتير في الامبراطورية الرومانية</p>

<h3>🎭 حياته قبل الايمان</h3>
<p>برفوريوس عاش في زمن الامبراطور يوليانوس الجاحد وكان من اشهر الممثلين الوثنيين اللي كانوا بيسخروا من المسيحيين على المسرح وكان بيقلد الصلوات والمعمودية بطريقة تهكم عشان يضحك الناس</p>

<h3>🎂 عيد ميلاد الامبراطور</h3>
<p>في عيد ميلاد الامبراطور يوليانوس اتجمع عدد كبير من الفنانين والمهرجين المشهورين وكان من ضمنهم برفوريوس اللي طلع يعمل عرض ساخر بيقلد فيه طقس المعمودية المسيحية زي ما كان متعود</p>

<h3>✝️ لحظة غيرت كل حاجة</h3>
<p>وهو بيقلد المعمودية رسم علامة الصليب باسم الاب والابن والروح القدس ونزل في الميه وطلع يلبس الابيض والناس كلها كانت بتضحك</p>

<p>بس اللي حصل بعد كده كان صدمة للجميع</p>

<p>برفوريوس وقف قدام الامبراطور وقال <strong>انا مسيحي</strong></p>

<p>الكل افتكرها جزء من التمثيل لكنه فضل يأكد انه بيتكلم بجد</p>

<p>ولما اتسأل ليه قال انه وهو في الميه شاف نور ونعمة ربنا غطت المكان وربنا فتح عينه وخرج من الميه انسان جديد</p>

<p>وصرخ قدام الكل <strong>انا مسيحي</strong></p>

<h3>🔥 ايمان مايهزش</h3>
<p>الامبراطور حاول يغير رأيه مرة بالتهديد ومرة بالاغراء ووعوده بالفلوس والكرامة لكن برفوريوس رفض ينكر ايمانه</p>

<p>وفي الاخر الامبراطور امر بقطع راسه</p>

<h3>📅 استشهاده</h3>
<p>استشهد القديس يوم <strong>18 توت - 28 سبتمبر</strong></p>

<h3>🕊️ شفاعته</h3>
<p>اتعرف عن القديس برفوريوس انه <strong>شفيع المكتئبين</strong></p>

<h3>🙏 طلبته تكون معنا</h3>
<p style="text-align: center; font-size: 1.1em; color: var(--sp-primary);">
<strong>بركة صلوات القديس برفوريوس البهلوان تكون معانا امين</strong>
</p>
STORY;
    }
    
    /**
     * Get quiz questions about the saint story
     */
    public function get_quiz_questions() {
        $questions = array(
            array(
                'id' => 1,
                'question' => 'القديس برفوريوس اتولد في انهي مدينة؟',
                'options' => array('الاسكندرية', 'افسس', 'روما', 'انطاكية'),
                'correct' => 1,
            ),
            array(
                'id' => 2,
                'question' => 'ايه كان شغل القديس برفوريوس قبل ما يؤمن؟',
                'options' => array('صياد', 'جندي', 'بهلوان وممثل', 'تاجر'),
                'correct' => 2,
            ),
            array(
                'id' => 3,
                'question' => 'برفوريوس عاش في زمن انهي امبراطور؟',
                'options' => array('نيرون', 'دقلديانوس', 'يوليانوس الجاحد', 'قسطنطين'),
                'correct' => 2,
            ),
            array(
                'id' => 4,
                'question' => 'برفوريوس كان بيعمل ايه على المسرح قبل ايمانه؟',
                'options' => array('يبشر بالمسيحية', 'يسخر من المسيحيين', 'يعلم الفلسفة', 'يغني ترانيم'),
                'correct' => 1,
            ),
            array(
                'id' => 5,
                'question' => 'برفوريوس كان بيقلد ايه عشان يضحك الناس؟',
                'options' => array('القداس', 'الصلوات والمعمودية', 'الجنازات', 'الافراح'),
                'correct' => 1,
            ),
            array(
                'id' => 6,
                'question' => 'في انهي مناسبة حصلت معمودية برفوريوس؟',
                'options' => array('عيد الفصح', 'عيد ميلاد الامبراطور', 'راس السنة', 'حفل تتويج'),
                'correct' => 1,
            ),
            array(
                'id' => 7,
                'question' => 'برفوريوس رسم ايه قبل ما ينزل في الميه؟',
                'options' => array('دايرة', 'علامة الصليب', 'نجمة', 'مارسمش حاجة'),
                'correct' => 1,
            ),
            array(
                'id' => 8,
                'question' => 'برفوريوس نزل في الميه باسم مين؟',
                'options' => array('الامبراطور', 'الاب والابن والروح القدس', 'الالهة الرومانية', 'مانطقش بحاجة'),
                'correct' => 1,
            ),
            array(
                'id' => 9,
                'question' => 'برفوريوس لبس ايه لما طلع من الميه؟',
                'options' => array('احمر', 'ابيض', 'اسود', 'مالبسش حاجة'),
                'correct' => 1,
            ),
            array(
                'id' => 10,
                'question' => 'الناس كانت بتعمل ايه لما برفوريوس طلع من الميه؟',
                'options' => array('بيصلوا', 'بتضحك', 'بيعيطوا', 'ساكتين'),
                'correct' => 1,
            ),
            array(
                'id' => 11,
                'question' => 'برفوريوس قال ايه لما وقف قدام الامبراطور؟',
                'options' => array('انا بهلوان', 'انا مسيحي', 'انا روماني', 'انا اسف'),
                'correct' => 1,
            ),
            array(
                'id' => 12,
                'question' => 'الناس افتكرت كلام برفوريوس ايه في الاول؟',
                'options' => array('كلام جد', 'جزء من التمثيل', 'جنون', 'مزاح'),
                'correct' => 1,
            ),
            array(
                'id' => 13,
                'question' => 'برفوريوس شاف ايه وهو في الميه؟',
                'options' => array('ملايكة', 'نور ونعمة ربنا', 'حمامة', 'سحاب'),
                'correct' => 1,
            ),
            array(
                'id' => 14,
                'question' => 'ربنا عمل ايه لبرفوريوس وهو في الميه؟',
                'options' => array('كلمه', 'فتح عينه', 'ادله كتاب', 'مافيش حاجة'),
                'correct' => 1,
            ),
            array(
                'id' => 15,
                'question' => 'برفوريوس خرج من الميه ازاي؟',
                'options' => array('زي ما كان', 'انسان جديد', 'مريض', 'حزين'),
                'correct' => 1,
            ),
            array(
                'id' => 16,
                'question' => 'الامبراطور حاول يغير راي برفوريوس بايه؟',
                'options' => array('الفلوس بس', 'التهديد بس', 'التهديد والاغراء', 'ماحاولش'),
                'correct' => 2,
            ),
            array(
                'id' => 17,
                'question' => 'الامبراطور وعد برفوريوس بايه؟',
                'options' => array('الحرية', 'الفلوس والكرامة', 'السفر', 'ماوعدوش بحاجة'),
                'correct' => 1,
            ),
            array(
                'id' => 18,
                'question' => 'برفوريوس عمل ايه لما الامبراطور حاول يغير رايه؟',
                'options' => array('وافق', 'رفض ينكر ايمانه', 'هرب', 'طلب وقت'),
                'correct' => 1,
            ),
            array(
                'id' => 19,
                'question' => 'في الاخر الامبراطور امر بايه؟',
                'options' => array('اطلاق سراحه', 'قطع راسه', 'سجنه', 'نفيه'),
                'correct' => 1,
            ),
            array(
                'id' => 20,
                'question' => 'القديس برفوريوس استشهد يوم كام توت؟',
                'options' => array('15 توت', '18 توت', '21 توت', '25 توت'),
                'correct' => 1,
            ),
            array(
                'id' => 21,
                'question' => 'القديس برفوريوس استشهد يوم كام سبتمبر؟',
                'options' => array('28 اغسطس', '28 سبتمبر', '28 اكتوبر', '28 نوفمبر'),
                'correct' => 1,
            ),
            array(
                'id' => 22,
                'question' => 'القديس برفوريوس شفيع مين؟',
                'options' => array('الممثلين', 'المكتئبين', 'المسافرين', 'الاطفال'),
                'correct' => 1,
            ),
            array(
                'id' => 23,
                'question' => 'برفوريوس كان مشهور ليه؟',
                'options' => array('كان غني', 'شاطر في التمثيل والحركات الاستعراضية', 'من العيلة المالكة', 'قائد عسكري'),
                'correct' => 1,
            ),
            array(
                'id' => 24,
                'question' => 'افسس كانت فين؟',
                'options' => array('مصر', 'اسيا الصغرى', 'ايطاليا', 'اليونان'),
                'correct' => 1,
            ),
            array(
                'id' => 25,
                'question' => 'ايه اللقب اللي بيتعرف بيه القديس برفوريوس؟',
                'options' => array('الشهيد', 'البهلوان', 'المعترف', 'الناسك'),
                'correct' => 1,
            ),
        );
        
        return $questions;
    }
    
    /**
     * Get random quiz questions
     */
    public function get_random_quiz($count = 5) {
        $all_questions = $this->get_quiz_questions();
        shuffle($all_questions);
        return array_slice($all_questions, 0, $count);
    }
    
    /**
     * Validate quiz answers
     */
    public function validate_quiz_answers($user_id, $answers) {
        $all_questions = $this->get_quiz_questions();
        $questions_map = array();
        foreach ($all_questions as $q) {
            $questions_map[$q['id']] = $q['correct'];
        }
        
        $correct = 0;
        $total = count($answers);
        
        foreach ($answers as $question_id => $answer) {
            if (isset($questions_map[$question_id]) && $questions_map[$question_id] == $answer) {
                $correct++;
            }
        }
        
        // Need at least 3 out of 5 correct (60%)
        $passed = ($correct >= 3);
        
        return array(
            'correct' => $correct,
            'total' => $total,
            'passed' => $passed,
            'percentage' => round(($correct / $total) * 100),
        );
    }
    // =============================================
    // Birthday Gift System
    // =============================================

    /**
     * Get available gift type definitions with icons
     */
    public function get_gift_types() {
        return array(
            'points' => array(
                'label' => __('نقاط', 'saint-porphyrius'),
                'icon'  => '⭐',
            ),
            'money' => array(
                'label' => __('مبلغ مالي', 'saint-porphyrius'),
                'icon'  => '💰',
            ),
            'gift' => array(
                'label' => __('هدية عينية', 'saint-porphyrius'),
                'icon'  => '🎁',
            ),
            'voucher' => array(
                'label' => __('قسيمة شراء', 'saint-porphyrius'),
                'icon'  => '🎟️',
            ),
            'other' => array(
                'label' => __('أخرى', 'saint-porphyrius'),
                'icon'  => '🎀',
            ),
        );
    }

    /**
     * Get all birthday gifts (optionally only active ones)
     */
    public function get_birthday_gifts($active_only = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_birthday_gifts';

        $where = $active_only ? 'WHERE is_active = 1' : '';
        return $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY sort_order ASC, id ASC");
    }

    /**
     * Get a single birthday gift by ID
     */
    public function get_birthday_gift($gift_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_birthday_gifts';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $gift_id));
    }

    /**
     * Create a birthday gift option
     */
    public function create_birthday_gift($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_birthday_gifts';

        $result = $wpdb->insert($table, array(
            'title'       => sanitize_text_field($data['title']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'gift_type'   => sanitize_text_field($data['gift_type']),
            'icon'        => mb_substr(sanitize_text_field($data['icon'] ?? '🎁'), 0, 10),
            'value'       => sanitize_text_field($data['value'] ?? ''),
            'is_active'   => !empty($data['is_active']) ? 1 : 0,
            'sort_order'  => absint($data['sort_order'] ?? 0),
        ));

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update a birthday gift option
     */
    public function update_birthday_gift($gift_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_birthday_gifts';

        $update = array();
        if (isset($data['title']))       $update['title'] = sanitize_text_field($data['title']);
        if (isset($data['description'])) $update['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['gift_type']))   $update['gift_type'] = sanitize_text_field($data['gift_type']);
        if (isset($data['icon']))        $update['icon'] = mb_substr(sanitize_text_field($data['icon']), 0, 10);
        if (isset($data['value']))       $update['value'] = sanitize_text_field($data['value']);
        if (isset($data['is_active']))   $update['is_active'] = !empty($data['is_active']) ? 1 : 0;
        if (isset($data['sort_order']))  $update['sort_order'] = absint($data['sort_order']);

        if (empty($update)) return false;

        return $wpdb->update($table, $update, array('id' => absint($gift_id)));
    }

    /**
     * Delete a birthday gift option
     */
    public function delete_birthday_gift($gift_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_birthday_gifts';
        return $wpdb->delete($table, array('id' => absint($gift_id)));
    }

    /**
     * Check if user has already claimed a birthday gift this year
     */
    public function has_claimed_birthday_gift($user_id, $year = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_birthday_gift_claims';
        $year = $year ?: date('Y');

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND claim_year = %s",
            $user_id, $year
        ));
    }

    /**
     * Get user's claimed gift for a year
     */
    public function get_user_birthday_gift_claim($user_id, $year = null) {
        global $wpdb;
        $claims_table = $wpdb->prefix . 'sp_birthday_gift_claims';
        $gifts_table = $wpdb->prefix . 'sp_birthday_gifts';
        $year = $year ?: date('Y');

        return $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, g.title, g.description, g.gift_type, g.icon, g.value
             FROM {$claims_table} c
             JOIN {$gifts_table} g ON g.id = c.gift_id
             WHERE c.user_id = %d AND c.claim_year = %s",
            $user_id, $year
        ));
    }

    /**
     * Claim a birthday gift (user picks one gift on their birthday)
     */
    public function claim_birthday_gift($user_id, $gift_id) {
        global $wpdb;
        $claims_table = $wpdb->prefix . 'sp_birthday_gift_claims';
        $current_year = date('Y');

        // Must be birthday period
        $period = $this->is_birthday_period($user_id);
        if (!$period) {
            return array('success' => false, 'message' => __('يمكنك اختيار الهدية فقط في فترة عيد ميلادك', 'saint-porphyrius'));
        }

        // Already claimed this year?
        if ($this->has_claimed_birthday_gift($user_id, $current_year)) {
            return array('success' => false, 'message' => __('لقد اخترت هديتك بالفعل هذا العام', 'saint-porphyrius'));
        }

        // Gift must exist and be active
        $gift = $this->get_birthday_gift($gift_id);
        if (!$gift || !$gift->is_active) {
            return array('success' => false, 'message' => __('هذه الهدية غير متاحة', 'saint-porphyrius'));
        }

        // Record the claim
        $result = $wpdb->insert($claims_table, array(
            'user_id'    => absint($user_id),
            'gift_id'    => absint($gift_id),
            'claim_year' => $current_year,
        ));

        if (!$result) {
            return array('success' => false, 'message' => __('حدث خطأ أثناء حفظ اختيارك', 'saint-porphyrius'));
        }

        // If gift type is points, award them automatically
        if ($gift->gift_type === 'points' && is_numeric($gift->value) && intval($gift->value) > 0) {
            $points_handler = SP_Points::get_instance();
            $points_handler->add(
                $user_id,
                intval($gift->value),
                'birthday_gift',
                null,
                sprintf(__('هدية عيد ميلاد: %s 🎂', 'saint-porphyrius'), $gift->title)
            );
        }

        return array(
            'success' => true,
            'message' => sprintf(__('تم اختيار هديتك: %s 🎉', 'saint-porphyrius'), $gift->title),
            'gift'    => $gift,
        );
    }

    // =============================================
    // Birthday Congratulations (Member-to-Member)
    // =============================================

    /**
     * Get members whose birthday falls within the 3-day window (yesterday, today, tomorrow)
     * Excludes the requesting user
     */
    public function get_birthday_members($exclude_user_id = 0) {
        $today = new DateTime();
        $yesterday = (clone $today)->modify('-1 day');
        $tomorrow = (clone $today)->modify('+1 day');

        $dates = array(
            $yesterday->format('m-d'),
            $today->format('m-d'),
            $tomorrow->format('m-d'),
        );

        $members = get_users(array(
            'role' => 'sp_member',
            'meta_key' => 'sp_birth_date',
            'exclude' => $exclude_user_id ? array($exclude_user_id) : array(),
        ));

        $birthday_members = array();
        foreach ($members as $member) {
            $birth_date = get_user_meta($member->ID, 'sp_birth_date', true);
            if (empty($birth_date)) {
                continue;
            }
            $birth = DateTime::createFromFormat('Y-m-d', $birth_date);
            if (!$birth) {
                continue;
            }
            $md = $birth->format('m-d');
            if (in_array($md, $dates)) {
                $is_today = ($md === $today->format('m-d'));
                $gender = get_user_meta($member->ID, 'sp_gender', true) ?: 'male';

                $profile_img = '';
                if (class_exists('SP_Social_Profile')) {
                    $profile_img = SP_Social_Profile::get_instance()->get_profile_image_url($member->ID);
                }

                $birthday_members[] = array(
                    'user_id'     => $member->ID,
                    'first_name'  => $member->first_name,
                    'middle_name' => get_user_meta($member->ID, 'sp_middle_name', true),
                    'last_name'   => $member->last_name,
                    'gender'      => $gender,
                    'is_today'    => $is_today,
                    'profile_img' => $profile_img,
                );
            }
        }

        // Sort: today's birthdays first
        usort($birthday_members, function ($a, $b) {
            return $b['is_today'] - $a['is_today'];
        });

        return $birthday_members;
    }

    /**
     * Check if sender already congratulated recipient this year
     */
    public function has_congratulated($sender_id, $recipient_id, $year = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_birthday_congratulations';
        $year = $year ?: date('Y');

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE sender_id = %d AND recipient_id = %d AND gift_year = %s",
            $sender_id, $recipient_id, $year
        ));
    }

    /**
     * Send birthday congratulation points from one member to another
     */
    public function send_birthday_congratulation($sender_id, $recipient_id, $points, $message = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_birthday_congratulations';
        $current_year = date('Y');

        $points = absint($points);
        if ($points < 1) {
            return array('success' => false, 'message' => __('يجب إرسال نقطة واحدة على الأقل', 'saint-porphyrius'));
        }

        if ($sender_id == $recipient_id) {
            return array('success' => false, 'message' => __('لا يمكنك تهنئة نفسك', 'saint-porphyrius'));
        }

        // Recipient must be in birthday period
        $period = $this->is_birthday_period($recipient_id);
        if (!$period) {
            return array('success' => false, 'message' => __('هذا العضو ليس في فترة عيد ميلاده', 'saint-porphyrius'));
        }

        // Already congratulated this year?
        if ($this->has_congratulated($sender_id, $recipient_id, $current_year)) {
            return array('success' => false, 'message' => __('لقد هنأت هذا العضو بالفعل هذا العام', 'saint-porphyrius'));
        }

        // Check sender balance
        $points_handler = SP_Points::get_instance();
        $sender_balance = $points_handler->get_balance($sender_id);
        if ($sender_balance < $points) {
            return array('success' => false, 'message' => __('رصيدك غير كافي', 'saint-porphyrius'));
        }

        $message = sanitize_text_field(mb_substr($message, 0, 191));

        // Get names for log
        $recipient_user = get_userdata($recipient_id);
        $recipient_first = $recipient_user->first_name;
        $recipient_middle = get_user_meta($recipient_id, 'sp_middle_name', true);
        $recipient_name = trim($recipient_first . ' ' . $recipient_middle) ?: $recipient_user->display_name;

        $sender_user = get_userdata($sender_id);
        $sender_first = $sender_user->first_name;
        $sender_middle = get_user_meta($sender_id, 'sp_middle_name', true);
        $sender_name = trim($sender_first . ' ' . $sender_middle) ?: $sender_user->display_name;

        // Record the congratulation
        $result = $wpdb->insert($table, array(
            'sender_id'    => absint($sender_id),
            'recipient_id' => absint($recipient_id),
            'points'       => $points,
            'message'      => $message,
            'gift_year'    => $current_year,
        ));

        if (!$result) {
            return array('success' => false, 'message' => __('حدث خطأ أثناء حفظ التهنئة', 'saint-porphyrius'));
        }

        // Deduct from sender
        $sender_reason = sprintf('تهنئة عيد ميلاد لـ %s في برفوريوس 🎂', $recipient_name);
        $sender_result = $points_handler->add($sender_id, -$points, 'birthday_congrats_sent', null, $sender_reason);

        if (is_wp_error($sender_result)) {
            // Rollback the congratulation record
            $wpdb->delete($table, array('id' => $wpdb->insert_id));
            return array('success' => false, 'message' => __('حدث خطأ أثناء خصم النقاط', 'saint-porphyrius'));
        }

        // Add to recipient
        $recipient_reason = sprintf('تهنئة عيد ميلاد من %s في برفوريوس 🎁', $sender_name);
        if ($message) {
            $recipient_reason .= ' - ' . $message;
        }
        $recipient_result = $points_handler->add($recipient_id, $points, 'birthday_congrats_received', null, $recipient_reason);

        if (is_wp_error($recipient_result)) {
            // Rollback sender deduction and congratulation record
            $points_handler->add($sender_id, $points, 'birthday_congrats_refund', null, 'استرداد تهنئة فاشلة');
            $wpdb->delete($table, array('id' => $wpdb->insert_id));
            return array('success' => false, 'message' => __('حدث خطأ أثناء إضافة النقاط للعضو', 'saint-porphyrius'));
        }

        // Send bell + push notifications
        $this->notify_birthday_congratulation($sender_id, $recipient_id, $sender_name, $recipient_name, $points, $message);

        return array(
            'success'     => true,
            'message'     => sprintf(__('تم إرسال %d نقطة لـ %s 🎉', 'saint-porphyrius'), $points, $recipient_name),
            'new_balance' => $points_handler->get_balance($sender_id),
        );
    }

    /**
     * Get congratulations received by a user for a year
     */
    public function get_congratulations_received($recipient_id, $year = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_birthday_congratulations';
        $year = $year ?: date('Y');

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name as sender_name
             FROM {$table} c
             JOIN {$wpdb->users} u ON u.ID = c.sender_id
             WHERE c.recipient_id = %d AND c.gift_year = %s
             ORDER BY c.created_at DESC",
            $recipient_id, $year
        ));
    }

    /**
     * Send bell (in-app) + push notifications for a birthday congratulation
     */
    private function notify_birthday_congratulation($sender_id, $recipient_id, $sender_name, $recipient_name, $points, $message = '') {
        $notifications = SP_Notifications::get_instance();
        $dashboard_url = home_url('/app/dashboard');
        $points_url = home_url('/app/points');

        // --- Notification to recipient ---
        $recipient_title = '🎂 تهنئة عيد ميلاد!';
        $recipient_msg = sprintf(
            __('%s من أسرة برفوريوس هنأك بعيد ميلادك وأرسلك %d نقطة 🎁', 'saint-porphyrius'),
            $sender_name,
            $points
        );
        if ($message) {
            $recipient_msg .= ' — "' . $message . '"';
        }

        $notifications->create_inbox_notification(array(
            'user_id' => $recipient_id,
            'title'   => $recipient_title,
            'message' => $recipient_msg,
            'icon'    => '🎂',
            'type'    => 'system',
            'url'     => $points_url,
        ));

        if ($notifications->is_configured()) {
            $notifications->send_to_users(array($recipient_id), $recipient_title, $recipient_msg, $points_url, 'auto_birthday');
        }

        // --- Confirmation to sender ---
        $sender_title = '🎁 تم إرسال تهنئتك';
        $sender_msg = sprintf(
            __('تم إرسال %d نقطة لـ %s بمناسبة عيد ميلاده 🎉', 'saint-porphyrius'),
            $points,
            $recipient_name
        );

        $notifications->create_inbox_notification(array(
            'user_id' => $sender_id,
            'title'   => $sender_title,
            'message' => $sender_msg,
            'icon'    => '🎁',
            'type'    => 'system',
            'url'     => $dashboard_url,
        ));

        if ($notifications->is_configured()) {
            $notifications->send_to_users(array($sender_id), $sender_title, $sender_msg, $dashboard_url, 'auto_birthday');
        }
    }

    /**
     * Broadcast a birthday announcement to all members (bell + push)
     * Runs once per birthday-person per year (guarded by sp_birthday_announced_{year} meta)
     */
    private function broadcast_birthday_announcement($user_id) {
        $current_year = date('Y');
        $meta_key = 'sp_birthday_announced_' . $current_year;

        // Only broadcast once per user per year
        if (get_user_meta($user_id, $meta_key, true)) {
            return;
        }
        update_user_meta($user_id, $meta_key, 1);

        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $first_name = $user->first_name;
        $middle_name = get_user_meta($user_id, 'sp_middle_name', true);
        $display = trim($first_name . ' ' . $middle_name) ?: $user->display_name;
        $gender = get_user_meta($user_id, 'sp_gender', true) ?: 'male';

        if ($gender === 'female') {
            $title = '🎂 عيد ميلاد سعيد!';
            $msg = sprintf(
                __('بنت برفوريوس %s! النهاردة عيد ميلادها 🎂 هنئيها وابعتيلها هدية نقاط 🎁', 'saint-porphyrius'),
                $display
            );
        } else {
            $title = '🎂 عيد ميلاد سعيد!';
            $msg = sprintf(
                __('ابن برفوريوس %s! النهاردة عيد ميلاده 🎂 هنئه وابعتله هدية نقاط من الداشبورد 🎁', 'saint-porphyrius'),
                $display
            );
        }

        $url = home_url('/app/dashboard');
        $notifications = SP_Notifications::get_instance();

        // Broadcast bell notification to all (user_id = 0)
        $notifications->create_inbox_notification(array(
            'user_id' => 0,
            'title'   => $title,
            'message' => $msg,
            'icon'    => '🎂',
            'type'    => 'system',
            'url'     => $url,
        ));

        // Push notification to all
        if ($notifications->is_configured()) {
            $notifications->send_to_all($title, $msg, $url, array(), 'auto_birthday');
        }
    }

    /**
     * Get birthday gift claims for admin reporting
     */
    public function get_gift_claims($args = array()) {
        global $wpdb;
        $claims_table = $wpdb->prefix . 'sp_birthday_gift_claims';
        $gifts_table = $wpdb->prefix . 'sp_birthday_gifts';

        $year = $args['year'] ?? date('Y');
        $limit = absint($args['limit'] ?? 50);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, g.title as gift_title, g.gift_type, g.icon, g.value,
                    u.display_name
             FROM {$claims_table} c
             JOIN {$gifts_table} g ON g.id = c.gift_id
             JOIN {$wpdb->users} u ON u.ID = c.user_id
             WHERE c.claim_year = %s
             ORDER BY c.claimed_at DESC
             LIMIT %d",
            $year, $limit
        ));
    }
}

// Initialize
SP_Gamification::get_instance();

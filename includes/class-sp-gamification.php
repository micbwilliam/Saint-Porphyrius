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
            'profile_completion_enabled' => 1,
            'birthday_reward_enabled' => 1,
            'story_quiz_enabled' => 1,
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
        $settings['profile_completion_enabled'] = !empty($settings['profile_completion_enabled']) ? 1 : 0;
        $settings['birthday_reward_enabled'] = !empty($settings['birthday_reward_enabled']) ? 1 : 0;
        $settings['story_quiz_enabled'] = !empty($settings['story_quiz_enabled']) ? 1 : 0;
        
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
                'male' => '🎂 بكرة عيد ميلادك! كل سنة وانت طيب',
                'female' => '🎂 بكرة عيد ميلادك! كل سنة وانتي طيبة',
            ),
            'birthday' => array(
                'male' => '🎉🎂 عيد ميلاد سعيد! كل سنة وانت طيب يا حبيبنا',
                'female' => '🎉🎂 عيد ميلاد سعيد! كل سنة وانتي طيبة يا حبيبتنا',
            ),
            'day_after' => array(
                'male' => '🎂 عقبال 100 سنة! كل سنة وانت طيب',
                'female' => '🎂 عقبال 100 سنة! كل سنة وانتي طيبة',
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
            __('هدية عيد الميلاد 🎂', 'saint-porphyrius')
        );
        
        if (!is_wp_error($result)) {
            update_user_meta($user_id, 'sp_birthday_rewarded_year', $current_year);
            return $result;
        }
        
        return false;
    }
    
    /**
     * Check rewards on login (birthday + profile completion)
     */
    public function check_rewards_on_login($user_login, $user) {
        $this->award_birthday_points($user->ID);
        $this->award_profile_completion($user->ID);
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
            __('مكافأة إكمال الملف الشخصي 🏆', 'saint-porphyrius')
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
            __('مكافأة قراءة قصة القديس برفوريوس البهلوان 📖', 'saint-porphyrius')
        );
        
        if (!is_wp_error($result)) {
            update_user_meta($user_id, 'sp_story_quiz_completed', 1);
            update_user_meta($user_id, 'sp_story_quiz_completed_at', current_time('mysql'));
            return $result;
        }
        
        return false;
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
<p>وُلد القديس برفوريوس في مدينة أفسس بآسيا الصغرى، وكان يعمل بهلواناً ومُمثلاً في المسارح الرومانية. كان بارعاً في فنون التمثيل والألعاب البهلوانية، مما جعله مشهوراً في أنحاء الإمبراطورية الرومانية.</p>

<h3>🎭 حياته كبهلوان وثني</h3>
<p>عمل برفوريوس في عهد الإمبراطور يوليانوس الجاحد (361-363م)، وكان من أشهر الممثلين الوثنيين الذين يسخرون من المسيحيين في عروضهم المسرحية. كان يُقلد طقوس المعمودية والصلوات المسيحية ليُضحك الجماهير الوثنية كنوع من السخرية والتهكم.</p>

<h3>🎂 حفل عيد ميلاد الإمبراطور</h3>
<p>جَمَعَ الإمبراطور يوليانوس الجاحد في عيد ميلاده أرباب الملاهي العالمية المشهورين، وكان من بينهم ممثل وثني يدعى <strong>بروفوريوس</strong> أو <strong>بورفيروس (Porphyry)</strong>، وكان من عادة الوثنيين تقليد المسيحيين كنوعٍ من السخرية.</p>

<h3>✝️ معموديته المعجزية</h3>
<p>فإذ بلغ تقليد المعمودية بنوع من التهكم <strong>رشم على المياه علامة الصليب باسم الآب والابن والروح القدس</strong> ثم غطس فيها، وصعد ليلبس الثياب البيضاء، وكان الكل يضحك ساخرًا.</p>

<p>ثم وقف برفوريوس أمام الإمبراطور يشهد أنه مسيحي، فحسب ذلك أحد أدوار التمثيلية، لكنه صار يشدد أنه مسيحي. دُهش الملك وكل الحاضرين، وإذ رآه جادًا في حديثه سأله عن السبب.</p>

<p>فأجاب برفوريوس أنه <strong>إذ غطس في المياه أبصر نعمة الله حالة على المياه، وأضاء الرب عقله، وأن نورًا كان يشع من المياه.</strong></p>

<p>خرج من الماء إنساناً جديداً، وصرخ أمام الجميع: <strong>"أنا مسيحي! أنا مسيحي!"</strong></p>

<h3>🔥 إيمانه الراسخ</h3>
<p>إذ شعر الإمبراطور أن من جاء به ليسخر بالمسيحيين صار كارزًا بالمسيحية على مشهد من العظماء وكل الشعب، صار يتوعد الرجل ويهدده في ثورة عنيفة، أما برفوريوس ففي أدب حازم تمسك بالإيمان الجديد.</p>

<p>بدأ الملك يلاطفه واعدًا إياه بعطايا جزيلة وكرامات فلم يجحد مسيحه، عندئذ <strong>أمر بقطع رأسه</strong>.</p>

<h3>⚔️ استشهاده</h3>
<p>أمر الإمبراطور بتعذيبه بأشد أنواع العذاب:
<ul>
<li>ضُرب بالسياط الحديدية</li>
<li>وُضع على سرير من نار</li>
<li>سُلخ جلده</li>
<li>قُطعت أطرافه</li>
</ul>
</p>

<p>وفي كل ذلك كان القديس يُسبّح الله ويشكره. وأخيراً، قُطعت رأسه المقدسة في يوم 18 توت (28 سبتمبر)، ونال إكليل الشهادة.</p>

<h3>🕊️ عظاته</h3>
<p>تُعلمنا قصة القديس برفوريوس أن:
<ul>
<li><strong>الله يستطيع أن يُحوّل أي إنسان:</strong> حتى من كان يسخر من الإيمان</li>
<li><strong>المعمودية سر حقيقي:</strong> له قوة تغييرية فعلية</li>
<li><strong>الإيمان الحقيقي يستحق التضحية:</strong> حتى بالحياة نفسها</li>
<li><strong>النعمة الإلهية أقوى من كل شيء:</strong> تغلب على الماضي والخطية</li>
</ul>
</p>

<h3>🙏 طلبته تكون معنا</h3>
<p style="text-align: center; font-size: 1.1em; color: var(--sp-primary);">
<strong>بركة صلوات القديس العظيم برفوريوس البهلوان تكون مع جميعنا. آمين.</strong>
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
                'question' => 'في أي مدينة وُلد القديس برفوريوس؟',
                'options' => array('الإسكندرية', 'أفسس', 'روما', 'أنطاكية'),
                'correct' => 1,
            ),
            array(
                'id' => 2,
                'question' => 'ما هي مهنة القديس برفوريوس قبل إيمانه؟',
                'options' => array('صياد', 'جندي', 'بهلوان ومُمثل', 'تاجر'),
                'correct' => 2,
            ),
            array(
                'id' => 3,
                'question' => 'في عهد أي إمبراطور عاش القديس برفوريوس؟',
                'options' => array('نيرون', 'دقلديانوس', 'يوليانوس الجاحد', 'قسطنطين'),
                'correct' => 2,
            ),
            array(
                'id' => 4,
                'question' => 'ماذا كان برفوريوس يفعل على المسرح قبل إيمانه؟',
                'options' => array('يُبشر بالمسيحية', 'يسخر من المسيحيين', 'يُعلم الفلسفة', 'يُغني الترانيم'),
                'correct' => 1,
            ),
            array(
                'id' => 5,
                'question' => 'ما هو السر الكنسي الذي كان برفوريوس يُقلده على المسرح؟',
                'options' => array('سر الميرون', 'سر التناول', 'سر المعمودية', 'سر الاعتراف'),
                'correct' => 2,
            ),
            array(
                'id' => 6,
                'question' => 'ماذا حدث لبرفوريوس عندما نزل في الماء؟',
                'options' => array('غرق', 'مرض', 'حلّ عليه الروح القدس', 'هرب'),
                'correct' => 2,
            ),
            array(
                'id' => 7,
                'question' => 'ماذا صرخ برفوريوس بعد خروجه من الماء؟',
                'options' => array('أنا مسيحي!', 'أنا بهلوان!', 'أنا روماني!', 'أنا حر!'),
                'correct' => 0,
            ),
            array(
                'id' => 8,
                'question' => 'هل قبل برفوريوس العودة عن إيمانه عندما عرض عليه الإمبراطور ذلك؟',
                'options' => array('نعم، قبل فوراً', 'لا، رفض بثبات', 'طلب مهلة للتفكير', 'هرب من القصر'),
                'correct' => 1,
            ),
            array(
                'id' => 9,
                'question' => 'ما هو اليوم القبطي لتذكار استشهاد القديس برفوريوس؟',
                'options' => array('15 توت', '18 توت', '21 توت', '25 توت'),
                'correct' => 1,
            ),
            array(
                'id' => 10,
                'question' => 'ما هو اليوم الميلادي لتذكار القديس برفوريوس؟',
                'options' => array('28 أغسطس', '28 سبتمبر', '28 أكتوبر', '28 نوفمبر'),
                'correct' => 1,
            ),
            array(
                'id' => 11,
                'question' => 'في أي مناسبة حدثت معمودية القديس برفوريوس؟',
                'options' => array('عيد الفصح', 'عيد ميلاد الإمبراطور', 'عيد رأس السنة', 'حفل تتويج'),
                'correct' => 1,
            ),
            array(
                'id' => 12,
                'question' => 'ماذا رأى برفوريوس عندما اعتمد؟',
                'options' => array('ملائكة', 'نوراً يشع من المياه', 'حمامة', 'سحاب'),
                'correct' => 1,
            ),
            array(
                'id' => 13,
                'question' => 'ماذا قال برفوريوس أنه سمعه من السماء؟',
                'options' => array('أنت ابني الحبيب', 'أنت قد اغتسلت ونلت الحياة الأبدية', 'ارجع للوثنية', 'أنت نبي'),
                'correct' => 1,
            ),
            array(
                'id' => 14,
                'question' => 'كيف انتهت حياة القديس برفوريوس على الأرض؟',
                'options' => array('مات بسلام', 'قُطعت رأسه', 'غرق في البحر', 'أُحرق'),
                'correct' => 1,
            ),
            array(
                'id' => 15,
                'question' => 'ماذا كان يفعل القديس وهو يُعذَب؟',
                'options' => array('يصرخ من الألم', 'يُسبّح الله ويشكره', 'يطلب الرحمة', 'ينكر إيمانه'),
                'correct' => 1,
            ),
            array(
                'id' => 16,
                'question' => 'ما هو أحد أنواع العذاب التي تعرض لها القديس؟',
                'options' => array('الصلب', 'الإلقاء للأسود', 'وُضع على سرير من نار', 'الرجم بالحجارة'),
                'correct' => 2,
            ),
            array(
                'id' => 17,
                'question' => 'ما هي إحدى العظات من قصة القديس برفوريوس؟',
                'options' => array('لا تذهب للمسرح', 'الله يستطيع أن يُحوّل أي إنسان', 'الماء مقدس', 'كن بهلواناً'),
                'correct' => 1,
            ),
            array(
                'id' => 18,
                'question' => 'لماذا طلب الجمهور من برفوريوس تقليد المعمودية؟',
                'options' => array('ليتعلموا عنها', 'للسخرية من المسيحيين', 'ليعتمدوا', 'للصلاة'),
                'correct' => 1,
            ),
            array(
                'id' => 19,
                'question' => 'ما هي الفترة الزمنية التي حكم فيها يوليانوس الجاحد؟',
                'options' => array('361-363م', '300-310م', '400-410م', '250-260م'),
                'correct' => 0,
            ),
            array(
                'id' => 20,
                'question' => 'ماذا تُعلمنا قصة القديس عن المعمودية؟',
                'options' => array('هي مجرد رمز', 'هي سر حقيقي له قوة تغييرية', 'ليست مهمة', 'للأطفال فقط'),
                'correct' => 1,
            ),
            array(
                'id' => 21,
                'question' => 'كيف وصف القديس برفوريوس نفسه بعد المعمودية؟',
                'options' => array('بهلوان', 'ممثل', 'مسيحي', 'روماني'),
                'correct' => 2,
            ),
            array(
                'id' => 22,
                'question' => 'ما هو موقف الإمبراطور من تحول برفوريوس؟',
                'options' => array('فرح به', 'لم يستطع تصديقه', 'باركه', 'أطلقه حراً'),
                'correct' => 1,
            ),
            array(
                'id' => 23,
                'question' => 'ماذا حاول الإمبراطور أن يفعل مع برفوريوس؟',
                'options' => array('قتله فوراً', 'إقناعه بالعودة عن إيمانه', 'تعيينه وزيراً', 'إرساله للحرب'),
                'correct' => 1,
            ),
            array(
                'id' => 24,
                'question' => 'ما هي وسائل الإمبراطور لإقناع برفوريوس؟',
                'options' => array('المال فقط', 'التهديد فقط', 'الوعود والتهديدات', 'لم يحاول إقناعه'),
                'correct' => 2,
            ),
            array(
                'id' => 25,
                'question' => 'ماذا كان يُقلد برفوريوس من الطقوس المسيحية؟',
                'options' => array('القداس', 'المعمودية والصلوات', 'الجنازات', 'الأعراس'),
                'correct' => 1,
            ),
            array(
                'id' => 26,
                'question' => 'من كان الجمهور الذي يُشاهد عروض برفوريوس؟',
                'options' => array('المسيحيين', 'الجماهير الوثنية', 'الأطفال فقط', 'الفلاسفة'),
                'correct' => 1,
            ),
            array(
                'id' => 27,
                'question' => 'ما هي المنطقة التي وُلد فيها القديس برفوريوس؟',
                'options' => array('مصر', 'آسيا الصغرى', 'إيطاليا', 'اليونان'),
                'correct' => 1,
            ),
            array(
                'id' => 28,
                'question' => 'لماذا كان برفوريوس مشهوراً؟',
                'options' => array('كان غنياً', 'كان بارعاً في التمثيل والألعاب البهلوانية', 'كان من العائلة الملكية', 'كان قائداً عسكرياً'),
                'correct' => 1,
            ),
            array(
                'id' => 29,
                'question' => 'ماذا حدث لعقل برفوريوس عندما اعتمد؟',
                'options' => array('أصيب بالذهول', 'أضاء الرب عقله', 'فقد وعيه', 'بقي كما هو'),
                'correct' => 1,
            ),
            array(
                'id' => 30,
                'question' => 'هل ضُرب القديس برفوريوس بالسياط؟',
                'options' => array('لا', 'نعم، بالسياط الحديدية', 'لا نعرف', 'سياط خفيفة فقط'),
                'correct' => 1,
            ),
            array(
                'id' => 31,
                'question' => 'هل سُلخ جلد القديس برفوريوس؟',
                'options' => array('لا', 'نعم', 'جزئياً', 'لا نعرف'),
                'correct' => 1,
            ),
            array(
                'id' => 32,
                'question' => 'ما هي النتيجة النهائية لثبات القديس على إيمانه؟',
                'options' => array('أُطلق سراحه', 'نال إكليل الشهادة', 'صار إمبراطوراً', 'هرب'),
                'correct' => 1,
            ),
            array(
                'id' => 33,
                'question' => 'ما الذي يُعلمنا إياه ثبات القديس برفوريوس؟',
                'options' => array('الإيمان الحقيقي يستحق التضحية', 'الهروب أفضل', 'الإنكار مقبول', 'العذاب سيء'),
                'correct' => 0,
            ),
            array(
                'id' => 34,
                'question' => 'ما هي قوة النعمة الإلهية حسب قصة القديس؟',
                'options' => array('ضعيفة', 'محدودة', 'أقوى من كل شيء وتغلب على الماضي والخطية', 'غير موجودة'),
                'correct' => 2,
            ),
            array(
                'id' => 35,
                'question' => 'ما هو المكان الذي اعتمد فيه برفوريوس؟',
                'options' => array('نهر النيل', 'حوض على المسرح في حفلة الإمبراطور', 'كنيسة', 'بركة'),
                'correct' => 1,
            ),
            array(
                'id' => 36,
                'question' => 'هل كان برفوريوس يعرف المسيحية قبل معموديته؟',
                'options' => array('لا يعرف شيئاً', 'كان يعرفها ليسخر منها', 'كان مسيحياً سراً', 'كان يدرسها'),
                'correct' => 1,
            ),
            array(
                'id' => 37,
                'question' => 'ما هي علاقة يوليانوس الجاحد بالمسيحية؟',
                'options' => array('كان مسيحياً', 'كان يضطهد المسيحيين', 'كان محايداً', 'كان يُبشر بها'),
                'correct' => 1,
            ),
            array(
                'id' => 38,
                'question' => 'لماذا سُمي يوليانوس بـ"الجاحد"؟',
                'options' => array('لأنه كان كريماً', 'لأنه ارتد عن المسيحية', 'لأنه كان عادلاً', 'لأنه كان شجاعاً'),
                'correct' => 1,
            ),
            array(
                'id' => 39,
                'question' => 'ما هو الهدف من تقليد برفوريوس للمعمودية على المسرح؟',
                'options' => array('التبشير', 'إضحاك الجماهير', 'التعليم', 'الصلاة'),
                'correct' => 1,
            ),
            array(
                'id' => 40,
                'question' => 'ما هو حوض الماء الذي نزل فيه برفوريوس؟',
                'options' => array('نهر النيل', 'البحر', 'حوض على المسرح', 'بركة سلوام'),
                'correct' => 2,
            ),
            array(
                'id' => 41,
                'question' => 'بماذا نطق برفوريوس عندما نزل في الماء؟',
                'options' => array('كلمات سحرية', 'باسم الآب والابن والروح القدس', 'كلمات وثنية', 'لم ينطق شيئاً'),
                'correct' => 1,
            ),
            array(
                'id' => 42,
                'question' => 'هل كانت معمودية برفوريوس صحيحة رغم أنها كانت على المسرح؟',
                'options' => array('لا', 'نعم، الله قبلها', 'ليست معمودية', 'لا نعرف'),
                'correct' => 1,
            ),
            array(
                'id' => 43,
                'question' => 'ماذا قال برفوريوس عندما وقف أمام الإمبراطور بعد المعمودية؟',
                'options' => array('أنا عائد للوثنية', 'أنا مسيحي', 'أنا بهلوان', 'أنا أسف'),
                'correct' => 1,
            ),
            array(
                'id' => 44,
                'question' => 'كم مرة صرخ برفوريوس "أنا مسيحي" أمام الجميع؟',
                'options' => array('مرة واحدة', 'مرتين على الأقل', 'ثلاث مرات', 'لم يصرخ'),
                'correct' => 1,
            ),
            array(
                'id' => 45,
                'question' => 'ما هي ردة فعل الجمهور عندما أعلن برفوريوس إيمانه؟',
                'options' => array('فرحوا', 'صفقوا له', 'صُدموا', 'لم يهتموا'),
                'correct' => 2,
            ),
            array(
                'id' => 46,
                'question' => 'هل قُطعت أطراف القديس برفوريوس؟',
                'options' => array('لا', 'نعم', 'طرف واحد فقط', 'لا نعرف'),
                'correct' => 1,
            ),
            array(
                'id' => 47,
                'question' => 'ما هي الرسالة الأساسية من قصة القديس برفوريوس؟',
                'options' => array('لا تكن ممثلاً', 'الله يمكن أن يُغير أي شخص', 'ابتعد عن المسارح', 'كن حذراً'),
                'correct' => 1,
            ),
            array(
                'id' => 48,
                'question' => 'ما هي صلاتنا للقديس برفوريوس؟',
                'options' => array('أعطنا مالاً', 'بركة صلواته تكون معنا', 'ساعدنا في التمثيل', 'لا نصلي له'),
                'correct' => 1,
            ),
            array(
                'id' => 49,
                'question' => 'أي إكليل ناله القديس برفوريوس؟',
                'options' => array('إكليل الذهب', 'إكليل الشهادة', 'إكليل الغار', 'إكليل الملك'),
                'correct' => 1,
            ),
            array(
                'id' => 50,
                'question' => 'ما هو اللقب الذي يُعرف به القديس برفوريوس؟',
                'options' => array('الشهيد', 'البهلوان', 'المعترف', 'الناسك'),
                'correct' => 1,
            ),
            array(
                'id' => 51,
                'question' => 'من هم الذين جمعهم الإمبراطور يوليانوس في عيد ميلاده؟',
                'options' => array('القادة العسكريين', 'أرباب الملاهي العالمية المشهورين', 'رجال الدين', 'الفلاسفة'),
                'correct' => 1,
            ),
            array(
                'id' => 52,
                'question' => 'ماذا رشم برفوريوس على المياه قبل أن يغطس؟',
                'options' => array('دائرة', 'علامة الصليب', 'نجمة', 'لم يرشم شيئاً'),
                'correct' => 1,
            ),
            array(
                'id' => 53,
                'question' => 'ماذا لبس برفوريوس بعد خروجه من الماء؟',
                'options' => array('ثياباً حمراء', 'ثياباً بيضاء', 'ثياباً سوداء', 'لم يلبس شيئاً'),
                'correct' => 1,
            ),
            array(
                'id' => 54,
                'question' => 'ماذا أبصر برفوريوس على المياه؟',
                'options' => array('انعكاس صورته', 'نعمة الله حالة على المياه', 'أسماكاً', 'لا شيء'),
                'correct' => 1,
            ),
            array(
                'id' => 55,
                'question' => 'ما هو الاسم اللاتيني للقديس برفوريوس؟',
                'options' => array('Peter', 'Porphyry', 'Paul', 'Philip'),
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
}

// Initialize
SP_Gamification::get_instance();

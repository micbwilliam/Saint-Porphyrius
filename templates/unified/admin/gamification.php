<?php
/**
 * Saint Porphyrius - Admin Gamification Settings
 * Configure points for profile completion, birthday, and story quiz
 */

if (!defined('ABSPATH')) {
    exit;
}

$gamification = SP_Gamification::get_instance();
$settings = $gamification->get_settings();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_gamification_nonce'])) {
    if (wp_verify_nonce($_POST['sp_gamification_nonce'], 'sp_gamification_settings')) {
        $new_settings = array(
            'profile_completion_points' => absint($_POST['profile_completion_points'] ?? 50),
            'birthday_points' => absint($_POST['birthday_points'] ?? 20),
            'story_quiz_points' => absint($_POST['story_quiz_points'] ?? 25),
            'feast_day_points' => absint($_POST['feast_day_points'] ?? 100),
            'profile_completion_enabled' => !empty($_POST['profile_completion_enabled']) ? 1 : 0,
            'birthday_reward_enabled' => !empty($_POST['birthday_reward_enabled']) ? 1 : 0,
            'story_quiz_enabled' => !empty($_POST['story_quiz_enabled']) ? 1 : 0,
            'feast_day_reward_enabled' => !empty($_POST['feast_day_reward_enabled']) ? 1 : 0,
        );
        
        $settings = $gamification->update_settings($new_settings);
        $success_message = __('تم حفظ الإعدادات بنجاح', 'saint-porphyrius');
    }
}

// Get stats
global $wpdb;
$profile_complete_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'sp_profile_completion_rewarded' AND meta_value = '1'");
$birthday_rewarded_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'sp_birthday_rewarded_year' AND meta_value = %s", date('Y')));
$story_completed_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'sp_story_quiz_completed' AND meta_value = '1'");
$feast_day_rewarded_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'sp_feast_day_rewarded_year' AND meta_value = %s", date('Y')));
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('إعدادات المكافآت', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-actions"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content">
    <?php if (isset($success_message)): ?>
    <div class="sp-alert sp-alert-success" style="margin: var(--sp-space-lg);">
        <div class="sp-alert-icon">✅</div>
        <div class="sp-alert-content"><?php echo esc_html($success_message); ?></div>
    </div>
    <?php endif; ?>
    
    <!-- Stats Section -->
    <div class="sp-section" style="padding: var(--sp-space-lg);">
        <div class="sp-section-header">
            <h3 class="sp-section-title">📊 <?php _e('إحصائيات المكافآت', 'saint-porphyrius'); ?></h3>
        </div>
        <div class="sp-stats-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--sp-space-md);">
            <div class="sp-stat-card" style="background: var(--sp-success-light); border-radius: var(--sp-radius-lg); padding: var(--sp-space-lg); text-align: center;">
                <div class="sp-stat-value" style="font-size: var(--sp-font-size-2xl); font-weight: var(--sp-font-bold); color: var(--sp-success);">
                    <?php echo esc_html($profile_complete_count); ?>
                </div>
                <div class="sp-stat-label" style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                    <?php _e('أكملوا الملف', 'saint-porphyrius'); ?>
                </div>
            </div>
            <div class="sp-stat-card" style="background: var(--sp-warning-light); border-radius: var(--sp-radius-lg); padding: var(--sp-space-lg); text-align: center;">
                <div class="sp-stat-value" style="font-size: var(--sp-font-size-2xl); font-weight: var(--sp-font-bold); color: var(--sp-warning);">
                    <?php echo esc_html($birthday_rewarded_count); ?>
                </div>
                <div class="sp-stat-label" style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                    <?php _e('هدايا عيد ميلاد', 'saint-porphyrius'); ?>
                </div>
            </div>
            <div class="sp-stat-card" style="background: var(--sp-info-light); border-radius: var(--sp-radius-lg); padding: var(--sp-space-lg); text-align: center;">
                <div class="sp-stat-value" style="font-size: var(--sp-font-size-2xl); font-weight: var(--sp-font-bold); color: var(--sp-info);">
                    <?php echo esc_html($story_completed_count); ?>
                </div>
                <div class="sp-stat-label" style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                    <?php _e('أكملوا القصة', 'saint-porphyrius'); ?>
                </div>
            </div>
            <div class="sp-stat-card" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border-radius: var(--sp-radius-lg); padding: var(--sp-space-lg); text-align: center;">
                <div class="sp-stat-value" style="font-size: var(--sp-font-size-2xl); font-weight: var(--sp-font-bold); color: #e65100;">
                    <?php echo esc_html($feast_day_rewarded_count); ?>
                </div>
                <div class="sp-stat-label" style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                    <?php _e('هدايا عيد الشفيع', 'saint-porphyrius'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Settings Form -->
    <div class="sp-section" style="padding: 0 var(--sp-space-lg) var(--sp-space-lg);">
        <form method="post" class="sp-card" style="padding: var(--sp-space-xl);">
            <?php wp_nonce_field('sp_gamification_settings', 'sp_gamification_nonce'); ?>
            
            <!-- Profile Completion Section -->
            <div class="sp-settings-section" style="margin-bottom: var(--sp-space-2xl);">
                <div style="display: flex; align-items: center; gap: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">
                    <span style="font-size: 32px;">📝</span>
                    <div>
                        <h4 style="margin: 0; font-size: var(--sp-font-size-lg);"><?php _e('مكافأة إكمال الملف الشخصي', 'saint-porphyrius'); ?></h4>
                        <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                            <?php _e('نقاط تُمنح عند إكمال جميع بيانات الملف الشخصي', 'saint-porphyrius'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                    <label class="sp-checkbox">
                        <input type="checkbox" name="profile_completion_enabled" value="1" <?php checked($settings['profile_completion_enabled'], 1); ?>>
                        <span class="sp-checkbox-mark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span class="sp-checkbox-text"><?php _e('تفعيل مكافأة إكمال الملف', 'saint-porphyrius'); ?></span>
                    </label>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('عدد النقاط', 'saint-porphyrius'); ?></label>
                    <input type="number" name="profile_completion_points" class="sp-form-input" 
                           value="<?php echo esc_attr($settings['profile_completion_points']); ?>" 
                           min="0" style="max-width: 150px;">
                </div>
            </div>
            
            <!-- Birthday Section -->
            <div class="sp-settings-section" style="margin-bottom: var(--sp-space-2xl);">
                <div style="display: flex; align-items: center; gap: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">
                    <span style="font-size: 32px;">🎂</span>
                    <div>
                        <h4 style="margin: 0; font-size: var(--sp-font-size-lg);"><?php _e('هدية عيد الميلاد', 'saint-porphyrius'); ?></h4>
                        <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                            <?php _e('نقاط تُمنح للعضو في يوم عيد ميلاده', 'saint-porphyrius'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                    <label class="sp-checkbox">
                        <input type="checkbox" name="birthday_reward_enabled" value="1" <?php checked($settings['birthday_reward_enabled'], 1); ?>>
                        <span class="sp-checkbox-mark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span class="sp-checkbox-text"><?php _e('تفعيل هدية عيد الميلاد', 'saint-porphyrius'); ?></span>
                    </label>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('عدد النقاط', 'saint-porphyrius'); ?></label>
                    <input type="number" name="birthday_points" class="sp-form-input" 
                           value="<?php echo esc_attr($settings['birthday_points']); ?>" 
                           min="0" style="max-width: 150px;">
                </div>
            </div>
            
            <!-- Story Quiz Section -->
            <div class="sp-settings-section" style="margin-bottom: var(--sp-space-2xl);">
                <div style="display: flex; align-items: center; gap: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">
                    <span style="font-size: 32px;">📖</span>
                    <div>
                        <h4 style="margin: 0; font-size: var(--sp-font-size-lg);"><?php _e('مكافأة قصة شفيعنا', 'saint-porphyrius'); ?></h4>
                        <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                            <?php _e('نقاط تُمنح عند قراءة قصة القديس برفوريوس واجتياز الاختبار', 'saint-porphyrius'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                    <label class="sp-checkbox">
                        <input type="checkbox" name="story_quiz_enabled" value="1" <?php checked($settings['story_quiz_enabled'], 1); ?>>
                        <span class="sp-checkbox-mark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span class="sp-checkbox-text"><?php _e('تفعيل مكافأة قصة شفيعنا', 'saint-porphyrius'); ?></span>
                    </label>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('عدد النقاط', 'saint-porphyrius'); ?></label>
                    <input type="number" name="story_quiz_points" class="sp-form-input" 
                           value="<?php echo esc_attr($settings['story_quiz_points']); ?>" 
                           min="0" style="max-width: 150px;">
                </div>
            </div>
            
            <!-- Feast Day Section -->
            <div class="sp-settings-section" style="margin-bottom: var(--sp-space-2xl);">
                <div style="display: flex; align-items: center; gap: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">
                    <span style="font-size: 32px;">⛪</span>
                    <div>
                        <h4 style="margin: 0; font-size: var(--sp-font-size-lg);"><?php _e('هدية عيد شفيعنا', 'saint-porphyrius'); ?></h4>
                        <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                            <?php _e('نقاط تُمنح في عيد القديس برفوريوس البهلوان (18 توت - 28 سبتمبر)', 'saint-porphyrius'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                    <label class="sp-checkbox">
                        <input type="checkbox" name="feast_day_reward_enabled" value="1" <?php checked($settings['feast_day_reward_enabled'], 1); ?>>
                        <span class="sp-checkbox-mark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span class="sp-checkbox-text"><?php _e('تفعيل هدية عيد الشفيع', 'saint-porphyrius'); ?></span>
                    </label>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('عدد النقاط', 'saint-porphyrius'); ?></label>
                    <input type="number" name="feast_day_points" class="sp-form-input" 
                           value="<?php echo esc_attr($settings['feast_day_points']); ?>" 
                           min="0" style="max-width: 150px;">
                </div>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?php _e('حفظ الإعدادات', 'saint-porphyrius'); ?>
            </button>
        </form>
    </div>
</main>

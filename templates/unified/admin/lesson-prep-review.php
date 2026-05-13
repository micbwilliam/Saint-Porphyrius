<?php
/**
 * Saint Porphyrius - Admin Lesson Prep Review Queue
 * List submitted preparations awaiting review
 *
 * @since 6.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    echo '<main class="sp-page-content"><div class="sp-card" style="text-align:center;padding:var(--sp-space-xl);"><p>ليس لديك صلاحية</p></div></main>';
    return;
}

$handler = SP_Lesson_Prep::get_instance();
$status_filter = sanitize_text_field($_GET['status'] ?? 'submitted');
$preparations = $handler->get_preparations(array('status' => $status_filter, 'limit' => 100));
$status_labels = SP_Lesson_Prep::get_status_labels();
?>

<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin/lesson-prep'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('مراجعة التحضيرات', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content">
    <div style="padding:var(--sp-space-md);">

        <!-- Filter tabs -->
        <div style="display:flex;gap:4px;margin-bottom:var(--sp-space-md);overflow-x:auto;padding-bottom:4px;">
            <?php
            $status_filters = array('submitted', 'under_review', 'approved', 'needs_revision');
            foreach ($status_filters as $sf):
                $active = $status_filter === $sf;
            ?>
                <a href="?status=<?php echo $sf; ?>" class="sp-btn <?php echo $active ? 'sp-btn-primary' : 'sp-btn-outline'; ?> sp-btn-sm" style="font-size:0.75rem;white-space:nowrap;">
                    <?php echo esc_html($status_labels[$sf] ?? $sf); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($preparations)): ?>
            <div class="sp-card" style="text-align:center;padding:var(--sp-space-xl);">
                <div style="font-size:3rem;">📭</div>
                <p style="color:var(--sp-text-secondary);"><?php _e('لا توجد تحضيرات بهذه الحالة', 'saint-porphyrius'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($preparations as $prep): 
                $ai_score = floatval($prep->ai_detection_score ?? 0);
                $ai_color = $ai_score > 70 ? '#DC2626' : ($ai_score > 40 ? '#D97706' : '#059669');
            ?>
                <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
                        <div style="flex:1;min-width:0;">
                            <h3 style="margin:0;font-size:0.95rem;"><?php echo esc_html($prep->lesson_title_ar); ?></h3>
                            <p style="margin:4px 0;font-size:0.8rem;color:var(--sp-text-secondary);">
                                <?php echo esc_html($prep->user_name_ar ?: $prep->display_name); ?>
                                <?php if ($prep->user_church): ?>| ⛪ <?php echo esc_html($prep->user_church); ?><?php endif; ?>
                                <?php if ($prep->grade): ?>| 📚 <?php echo sprintf(__('الصف %d', 'saint-porphyrius'), $prep->grade); ?><?php endif; ?>
                            </p>
                            <p style="margin:4px 0;font-size:0.75rem;color:var(--sp-text-tertiary);">
                                <?php echo date_i18n(get_option('date_format'), strtotime($prep->submitted_at ?: $prep->created_at)); ?>
                                | ⭐ <?php echo $prep->total_points_awarded; ?> <?php _e('نقطة', 'saint-porphyrius'); ?>
                            </p>
                        </div>
                        <div style="text-align:center;">
                            <a href="<?php echo home_url('/app/admin/lesson-prep/review/' . $prep->id); ?>" class="sp-btn sp-btn-primary sp-btn-sm" style="font-size:0.75rem;">
                                🔍 <?php _e('مراجعة', 'saint-porphyrius'); ?>
                            </a>
                            <?php if ($ai_score > 0): ?>
                                <div style="margin-top:4px;font-size:0.75rem;font-weight:600;color:<?php echo $ai_color; ?>;">
                                    🤖 <?php echo $ai_score; ?>%
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

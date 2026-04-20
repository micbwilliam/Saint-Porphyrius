<?php
/**
 * Saint Porphyrius - Admin Birthday Gifts Management
 * Manage birthday gift options that members can choose from
 */

if (!defined('ABSPATH')) {
    exit;
}

$gamification = SP_Gamification::get_instance();
$gift_types = $gamification->get_gift_types();
$message = '';
$message_type = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_birthday_gifts_nonce'])) {
    if (wp_verify_nonce($_POST['sp_birthday_gifts_nonce'], 'sp_birthday_gifts_action')) {
        $action = sanitize_text_field($_POST['gift_action'] ?? '');

        if ($action === 'create') {
            $result = $gamification->create_birthday_gift(array(
                'title'       => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'gift_type'   => $_POST['gift_type'] ?? 'points',
                'icon'        => $_POST['icon'] ?? '🎁',
                'value'       => $_POST['value'] ?? '',
                'is_active'   => isset($_POST['is_active']) ? 1 : 0,
                'sort_order'  => $_POST['sort_order'] ?? 0,
            ));
            if ($result) {
                $message = __('تم إضافة الهدية بنجاح', 'saint-porphyrius');
                $message_type = 'success';
            } else {
                $message = __('حدث خطأ أثناء إضافة الهدية', 'saint-porphyrius');
                $message_type = 'error';
            }
        } elseif ($action === 'update') {
            $gift_id = absint($_POST['gift_id'] ?? 0);
            $result = $gamification->update_birthday_gift($gift_id, array(
                'title'       => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'gift_type'   => $_POST['gift_type'] ?? 'points',
                'icon'        => $_POST['icon'] ?? '🎁',
                'value'       => $_POST['value'] ?? '',
                'is_active'   => isset($_POST['is_active']) ? 1 : 0,
                'sort_order'  => $_POST['sort_order'] ?? 0,
            ));
            $message = __('تم تحديث الهدية بنجاح', 'saint-porphyrius');
            $message_type = 'success';
        } elseif ($action === 'delete') {
            $gift_id = absint($_POST['gift_id'] ?? 0);
            $gamification->delete_birthday_gift($gift_id);
            $message = __('تم حذف الهدية', 'saint-porphyrius');
            $message_type = 'success';
        } elseif ($action === 'toggle') {
            $gift_id = absint($_POST['gift_id'] ?? 0);
            $gift = $gamification->get_birthday_gift($gift_id);
            if ($gift) {
                $gamification->update_birthday_gift($gift_id, array('is_active' => $gift->is_active ? 0 : 1));
                $message = $gift->is_active ? __('تم إلغاء تفعيل الهدية', 'saint-porphyrius') : __('تم تفعيل الهدية', 'saint-porphyrius');
                $message_type = 'success';
            }
        }
    }
}

$gifts = $gamification->get_birthday_gifts();
$claims = $gamification->get_gift_claims(array('year' => date('Y'), 'limit' => 100));
$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'gifts';
$edit_gift = null;
if (isset($_GET['edit'])) {
    $edit_gift = $gamification->get_birthday_gift(absint($_GET['edit']));
}

// Icon options for gifts
$icon_options = array('🎁', '⭐', '💰', '🎟️', '🎀', '🏆', '💎', '🎂', '🎉', '🎊', '🎈', '💝', '🌟', '👑', '🎮', '📱', '🎧', '📚');
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo esc_url(home_url('/app/admin')); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('هدايا عيد الميلاد', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-actions"></div>
    </div>
</div>

<main class="sp-page-content">

    <?php if ($message): ?>
    <div class="sp-alert sp-alert-<?php echo esc_attr($message_type); ?>" style="margin: 16px;">
        <div class="sp-alert-icon"><?php echo $message_type === 'success' ? '✅' : '❌'; ?></div>
        <div class="sp-alert-content"><?php echo esc_html($message); ?></div>
    </div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <div class="sp-filter-tabs" style="padding: 16px 16px 0;">
        <a href="<?php echo esc_url(add_query_arg('view', 'gifts')); ?>" class="sp-filter-tab <?php echo $view === 'gifts' ? 'active' : ''; ?>">
            🎁 <?php _e('الهدايا', 'saint-porphyrius'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('view', 'claims')); ?>" class="sp-filter-tab <?php echo $view === 'claims' ? 'active' : ''; ?>">
            📋 <?php _e('الاختيارات', 'saint-porphyrius'); ?>
            <?php if (count($claims) > 0): ?>
            <span class="sp-admin-stat-badge" style="margin-right: 4px;"><?php echo count($claims); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('view', 'add')); ?>" class="sp-filter-tab <?php echo ($view === 'add' || $edit_gift) ? 'active' : ''; ?>">
            ➕ <?php _e('إضافة', 'saint-porphyrius'); ?>
        </a>
    </div>

    <?php if ($view === 'add' || $edit_gift): ?>
    <!-- Add/Edit Gift Form -->
    <div style="padding: 16px;">
        <div class="sp-card" style="padding: 20px;">
            <h3 style="margin: 0 0 16px; font-size: 1.1rem;">
                <?php echo $edit_gift ? __('تعديل الهدية', 'saint-porphyrius') : __('إضافة هدية جديدة', 'saint-porphyrius'); ?>
            </h3>
            <form method="post">
                <?php wp_nonce_field('sp_birthday_gifts_action', 'sp_birthday_gifts_nonce'); ?>
                <input type="hidden" name="gift_action" value="<?php echo $edit_gift ? 'update' : 'create'; ?>">
                <?php if ($edit_gift): ?>
                <input type="hidden" name="gift_id" value="<?php echo esc_attr($edit_gift->id); ?>">
                <?php endif; ?>

                <div class="sp-form-group" style="margin-bottom: 16px;">
                    <label class="sp-form-label"><?php _e('اسم الهدية', 'saint-porphyrius'); ?> *</label>
                    <input type="text" name="title" class="sp-form-input" required
                           value="<?php echo esc_attr($edit_gift->title ?? ''); ?>"
                           placeholder="<?php esc_attr_e('مثال: ٥٠ نقطة إضافية', 'saint-porphyrius'); ?>">
                </div>

                <div class="sp-form-group" style="margin-bottom: 16px;">
                    <label class="sp-form-label"><?php _e('الوصف', 'saint-porphyrius'); ?></label>
                    <textarea name="description" class="sp-form-input" rows="2"
                              placeholder="<?php esc_attr_e('وصف مختصر للهدية', 'saint-porphyrius'); ?>"><?php echo esc_textarea($edit_gift->description ?? ''); ?></textarea>
                </div>

                <div class="sp-form-group" style="margin-bottom: 16px;">
                    <label class="sp-form-label"><?php _e('نوع الهدية', 'saint-porphyrius'); ?></label>
                    <select name="gift_type" class="sp-form-input" id="sp-gift-type-select">
                        <?php foreach ($gift_types as $type_key => $type_info): ?>
                        <option value="<?php echo esc_attr($type_key); ?>" <?php selected($edit_gift->gift_type ?? 'points', $type_key); ?>>
                            <?php echo esc_html($type_info['icon'] . ' ' . $type_info['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sp-form-group" style="margin-bottom: 16px;">
                    <label class="sp-form-label" id="sp-value-label"><?php _e('القيمة', 'saint-porphyrius'); ?></label>
                    <input type="text" name="value" class="sp-form-input" id="sp-gift-value"
                           value="<?php echo esc_attr($edit_gift->value ?? ''); ?>"
                           placeholder="<?php esc_attr_e('مثال: 50', 'saint-porphyrius'); ?>">
                    <small style="color: #6B7280; font-size: 0.8rem;" id="sp-value-help">
                        <?php _e('للنقاط: أدخل العدد. للمال: أدخل المبلغ. للهدايا: أدخل وصف الهدية', 'saint-porphyrius'); ?>
                    </small>
                </div>

                <div class="sp-form-group" style="margin-bottom: 16px;">
                    <label class="sp-form-label"><?php _e('أيقونة الهدية', 'saint-porphyrius'); ?></label>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;" id="sp-icon-picker">
                        <?php foreach ($icon_options as $icon): ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="icon" value="<?php echo esc_attr($icon); ?>" 
                                   <?php checked($edit_gift->icon ?? '🎁', $icon); ?>
                                   style="display: none;">
                            <span class="sp-icon-option" style="display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px; font-size: 1.5rem; border: 2px solid #E5E7EB; transition: all 0.2s;">
                                <?php echo $icon; ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="sp-form-group" style="margin-bottom: 16px;">
                    <label class="sp-form-label"><?php _e('ترتيب العرض', 'saint-porphyrius'); ?></label>
                    <input type="number" name="sort_order" class="sp-form-input" style="max-width: 100px;"
                           value="<?php echo esc_attr($edit_gift->sort_order ?? 0); ?>" min="0">
                </div>

                <div class="sp-form-group" style="margin-bottom: 20px;">
                    <label class="sp-checkbox">
                        <input type="checkbox" name="is_active" value="1" <?php checked($edit_gift->is_active ?? 1, 1); ?>>
                        <span class="sp-checkbox-mark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span class="sp-checkbox-text"><?php _e('مفعّلة (تظهر للأعضاء)', 'saint-porphyrius'); ?></span>
                    </label>
                </div>

                <button type="submit" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block">
                    <?php echo $edit_gift ? __('حفظ التعديلات', 'saint-porphyrius') : __('إضافة الهدية', 'saint-porphyrius'); ?>
                </button>

                <?php if ($edit_gift): ?>
                <a href="<?php echo esc_url(add_query_arg('view', 'gifts', remove_query_arg('edit'))); ?>" 
                   class="sp-btn sp-btn-secondary sp-btn-lg sp-btn-block" style="margin-top: 8px; text-align: center;">
                    <?php _e('إلغاء', 'saint-porphyrius'); ?>
                </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php elseif ($view === 'claims'): ?>
    <!-- Claims List -->
    <div style="padding: 16px;">
        <div class="sp-admin-summary" style="margin-bottom: 16px; padding: 16px; background: linear-gradient(135deg, #EDE9FE, #DDD6FE); border-radius: 16px; text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 4px;">🎉</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #6D28D9;">
                <?php echo esc_html(count($claims)); ?>
            </div>
            <div style="font-size: 0.85rem; color: #5B21B6;">
                <?php printf(__('عضو اختار هديته في %s', 'saint-porphyrius'), date('Y')); ?>
            </div>
        </div>

        <?php if (empty($claims)): ?>
        <div class="sp-empty-state">
            <div class="sp-empty-state-icon">📋</div>
            <h3><?php _e('لا توجد اختيارات بعد', 'saint-porphyrius'); ?></h3>
            <p><?php _e('لم يختار أي عضو هديته بعد هذا العام', 'saint-porphyrius'); ?></p>
        </div>
        <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($claims as $claim): ?>
            <div class="sp-card" style="padding: 14px 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="font-size: 1.8rem; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: #F3F4F6; border-radius: 12px;">
                        <?php echo esc_html($claim->icon); ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; font-size: 0.95rem;"><?php echo esc_html($claim->display_name); ?></div>
                        <div style="font-size: 0.8rem; color: #6B7280;">
                            <?php echo esc_html($claim->gift_title); ?>
                            <?php if ($claim->gift_type === 'points'): ?>
                                <span style="color: #059669;">(<?php echo esc_html($claim->value); ?> نقطة)</span>
                            <?php elseif ($claim->gift_type === 'money'): ?>
                                <span style="color: #B45309;">(<?php echo esc_html($claim->value); ?> جنيه)</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #9CA3AF; margin-top: 2px;">
                            <?php echo esc_html(date_i18n('j F Y - g:i a', strtotime($claim->claimed_at))); ?>
                        </div>
                    </div>
                    <div style="padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: #ECFDF5; color: #059669;">
                        <?php echo esc_html($gift_types[$claim->gift_type]['label'] ?? $claim->gift_type); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- Gifts List -->
    <div style="padding: 16px;">
        <?php if (empty($gifts)): ?>
        <div class="sp-empty-state">
            <div class="sp-empty-state-icon">🎁</div>
            <h3><?php _e('لا توجد هدايا بعد', 'saint-porphyrius'); ?></h3>
            <p><?php _e('أضف هدايا ليختار منها الأعضاء في عيد ميلادهم', 'saint-porphyrius'); ?></p>
            <a href="<?php echo esc_url(add_query_arg('view', 'add')); ?>" class="sp-btn sp-btn-primary" style="margin-top: 12px;">
                ➕ <?php _e('إضافة هدية', 'saint-porphyrius'); ?>
            </a>
        </div>
        <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($gifts as $gift): ?>
            <div class="sp-card" style="padding: 16px; <?php echo !$gift->is_active ? 'opacity: 0.6;' : ''; ?>">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="font-size: 2rem; width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; background: <?php echo $gift->is_active ? '#FEF3C7' : '#F3F4F6'; ?>; border-radius: 14px; flex-shrink: 0;">
                        <?php echo esc_html($gift->icon); ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <h4 style="margin: 0; font-size: 1rem; font-weight: 600;"><?php echo esc_html($gift->title); ?></h4>
                            <?php if ($gift->is_active): ?>
                                <span style="background: #ECFDF5; color: #059669; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: 600;"><?php _e('مفعّل', 'saint-porphyrius'); ?></span>
                            <?php else: ?>
                                <span style="background: #FEF2F2; color: #DC2626; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: 600;"><?php _e('معطّل', 'saint-porphyrius'); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($gift->description): ?>
                        <p style="margin: 0 0 4px; font-size: 0.85rem; color: #6B7280;"><?php echo esc_html($gift->description); ?></p>
                        <?php endif; ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; font-size: 0.8rem; color: #9CA3AF;">
                            <span><?php echo esc_html($gift_types[$gift->gift_type]['icon'] . ' ' . $gift_types[$gift->gift_type]['label']); ?></span>
                            <?php if ($gift->value): ?>
                            <span>•</span>
                            <span>
                                <?php if ($gift->gift_type === 'points'): ?>
                                    <?php printf(__('%s نقطة', 'saint-porphyrius'), esc_html($gift->value)); ?>
                                <?php elseif ($gift->gift_type === 'money'): ?>
                                    <?php printf(__('%s جنيه', 'saint-porphyrius'), esc_html($gift->value)); ?>
                                <?php else: ?>
                                    <?php echo esc_html($gift->value); ?>
                                <?php endif; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #F3F4F6;">
                    <a href="<?php echo esc_url(add_query_arg(array('view' => 'add', 'edit' => $gift->id))); ?>" 
                       class="sp-btn sp-btn-secondary" style="flex: 1; text-align: center; font-size: 0.85rem; padding: 8px;">
                        ✏️ <?php _e('تعديل', 'saint-porphyrius'); ?>
                    </a>
                    <form method="post" style="flex: 1;">
                        <?php wp_nonce_field('sp_birthday_gifts_action', 'sp_birthday_gifts_nonce'); ?>
                        <input type="hidden" name="gift_action" value="toggle">
                        <input type="hidden" name="gift_id" value="<?php echo esc_attr($gift->id); ?>">
                        <button type="submit" class="sp-btn sp-btn-secondary sp-btn-block" style="font-size: 0.85rem; padding: 8px;">
                            <?php echo $gift->is_active ? '🔴 ' . __('تعطيل', 'saint-porphyrius') : '🟢 ' . __('تفعيل', 'saint-porphyrius'); ?>
                        </button>
                    </form>
                    <form method="post" style="flex-shrink: 0;" onsubmit="return confirm('<?php esc_attr_e('هل أنت متأكد من حذف هذه الهدية؟', 'saint-porphyrius'); ?>');">
                        <?php wp_nonce_field('sp_birthday_gifts_action', 'sp_birthday_gifts_nonce'); ?>
                        <input type="hidden" name="gift_action" value="delete">
                        <input type="hidden" name="gift_id" value="<?php echo esc_attr($gift->id); ?>">
                        <button type="submit" class="sp-btn" style="background: #FEF2F2; color: #DC2626; font-size: 0.85rem; padding: 8px 12px; border-radius: 10px;">
                            🗑️
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<style>
    #sp-icon-picker input:checked + .sp-icon-option {
        border-color: #6D28D9;
        background: #EDE9FE;
        box-shadow: 0 0 0 2px rgba(109, 40, 217, 0.2);
    }
</style>

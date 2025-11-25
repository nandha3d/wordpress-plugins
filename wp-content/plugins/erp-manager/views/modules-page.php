<?php
/**
 * Modules Management Page View
 * File: views/modules-page.php
 */

if (!defined('ABSPATH')) exit;

$modules = FERP_Modular_Manager::get_modules();
$active_modules = FERP_Modular_Manager::get_active_modules();
?>

<div class="ferp-modules-header">
    <h1><?php _e('🧩 Modules Manager', 'ferp-modular'); ?></h1>
    <p><?php _e('Enable or disable modules with a single click', 'ferp-modular'); ?></p>
</div>

<!-- Statistics -->
<div class="ferp-stats-row">
    <div class="ferp-stat-box">
        <div class="ferp-stat-number"><?php echo count($modules); ?></div>
        <div class="ferp-stat-label"><?php _e('Total Modules', 'ferp-modular'); ?></div>
    </div>
    <div class="ferp-stat-box">
        <div class="ferp-stat-number"><?php echo count($active_modules); ?></div>
        <div class="ferp-stat-label"><?php _e('Active Modules', 'ferp-modular'); ?></div>
    </div>
    <div class="ferp-stat-box">
        <div class="ferp-stat-number"><?php echo count($modules) - count($active_modules); ?></div>
        <div class="ferp-stat-label"><?php _e('Inactive Modules', 'ferp-modular'); ?></div>
    </div>
    <div class="ferp-stat-box">
        <div class="ferp-stat-label"><?php _e('Modules Path', 'ferp-modular'); ?></div>
        <div class="ferp-stat-path">
            <code><?php echo esc_html(FERP_MODULAR_PATH . 'modules/'); ?></code>
        </div>
    </div>
</div>

<!-- Upload Module -->
<div class="ferp-upload-module">
    <h2><?php _e('📦 Install New Module', 'ferp-modular'); ?></h2>
    <form id="ferp-upload-module-form" enctype="multipart/form-data">
        <div class="ferp-upload-area" id="ferp-upload-area">
            <div class="ferp-upload-icon">
                <span class="dashicons dashicons-upload"></span>
            </div>
            <p><?php _e('Drag & drop module ZIP file here or click to browse', 'ferp-modular'); ?></p>
            <input type="file" id="module-zip-input" name="module_zip" accept=".zip" style="display: none;">
            <button type="button" class="button button-primary" id="browse-button">
                <?php _e('Browse Files', 'ferp-modular'); ?>
            </button>
        </div>
    </form>
    <p style="margin-top: 15px; color: #6b7280; font-size: 13px;">
        <strong><?php _e('Note:', 'ferp-modular'); ?></strong> 
        <?php _e('Module must be a ZIP file with proper structure. It will be automatically detected after upload.', 'ferp-modular'); ?>
    </p>
</div>

<!-- Toggle All Modules -->
<div class="ferp-toggle-controls">
    <div class="ferp-toggle-header">
        <h2><?php _e('Toggle all Modules', 'ferp-modular'); ?></h2>
        <label class="ferp-master-toggle">
            <input type="checkbox" id="toggle-all-modules" <?php echo (count($active_modules) === count($modules) && count($modules) > 0) ? 'checked' : ''; ?>>
            <span class="ferp-toggle-slider"></span>
        </label>
    </div>
    <p class="ferp-toggle-description"><?php _e('You can disable some modules for better performance.', 'ferp-modular'); ?></p>
</div>

<!-- Filter Tabs -->
<div class="ferp-filter-tabs">
    <button class="ferp-tab-button active" data-filter="all"><?php _e('All Modules', 'ferp-modular'); ?></button>
    <button class="ferp-tab-button" data-filter="core"><?php _e('Core', 'ferp-modular'); ?></button>
    <button class="ferp-tab-button" data-filter="pro"><?php _e('Pro', 'ferp-modular'); ?></button>
</div>

<!-- Modules List -->
<?php if (empty($modules)): ?>
    <div class="ferp-empty-state">
        <span class="dashicons dashicons-admin-plugins"></span>
        <h3><?php _e('No Modules Found', 'ferp-modular'); ?></h3>
        <p><?php printf(__('Drop module folders into: %s', 'ferp-modular'), '<code>' . FERP_MODULAR_PATH . 'modules/</code>'); ?></p>
        <p><?php _e('Or upload a module ZIP file above.', 'ferp-modular'); ?></p>
    </div>
<?php else: ?>
    <div class="ferp-modules-toggle-list">
        <?php foreach ($modules as $slug => $module): 
            $is_active = in_array($slug, $active_modules);
        ?>
            <div class="ferp-module-toggle-item" data-module="<?php echo esc_attr($slug); ?>">
                <div class="ferp-module-toggle-left">
                    <div class="ferp-module-toggle-icon">
                        <?php if (!empty($module['icon'])): ?>
                            <span class="dashicons dashicons-<?php echo esc_attr($module['icon']); ?>"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-admin-plugins"></span>
                        <?php endif; ?>
                    </div>
                    <div class="ferp-module-toggle-info">
                        <h3 class="ferp-module-toggle-name">
                            <?php echo esc_html($module['name']); ?>
                            <?php if (!empty($module['requires'])): ?>
                                <span class="ferp-module-badge-new">NEW</span>
                            <?php endif; ?>
                        </h3>
                        <a href="#" class="ferp-module-toggle-demo"><?php _e('View Demo', 'ferp-modular'); ?></a>
                    </div>
                </div>
                <div class="ferp-module-toggle-right">
                    <label class="ferp-toggle-switch">
                        <input 
                            type="checkbox" 
                            class="module-toggle-checkbox"
                            data-module="<?php echo esc_attr($slug); ?>"
                            data-action="<?php echo $is_active ? 'deactivate' : 'activate'; ?>"
                            <?php echo $is_active ? 'checked' : ''; ?>
                        >
                        <span class="ferp-toggle-slider"></span>
                    </label>
                    <button class="ferp-module-delete-btn" data-module="<?php echo esc_attr($slug); ?>" title="<?php _e('Delete Module', 'ferp-modular'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php
/**
 * Plugin Name: Cookie Consent Banner
 * Plugin URI: https://github.com/mxstudiopl/cookiebanner.git
 * Description: Simple WordPress plugin for displaying cookie consent banner with Google Consent Mode v2 support
 * Version: 1.0.0
 * Author: MX Studio
 * Author URI: https://mx-studio.pl
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cookie-consent-banner
 */

// If this file is called directly, abort
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CCB_VERSION', '1.0.0');
define('CCB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CCB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CCB_PLUGIN_FILE', __FILE__);
define('CCB_GITHUB_REPO', 'mxstudiopl/cookiebanner');
define('CCB_GITHUB_USER', 'mxstudiopl');

/**
 * Sanitize hex color
 */
if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($color) {
        if (empty($color)) {
            return '';
        }
        // Remove # if present
        $color = ltrim($color, '#');
        // Check if it's a valid hex color
        if (preg_match('/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return '#' . $color;
        }
        return '#00852D'; // Default color
    }
}

/**
 * Plugin Updater Class
 */
class CCB_Plugin_Updater {
    
    private static $instance = null;
    private $plugin_slug;
    private $plugin_basename;
    private $github_username;
    private $github_repository;
    private $plugin_data;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->plugin_basename = plugin_basename(CCB_PLUGIN_FILE);
        $this->plugin_slug = dirname($this->plugin_basename);
        $this->github_username = CCB_GITHUB_USER;
        $this->github_repository = CCB_GITHUB_REPO;
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_api_call'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }
    
    /**
     * Get plugin data
     */
    private function get_plugin_data() {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        return get_plugin_data(CCB_PLUGIN_FILE);
    }
    
    /**
     * Get latest release from GitHub
     */
    private function get_latest_release() {
        $cache_key = 'ccb_latest_release';
        $release = get_transient($cache_key);
        
        if (false === $release) {
            $api_url = sprintf('https://api.github.com/repos/%s/releases/latest', $this->github_repository);
            
            $response = wp_remote_get($api_url, array(
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                ),
            ));
            
            if (is_wp_error($response)) {
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            $release = json_decode($body);
            
            if (isset($release->tag_name)) {
                set_transient($cache_key, $release, 12 * HOUR_IN_SECONDS);
            } else {
                return false;
            }
        }
        
        return $release;
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $latest_release = $this->get_latest_release();
        
        if (!$latest_release || !isset($latest_release->tag_name)) {
            return $transient;
        }
        
        $latest_version = ltrim($latest_release->tag_name, 'v');
        $current_version = CCB_VERSION;
        
        if (version_compare($current_version, $latest_version, '<')) {
            $plugin_data = $this->get_plugin_data();
            
            // Create download URL from GitHub release
            $download_url = sprintf(
                'https://github.com/%s/archive/refs/tags/%s.zip',
                $this->github_repository,
                $latest_release->tag_name
            );
            
            $obj = new stdClass();
            $obj->slug = $this->plugin_slug;
            $obj->plugin = $this->plugin_basename;
            $obj->new_version = $latest_version;
            $obj->url = $plugin_data['PluginURI'];
            $obj->package = $download_url;
            $obj->tested = '';
            $obj->requires_php = '';
            
            if (isset($latest_release->body)) {
                $obj->sections = array(
                    'changelog' => $latest_release->body
                );
            }
            
            $transient->response[$this->plugin_basename] = $obj;
        }
        
        return $transient;
    }
    
    /**
     * Plugin API call
     */
    public function plugin_api_call($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== $this->plugin_slug) {
            return $result;
        }
        
        $latest_release = $this->get_latest_release();
        
        if (!$latest_release) {
            return $result;
        }
        
        $plugin_data = $this->get_plugin_data();
        $latest_version = ltrim($latest_release->tag_name, 'v');
        
        $result = new stdClass();
        $result->name = $plugin_data['Name'];
        $result->slug = $this->plugin_slug;
        $result->version = $latest_version;
        $result->author = $plugin_data['Author'];
        $result->homepage = $plugin_data['PluginURI'];
        $result->requires = '';
        $result->tested = '';
        $result->downloaded = 0;
        $result->last_updated = isset($latest_release->published_at) ? $latest_release->published_at : '';
        $result->sections = array(
            'description' => $plugin_data['Description'],
            'changelog' => isset($latest_release->body) ? $latest_release->body : ''
        );
        $result->download_link = sprintf(
            'https://github.com/%s/archive/refs/tags/%s.zip',
            $this->github_repository,
            $latest_release->tag_name
        );
        
        return $result;
    }
    
    /**
     * After plugin install
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->plugin_basename) {
            $install_directory = plugin_dir_path(CCB_PLUGIN_FILE);
            $source = $result['destination'];
            
            // GitHub zip contains a folder with repo name and tag
            // We need to find the plugin folder inside
            $folders = $wp_filesystem->dirlist($source);
            
            if (!empty($folders)) {
                $top_folder = key($folders);
                $top_folder_path = trailingslashit($source) . $top_folder;
                
                // Check if plugin folder exists inside
                $subfolders = $wp_filesystem->dirlist($top_folder_path);
                if ($subfolders && isset($subfolders[$this->plugin_slug])) {
                    // Plugin folder is inside the extracted folder
                    $plugin_source = trailingslashit($top_folder_path) . $this->plugin_slug;
                } else {
                    // Files are directly in the top folder
                    $plugin_source = $top_folder_path;
                }
                
                // Copy all files from source to install directory
                $this->copy_directory($plugin_source, $install_directory);
            }
            
            $result['destination'] = $install_directory;
            
            // Clear update cache
            delete_transient('ccb_latest_release');
        }
        
        return $result;
    }
    
    /**
     * Copy directory recursively
     */
    private function copy_directory($source, $destination) {
        global $wp_filesystem;
        
        $files = $wp_filesystem->dirlist($source, true);
        
        if ($files) {
            foreach ($files as $file) {
                $source_path = trailingslashit($source) . $file['name'];
                $dest_path = trailingslashit($destination) . $file['name'];
                
                if ($file['type'] === 'd') {
                    // Directory
                    $wp_filesystem->mkdir($dest_path);
                    $this->copy_directory($source_path, $dest_path);
                } else {
                    // File
                    $wp_filesystem->copy($source_path, $dest_path, true);
                }
            }
        }
    }
}

class Cookie_Consent_Banner {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Enqueue styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add banner to footer
        add_action('wp_footer', array($this, 'render_banner'));
        
        // Add settings page to admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Initialize update checker
        CCB_Plugin_Updater::get_instance();
    }
    
    /**
     * Enqueue styles and scripts
     */
    public function enqueue_scripts() {
        // Enqueue jQuery (WordPress includes it by default)
        wp_enqueue_script('jquery');
        
        // Enqueue CSS
        wp_enqueue_style(
            'ccb-styles',
            CCB_PLUGIN_URL . 'assets/css/consent-banner.css',
            array(),
            CCB_VERSION
        );
        
        // Enqueue jQuery Cookie
        wp_enqueue_script(
            'jquery-cookie',
            CCB_PLUGIN_URL . 'assets/js/jquery.cookie.js',
            array('jquery'),
            '1.4.1',
            true
        );
        
        // Enqueue main script
        wp_enqueue_script(
            'ccb-script',
            CCB_PLUGIN_URL . 'assets/js/consent.js',
            array('jquery', 'jquery-cookie'),
            CCB_VERSION,
            true
        );
    }
    
    /**
     * Render banner in footer
     */
    public function render_banner() {
        // Check if plugin is active
        $is_active = get_option('ccb_active', '1');
        if ($is_active !== '1') {
            return;
        }
        
        // Get settings
        $title = get_option('ccb_title', 'Cookie Consent');
        $text = get_option('ccb_text', 'This website uses cookies to store information on your computer. By using this site, you consent to the use of cookies in accordance with your current browser settings, which you can change at any time.');
        $accept_all = get_option('ccb_accept_all', 'Accept All');
        $settings = get_option('ccb_settings', 'Cookie Settings');
        $reject_all = get_option('ccb_reject_all', 'Reject All');
        $save = get_option('ccb_save', 'Set Cookie');
        $back = get_option('ccb_back', 'Back');
        $ad_storage = get_option('ccb_ad_storage', 'Storage Cookie');
        $ad_user_data = get_option('ccb_ad_user_data', 'User Data Cookie');
        $ad_personalization = get_option('ccb_ad_personalization', 'Personalization Cookie');
        $analytics_storage = get_option('ccb_analytics_storage', 'Analytics Cookie');
        $ga_id = get_option('ccb_ga_id', '');
        $primary_color = get_option('ccb_primary_color', '#00852D');
        ?>
        <style>
            #consent_banner {
                --ccb-primary-color: <?php echo esc_attr($primary_color); ?>;
            }
        </style>
        <div id="consent_banner" class="consent_banner">
            <div class="container">
                <div class="consent_banner-body">
                    <div class="consent_banner-preview">
                        <h2><?php echo esc_html($title); ?></h2>
                        <p><?php echo esc_html($text); ?></p>
                        <div class="consent_banner-btns">
                            <a href="javascript:;" class="banner-btn banner-btn-primary js-all"><?php echo esc_html($accept_all); ?></a>
                            <a href="javascript:;" class="banner-btn banner-btn-primary js-set"><?php echo esc_html($settings); ?></a>
                            <a href="javascript:;" class="banner-btn banner-btn-deny js-deny"><?php echo esc_html($reject_all); ?></a>
                        </div>
                    </div>
                    <div class="consent_banner-settings">
                        <div class="consent_banner-settings_block">
                            <div class="consent_banner-row">
                                <input class="setting_check" type="checkbox" name="ad_storage" id="ad_storage" checked disabled>
                                <label class="setting_label" for="ad_storage"></label>
                                <span><?php echo esc_html($ad_storage); ?></span>
                            </div>
                            <div class="consent_banner-row">
                                <input id="ad_user_data" class="setting_check" type="checkbox" name="ad_user_data" checked>
                                <label class="setting_label" for="ad_user_data"></label>
                                <span><?php echo esc_html($ad_user_data); ?></span>
                            </div>
                            <div class="consent_banner-row">
                                <input id="ad_personalization" class="setting_check" type="checkbox" name="ad_personalization" checked>
                                <label class="setting_label" for="ad_personalization"></label>
                                <span><?php echo esc_html($ad_personalization); ?></span>
                            </div>
                            <div class="consent_banner-row">
                                <input id="analytics_storage" class="setting_check" type="checkbox" name="analytics_storage" checked>
                                <label class="setting_label" for="analytics_storage"></label>
                                <span><?php echo esc_html($analytics_storage); ?></span>
                            </div>
                        </div>
                        <div class="consent_banner-settings_btns">
                            <a href="javascript:;" class="banner-btn banner-btn-primary js-save"><?php echo esc_html($save); ?></a>
                            <a href="javascript:;" class="banner-btn banner-btn-deny js-back"><?php echo esc_html($back); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="consent-overlay"></div>
        
        <script>
          window.dataLayer = window.dataLayer || []

          function gtag () {
            dataLayer.push(arguments)
          }

          gtag('consent', 'default', {
                'ad_storage':         'denied',
                'ad_user_data':       'denied',
                'ad_personalization': 'denied',
                'analytics_storage':  'denied',
                'personalization_storage': 'denied',
                'functionality_storage': 'denied',
                'security_storage': 'denied'
              })

          var consentCookie = jQuery.cookie('consent_cookie');

          if (consentCookie && consentCookie != 'false') {
            var cookieObject = JSON.parse(consentCookie);
            console.log(cookieObject)
            gtag('consent', 'update', cookieObject);
          }
        </script>
        
        <?php if (!empty($ga_id)): ?>
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js($ga_id); ?>"></script>
        <script>
          window.dataLayer = window.dataLayer || []

          function gtag () {dataLayer.push(arguments)}

          gtag('js', new Date())

          gtag('config', '<?php echo esc_js($ga_id); ?>')
        </script>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_options_page(
            'Cookie Consent Banner Settings',
            'Cookie Consent',
            'manage_options',
            'cookie-consent-banner',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register all settings
        register_setting('ccb_settings_group', 'ccb_title');
        register_setting('ccb_settings_group', 'ccb_text');
        register_setting('ccb_settings_group', 'ccb_accept_all');
        register_setting('ccb_settings_group', 'ccb_settings');
        register_setting('ccb_settings_group', 'ccb_reject_all');
        register_setting('ccb_settings_group', 'ccb_save');
        register_setting('ccb_settings_group', 'ccb_back');
        register_setting('ccb_settings_group', 'ccb_ad_storage');
        register_setting('ccb_settings_group', 'ccb_ad_user_data');
        register_setting('ccb_settings_group', 'ccb_ad_personalization');
        register_setting('ccb_settings_group', 'ccb_analytics_storage');
        register_setting('ccb_settings_group', 'ccb_ga_id');
        register_setting('ccb_settings_group', 'ccb_primary_color');
        register_setting('ccb_settings_group', 'ccb_active');
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Save settings
        if (isset($_POST['ccb_save_settings']) && check_admin_referer('ccb_save_settings')) {
            update_option('ccb_title', sanitize_text_field($_POST['ccb_title']));
            update_option('ccb_text', sanitize_textarea_field($_POST['ccb_text']));
            update_option('ccb_accept_all', sanitize_text_field($_POST['ccb_accept_all']));
            update_option('ccb_settings', sanitize_text_field($_POST['ccb_settings']));
            update_option('ccb_reject_all', sanitize_text_field($_POST['ccb_reject_all']));
            update_option('ccb_save', sanitize_text_field($_POST['ccb_save']));
            update_option('ccb_back', sanitize_text_field($_POST['ccb_back']));
            update_option('ccb_ad_storage', sanitize_text_field($_POST['ccb_ad_storage']));
            update_option('ccb_ad_user_data', sanitize_text_field($_POST['ccb_ad_user_data']));
            update_option('ccb_ad_personalization', sanitize_text_field($_POST['ccb_ad_personalization']));
            update_option('ccb_analytics_storage', sanitize_text_field($_POST['ccb_analytics_storage']));
            update_option('ccb_ga_id', sanitize_text_field($_POST['ccb_ga_id']));
            
            // Use hex code if provided, otherwise use color picker value
            $color_value = !empty($_POST['ccb_primary_color_hex']) ? $_POST['ccb_primary_color_hex'] : $_POST['ccb_primary_color'];
            update_option('ccb_primary_color', sanitize_hex_color($color_value));
            
            update_option('ccb_active', isset($_POST['ccb_active']) ? '1' : '0');
            
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        // Get current settings
        $title = get_option('ccb_title', 'Cookie Consent');
        $text = get_option('ccb_text', 'This website uses cookies to store information on your computer. By using this site, you consent to the use of cookies in accordance with your current browser settings, which you can change at any time.');
        $accept_all = get_option('ccb_accept_all', 'Accept All');
        $settings = get_option('ccb_settings', 'Cookie Settings');
        $reject_all = get_option('ccb_reject_all', 'Reject All');
        $save = get_option('ccb_save', 'Set Cookie');
        $back = get_option('ccb_back', 'Back');
        $ad_storage = get_option('ccb_ad_storage', 'Storage Cookie');
        $ad_user_data = get_option('ccb_ad_user_data', 'User Data Cookie');
        $ad_personalization = get_option('ccb_ad_personalization', 'Personalization Cookie');
        $analytics_storage = get_option('ccb_analytics_storage', 'Analytics Cookie');
        $ga_id = get_option('ccb_ga_id', '');
        $primary_color = get_option('ccb_primary_color', '#00852D');
        $is_active = get_option('ccb_active', '1');
        ?>
        <div class="wrap">
            <h1>Cookie Consent Banner Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('ccb_save_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ccb_active">Active</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="ccb_active" name="ccb_active" value="1" <?php checked($is_active, '1'); ?> />
                                Enable Cookie Consent Banner
                            </label>
                            <p class="description">Uncheck to disable the banner on your site</p>
                        </td>
                    </tr>
                </table>
                <hr>
                <h2>Banner Content</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ccb_title">Banner Title</label>
                        </th>
                        <td>
                            <input type="text" id="ccb_title" name="ccb_title" value="<?php echo esc_attr($title); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_text">Banner Text</label>
                        </th>
                        <td>
                            <textarea id="ccb_text" name="ccb_text" rows="5" class="large-text"><?php echo esc_textarea($text); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_accept_all">Accept All Button Text</label>
                        </th>
                        <td>
                            <input type="text" id="ccb_accept_all" name="ccb_accept_all" value="<?php echo esc_attr($accept_all); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_settings">Settings Button Text</label>
                        </th>
                        <td>
                            <input type="text" id="ccb_settings" name="ccb_settings" value="<?php echo esc_attr($settings); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_reject_all">Reject All Button Text</label>
                        </th>
                        <td>
                            <input type="text" id="ccb_reject_all" name="ccb_reject_all" value="<?php echo esc_attr($reject_all); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_save">Save Button Text</label>
                        </th>
                        <td>
                            <input type="text" id="ccb_save" name="ccb_save" value="<?php echo esc_attr($save); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_back">Back Button Text</label>
                        </th>
                        <td>
                            <input type="text" id="ccb_back" name="ccb_back" value="<?php echo esc_attr($back); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_ad_storage">Storage Cookie Label</label>
                        </th>
                        <td>
                            <input type="text" id="ccb_ad_storage" name="ccb_ad_storage" value="<?php echo esc_attr($ad_storage); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_ad_user_data">User Data Cookie Label</label>
                        </th>
                        <td>
                            <input type="text" id="ccb_ad_user_data" name="ccb_ad_user_data" value="<?php echo esc_attr($ad_user_data); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_ad_personalization">Personalization Cookie Label</label>
                        </th>
                        <td>
                            <input type="text" id="ccb_ad_personalization" name="ccb_ad_personalization" value="<?php echo esc_attr($ad_personalization); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_analytics_storage">Analytics Cookie Label</label>
                        </th>
                        <td>
                            <input type="text" id="ccb_analytics_storage" name="ccb_analytics_storage" value="<?php echo esc_attr($analytics_storage); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_ga_id">Google Analytics ID (optional)</label>
                        </th>
                        <td>
                            <input type="text" id="ccb_ga_id" name="ccb_ga_id" value="<?php echo esc_attr($ga_id); ?>" class="regular-text" placeholder="UA-159534180-1" />
                            <p class="description">Enter your Google Analytics ID if you want to automatically connect it</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ccb_primary_color">Primary Color</label>
                        </th>
                        <td>
                            <input type="color" id="ccb_primary_color" name="ccb_primary_color" value="<?php echo esc_attr($primary_color); ?>" style="vertical-align: middle; margin-right: 10px;" />
                            <input type="text" id="ccb_primary_color_hex" name="ccb_primary_color_hex" value="<?php echo esc_attr($primary_color); ?>" placeholder="#00852D" class="regular-text" style="width: 120px; vertical-align: middle;" />
                            <p class="description">This color will be used for the title, buttons, and toggle switches. You can use the color picker or enter a hex code directly.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'ccb_save_settings'); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var colorPicker = $('#ccb_primary_color');
            var hexInput = $('#ccb_primary_color_hex');
            
            // Sync color picker to hex input
            colorPicker.on('change', function() {
                hexInput.val($(this).val());
            });
            
            // Sync hex input to color picker
            hexInput.on('input', function() {
                var hex = $(this).val();
                // Remove # if present
                hex = hex.replace('#', '');
                // Validate hex color
                if (/^[0-9A-Fa-f]{6}$/.test(hex)) {
                    colorPicker.val('#' + hex);
                } else if (/^[0-9A-Fa-f]{3}$/.test(hex)) {
                    // Expand 3-digit hex to 6-digit
                    hex = hex.split('').map(function(char) {
                        return char + char;
                    }).join('');
                    colorPicker.val('#' + hex);
                    $(this).val('#' + hex);
                }
            });
            
            // Format hex input on blur
            hexInput.on('blur', function() {
                var hex = $(this).val().replace('#', '');
                if (/^[0-9A-Fa-f]{3,6}$/.test(hex)) {
                    if (hex.length === 3) {
                        hex = hex.split('').map(function(char) {
                            return char + char;
                        }).join('');
                    }
                    $(this).val('#' + hex);
                    colorPicker.val('#' + hex);
                }
            });
        });
        </script>
        <?php
    }
}

// Initialize plugin
Cookie_Consent_Banner::get_instance();


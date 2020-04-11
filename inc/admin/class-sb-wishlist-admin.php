<?php

/**
 * SB Wishlist Admin class.
 *
 * @package SB Wishlist
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('SB_Wishlist_Admin') && class_exists('SB_Wishlist')) :

    class SB_Wishlist_Admin {

        const AJAX_ACTION = 'sbws';

        private static $instance = null;

        private function __construct() {
            /* Nothing here! */
        }

        public function __clone() {
            _doing_it_wrong(__FUNCTION__, __("Please don't clone SomethinBorrowed wishlist & styling plugin", 'sb-wishlist'), '1.0.0');
        }

        public function __wakeup() {
            _doing_it_wrong(__FUNCTION__, __("Please don't unserialize/wakeup SomethinBorrowed wishlist & styling plugin", 'sb-wishlist'), '1.0.0');
        }

        public static function instance() {
            if (is_null(self::$instance)) {
                self::$instance = new self();
                self::$instance->admin_init();
            }
            return self::$instance;
        }

        public function admin_init() {
            if (false === sbws_init()->get_data('admin') && false === sbws_init()->get_option('is_plugin_active')) {
                return;
            }

            /* Step actions */
            add_action('wp_ajax_' . self::AJAX_ACTION . '_save_settings', array($this, 'save_settings'));
            
            /* Step actions */
            add_action('wp_ajax_' . self::AJAX_ACTION . '_add_step', array($this, 'ajax_add_step'));
            add_action('wp_ajax_' . self::AJAX_ACTION . '_remove_step', array($this, 'ajax_remove_step'));

            /* Field actions */
            add_action('wp_ajax_' . self::AJAX_ACTION . '_add_field', array($this, 'ajax_add_field'));
            add_action('wp_ajax_' . self::AJAX_ACTION . '_remove_field', array($this, 'ajax_remove_field'));
            add_action('wp_ajax_' . self::AJAX_ACTION . '_update_field', array($this, 'ajax_update_field'));

            add_action('wp_ajax_' . self::AJAX_ACTION . '_get_form', array($this, 'ajax_get_form'));
            add_action('wp_ajax_' . self::AJAX_ACTION . '_get_field', array($this, 'ajax_get_field'));
            
            add_action('wp_ajax_' . self::AJAX_ACTION . '_get_field_options', array($this, 'ajax_get_field_options'));
            add_action('wp_ajax_' . self::AJAX_ACTION . '_update_field_options', array($this, 'ajax_update_field_options'));
            add_action('wp_ajax_' . self::AJAX_ACTION . '_save_selected_options', array($this, 'ajax_save_selected_options'));
            
            add_action('wp_ajax_' . self::AJAX_ACTION . '_add_image_options', array($this, 'ajax_add_image_options'));
            add_action('wp_ajax_' . self::AJAX_ACTION . '_save_image_options', array($this, 'ajax_save_image_options'));
            
            add_action('wp_ajax_' . self::AJAX_ACTION . '_save_text_options', array($this, 'ajax_save_text_options'));

            add_action('init', array($this, 'maybe_delete_transients'), 11);

            add_action('admin_menu', array($this, 'add_menu_page'));

            add_action('admin_init', array($this, 'register_settings'));
            
            add_filter('woocommerce_product_data_store_cpt_get_products_query', array($this, 'exclude_cat_query'), 10, 2);
            
            add_filter ('theme_page_templates', array($this,'add_page_template'));
            add_filter ('page_template', array($this, 'redirect_page_template'));
        }
        
        public function exclude_cat_query($query, $query_vars) {
            if (!empty($query_vars['exclude_category'])) {
                $query['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $query_vars['exclude_category'],
                    'operator' => 'NOT IN',
                );
            }
            return $query;
        }
        public function add_page_template ($templates) {
            $templates['template-checkout.php'] = 'Wishlist template';
            return $templates;
        }
        function redirect_page_template ($template) {
            $post = get_post();
            $page_template = get_post_meta( $post->ID, '_wp_page_template', true );
            if ('template-checkout.php' == basename ($page_template))
                $template = sbws_init()->get_plugin_path() . '/inc/admin/view/template-checkout.php';
            return $template;
        }

        public function maybe_delete_transients() {
            if (isset($_POST[sbws_init()->get_option_name()])) {
                if (isset($_POST['_wpnonce']) && !wp_verify_nonce($_POST['_wpnonce'], sbws_init()->get_slug() . '-options')) {
                    wp_die(__('You do not have sufficient permissions to delete transients.', 'sb-wishlist'));
                }
                self::delete_transients();
            } elseif (!sbws_init()->get_option('sbws_version', 0) || version_compare(sbws_init()->get_version(), sbws_init()->get_option('sbws_version', 0), '<')) {
                //sbws_init()->set_option('sbws_version', '1.0.1');
                self::delete_transients();
            }
        }

        private function delete_transients() {
            delete_site_transient(sbws_init()->get_option_name());
        }

        public function add_menu_page() {
            if (SBWS_ACTIVATED && !is_super_admin()) {
                return;
            }
            $page = add_menu_page(
                    __('Wishlisth & Styling', 'sb-wishlist'),
                    __('Wishlisth & Styling', 'sb-wishlist'),
                    'manage_options',
                    sbws_init()->get_slug(),
                    array($this, 'render_view_page'),
                    sbws_init()->get_plugin_url() . 'assets/img/menu-icon.png'
            );

            add_action('admin_print_styles-' . $page, array($this, 'admin_enqueue_style'));

            add_action('admin_footer-' . $page, array($this, 'render_templates'));
        }

        private function fonts_url() {
            $fonts_url = '';
            $font_families = array('Barlow+Condensed:300,400,500,600');
            $query_args = array(
                'family' => implode('|', $font_families),
                'display' => urlencode('swap'),
                'subset' => urlencode('latin'),
            );
            $fonts_url = add_query_arg($query_args, 'https://fonts.googleapis.com/css');
            return esc_url_raw($fonts_url);
        }

        public function admin_enqueue_style() {
            wp_enqueue_style('google-fonts', self::fonts_url(), array(), null);
            wp_enqueue_style('select2_css', 'https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css', array(), sbws_init()->get_version());

            $file_url = sbws_init()->get_plugin_url() . 'assets/css/sbws-styles.css';
            wp_enqueue_style(sbws_init()->get_slug(), $file_url, array(), sbws_init()->get_version());

            wp_enqueue_script('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/js/all.min.js', array(), '5.11.2');
            wp_enqueue_script('select2_js', 'https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js', array('jquery'), '4.0.12');
            wp_enqueue_editor();
            wp_enqueue_script(sbws_init()->get_slug(), sbws_init()->get_plugin_url() . 'assets/js/sbws-scripts.js', array('jquery', 'jquery-ui-dialog', 'wp-a11y', 'wp-util'), '1.0.0', true);

            $data = array(
                'nonce' => wp_create_nonce(self::AJAX_ACTION),
                'action' => self::AJAX_ACTION,
                'i18n' => array(
                    'save' => __('Save', 'sb-wishlist'),
                    'remove' => __('Remove', 'sb-wishlist'),
                    'cancel' => __('Cancel', 'sb-wishlist'),
                    'error' => __('An unknown error occurred. Try again.', 'sb-wishlist'),
                ),
            );
            wp_scripts()->add_data(
                    sbws_init()->get_slug(),
                    'data',
                    sprintf('var _sbWishlist = %s;', wp_json_encode($data))
            );
        }
        
        /* Render templates */
        public function render_templates() {
            ?>
            <script type="text/html" id="tmpl-sbws-form-step">
                <div class="sbws-form-step" data-id="{{ data.id }}">
                    <div class="step-header">
                        <h3 class="step-header-title">Step</h3>
                        <button>
                            <span class="dashicons dashicons-arrow-up"></span>
                        </button>
                    </div>
                    <div class="step-inner">
                        <div class="step-inner-wrap">
                            <div class="step-placeholder-text">
                                Unfortunately, it's empty at the moment. <br>
                                Add the questions to this step.
                            </div>
                        </div>
                        <div class="step-footer">
                            <div class="step-footer-buttons">
                                <button data-field="checkbox" class="button button-primary sbws-form-add-field-button">Add Checkbox</button>
                                <button data-field="radiobox" class="button button-primary sbws-form-add-field-button">Add Radiobox</button>
                                <button data-field="imagebox" class="button button-primary sbws-form-add-field-button">Add Imagebox</button>
                                <button data-field="yes_or_no" class="button button-primary sbws-form-add-field-button">Add Yes/No</button>
                                <button data-field="textbox" class="button button-primary sbws-form-add-field-button">Add Textbox</button>
                                <button data-field="plain_text" class="button button-primary sbws-form-add-field-button">Add plain text</button>
                                <button class="button button-alert sbws-form-remove-step-button"><i class="far fa-trash-alt"></i> Remove Step</button>
                            </div>
                        </div>
                    </div>
                </div>
            </script>
            
            <script type="text/html" id="tmpl-sbws-form-field">
                <div class="sbws-form-field" data-type="{{ data.type }}" data-field_id="{{ data.field_id }}" data-step_id="{{ data.step_id }}" data-order="{{ data.order }}">
                    <div class="sbws-field-col" data-field="sort">
                        <div class="move-item-handle">
                            <i class="fas fa-ellipsis-v"></i>
                        </div>
                    </div>
                    <div class="sbws-field-col" data-field="type">
                        {{ data.type }}
                    </div>
                    
                    <div class="sbws-field-col" data-field="name">
                        <div class="field-name">
                            <input type="text" class="field-control" name="field_name_{{ data.field_id }}" placeholder="Write a question" value="{{ data.name }}" disabled/>
                            <button class="field-edit"><i class="fas fa-pen"></i></button>
                        </div>
                    </div>
                    <# if(data.type !== 'yes_or_no' && data.type !== 'textbox' && data.type !== 'plain_text' && data.type !== 'imagebox') { #>
                    <div class="sbws-field-col" data-field="options">
                        <div class="field-options">
                            <h4 class="field-options-title">Get the options from:</h4>
                            <div class="field-options-controls">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="field_options_get_{{ data.field_id }}" id="field_options_get_{{ data.field_id }}_1" value="product_cat" {{ data.category == 'product_cat' ? 'checked' : '' }} >
                                    <label class="form-check-label" for="field_options_get_{{ data.field_id }}_1">Categories</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="field_options_get_{{ data.field_id }}" id="field_options_get_{{ data.field_id }}_2" value="product_tag" {{ data.category == 'product_tag' ? 'checked' : '' }} >
                                    <label class="form-check-label" for="field_options_get_{{ data.field_id }}_2">Tags</label>
                                </div>
                                <?php $attr_tax = wc_get_attribute_taxonomies(); $i = 3; ?>
                                <?php foreach($attr_tax as $item):  ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="field_options_get_{{ data.field_id }}" id="field_options_get_{{ data.field_id }}_<?php echo $i; ?>" value="pa_<?php echo $item->attribute_name; ?>" {{ data.category == 'pa_<?php echo $item->attribute_name; ?>' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="field_options_get_{{ data.field_id }}_<?php echo $i; ?>"><?php echo $item->attribute_label; ?></label>
                                </div>
                                <?php $i++; endforeach; ?>
                            </div>
                            <h4 class="field-options-title">Connected to:
                                <a href="#" class="field-options-select-save">Save</a>
                            </h4>
                            <div class="field-options-controls">
                                <select class="field-options-select" data-option="connect">
                                    <?php $cats = get_terms( 'product_cat', array( 'orderby' => 'name',  'order' => 'asc', 'hide_empty' => false)); ?>
                                    <?php foreach($cats as $item): ?>
                                        <option {{ data.connected == '<?php echo $item->term_id; ?>' ? 'selected' : '' }} value="<?php echo $item->term_id; ?>"><?php echo $item->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="sbws-field-col" data-field="variables"></div>
                    <# } #>
                    <# if(data.type === 'plain_text'){ #>
                    <div class="sbws-field-col" data-field="text_editor">
                        
                    </div>
                    <# } #>
                    <# if(data.type === 'imagebox'){ #>
                    <div class="sbws-field-col" data-field="options">
                        <div class="field-options">
                            <h4 class="field-options-title">Get the options from:</h4>
                            <div class="field-options-controls">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="field_options_get_{{ data.field_id }}" id="field_options_get_{{ data.field_id }}_1" value="product_cat" {{ data.category == 'product_cat' ? 'checked' : '' }} >
                                    <label class="form-check-label" for="field_options_get_{{ data.field_id }}_1">Categories</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="field_options_get_{{ data.field_id }}" id="field_options_get_{{ data.field_id }}_2" value="product_tag" {{ data.category == 'product_tag' ? 'checked' : '' }} >
                                    <label class="form-check-label" for="field_options_get_{{ data.field_id }}_2">Tags</label>
                                </div>
                                <?php $attr_tax = wc_get_attribute_taxonomies(); $i = 3; ?>
                                <?php foreach($attr_tax as $item):  ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="field_options_get_{{ data.field_id }}" id="field_options_get_{{ data.field_id }}_<?php echo $i; ?>" value="pa_<?php echo $item->attribute_name; ?>" {{ data.category == 'pa_<?php echo $item->attribute_name; ?>' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="field_options_get_{{ data.field_id }}_<?php echo $i; ?>"><?php echo $item->attribute_label; ?></label>
                                </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="sbws-field-col" data-field="images">
                        <div class="field-options">
                            <h4 class="field-options-title">Add options with images:</h4>
                            <div class='image-options-wrapper'>
                                
                            </div>
                            <div class='image-options-controls'>
                                <a href='#' class="button button-primary sbws-form-add-option-image">Add image option</a>
                                <a href="#" class="button button-primary field-options-image-save">Save</a>
                            </div>
                        </div>
                    </div>
                    <# } #>
                    <div class="sbws-field-col" data-field="controls">
                        <button class="button button-alert sbws-form-remove-field-button"><i class="far fa-trash-alt"></i></button>
                    </div>
                </div>
            </script>
            <script type="text/html" id="tmpl-sbws-form-field-options">
                <div class="field-options">
                    <h4 class="field-options-title">Check options:
                        <a href="#" class="field-options-select-save">Save</a>
                    </h4>
                    <div class="field-options-controls">  
                        <select class="field-options-select" multiple="multiple" value="{{ data.checked }}" data-option="terms">
                            <# _(data.values).each(function(el){ #>
                                <# if(_.contains(data.checked, el.term_id)){ #>
                                    <option selected value="{{ el.term_id }}">{{ el.name }}</option>
                                <# }else{ #>
                                    <option value="{{ el.term_id }}">{{ el.name }}</option>
                            <# }}); #>
                        </select>
                    </div>
                </div>
            </script>
            <script type="text/html" id="tmpl-sbws-text-field">
                <div class="field-options">
                    <h4 class="field-options-title">Add content:</h4>
                    <div class="field-options-controls">
                        <textarea rows='12' name="field_options_get_{{ data.field_id }}" class='sbws-text-editor' id="sbws-text-editor_{{ data.field_id }}">{{ data.text !== undefined ? data.text : '' }}</textarea>
                    </div>
                    <div class='image-options-controls'>
                        <a href="#" class="button button-primary field-options-textbox-save">Save</a>
                    </div>
                </div>
            </script>
            <script type='text/html' id='tmpl-sbws-image-field'>
                <div class='image-option-field'>
                    <div class='image-option-field-thumbnail'>
                        <a href='#' class='sbws-add-image-button'>
                            <div class='image-preview'>
                                <# if(data.image_url != undefined){ #>
                                <img class="image" src="{{ data.image_url }}">
                                <# }else{ #>
                                <img class="image" src="<?php echo sbws_init()->get_plugin_url() ?>assets/img/placeholder.png">
                                <# } #>
                            </div>
                            <input type='hidden' class='sbws-image-id' value='{{ data.image.img_id }}' />
                        </a>
                    </div>
                    <button class="button button-alert sbws-form-remove-image-option"><i class="far fa-trash-alt"></i></button>
                    <div class='image-option-field-settings'>
                        <input type="text" class="field-control" name="image_title" placeholder="Title" value="{{ data.image.title }}" />
                        <select class="field-options-select" value="{{ data.image.cat_id }}">
                            <# _(data.values).each(function(el){ #>
                                <# if(parseInt(data.image.cat_id) === el.term_id){ #>
                                    <option selected value="{{ el.term_id }}">{{ el.name }}</option>
                                <# }else{ #>
                                    <option value="{{ el.term_id }}">{{ el.name }}</option>
                            <# }}); #>
                        </select>
                    </div>
                </div>
            </script>
            <?php

        }

        /* Admin page init */
        public function render_view_page() {
            if (!current_user_can('manage_options')) {
                return;
            }
            require( sbws_init()->get_plugin_path() . 'inc/admin/view/admin.php' );
        }

        /* Parts */
        public function render_view_part_header() {
            require( sbws_init()->get_plugin_path() . 'inc/admin/view/parts/header.php' );
        }

        public function render_view_part_tabs() {
            require( sbws_init()->get_plugin_path() . 'inc/admin/view/parts/tabs.php' );
        }

        public function get_tabs() {
            $tabs = array(
                'settings' => __('Settings', 'sb-wishlist'),
                'form' => __('Form layout', 'sb-wishlist'),
                'styling' => __('Styling variables', 'sb-wishlist'),
                'wishlist' => __('Wishlist', 'sb-wishlist'),
                'users' => __('Users', 'sb-wishlist'),
                'analytics' => __('Analytics', 'sb-wishlist'),
                'export' => __('Export data', 'sb-wishlist'),
            );
            return $tabs;
        }

        /* Admin Pages */
        public function render_view_page_settings() {
            require( sbws_init()->get_plugin_path() . 'inc/admin/view/pages/settings.php' );
        }

        public function render_view_page_form() {
            require( sbws_init()->get_plugin_path() . 'inc/admin/view/pages/form.php' );
        }
        public function render_view_page_variables() {
            $list = self::get_variables_list();
            require( sbws_init()->get_plugin_path() . 'inc/admin/view/pages/variables.php' );
        }
        public function render_view_page_wishlist() {
            require( sbws_init()->get_plugin_path() . 'inc/admin/view/pages/wishlist.php' );
        }
        public function render_view_page_users() {
            require( sbws_init()->get_plugin_path() . 'inc/admin/view/pages/users.php' );
        }

        public function register_settings() {
            register_setting(sbws_init()->get_slug(), sbws_init()->get_option_name());

            /* Settings tab */
            add_settings_section(
                    sbws_init()->get_option_name() . '_settings_section',
                    __('Settings', 'sb-wishlist'),
                    array($this, ''),
                    sbws_init()->get_slug()
            );
            add_settings_field(
                'enable_styling',
                __('Enable styling suggestions', 'sb-wishlist'),
                array($this, 'options_settings_callback'),
                sbws_init()->get_slug(),
                sbws_init()->get_option_name() . '_settings_section',
                array('id' => 'settings_enable_styling', 'value' => false)
            );
            add_settings_field(
                'enable_wishlist',
                __('Enable wishlist', 'sb-wishlist'),
                array($this, 'options_settings_callback'),
                sbws_init()->get_slug(),
                sbws_init()->get_option_name() . '_settings_section',
                array('id' => 'settings_enable_wishlist', 'value' => false)
            );
            add_settings_field(
                'enable_form',
                __('Enable registration form', 'sb-wishlist'),
                array($this, 'options_settings_callback'),
                sbws_init()->get_slug(),
                sbws_init()->get_option_name() . '_settings_section',
                array('id' => 'settings_enable_form', 'value' => false)
            );
        }

        /* Settings tab options */
        public function options_settings_callback($args) {
            require( sbws_init()->get_plugin_path() . 'inc/admin/view/callback/settings/settings.php' );
        }

        public function ajax_add_step() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }

            global $wpdb;
            $table = $wpdb->prefix . 'sbws_form';

            $data = array();
            $wpdb->insert($table, array(
                'option_name' => 'form_step',
                'option_value' => ''
            ));
            $id = $wpdb->insert_id;

            $response['id'] = $id;
            wp_send_json_success($response);
        }

        public function ajax_remove_step() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }

            global $wpdb;
            $table = $wpdb->prefix . 'sbws_form';
            $id = $_POST['id'];
            $wpdb->delete($table, array('option_id' => $id), array('%d'));

            $meta = $wpdb->prefix . 'sbws_formmeta';
            $wpdb->delete($meta, array('form_id' => $id), array('%d'));
            
            $table_vars = $wpdb->prefix . 'sbws_formvariables';
            $wpdb->delete($table_vars, array('option_form_id' => $id), array('%d'));

            $response['success'] = 'success';
            wp_send_json_success($response);
        }

        public function ajax_add_field() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }

            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            $fieldType = $_POST['field'];
            $stepID = $_POST['step_id'];
            $order = $_POST['order'];

            $wpdb->insert($table, array(
                'form_id' => $stepID,
                'meta_type' => $fieldType,
                'meta_name' => '',
                'meta_order' => $order,
                'meta_category' => 'product_cat',
                'meta_category_data' => ''
            ));
            $fieldID = $wpdb->insert_id;
            
            $table_vars = $wpdb->prefix . 'sbws_formvariables';
            $wpdb->insert($table_vars, array(
                'option_name' => 'field_'.$wpdb->insert_id,
                'option_active' => 0,
                'option_mandatory' => 0,
                'option_score' => 0,
                'option_field_id' => $wpdb->insert_id,
                'option_form_id' => $stepID
            ));

            $response['field_id'] = $fieldID;
            wp_send_json_success($response);
        }

        public function ajax_remove_field() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            $id = $_POST['id'];
            $wpdb->delete($table, array('meta_id' => $id), array('%d'));
            
            $table_vars = $wpdb->prefix . 'sbws_formvariables';
            $wpdb->delete($table_vars, array('option_field_id' => $id), array('%d'));

            $response['success'] = 'success';
            wp_send_json_success($response);
        }
           
        public function ajax_update_field() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            
            $data = $_POST['data'];
            $wpdb->update($table, array('meta_name' => $data['name'],'meta_order'=>$data['order']), array('meta_id' => $data['id']));
            
            $table_vars = $wpdb->prefix . 'sbws_formvariables';
            $wpdb->update($table_vars, array('option_name' => $data['name']), array('option_field_id' => $data['id']));
            
            $response['success'] = 'success';
            wp_send_json_success($response);
        }

        public function ajax_get_form() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }

            global $wpdb;
            $table = $wpdb->prefix . 'sbws_form';
            $result = $wpdb->get_results("SELECT * FROM $table ORDER BY option_id ASC");

            $response['data'] = $result;
            wp_send_json_success($response);
        }
        
        public function ajax_get_field() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }

            $stepID = $_POST['step_id'];
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            $result = $wpdb->get_results("SELECT * FROM $table WHERE form_id = $stepID ORDER BY meta_order ASC");
           /* $count = count($result);
            for($i = 0; $i < $count; $i++){
                $data = unserialize($result[$i]->meta_value);
                $result[$i]->meta_value  = $data;
            }*/
            wp_send_json_success($result);
        }
        
        public function ajax_get_field_options() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            
            $data = $_POST['data'];
            $field_ID = $data['id'];
            $result = $wpdb->get_results("SELECT * FROM $table WHERE meta_id = $field_ID");
            $cat_args = array(
                'orderby'    => 'name',
                'order'      => 'asc',
                'hide_empty' => false,
            );
            
            if($result[0]->meta_type === 'imagebox'){
                $json['categories'] = get_terms( $data['category'], $cat_args );
                $json['images'] = unserialize($result[0]->meta_category_data);
            }elseif($result[0]->meta_type === 'plain_text'){
                $json['text'] = $result[0]->meta_category_data;
            }elseif($result[0]->meta_type === 'checkbox' || $result[0]->meta_type === 'radiobox'){
                $json['categories'] = get_terms( $data['category'], $cat_args );
                $json['checked'] = array_map('intval', unserialize($result[0]->meta_category_data));
            }else{
                $json['success'] = true;
            }
            wp_send_json_success($json);
        }
        public function ajax_update_field_options() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            
            $data = $_POST['data'];
            $wpdb->update($table, array('meta_category' => $data['category']), array('meta_id' => $data['id']));
            
            wp_send_json_success($data);
        }
        public function ajax_save_selected_options() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            
            $data = $_POST['data'];
            if($data['type'] == 'terms'){
                $wpdb->update($table, array('meta_category_data' => serialize($data['values'])), array('meta_id' => $data['id']));
                $data = array('success' => true);
            }else{
                $wpdb->update($table, array('meta_connected' => intval($data['values'])), array('meta_id' => $data['id']));
            }
            
            wp_send_json_success($data);
        }
        public function ajax_add_image_options(){
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            
            $data = $_POST['data'];
            $field_ID = $data['id'];
            $cat_args = array(
                'orderby'    => 'name',
                'order'      => 'asc',
                'hide_empty' => false,
            );
            $json['categories'] = get_terms( $data['category'], $cat_args );
            $json['images'] = array('img_id' => '', 'title' => '', 'cat_id' => '');
            wp_send_json_success($json);
        }
        public function ajax_save_image_options(){
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            $id = $_POST['id'];
            $data = $_POST['data'];
            $wpdb->update($table, array('meta_category_data' => serialize($data)), array('meta_id' => $id));
            $response['success'] = 'success';
            wp_send_json_success($response);
        }
        public function ajax_save_text_options(){
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            $id = $_POST['id'];
            $data = $_POST['data'];
            $wpdb->update($table, array('meta_category_data' => $data), array('meta_id' => $id));
            $response['success'] = 'success';
            wp_send_json_success($response);
        }
        
        public function get_variables_list(){
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formvariables';
            $result = $wpdb->get_results("SELECT * FROM $table");
            return $result;
        }
        public function get_wishlist(){
            $paged = $_GET['paged'] ? absint($_GET['paged']) : 1;
            $args = array(
                'limit' => 50,
                'page' => $paged,
                'paginate' => true,
                'status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
                'exclude_category' => 'okategoriserad',
            );
            $products = wc_get_products($args);
            return $products;
        }
        
        public function save_settings(){
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            
            $data = $_POST['data'];
            $tab = $_POST['tab'];
             
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formvariables';
            
            foreach ($data as $item) {
                $wpdb->update($table, array(
                        'option_active' => $item['option_active'],
                        'option_mandatory' => $item['option_mandatory'],
                        'option_score' => $item['option_score']
                    ), 
                    array('option_id' => $item['option_id']));
            }
            wp_send_json_success(array('success' => true));
        }
        
        public function get_subscribed_users(){
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_users';
            
            $users = $wpdb->get_results("SELECT * FROM $table");
            
            return $users;
        }
        public function get_user_fields(){
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            
            $fields = $wpdb->get_results("SELECT meta_id, meta_type, meta_name, meta_category, meta_category_data FROM $table WHERE NOT meta_type = 'plain_text' ORDER BY meta_id ASC");
            
            return $fields;
        }
        
        public function get_suggested_list($userID){
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            
            $fields = $wpdb->get_results("SELECT meta_id, meta_category, meta_connected FROM $table WHERE meta_type = 'checkbox' ORDER BY meta_id ASC");
            /*$cats = array_map(array($this, 'get_cats'), $fields);
            /*$cats = array();
            foreach ($fields as $f) {
                array_push($cats, [
                    'taxonomy' => 'product_cat',
                    'terms' => intval($f->meta_connected),
                ]);
            }*/
            
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => '12',
                'status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
                'exclude_category' => 'okategoriserad',
                'stock_status' => 'instock',
                'tax_query' => array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'id',
                            'terms' => array(109),
                            'operator' => 'IN'
                        ),
                        array(
                            'taxonomy' => 'pa_storlek',
                            'field' => 'id',
                            'terms' => array(24, 26),
                            'operator' => 'IN'
                        )
                    ),
                    array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'id',
                            'terms' => array(109),
                            'operator'  => 'IN'
                        ),
                        array(
                            'taxonomy' => 'pa_storlek',
                            'field' => 'id',
                            'terms' => array(24, 26),
                            'operator' => 'IN'
                        )
                    ),
                    array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'id',
                            'terms' => array(109),
                            'operator' => 'IN'
                        ),
                        array(
                            'taxonomy' => 'pa_storlek',
                            'field' => 'id',
                            'terms' => array(24, 26),
                            'operator' => 'IN'
                        )
                    ),
                )
                
            );
            $products = wc_get_products( $args );
            return $products;
        }
         private function get_cats($f){
            return array(
                'taxonomy' => 'product_cat',
                'terms' => intval($f->meta_connected),
            );
        }
    }
endif;

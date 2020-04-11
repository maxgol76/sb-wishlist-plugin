<?php
/**
 * SB Wishlist fields builder.
 *
 * @package SB Wishlist
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'SB_Wishlist_Form' ) ) :
    final class SB_Wishlist_Form {
        
        const AJAX_ACTION = 'sbws';
        
        private static $instance = null;
        
        private function __construct() {
            /* Nothing here! */
        }

	public function __clone() {
            _doing_it_wrong( __FUNCTION__, __("Please don't clone SomethinBorrowed wishlist & styling plugin", 'sb-wishlist'), '1.0.0' );
	}

	public function __wakeup() {
            _doing_it_wrong( __FUNCTION__, __("Please don't unserialize/wakeup SomethinBorrowed wishlist & styling plugin", 'sb-wishlist'), '1.0.0' );
	}
        
        public static function instance(){
            if( !isset( self::$instance)){
                self::$instance = new self();
                self::$instance->form_init();
            }
            
            return self::$instance;
        }
        
        public function form_init(){
            
            add_action('wp_ajax_' . self::AJAX_ACTION . '_get_form', array($this, 'ajax_get_form'));
            add_action('wp_ajax_' . self::AJAX_ACTION . '_get_fields', array($this, 'ajax_get_fields'));
            add_action('wp_ajax_' . self::AJAX_ACTION . '_submit_form', array($this, 'ajax_submit_form'));
            add_action('wp_ajax_nopriv_' . self::AJAX_ACTION . '_get_form', array($this, 'ajax_get_form'));
            add_action('wp_ajax_nopriv_' . self::AJAX_ACTION . '_get_fields', array($this, 'ajax_get_fields'));
            add_action('wp_ajax_nopriv_' . self::AJAX_ACTION . '_submit_form', array($this, 'ajax_submit_form'));

            
            add_action('wp_footer', array($this, 'render_templates'),21);
            add_action('wp_enqueue_scripts', array($this, 'form_scripts'),30);
            add_filter( 'woocommerce_locate_template', array($this,'woo_addon_plugin_template'), 10, 3 );
            add_filter( 'woocommerce_account_menu_items', array($this,'woo_addon_menu_item'), 10, 1);
            add_action( 'woocommerce_account_styling-preferences_endpoint', array($this, 'woo_addon_endpoint_content'),10, 1 );
            add_action( 'init', array($this, 'woo_addon_flush_rewrite_rules'), 10, 1 );

        }
        
        public function woo_addon_plugin_template( $template, $template_name, $template_path ) {
            
                global $woocommerce;
                $_template = $template;
                if (!$template_path)
                    $template_path = $woocommerce->template_url;

                $plugin_path = sbws_init()->get_plugin_path() . '/woocommerce/';

                // Look within passed path within the theme - this is priority
                $template = locate_template(
                        array(
                            $template_path . $template_name,
                            $template_name
                        )
                );

                if (!$template && file_exists($plugin_path . $template_name))
                    $template = $plugin_path . $template_name;

                if (!$template)
                    $template = $_template;
                
                return $template;
        }
        public function insert_after_helper( $items, $new_items, $after ) {
            $position = array_search( $after, array_keys( $items ) ) + 1;
            $array = array_slice( $items, 0, $position, true );
            $array += $new_items;
            $array += array_slice( $items, $position, count( $items ) - $position, true );

            return $array;
        }
        public function woo_addon_menu_item( $items ) {
            $new_items = array();
            $new_items['styling-preferences'] = __( 'Styling preferences', 'sb-wishlist' );

            return self::insert_after_helper( $items, $new_items, 'subscriptions' );
        }
        public function woo_addon_endpoint_content() {
            $plugin_path = sbws_init()->get_plugin_path() . '/woocommerce/';
            include_once $plugin_path . 'myaccount/styling_preferences.php';
        }

        public function woo_addon_flush_rewrite_rules() {
            add_rewrite_endpoint( 'styling-preferences', EP_ROOT | EP_PAGES );
            flush_rewrite_rules();
        }

        public function form_scripts(){
            wp_enqueue_style('sbws-form', sbws_init()->get_plugin_url() . 'assets/css/sbws-form.css', array(), '1.0.0');
            
            wp_enqueue_script('sbws-form', sbws_init()->get_plugin_url() . 'assets/js/sbws-form.js', array('jquery', 'wp-api', 'wp-a11y', 'wp-util'), '1.0.0', true);
        
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
                'sbws-form',
                'data',
                sprintf('var _sbWishlist = %s;', wp_json_encode($data))
            );  
        }
        public function get_wishlist_form($order_id){
            $order = wc_get_order( $order_id );
            $is_subscription = false;     
            foreach ( $order->get_items() as $item_id => $item ) {
                if(in_array($item['product_id'], sbws_init()->get_option( 'form_product' ))){
                    $is_subscription = true;
                }
            }
            if($is_subscription && !self::check_if_user_exist($order->customer_id)){
                $result = self::add_new_user($order);
            }
            if($is_subscription && sbws_init()->get_option( 'enable_form' )){ ?>
                <div class="sbws-form" id='sbws-form'>
                    <form class="sbws-form-wrap" method="POST">
                        <input type="hidden" value="<?php echo $order->customer_id; ?>" name="sb_user_id" />
                    </form>
                    <div class="sbws-form-controls">
                        <div class="sbws-form-controls-points">
                            <div class='progress'></div>
                        </div>
                        <div class="sbws-form-controls-buttons">
                            <button type="button" class="btn btn-skip">Skip</button>
                            <button type="button" class="btn btn-next">Next</button>
                        </div>
                    </div>
                </div>
        <?php
            }
        }
        private function add_new_user($order){
            $userID = $order->customer_id;
            $date_start = $order->order_date;
            
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_users';
            $result = $wpdb->insert($table, array('user_id' => $userID, 'user_date_start' => $date_start, 'user_date_end' => $date_start, 'user_active' => true, 'user_data' => ''));
            
            return $result;
        }
        private function check_if_user_exist($userID){
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_users';
            $result = $wpdb->get_results("SELECT user_id FROM $table WHERE user_id = $userID");
            if(intval($result[0]->user_id) === $userID) {
                return true;
            }
            else {
                return false;
            }
        }
        public function get_current_user($userID){
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_users';
            $result = $wpdb->get_results("SELECT * FROM $table WHERE user_id = $userID");
            
            return $result;
        }
        public function get_step_data(){
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_form';
            $result = $wpdb->get_results("SELECT * FROM $table ORDER BY option_id ASC");
            return $result;
        }
        public function get_meta_data($step_id){
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_formmeta';
            $result = $wpdb->get_results("SELECT * FROM $table WHERE form_id = $step_id ORDER BY meta_order ASC");
            return $result;
        }
        
        public function render_templates(){
            ?>
            <script type="text/html" id="tmpl-sbws-form-step">
                <div class="sbws-form-step" data-id="{{ data.id }}" >
                    <div class="sbws-step-content">
                    </div>
                    <div class="alert alert-error" style="display: none;">All field are required! Please, make your choice or skipt the step.</div>
                </div>
            </script>
            <script type="text/html" id="tmpl-sbws-form-field">
                <div class="sbws-form-field" data-type="{{ data.type }}" data-field_id="{{ data.field_id }}" data-step_id="{{ data.step_id }}">
                    <div class="sbws-form-field-header">
                        <h3 class="sbws-form-field-title">{{ data.name }}</h3>
                    </div>
                    <div class="sbws-form-field-content">
                    
                    <# if(data.type == 'checkbox'){ #>
                        <# _.each(data.meta,function(el){ #>
                        <div class="form-check form-checkbox">
                            <input class="form-check-input" type="checkbox" name="field_option_{{ data.field_id }}[]" id="field_id_{{ data.field_id }}_{{ el.term_id }}" value="{{ el.term_id }}">
                            <label class="form-check-label" for="field_id_{{ data.field_id }}_{{ el.term_id }}">{{ el.name }}</label>
                        </div>
                        <# }); #>
                    <# }else if(data.type == 'radiobox'){ #>
                        <# _.each(data.meta,function(el){ #>
                        <div class="form-check form-radio">
                            <input class="form-check-input" type="radio" name="field_option_{{ data.field_id }}" id="field_id_{{ data.field_id }}_{{ el.term_id }}" value="{{ el.term_id }}">
                            <label class="form-check-label" for="field_id_{{ data.field_id }}_{{ el.term_id }}">{{ el.name }}</label>
                        </div>
                        <# }); #>
                    <# }else if(data.type == 'imagebox'){ #>
                        <# _.each(data.meta,function(el){ #>
                        <# var img = new wp.api.models.Media({ id: el.img_id }); img.fetch().done(function(response){ 
                            img = document.createElement('img'), container = document.getElementById('form_field_'+el.cat_id);
                            img.setAttribute('src', response.source_url);
                            container.prepend(img);
                        }); #>
                        <div class="form-check form-image">
                            <input class="form-check-input" type="radio" name="field_option_{{ data.field_id }}" id="field_id_{{ el.cat_id }}" value="{{ el.cat_id }}">
                            <label class="form-check-label" id='form_field_{{ el.cat_id }}' for="field_id_{{ el.cat_id }}"><span>{{ el.title }}</span></label>
                        </div>
                        <# }); #>
                    <# }else if(data.type == 'yes_or_no'){ #>
                        <div class="form-check form-check-inline form-radio">
                            <input class="form-check-input" type="radio" name="field_option_{{ data.field_id }}" id="field_id_{{ data.field_id }}_1" value="1">
                            <label class="form-check-label" for="field_id_{{ data.field_id }}_1">Yes</label>
                        </div>
                        <div class="form-check form-check-inline form-radio">
                            <input class="form-check-input" type="radio" name="field_option_{{ data.field_id }}" id="field_id_{{ data.field_id }}_2" value="0">
                            <label class="form-check-label" for="field_id_{{ data.field_id }}_2">No</label>
                        </div>
                    <# }else if(data.type == 'textbox'){ #>
                        <div class='form-group'>
                            <textarea name="field_option_{{ data.field_id }}" class='form-control'></textarea>
                        </div>
                    <# }else{ #>
                        <div class='plain-text-group' id="plain-text_{{data.field_id}}">
                            {{{ data.meta }}}
                        </div>
                    <# } #>
                    </div>
                </div>
            </script>
            <?php
        }
        
        public function ajax_get_form() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            
            $result = self::$instance->get_step_data();
            $response['data'] = $result;
            wp_send_json_success($response);
        }
        public function ajax_get_fields() {
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            
            $stepID = $_POST['step_id'];
            $result = self::$instance->get_meta_data($stepID);
            $count = count($result);
            for($i = 0; $i < $count; $i++){
                if($result[$i]->meta_type === 'imagebox'){
                    $data = unserialize($result[$i]->meta_category_data);
                    $result[$i]->meta_category_data = $data;
                }elseif($result[$i]->meta_type === 'checkbox' || $result[$i]->meta_type === 'radiobox'){
                    $data = array_map('intval', unserialize($result[$i]->meta_category_data));
                    $cat_args = array(
                        'orderby'       => 'name',
                        'order'         => 'asc',
                        'hide_empty'    => false,
                        'include'       => $data
                    );
                    $cats = get_terms( $result[$i]->meta_category, $cat_args );
                    $result[$i]->meta_category_data = $cats;
                }else{
                    $data = apply_filters('the_content', $result[$i]->meta_category_data);
                    $result[$i]->meta_category_data = $data;
                }
            }
            wp_send_json_success($result);
        }
        public function ajax_submit_form(){
            if (!check_ajax_referer(self::AJAX_ACTION, 'nonce', false)) {
                status_header(400);
                wp_send_json_error('bad_nonce');
            } elseif ('POST' !== $_SERVER['REQUEST_METHOD']) {
                status_header(405);
                wp_send_json_error('bad_method');
            }
            $data = $_POST['data'];
            $user = array_shift($data);
            
            $data = array_map('clean_submit_form', $data);
            
            global $wpdb;
            $table = $wpdb->prefix . 'sbws_users';
            $wpdb->update($table, array('user_data' => serialize($data)), array('user_id' => $user['value']));
            
            $response['form'] = $data;
            wp_send_json_success($response);
        }
        
        
        
    }
endif;
<?php
/**
 * SB Wishlist class.
 *
 * @package SB Wishlist
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'SB_Wishlist' ) ) :
    final class SB_Wishlist {
        
        private $version;
        private $slug;
        private $plugin_url;
        private $plugin_path;
        private $page_url;
        private $option_name;
        
        private $data;
    
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
                self::$instance->setup();
                self::$instance->add_includes();
                self::$instance->add_actions();
            }
            
            return self::$instance;
        }
        
        private function setup(){
            /* Setup variables */
            $this->data        = new stdClass();
            $this->version     = SBWS_VERSION;
            $this->slug        = SBWS_SLUG;
            $this->option_name = self::sanitize_key( $this->slug );
            $this->plugin_url  = SBWS_URI;
            $this->plugin_path = SBWS_PATH;
            $this->page_url    = admin_url( 'admin.php?page=' . $this->slug );
            $this->data->admin = true;
        }
        
        private function add_includes() {
            require $this->plugin_path . '/inc/admin/class-sb-wishlist-admin.php';
            require $this->plugin_path . '/inc/admin/class-sb-wishlist-form.php';
            require $this->plugin_path . '/inc/admin/functions.php';
            // Incoming Work
            //require $this->plugin_path . '/inc/class-sb-wishlist-suggestion.php';
            //require $this->plugin_path . '/inc/class-sb-wishlist-form.php';
        }
        
        private function add_actions() {
            // Activate plugin
            register_activation_hook(SBWS_CORE_FILE, array($this, 'activate'));
            
            // Deactivate plugin
            register_deactivation_hook(SBWS_CORE_FILE, array($this, 'deactivate'));
            
            // Load the textdomain
            add_action( 'init', array( $this, 'load_textdomain' ) );
            
            add_action( 'init', array( $this, 'admin' ) );
        }

        /*
         * Activate plugin
         * @since 1.0.0
         */
        public function activate(){
            global $wpdb;
            $charset = $wpdb->get_charset_collate();
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            
            $table_form = $wpdb->prefix . 'sbws_form';
            $sql_form = "CREATE TABLE $table_form (
                option_id BIGINT NOT NULL AUTO_INCREMENT ,
                option_name VARCHAR(255) NOT NULL ,
                option_value LONGTEXT NOT NULL ,
                PRIMARY KEY (option_id))
                $charset";
            dbDelta( $sql_form );
            
            $table_formmeta = $wpdb->prefix . 'sbws_formmeta';
            $sql_formmeta = "CREATE TABLE $table_formmeta (
                meta_id BIGINT NOT NULL AUTO_INCREMENT ,
                form_id BIGINT NOT NULL ,
                meta_type VARCHAR(255) NOT NULL ,
                meta_name VARCHAR(255) NOT NULL ,
                meta_order BIGINT NOT NULL ,
                meta_category  VARCHAR(255) NOT NULL ,
                meta_connected BIGINT NOT NULL ,
                meta_category_data LONGTEXT NOT NULL ,
                PRIMARY KEY (meta_id))
                $charset";
            dbDelta( $sql_formmeta );
            
            $table_formvars = $wpdb->prefix . 'sbws_formvariables';
            $sql_form = "CREATE TABLE $table_formvars (
                option_id BIGINT NOT NULL AUTO_INCREMENT ,
                option_name VARCHAR(255) NOT NULL ,
                option_active BOOLEAN NOT NULL ,
                option_mandatory BOOLEAN NOT NULL ,
                option_score INT NOT NULL ,
                option_field_id INT NOT NULL ,
                option_form_id INT NOT NULL ,
                PRIMARY KEY (option_id))
                $charset";
            dbDelta( $sql_form );
            
            $table_users = $wpdb->prefix . 'sbws_users';
            $sql_form = "CREATE TABLE $table_users (
                id BIGINT NOT NULL AUTO_INCREMENT,
                user_id BIGINT NOT NULL,
                user_date_start DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
                user_date_end DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
                user_active BOOLEAN NOT NULL ,
                user_data LONGTEXT NOT NULL ,
                PRIMARY KEY (id))
                $charset";
            dbDelta( $sql_form );

            $table_dislike_list = $wpdb->prefix . 'sbws_dislike_list';
            $sql_form = "CREATE TABLE $table_dislike_list (
                id BIGINT NOT NULL AUTO_INCREMENT,
                prod_id BIGINT NOT NULL,
                quantity BIGINT NOT NULL,
                user_id BIGINT NOT NULL,                
                dateadded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,                
                PRIMARY KEY (id))
                $charset";
            dbDelta( $sql_form );

            $table_like_list = $wpdb->prefix . 'sbws_like_list';
            $sql_form = "CREATE TABLE $table_like_list (
                id BIGINT NOT NULL AUTO_INCREMENT,
                prod_id BIGINT NOT NULL,
                quantity BIGINT NOT NULL,
                user_id BIGINT NOT NULL,                
                dateadded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,                
                PRIMARY KEY (id))
                $charset";
            dbDelta( $sql_form );

            self::set_plugin_state( true );
        }
        
        /*
         * Deactivate plugin
         * @since 1.0.0
         */
        public function deactivate(){
            global $wpdb;
            
            /*$table_form = $wpdb->prefix . 'sbws_form';
            $sql_form = "DROP TABLE IF EXISTS $table_form";
            $wpdb->query($sql_form);
            
            $table_formmeta = $wpdb->prefix . 'sbws_formmeta';
            $sql_formmeta = "DROP TABLE IF EXISTS $table_formmeta";
            $wpdb->query($sql_formmeta);
            
            $table_formvars = $wpdb->prefix . 'sbws_formvariables';
            $sql_formvars = "DROP TABLE IF EXISTS $table_formvars";
            $wpdb->query($sql_formvars);*/
            
            self::set_plugin_state( false );
        }
        
        /*
         * Load translations
         * @since 1.0.0
         */
        public function load_textdomain() {
            load_plugin_textdomain( 'sb-wishlist', false, SBWS_PATH . 'languages/' );
        }
        
        /*
         * Sanitize data key
         * @since 1.0.0
         */
        private function sanitize_key( $key ) {
            return preg_replace( '/[^A-Za-z0-9\_]/i', '', str_replace( array( '-', ':' ), '_', $key ) );
        }
        
        /*
         * Convert data arrays to objects
         * @since 1.0.0
         */
        private function convert_data( $array ) {
            foreach ((array) $array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = self::convert_data($value);
                }
            }
            return (object) $array;
        }
        
        /*
         * Set plugin state
         * @since 1.0.0
         */
        private function set_plugin_state( $value ) {
            self::set_option( 'is_plugin_active', $value );
        }
        
        /*
         * Set option value
         * @since 1.0.0
         */
        public function set_option($name, $option){
            $options = self::get_options();
            $name = self::sanitize_key( $name );
            $options[ $name ] = esc_html( $option );
            $this->set_options($options);
        }
        
        /*
         * Set the options
         * @since 1.0.0
         */
        public function set_options( $options ) {
            update_option( $this->option_name, $options );
        }
        
        /*
         * Return the options
         * @since 1.0.0
         */
        public function get_options() {
            return get_option( $this->option_name, array() );
        }
        
        /*
         * Return option value
         * @since 1.0.0
         */
        public function get_option( $name, $default = '' ) {
            $options = self::get_options();
            $name    = self::sanitize_key( $name );
            return isset( $options[ $name ] ) ? $options[ $name ] : $default;
        }
        
        /*
         * Get data
         * @since 1.0.0
         */
        public function set_data($key, $data) {
            if (!empty($key)) {
                if (is_array($data)) {
                    $data = self::convert_data($data);
                }
                $key = self::sanitize_key($key);
                $this->data->$key = $data;
            }
        }

        /*
         * Get data
         * @since 1.0.0
         */
        public function get_data($key) {
            return isset($this->data->$key) ? $this->data->$key : '';
        }
        
        /*
         * Get slug
         * @since 1.0.0
         */
        public function get_slug(){
            return $this->slug;
        }
        
        /*
         * Get version
         * @since 1.0.0
         */
        public function get_version() {
            return $this->version;
        }
        
        /*
         * Return the plugin url
         * @since 1.0.0
         */
        public function get_plugin_url() {
            return $this->plugin_url;
        }

        /*
         * Return the plugin path
         * @since 1.0.0
         */
        public function get_plugin_path() {
            return $this->plugin_path;
        }

        /*
         * Return the plugin page URL
         * @since 1.0.0
         */
        public function get_page_url() {
            return $this->page_url;
        }
        
         /*
         * Return the option settings name
         * @since 1.0.0
         */
        public function get_option_name() {
            return $this->option_name;
        }
        
        public function admin(){
            return SB_Wishlist_Admin::instance();
        }

    }
endif;

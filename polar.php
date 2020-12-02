<?php defined('ABSPATH') or die('No script kiddies please!');
/**
* Plugin Name: Polar Auto Post
* Plugin URI: https://wordpress.org/
* Description: A plugin to publish your wordpress posts to your facebook profile and fan pages.
* Version: 1.0.0
* Author: Villager
* Author URI: http://localhost/wordpress
* Text Domain: polar
* Domain Path: /languages/
* License: GPL2
*/

/**
 * Declaration of plugin main class
 * */
class Polar {

    var $polar_settings;
    var $polar_extra_settings;

    /**
     * Constructor
     */
    function __construct() {
        $this->polar_settings = get_option('polar_settings');
        $this->polar_extra_settings = get_option('polar_extra_settings');
        $this->define_constants();
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));

        add_action('admin_init', array($this, 'plugin_init')); 
        add_action('admin_menu', array($this, 'polar_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets')); 

        add_action('admin_post_polar_fb_authorize_action', array($this, 'fb_authorize_action')); 
        add_action('admin_post_polar_callback_authorize', array($this, 'polar_callback_authorize')); 
        add_action('admin_post_polar_form_action', array($this, 'polar_form_action')); 
       
        add_action('admin_post_polar_clear_log', array($this, 'polar_clear_log')); 
        add_action('admin_post_polar_delete_log', array($this, 'delete_log'));
        add_action('admin_post_polar_restore_settings', array($this, 'restore_settings')); 

        add_action('add_meta_boxes', array($this, 'add_polar_meta_box')); 
        add_action('save_post', array($this, 'save_polar_meta_value'));

        add_action('admin_init', array($this, 'auto_post_trigger')); 
        add_action('future_to_publish', array($this, 'auto_post_schedule'));
        add_action( 'transition_post_status',  array($this,'auto_post'), 10, 3 );

         // Facebook Mobile API: Ajax Action for generating Access Token from given Email and Password
        add_action('wp_ajax_polar_access_token_ajax_action', array($this, 'polar_access_token_ajax_action'));
        add_action('wp_ajax_nopriv_polar_access_token_ajax_action', array($this, 'polar_access_token_ajax_action'));
        // Ajax Action for getting the list of all the pages and groups associated with the email address
        add_action('wp_ajax_polar_add_account_action', array($this, 'polar_add_account_action'));
        add_action('wp_ajax_nopriv_polar_add_account_action', array($this, 'polar_add_account_action'));

        add_action( 'admin_init', array( $this, 'redirect_to_site' ), 1 );
        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
        add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ) );
    }

    /**
      * Activation 
     **/
    function plugin_activation() {
        $polar_settings = $this->get_default_settings();
        $polar_extra_settings = array('authorize_status' => 0);
        if (!get_option('polar_settings')) {
            update_option('polar_settings', $polar_settings);
            update_option('polar_extra_settings', $polar_extra_settings);
        }

        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $log_table_name = $wpdb->prefix . "polar_logs";


        $log_tbl_query = "CREATE TABLE IF NOT EXISTS $log_table_name (
                            log_id INT NOT NULL AUTO_INCREMENT,
                            PRIMARY KEY(log_id),
                            post_id INT NOT NULL,
                            log_status INT NOT NULL,
                            log_time VARCHAR(255),
                            log_details TEXT
                          ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($log_tbl_query);
    
    } 

    /**
     * Starts session on admin_init hook
     */
    function plugin_init() {
        if (!session_id()) {
            session_start();
            session_write_close();
        }
        load_plugin_textdomain( 'polar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
      *  Admin
     **/
    function polar_admin_menu() {
        add_menu_page(__('Polar', 'polar'), __('Polar', 'polar'), 'manage_options', 'polar', array($this, 'plugin_settings'),'dashicons-facebook-alt');
    }

    /**
      * Default Settings
     **/
    function get_default_settings() {
        $default_settings = array('auto_publish' => 0,
            'application_id' => '',
            'application_secret' => '',
            'facebook_user_id' => '',
            'message_format' => '',
            'post_format' => 'simple',
            'include_image'=>0,
            'post_image' => 'featured',
            'custom_image_url' => '',
            'auto_post_pages' => array(),
            'post_types' => array(),
            'category' => array());
        return $default_settings;
    }
    
    function admin_footer_text( $text ){
        if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'polar') {
            $link = 'https://wordpress.org/support/plugin/polar/reviews/#new-post';
            $pro_link = 'https://localhost/wordpress/';
            $text = 'Enjoyed Polar? <a href="' . $link . '" target="_blank">Please leave us a ★★★★★ rating</a> We really appreciate your support! | Try premium version of <a href="' . $pro_link . '" target="_blank">Polar Premium</a> - more features, more power!';
            return $text;
        } else {
            return $text;
        }
    }

    function redirect_to_site(){
        if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'faposter-doclinks' ) {
            wp_redirect( 'https://plugintheme.net/product-category/wordpress-plugins/' );
            exit();
        }
        if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'faposter-premium' ) {
            wp_redirect( 'https://plugintheme.net/product-category/wordpress-plugins/' );
            exit();
        }
    }

    /**
      * Generating Access Token
     **/
    public function polar_access_token_ajax_action(){
        if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'polar_backend_ajax_nonce')) {
            $fb_email    = sanitize_email($_POST['fb_email']);
            $fb_password = sanitize_text_field($_POST['fb_password']);

            if( !empty( $fb_email ) && !empty($fb_password ) ) {
                $token_url = $this->get_token_url($fb_email , $fb_password);
                if( $token_url != false ) {
                    $response = array(
                        'type' => 'success',
                        'message' => $token_url
                    );
                }
            } else{
                $response = array(
                    'type' => 'error',
                    'message' => __( 'Please provide your facebook Username and Password.', 'polar' )
                );
            }
            wp_send_json($response);
            exit;
        }
    }

    /**
      * Generating Access Token For Android and iPhone using Email Id and Password
     **/
     function get_token_url($fb_email, $fb_password){

        $sig = md5("appId=".$fb['appId']."credentials_type=passwordemail=".trim($fb_email)."format=JSONgenerate_machine_id=1generate_session_cookies=1locale=en_USmethod=auth.loginpassword=".trim($fb_password)."return_ssl_resources=0v=1.0".$fb[application_secret]);

        $fb_token_url = "https://api.facebook.com/restserver.php?api_key=".$fb['appId']."&credentials_type=password&email=".urlencode(trim($fb_email))."&format=JSON&generate_machine_id=1&generate_session_cookies=1&locale=en_US&method=auth.login&password=".urlencode(trim($fb_password))."&return_ssl_resources=0&v=1.0&sig=".$sig;

        return $fb_token_url;
    }

    public function polar_add_account_action(){
         if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'polar_backend_ajax_nonce')) {
             $token_url = sanitize_text_field($_POST['token_url']);
             $token_response = array();
            if(!empty($token_url)){
                $fb_token = stripslashes($token_url);
                $token_response = $this->polar_add_account($fb_token);
                if( !empty($token_response) ) {
                     $response =
                    array(
                        'type'=> 'success',
                        'result' => $token_response,
                        'message' => __('Your account added successfully.', 'polar')
                    );
                }
                else{
                    $response = array(
                        'type' => 'error',
                        'message' =>  __('Invalid access token/User data not found.', 'polar')
                    );
                }
            } else{
                $response = array(
                    'type' => 'error',
                    'message' => __( 'Please enter the access token.', 'polar' )
                );
            }
             wp_send_json($response);
        }
    }

    public function polar_add_account($fb_token) {
        $fb_sess_data = array();
        $polar_user_details = array();
        $user_accounts = array();
        $token_result = json_decode($fb_token);
        if( isset( $token_result->error_msg ) ){
            $error = $token_result->error_msg;
            return false;
        }
        elseif ( empty( $token_result ) ) {
            return false;
        }
        $user_app_secret = $token_result->secret;
        $user_accounts = $this->polar_get_user_accounts($token_result);

        if(!empty($user_accounts)) {
            $fb_sess_data =
             array(
                'fap_user_cache' => array(
                                    'name' => $user_accounts['fb_user_name'],
                                    'id'   => $user_accounts['fb_user_id'],
                ),
                'fap_user_id'       => $user_accounts['fb_user_id'],
                'fap_user_accounts' => $user_accounts,
            );
            $polar_user_details = $fb_sess_data;
            return $polar_user_details;
        }
        return false;
    }

    function polar_get_user_accounts($token_result) {
        require_once( POLAR_PLUGIN_PATH . '/Facebook/Facebook_API.php' );
        $polar_facebook_api = new POLAR_REST_API();
        $user_accounts  = array();

        if( empty( $token_result ) || empty( $token_result->access_token)){
            return false;
        }

        $access_token = $token_result->access_token;
        $userData   = $this->polar_get_user_data($access_token, $polar_facebook_api);
        $userPages  = $this->polar_get_page_data($access_token, $polar_facebook_api);
        $userGroups = $this->polar_get_group_data($access_token, $polar_facebook_api);

        if( !empty( $userData ) && $userData ) {
            $facebook_user = $userData;
            $user_accounts['fb_user_name']= $userData->name ;
            $user_accounts['fb_user_id']= $userData->id ;

            $user_accounts['auth_accounts'][$userData->id] = $userData->name.' ('.$userData->id.')';
            $user_accounts['auth_tokens'][$userData->id] = $access_token;
        }

        if( !empty( $userPages ) && $userPages ) {
            foreach ( $userPages as $key => $page ) {
                $user_accounts['auth_accounts'][$page->id] = $page->name;
                $user_accounts['auth_tokens'][$page->id] = ( isset( $page->access_token)) ? $page->access_token : $access_token;
            }
        }

        if( !empty( $userGroups ) && $userGroups ) {
            foreach ( $userGroups as $key => $group ) {
                $user_accounts['auth_accounts'][$group->id] = $group->name . ' ('.$group->privacy.')';
                $user_accounts['auth_tokens'][$group->id] = $access_token;
            }
        }
        return $user_accounts;
    }

     function polar_get_group_data($access_token, $polar_facebook_api, $limit = 1000 ){
        $polar_facebook_api->setApiVersion('v2.9');
        $polar_facebook_api->setNode('me');
        $polar_facebook_api->setEndPoint('groups');
        $polar_facebook_api->setAccessToken($access_token);

        $params = array(
            'fields'=> 'id,name,privacy,members.summary(total_count).limit(0)',
            'limit' => $limit,
        );

        $rawResponse = $polar_facebook_api->request($params);

        if( $rawResponse ) {
            $res = json_decode( $rawResponse->getBody());
        } else {
            return false;
        }

        if(isset($res->error)){
            $error = $res->error->message;
            return false;
        }

        $groups = (array)$res->data;

        return $groups;
    }

    function polar_get_page_data( $access_token, $polar_facebook_api, $limit = 500 ){
        $p = $limit > 99 ? $limit / 100 : 1;
        $limit = $limit > 100 ? 100 : $limit;
        $pages = array();

        $params = array(
            'fields'=> 'id,name,likes,access_token',
            'limit' => $limit,
        );

        for ($i=0; $i<$p ; $i++) {

            $polar_facebook_api->setApiVersion('v2.3');
            $polar_facebook_api->setNode('me');
            $polar_facebook_api->setEndPoint('accounts');
            $polar_facebook_api->setAccessToken($access_token);

            if($rawResponse = $polar_facebook_api->request($params)){
                $res = json_decode($rawResponse->getBody());
                if(isset($res->data)){
                    if(!empty($res->data)){
                        $pages = array_merge($pages,$res->data);
                        if(isset($res->paging->cursors->after)){
                            $params['after'] = $res->paging->cursors->after;
                            continue;
                        }
                    }
                }
                break;
            }
        }
        return $pages;
    }

  function polar_get_user_data($access_token, $polar_facebook_api){
        $polar_facebook_api->setNode("me");
        $polar_facebook_api->setMethod("get");
        $polar_facebook_api->setAccessToken($access_token);

        $params =  array('fields'=>'id,name,first_name,last_name');
        $rawResponse = $polar_facebook_api->request($params);
        $res = json_decode($rawResponse->getBody());
        if(isset($res->error)){
            $error = $res->error->message;
            return false;
        }
        return $res;
    }

    /**
     * Necessary constants define
     */
    function define_constants(){
       if (!defined('POLAR_CSS_DIR')) {
            define('POLAR_CSS_DIR', plugin_dir_url(__FILE__) . 'css');
        }
        if( !defined( 'POLAR_IMAGE_DIR' ) ) {
             define( 'POLAR_IMAGE_DIR', plugin_dir_url( __FILE__ ) . 'images' );
        }
        if (!defined('POLAR_IMG_DIR')) {
             define('POLAR_IMG_DIR', plugin_dir_url(__FILE__) . 'images');
        }
        if (!defined('POLAR_JS_DIR')) {
             define('POLAR_JS_DIR', plugin_dir_url(__FILE__) . 'js');
        }
        if (!defined('POLAR_VERSION')) {
             define('POLAR_VERSION', '2.1.1');
        }
        if (!defined('POLAR_TD')) {
             define('POLAR_TD', 'polar');
        }
        if (!defined('POLAR_PLUGIN_FILE')) {
             define('POLAR_PLUGIN_FILE', __FILE__);
        }
        if (!defined('POLAR_PLUGIN_PATH')) {
             define('POLAR_PLUGIN_PATH', plugin_dir_path(__FILE__).'api/facebook-mobile');
        }

        if (!defined('POLAR_API_VERSION')) {
             define('POLAR_API_VERSION', 'v2.0');
        }

        if (!defined('POLAR_api')) {
             define('POLAR_api', 'https://api.facebook.com/' . POLAR_API_VERSION . '/');
        }
        if (!defined('polar_api_video')) {
             define('POLAR_api_video', 'https://api-video.facebook.com/' . POLAR_API_VERSION . '/');
        }

        if (!defined('POLAR_api_read')) {
             define('POLAR_api_read', 'https://api-read.facebook.com/' . POLAR_API_VERSION . '/');
        }

        if (!defined('POLAR_graph')) {
            define('POLAR_graph', 'https://graph.facebook.com/' . POLAR_API_VERSION . '/');
        }

        if (!defined('POLAR_graph_video')) {
            define('POLAR_graph_video', 'https://graph-video.facebook.com/' . POLAR_API_VERSION . '/');
        }
        if (!defined('POLAR_www')) {
            define('POLAR_www', 'https://www.facebook.com/' . POLAR_API_VERSION . '/');
        }
    }


    /**
      * Plugin Settings Page
     **/
    function plugin_settings() {
        include('inc/main-page.php');
    }

    /**
     * Registers Admin Assets
     */
    function register_admin_assets() {
        if (isset($_GET['page']) && $_GET['page'] == 'polar') {
            wp_enqueue_style('apsp-fontawesome-css', POLAR_CSS_DIR.'/font-awesome.min.css', POLAR_VERSION);
            wp_enqueue_style('polar-admin-css', POLAR_CSS_DIR . '/admin-style.css', array(), POLAR_VERSION);
            wp_enqueue_script('polar-admin-js', POLAR_JS_DIR . '/admin-script.js', array('jquery'), POLAR_VERSION);
           $ajax_js_obj = array('ajax_url' => admin_url('admin-ajax.php'),
                            'ajax_nonce' => wp_create_nonce('polar_backend_ajax_nonce')
                           );
            wp_localize_script('polar-admin-js', 'polar_backend_js_obj', $ajax_js_obj);
        }
    }

    /**
     * Returns all registered post types
     */
    function get_registered_post_types() {
        $post_types = get_post_types();
        unset($post_types['revision']);
        unset($post_types['attachment']);
        unset($post_types['nav_menu_item']);
        return $post_types;
    }

    /**
     * Prints array in pre format
     */
    function print_array($array) {
        echo "<pre>";
        print_r($array);
        echo "</pre>";
    }

    /**
      * Action to authorize the facebook
     **/
    function fb_authorize_action() {
        if (!empty($_POST) && wp_verify_nonce($_POST['polar_fb_authorize_nonce'], 'polar_fb_authorize_action')) {
            include('inc/cores/fb-authorization.php');
        } else {
            die('No script kiddies please');
        }
    }

    /**
     * Facebook Authorize Callback
     */
    function polar_callback_authorize() {
        if (isset($_COOKIE['polar_session_state']) && isset($_REQUEST['state']) && ($_COOKIE['polar_session_state'] === $_REQUEST['state'])) {
            include('inc/cores/fb-authorization-callback.php');
        } else {
            die('No script kiddies please!');
        }
    }

    /**
     * Action to save settings
     */
    function polar_form_action() {
        if (!empty($_POST) && wp_verify_nonce($_POST['polar_form_nonce'], 'polar_form_action')) {
            include('inc/cores/save-settings.php');
        } else {
            die('No script kiddies please!!');
        }
    }

    /**
      * Auto Post Trigger
     **/
    function auto_post_trigger() {
        $post_types = $this->get_registered_post_types();
        foreach ($post_types as $post_type) {
            $publish_action = 'publish_' . $post_type;
            $publish_future_action = 'publish_future_'.$post_type;
        }
    }

    /**
      * Auto Post Action
     **/
    function auto_post($new_status, $old_status, $post) {
        if($new_status == 'publish'){
            $auto_post = (isset($_POST['polar_auto_post']) && $_POST['polar_auto_post'] == 'yes')?'yes':'no';
            if ($auto_post == 'yes' || $auto_post == '') {
                include_once('api/facebook.php'); // facebook api library
                include_once( POLAR_PLUGIN_PATH . '/Facebook/Facebook_API.php' );
                include('inc/cores/auto-post.php');
                $check = update_post_meta($post->ID, 'polar_auto_post', 'no');
                $_POST['polar_auto_post'] = 'no';
            }
        }
    }

    function auto_post_schedule($post){
        $auto_post = get_post_meta($post->ID,'polar_auto_post',true);
        if ($auto_post == 'yes' || $auto_post == '') {
            include_once('api/facebook.php'); // facebook api library
            include_once( POLAR_PLUGIN_PATH . '/Facebook/Facebook_API.php' );
            include('inc/cores/auto-post.php');
            $check = update_post_meta($post->ID, 'polar_auto_post', 'no');
            $_POST['polar_auto_post'] = 'no';
        }
    }

    /**
      * Logs
     **/
    function polar_clear_log() {
        if (!empty($_GET) && wp_verify_nonce($_GET['_wpnonce'], 'polar-clear-log-nonce')) {
            global $wpdb;
            $log_table_name = $wpdb->prefix . 'polar_logs';
            $wpdb->query("TRUNCATE TABLE $log_table_name");
            $_SESSION['polar_message'] = __('Logs cleared successfully.', 'polar');
            wp_redirect(admin_url('admin.php?page=polar&tab=logs'));
            exit();
        } else {
            die('No script kiddies please!');
        }
    }

    /**
     *
     * Delete Log
     */
    function delete_log() {
        if (!empty($_GET) && wp_verify_nonce($_GET['_wpnonce'], 'polar_delete_nonce')) {
            $log_id = esc_html__($_GET['log_id']);
            global $wpdb;
            $table_name = $wpdb->prefix . 'polar_logs';
            $wpdb->delete($table_name, array('log_id' => $log_id), array('%d'));
            $_SESSION['polar_message'] = __('Log Deleted Successfully', 'polar');
            wp_redirect(admin_url('admin.php?page=polar'));
        } else {
            die('No script kiddies please!');
        }
    }

    /**
     * Plugin's meta box
     * */
    function add_polar_meta_box($post_type) {
        add_meta_box(
                'polar_meta_box'
                , __('Social Auto Poster', 'polar')
                , array($this, 'render_meta_box_content')
                , $post_type
                , 'side'
                , 'high'
        );
    }

    /**
     * polar_meta_box html
     *
     **/
    function render_meta_box_content($post) {
        // Add an nonce field so we can check for it later.
        wp_nonce_field('polar_meta_box_nonce_action', 'polar_meta_box_nonce_field');
        $default_auto_post = in_array($post->post_status, array("future", "draft", "auto-draft", "pending"))?'yes':'no';
        // Use get_post_meta to retrieve an existing value from the database.
        $auto_post = get_post_meta($post->ID, 'polar_auto_post', true);
        //var_dump($auto_post);
        $auto_post = ($auto_post == '' || $auto_post == 'yes') ? $default_auto_post : 'no';

        // Display the form, using the current value.
        ?>
        <label for="polar_auto_post"><?php _e('Enable Auto Post For Facebook Profile or Fan Pages?', 'polar'); ?></label>
        <p>
            <select name="polar_auto_post">
                <option value="yes" <?php selected($auto_post, 'yes'); ?>><?php _e('Yes', 'polar'); ?></option>
                <option value="no" <?php selected($auto_post, 'no'); ?>><?php _e('No', 'polar'); ?></option>
            </select>
        </p>
        <?php
    }

    /**
      * Saves meta value
     **/
    function save_polar_meta_value($post_id) {

        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */

        // Check if our nonce is set.
        if (!isset($_POST['polar_auto_post']))
            return $post_id;
         $nonce = (isset($_POST['polar_meta_box_nonce_field']) && $_POST['polar_meta_box_nonce_field'] !='')?$_POST['polar_meta_box_nonce_field']:'';

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($nonce, 'polar_meta_box_nonce_action'))
            return $post_id;

        // If this is an autosave, our form has not been submitted,
        //     so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;

        // Check the user's permissions.
        if ('page' == $_POST['post_type']) {

            if (!current_user_can('edit_page', $post_id))
                return $post_id;
        } else {

            if (!current_user_can('edit_post', $post_id))
                return $post_id;
        }

        /* OK, its safe for us to save the data now. */

        // Sanitize the user input.
        $auto_post = sanitize_text_field($_POST['polar_auto_post']);

        // Update the meta field.
        update_post_meta($post_id, 'polar_auto_post', $auto_post);
    }

    /**
     * Restores Default Settings
     */
    function restore_settings(){
        $polar_settings = $this->get_default_settings();
        $polar_extra_settings = array('authorize_status'=>0);
        update_option('polar_extra_settings', $polar_extra_settings);
        update_option('polar_settings', $polar_settings);
        $_SESSION['polar_message'] = __('Default Settings Restored Successfully','polar');
        wp_redirect('admin.php?page=polar');
        exit();
    }

     function account_pages_and_groups($data) {
        $account_details = get_option('polar_settings');
         $polar_user_details = array();
        if (!empty($account_details)) {
            $page_group_lists = (isset($account_details['page_group_lists']) && !empty($account_details['page_group_lists']))?$account_details['page_group_lists']:array();
            $user_data_arr = (isset($account_details['user_data']) && !empty($account_details['user_data']))?$account_details['user_data']:array();
            if(!empty($user_data_arr)){
                $polar_user_details = json_decode( $user_data_arr ,TRUE);
            }
        }
        if( is_array($polar_user_details) && !empty($polar_user_details) ) {
            foreach ($polar_user_details as $fb_sess_data) {

         $fb_sess_acc = isset( $fb_sess_data['auth_accounts'] ) ? $fb_sess_data['auth_accounts'] : array();
         $fb_sess_token = isset( $fb_sess_data['auth_tokens'] ) ? $fb_sess_data['auth_tokens'] : array();
                // Loop of account and merging with page id and app key
                if($data == "all_app_users_with_name"){
                    foreach ( $fb_sess_acc as $fb_page_id => $fb_page_name ) {
                        $res_data[$fb_page_id] = $fb_page_name;
                    }
                }
                elseif($data == "all_auth_tokens"){
                    foreach ( $fb_sess_token as $fb_sess_token_id => $fb_sess_token_data ) {
                        $res_data[$fb_sess_token_id] = $fb_sess_token_data;
                    }
                }
            }
        }

        if(!empty($res_data)){
            return $res_data;
        }
    }

}
$polar_obj = new Polar();
<?php 
defined('ABSPATH') or die('No script kiddies please!');

$_POST = array_map(sanitize_text_field($_POST));

$account_details = $_POST['account_details'];

$account_details['post_types']    = isset($account_details['post_types'])?$account_details['post_types']:array();
$account_details['auto_publish']  = isset($account_details['auto_publish'])?$account_details['auto_publish']:0;
$account_details['include_image'] = isset($account_details['include_image'])?$account_details['include_image']:0;
$account_details['category']      = isset($account_details['category'])?$account_details['category']:array();

update_option('polar_settings',$account_details);
$_SESSION['polar_message'] = __('Settings saved successfully','polar');
wp_redirect('admin.php?page=polar');
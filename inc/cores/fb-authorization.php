<?php 
defined('ABSPATH') or die();
$account_details = get_option('polar_settings');

$app_id       = $account_details['application_id'];
$app_secret   = $account_details['application_secret'];
$redirect_url = admin_url('admin-post.php?action=polar_callback_authorize');
$api_version  = 'v2.0';
$param_url    = urlencode($redirect_url);
$asap_session_state = md5(uniqid(rand(), TRUE));

setcookie("polar_session_state", $asap_session_state, "0", "/");

$dialog_url = "https://www.facebook.com/" . $api_version . "/dialog/oauth?client_id="
        . $app_id . "&redirect_uri=" . $param_url . "&state="
        . $asap_session_state . "&scope=email,publish_pages,user_posts,publish_actions,manage_pages";


header("Location: " . $dialog_url);

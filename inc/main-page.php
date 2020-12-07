<?php defined('ABSPATH') or die('No script kiddies please!');?>
<div class="wrap">
    <!--Plugin Header-->
    <?php include('header.php'); ?>
    <!--Plugin Header-->
    <div class="polar-main-section">
    <?php
    if (isset($_SESSION['polar_message'])){
        echo esc_html_e($_SESSION['polar_message']);
        unset($_SESSION['polar_message']);
    }        
    global $wpdb;
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings'; 
    $active_tabs = esc_html_e($active_tab)?>
        <div class="polar-main-inner-wrap">
            <ul class="polar-tabs-wrap">
                <li class="polar-tab <?php if ($active_tabs == 'settings') { ?>polar-active-tab<?php } ?>" id="polar-tab-settings"><?php _e('Settings', 'polar'); ?></li>
                <li class="polar-tab <?php if ($active_tabs == 'logs') { ?>polar-active-tab<?php } ?>" id="polar-tab-logs"><?php _e('Logs', 'polar'); ?></li>
                <li class="polar-tab <?php if ($active_tabs == 'how') { ?>polar-active-tab<?php } ?>" id="polar-tab-how"><?php _e('How To Use', 'polar'); ?></li>
            </ul>
            <?php
            include_once('tabs/settings.php');
            include('tabs/logs.php');
            include_once('tabs/how-to-use.php');
            ?>
        </div>           
    </div>
</div>
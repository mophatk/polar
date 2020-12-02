<div class="polar-section" id="polar-section-settings" <?php if ($active_tab != 'settings') { ?>style="display: none;"<?php } ?>>
    <div class="polar-network-wrap polar-network-inner-wrap">
        <h4 class="polar-network-title"><?php _e('Your Account Details', 'polar'); ?></h4>

        <?php
        $account_details = get_option('polar_settings');
        $account_extra_details = get_option('polar_extra_settings');
        $authorize_status = $account_extra_details['authorize_status'];
        //$this->print_array($account_details);
        $api_type = (isset($account_details['api_type']) && $account_details['api_type'] != '')?esc_attr($account_details['api_type']):'graph_api';
        $page_group_lists = (isset($account_details['page_group_lists']) && !empty($account_details['page_group_lists']))?$account_details['page_group_lists']:array();
         $user_data_arr = (isset($account_details['user_data']) && !empty($account_details['user_data']))?$account_details['user_data']:'';
         $account_pages_and_groups = $this->account_pages_and_groups($data = 'all_app_users_with_name');
//        $this->print_array($account_extra_details);
        ?>
        <?php if (isset($_SESSION['polar_message'])) { ?><p class="polar-authorize_note"><?php
            echo $_SESSION['polar_message'];
            unset($_SESSION['polar_message']);
            ?></p><?php } ?>

    <div class="polar-graph-api-options">
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="polar_fb_authorize_action"/>
            <?php wp_nonce_field('polar_fb_authorize_action', 'polar_fb_authorize_nonce'); ?>
            <input type="submit" name="polar_fb_authorize" value="<?php echo ($authorize_status == 0) ? __('Authorize', 'polar') : __('Reauthorize', 'polar'); ?>" style="display: none;"/>
        </form>
    </div>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="polar_form_action"/>
            <?php wp_nonce_field('polar_form_action', 'polar_form_nonce') ?>
            <?php
                if ($authorize_status == 0) {
                    ?>
                    <p class="polar-authorize-note polar-graph-api-options"><?php _e('It seems that you haven\'t authorized your account yet.The auto publish for this account won\'t work until you will authorize.Please authorize using below button', 'polar'); ?></p>
                    <?php
                }
                ?>
                <input type="button" class="polar-authorize-btn polar-graph-api-options" id="polar-fb-authorize-ref" value="<?php echo ($authorize_status == 0) ? __('Authorize', 'polar') : __('Reauthorize', 'polar'); ?>"/>

               <p class="polar-authorize-note polar-android-api-options"><?php _e('As facebook had made some changes recently,so facebook graph API have few limitation.In such cases, if graph api is not working then please use Facebook Mobile API. This does not have any limitation.', 'polar'); ?></p>

                <div class="polar-network-field-wrap">
                    <label><?php _e('Choose API Type:', 'polar'); ?></label>
                    <div class="polar-network-field">
                        <label><input class="polar-apitype" type="radio" value="graph_api" <?php if($api_type == "graph_api") echo "checked";?> name="account_details[api_type]"/><?php _e('Graph API (Deprecated)','polar');?></label>
                        <label><input class="polar-apitype" type="radio" value="mobile_api" <?php if($api_type == "mobile_api") echo "checked";?> name="account_details[api_type]"/><?php _e('Mobile API','polar');?></label>
                    </div>
                </div>
                <div class="polar-network-field-wrap">
                    <label><?php _e('Auto Publish', 'polar'); ?></label>
                    <div class="polar-network-field"><input type="checkbox" value="1" name="account_details[auto_publish]" <?php checked($account_details['auto_publish'], true); ?>/></div>
                </div>
                 <!-- facebook graph api options start -->
                <div class="polar-network-field-wrap polar-graph-api-options">
                    <label><?php _e('Application ID', 'polar'); ?></label>
                    <div class="polar-network-field"><input type="text" name="account_details[application_id]" value="<?php echo isset($account_details['application_id']) ? esc_attr($account_details['application_id']) : ''; ?>"/></div>
                </div>
                <div class="polar-network-field-wrap polar-graph-api-options">
                    <label><?php _e('Application Secret', 'polar'); ?></label>
                    <div class="polar-network-field">
                        <input type="text" name="account_details[application_secret]" value="<?php echo isset($account_details['application_secret']) ? esc_attr($account_details['application_secret']) : ''; ?>"/>
                        <div class="polar-field-note">
                            <p><?php
                            $site_url = site_url();
                            _e("Please visit <a href='https://developers.facebook.com/apps' target='_blank'>here</a> and create new Facebook Application to get Application ID and Application Secret.<br/><br/> Also please make sure you follow below steps after creating app.<br/><br/>Navigate to Apps > Settings > Edit settings > Website > Site URL. Set the site url as : $site_url ", 'polar');
                            ?></p>
                            <p>
                            <?php _e('Please follow below screenshots too.','polar');?><br />
                            <a href="http://prntscr.com/gy0gol" target="_blank">http://prntscr.com/gy0gol</a><br/>
                            <a href="http://prntscr.com/gy0knj" target="_blank">http://prntscr.com/gy0knj</a><br/>
                            <a href="http://prntscr.com/hygifu" target="_blank">http://prntscr.com/hygifu</a>

                            </p>
                            <p>
                            <?php
                            $redirect_url = admin_url('admin-post.php?action=polar_callback_authorize');
                            _e('Please add below url in the Valid OAuth redirect URIs with reference to 3rd screenshot.','polar');?>
                            <textarea readonly="readonly" onfocus="this.select();" style="width: 100%;height:50px;margin-top:10px;"><?php echo $redirect_url;?></textarea>
                            </p>

                        </div>
                    </div>
                </div>
                <div class="polar-network-field-wrap polar-graph-api-options">
                    <label><?php _e('User ID', 'polar'); ?></label>
                    <div class="polar-network-field">
                        <input type="text" name="account_details[facebook_user_id]" value="<?php echo isset($account_details['facebook_user_id']) ? esc_attr($account_details['facebook_user_id']) : ''; ?>"/>
                        <div class="polar-field-note">
                            <?php _e('Please visit <a href="http://findmyfacebookid.com/" target="_blank">here</a> to get your facebook ID', 'polar'); ?>
                        </div>
                    </div>
                </div>
                <!-- facebook graph api options end -->
                <!-- facebook andriod api options start -->
                <div class="polar-network-field-wrap polar-android-api-options">
                    <label><?php _e('Account Email Address', 'polar'); ?></label>
                    <div class="polar-network-field">
                        <input type="text" class="polar-fb-emailid"/>
                        <div class="polar-field-note">
                          <p class="description"> <?php _e('Please enter a valid Facebook email address here.', 'polar') ?> </p>
                        </div>
                    </div>
                </div>
                <div class="polar-network-field-wrap polar-android-api-options">
                    <label><?php _e('Account Password', 'polar'); ?></label>
                    <div class="polar-network-field">
                       <input type="password" class="polar-fb-pass"/>
                       <div class="polar-field-note">
                       <p class="description">
                        <?php _e('Please enter your facebook password here. Your Facebook account email address and password will not be stored. We only use the password to generate a facebook token to grant permission to post content on yor facebook page.', 'polar') ?>
                       </p>
                       </div>
                    </div>
                </div>
                <div class="polar-network-field-wrap polar-android-api-options">
                    <label></label>
                    <div class="polar-network-field">
                        <a class="button-primary polar-generate-token-btn" href="#" >
                          <?php _e('Generate Access Token', 'polar'); ?>
                        </a>
                        <div class="polar-ajax-loader1">
                         <img src= "<?php echo esc_attr(polar_IMG_DIR).'/ajax-loader.gif'; ?>" >
                        </div>
                        <div class="polar-field-note">
                         <p class="description">
                            <?php _e('Simply fill the email address and password of your facebook account and then click on Generate Token button.', 'polar') ?>
                         </p>
                        </div>
                    </div>
                </div>
                <div class="polar-network-field-wrap polar-android-api-options polar-generated-atwrapper" style="display: none;">
                    <label></label>
                    <div class="polar-network-field">
                        <div class="polar-generated-access-token-wrapper"></div>
                        <div class="polar-field-note">
                         <p class="description">
                            <?php _e('Copy all generated value from above and paste it below field.', 'polar') ?>
                         </p>
                        </div>
                    </div>
                </div>

                <div class="polar-network-field-wrap polar-android-api-options">
                    <label></label>
                    <div class="polar-network-field">
                         <textarea id="polar-generated-access-url" class="polar-generated-access-url" rows="4" cols="50" placeholder="<?php _e('Paste copied access token here.', 'polar'); ?> "></textarea>
                    </div>
                </div>

                <div class="polar-network-field-wrap polar-android-api-options">
                    <label></label>
                    <div class="polar-network-field">
                        <a class="button-primary polar-add-account-button" href="#" >
                          <?php _e('Add Account', 'polar'); ?>
                        </a>
                        <div class="polar-ajax-loader">
                         <img src= "<?php echo esc_attr(POLAR_IMG_DIR).'/ajax-loader.gif'; ?>" >
                        </div>
                        <div id="polar-error-msg"></div>
                        </div>
                </div>

                <div class="polar-network-field-wrap polar-android-api-options">
                    <label><?php _e('List Of Pages/Groups', 'polar'); ?></label>
                    <div class="polar-network-field">
                        <select name="account_details[page_group_lists][]" id="polar-button-template-floating" multiple="multiple">
                           <?php if(!empty($account_pages_and_groups)){
                             foreach( $account_pages_and_groups as $account_num => $page_title) { ?>
                                <option value="<?php echo esc_attr($account_num); ?>" <?php if (in_array($account_num, $page_group_lists)) { ?> selected = "selected" <?php } ?>>
                                    <?php echo esc_attr($page_title); ?>
                                </option>
                            <?php }
                              }else{ ?>
                             <option selected="true" disabled> No any lists available.</option>
                           <?php  }?>
                        </select>
                         <textarea name="account_details[user_data]" id="polar-account-all-json" style="display:none;"><?php echo esc_attr($user_data_arr);?></textarea>
                    </div>
                </div>


                 <!-- facebook andriod api options end -->
                <div class="polar-network-field-wrap">
                    <label><?php _e('Post Message Format', 'polar'); ?></label>
                    <div class="polar-network-field">
                        <textarea name="account_details[message_format]"><?php echo $account_details['message_format']; ?></textarea>
                        <div class="polar-field-note">
                            <?php _e('Please use #post_title,#post_content,#post_excerpt,#post_link,#author_name for the corresponding post title, post content, post excerpt, post link, post author name respectively.', 'polar'); ?>
                        </div>
                    </div>
                </div>
                <div class="polar-network-field-wrap">
                    <label><?php _e('Post Format', 'polar'); ?></label>
                    <div class="polar-network-field">
                        <select name="account_details[post_format]">
                            <option value="simple" <?php echo (isset($account_details['post_format']) && $account_details['post_format'] == 'simple') ? 'selected="selected"' : ''; ?>><?php _e('Simple Text Message', 'polar'); ?></option>
                            <option value="link" <?php echo (isset($account_details['post_format']) && $account_details['post_format'] == 'link') ? 'selected="selected"' : ''; ?>><?php _e('Attach Blog Post', 'polar'); ?></option>
                        </select>
                        <div class="polar-field-note">
                        <?php _e('Note: For Blog Post format, please use Facebook open graph debugger <a href="https://developers.facebook.com/tools/debug/" target="_blank">here</a> to check if your site has proper facebook og tags to display Title, Image and Description in the Facebook for auto published post.If your site is missing these attribute, post might not get posted to fb.','polar');?>
                    </div>
                    </div>
                </div>
                <div class="polar-network-field-wrap polar-graph-api-options">
                    <label><?php _e('Auto Post Pages', 'polar'); ?></label>
                    <div class="polar-network-field">
                        <select name="account_details[auto_post_pages][]" multiple="">
                            <option value="1" <?php echo (isset($account_details['auto_post_pages']) && in_array(1, $account_details['auto_post_pages'])) ? 'selected="selected"' : ''; ?>><?php _e('Profile Page') ?></option>
                            <?php
                            if (isset($account_extra_details['pages']) && is_array($account_extra_details['pages'])) {
                                $pages = $account_extra_details['pages'];
                                //$this->print_array($pages);
                                if (count($pages) > 0) {
                                    foreach ($pages as $page) {
                                        ?>
                                        <option value="<?php echo $page->id; ?>" <?php echo (isset($account_details['auto_post_pages']) && is_array($account_details['auto_post_pages']) && in_array($page->id, $account_details['auto_post_pages'])) ? 'selected="selected"' : ''; ?>><?php echo $page->name; ?></option>
                                        <?php
                                    }
                                }
                            }
                            ?>
                        </select>
                        <div class="polar-field-note">
                            <?php _e('Note: Please use control or command key to select multiple options', 'polar'); ?>
                        </div>
                    </div>
                </div>
            <?php include('post-settings.php'); ?>
        </form>
    </div>
</div>

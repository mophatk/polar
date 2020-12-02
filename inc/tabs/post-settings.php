<h4 class="polar-network-title"><?php _e('Post Settings', 'polar'); ?></h4>
<div class="polar-network-inner-wrap">
    <div class="polar-network-field-wrap">
        <label><?php _e('Enable Auto Publish For:', 'polar'); ?></label>
        <div class="polar-network-field">
            <?php
            $post_types = $this->get_registered_post_types();
            foreach ($post_types as $post_type) {
                $post_type_obj = get_post_type_object($post_type);
                ?>
                <label class="polar-full-width"><input type="checkbox" name="account_details[post_types][]" value="<?php echo $post_type; ?>" <?php echo (isset($account_details['post_types']) && is_array($account_details['post_types']) && in_array($post_type, $account_details['post_types'])) ? 'checked="checked"' : ''; ?>/><?php echo $post_type_obj->labels->name; ?></label>
            <?php }
            ?>
        </div>
    </div>

    <div class="polar-network-field-wrap">
        <label><?php _e('Categories for Auto Post', 'polar'); ?></label>
        <div class="polar-network-field">
            <select name="account_details[category][]" multiple="multiple">
                <?php $category = isset($account_details['category']) ? $account_details['category'] : array(); ?>
                <option value="all" <?php echo (in_array('all', $category)) ? 'selected="selected"' : ''; ?>><?php _e('All', 'polar'); ?></option>
                <?php
                $taxonomies = get_taxonomies();
                unset($taxonomies['nav_menu']);
                unset($taxonomies['post_format']);
                
                foreach ($taxonomies as $taxonomy) {
                    $taxonomy_obj = get_taxonomy($taxonomy);

                    $terms = get_terms($taxonomy, array('hide_empty' => 0));
                    if (count($terms) > 0) {
                        ?>
                        <optgroup label="<?php echo $taxonomy_obj->label; ?>">
                            <?php
                            foreach ($terms as $term) {
                                ?>
                                <option value="<?php echo $term->term_id; ?>" <?php echo (in_array($term->term_id, $category)) ? 'selected="selected"' : ''; ?>><?php echo $term->name; ?></option>
                                <?php
                            }
                            ?>
                        </optgroup>
                        <?php
                    }
                }
                ?>
            </select>
            <div class="polar-field-note">
                <?php _e('Note:Please use command or control key to select multiple options.Not selecting any of the option will be considered as <strong>All</strong> selected.', 'polar'); ?>
            </div>
        </div>
    </div>
</div>
<div class="polar-network-field-wrap">
    <div class="polar-network-field">
        <input type="submit" name="edit_submit" value="<?php _e('Save Settings', 'polar'); ?>"/>
        <a href="<?php echo admin_url('admin-post.php?action=polar_restore_settings'); ?>" onclick="return confirm('<?php _e('Are you sure you want to restore the default settings?','polar');?>')"><input type="button" value="<?php _e('Restore Default Settings', 'polar'); ?>"/></a>
    </div>
</div>
<?php
/**
 * Plugin Name: Bulk Content & Meta Editor
 * Description: Massive editing of content and meta_value of Custom Post Types in WordPress.
 * Version: 1.0
 * Author: Jose Sandor Clavijo Aguilar
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ValuePostmetaBulkEditor {
    public function __construct() {
        add_action('admin_menu', [$this, 'create_admin_page']);
        add_action('wp_ajax_get_postmeta', [$this, 'get_postmeta']);
        add_action('wp_ajax_update_postmeta', [$this, 'update_postmeta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function create_admin_page() {
        add_menu_page(
            'Postmeta Bulk Editor',
            'Postmeta Bulk Editor',
            'manage_options',
            'postmeta-bulk-editor',
            [$this, 'admin_page_content'],
            'dashicons-edit'
        );
    }

    public function admin_page_content() {
        ?>
        <div class="wrap">
            <h1>Bulk Content & Meta Editor</h1>
            <label for="cpt_select">Select a Custom Post Type:</label>
            <select id="cpt_select">
                <option value="">-- Select --</option>
                <?php
                $post_types = get_post_types(['public' => true], 'objects');
                foreach ($post_types as $post_type) {
                    if (!in_array($post_type->name, ['attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset'])) {
                        echo '<option value="' . esc_attr($post_type->name) . '">' . esc_html($post_type->label) . '</option>';
                    }
                }
                ?>
            </select>
            
            <label for="meta_key">Meta Key:</label>
            <input type="text" id="meta_key" disabled>
            <button id="submit_query" disabled>Get Data</button>
            <button id="cancel_query">Cancel</button>
            
            <div id="results"></div>
			
            <button id="save_changes" style="display:none;">Save Changes</button>
        </div>
        <?php
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_postmeta-bulk-editor') return;
        wp_enqueue_script('postmeta-bulk-editor', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], null, true);
        wp_localize_script('postmeta-bulk-editor', 'postmetaBulkEditor', ['ajaxurl' => admin_url('admin-ajax.php')]);
        wp_enqueue_style('postmeta-bulk-editor-style', plugin_dir_url(__FILE__) . 'assets/style.css');
    }

    public function get_postmeta() {
		global $wpdb;
		$cpt = sanitize_text_field($_POST['cpt']);
		$meta_key = sanitize_text_field($_POST['meta_key']);
		$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

		$query = $wpdb->prepare(
			"SELECT p.ID, p.post_name, p.post_title, p.post_type, p.post_status, p.post_modified, 
					COALESCE(pm.meta_key, %s) AS meta_key, pm.meta_value 
			 FROM {$wpdb->posts} p 
			 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s 
			 WHERE p.post_type = %s 
			 LIMIT 30 OFFSET %d",
			$meta_key, $meta_key, $cpt, $offset
		);

		$results = $wpdb->get_results($query);

		foreach ($results as &$row) {
			if ($row->meta_value === null) {
				$row->meta_value = '';
				$row->meta_status = 'No existe';
			} else {
				$row->meta_status = 'Existe';
			}
		}

		wp_send_json_success($results);
	}

	public function update_postmeta() {
		global $wpdb;
		$updates = $_POST['updates'];
		
		foreach ($updates as $update) {
			$post_id = intval($update['post_id']);
			$meta_key = sanitize_text_field($update['meta_key']);
			$meta_value = sanitize_textarea_field($update['meta_value']);

			// Checks if the value has changed before upgrading
			$current_value = get_post_meta($post_id, $meta_key, true);
			if ($current_value !== $meta_value) {
				update_post_meta($post_id, $meta_key, $meta_value);
			}
		}
		
		wp_send_json_success();
	}
}

new ValuePostmetaBulkEditor();

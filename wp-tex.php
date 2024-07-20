<?php
/*
 * Plugin Name: TEX Viewer
 * Description: Allow users to write LaTeX (mainly tikz) code in a post, and this plugin will convert the post to an image. 
 * Version: 0.1
 * Author: gqqnbig
 * Requires PHP: 7.4
 */

require_once 'utilities.php';
require_once 'TexViewerSettingsPage.php';

class Tex_Viewer_Plugin
{
	public function __construct()
	{
		// add_action is essentially php call_user_func.
		// The callable can be an array.

		// A method of an instantiated object is passed as an array containing an object at index 0 and the method name at index 1.
		// Accessing protected and private methods from *within a class* is allowed.

		// Also, a PHP function is passed by its name as a string.
		// https://www.php.net/manual/en/language.types.callable.php
		add_action('init', array($this, 'register_custom_post_type_compiled_figures'));
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action('save_post', array($this, 'save_meta_boxes'), 10, 2);
		add_action('admin_notices', array($this, 'display_compilation_log'));
		add_filter('single_template', array($this, 'custom_plugin_register_templates'));
		add_action('activated_plugin', array($this, 'plugin_activated'));

		register_activation_hook(__FILE__, array($this, 'plugin_activating'));

	}

	function plugin_activating()
	{
		$this->register_custom_post_type_compiled_figures();
		flush_rewrite_rules();
		
		add_option('xelatex-path');
		add_option('magick-path');
	}

	function plugin_activated($plugin)
	{
		if ($plugin != plugin_basename(__FILE__))
			return;

		// check for dependencies and redirect to the settings page.
		// register_activation_hook can't do this.
		$xelatex_path = gqqnbig\get_executable_path('xelatex');
		if (!is_null($xelatex_path))
			update_option('xelatex-path', $xelatex_path);


		$magick_path = gqqnbig\get_executable_path('magick');
		if (!is_null($magick_path))
			update_option('magick-path', $magick_path);

		if (empty($xelatex_path) || empty($magick_path))
			exit(wp_redirect(admin_url('options-general.php?page=tex-viewer-settings')));
	}


	function register_custom_post_type_compiled_figures()
	{

	$labels = array(
		'name' => _x('Compiled Figures', 'Post Type General Name', 'text_domain'),
		'singular_name' => _x('Compiled Figure', 'Post Type Singular Name', 'text_domain'),
//		'menu_name' => __('Compiled Figures', 'text_domain'),
//		'name_admin_bar' => __('Compiled Figure', 'text_domain'),
//		'archives' => __('Compiled Figure Archives', 'text_domain'),
//		'attributes' => __('Compiled Figure Attributes', 'text_domain'),
//		'parent_item_colon' => __('Parent Compiled Figure:', 'text_domain'),
		'all_items' => __('All Compiled Figures', 'text_domain'),
		'add_new_item' => __('Add New Compiled Figure', 'text_domain'),
		'add_new' => __('Add New', 'text_domain'),
//		'new_item' => __('New Compiled Figure', 'text_domain'),
//		'edit_item' => __('Edit Compiled Figure', 'text_domain'),
//		'update_item' => __('Update Compiled Figure', 'text_domain'),
//		'view_item' => __('View Compiled Figure', 'text_domain'),
//		'view_items' => __('View Compiled Figures', 'text_domain'),
//		'search_items' => __('Search Compiled Figures', 'text_domain'),
//		'not_found' => __('Not found', 'text_domain'),
//		'not_found_in_trash' => __('Not found in Trash', 'text_domain'),
//		'featured_image' => __('Featured Image', 'text_domain'),
//		'set_featured_image' => __('Set featured image', 'text_domain'),
//		'remove_featured_image' => __('Remove featured image', 'text_domain'),
//		'use_featured_image' => __('Use as featured image', 'text_domain'),
//		'insert_into_item' => __('Insert into compiled figure', 'text_domain'),
//		'uploaded_to_this_item' => __('Uploaded to this compiled figure', 'text_domain'),
//		'items_list' => __('Compiled Figures list', 'text_domain'),
//		'items_list_navigation' => __('Compiled Figures list navigation', 'text_domain'),
//		'filter_items_list' => __('Filter compiled figures list', 'text_domain'),
	);
	$args = array(
		'label' => __('Compiled Figure', 'text_domain'),
//		'description' => __('Post type for Compiled Figures', 'text_domain'),
		'public' => true,
		'labels' => $labels,
		// Don't add 'custom-fields' because 'custom-fields' by default are key value pairs. Users can input any key and value.
		'supports' => array('title'),
//		'hierarchical' => false,
//		'show_ui' => true,
//		'show_in_menu' => true,
//		'menu_position' => 5,
//		'show_in_admin_bar' => true,
//		'show_in_nav_menus' => true,
//		'can_export' => true,
//		'exclude_from_search' => false,
//		'publicly_queryable' => true,
//		'capability_type' => 'post',
//		'show_in_rest' => false,
	);
	register_post_type('compiled_figure', $args);

	}


	function render_code_meta_box(WP_Post $post)
	{
		$latex_code = get_post_meta($post->ID, 'latex_code', true);

	?>
    <label for="latex_code">Full LaTeX/TikZ code:</label><br>
    <textarea id="latex_code" name="latex_code" rows="20"
              style="width: 100%;"><?= esc_textarea($latex_code); ?></textarea>
	<?php

	$magick_image_settings = get_post_meta($post->ID, 'magick_image_settings', true);
	$magick_image_operators = get_post_meta($post->ID, 'magick_image_operators', true);
	$img_format = get_post_meta($post->ID, 'img_format', true);

	?>
    Image conversion command line:
    <div>
        magick
        <textarea id="magick_image_settings" name="magick_image_settings" rows="10"
                  style="width: 100%;"><?= esc_textarea($magick_image_settings); ?></textarea>
        "<?= $post->post_title ?>.pdf"
        <textarea id="magick_image_operators" name="magick_image_operators" rows="10"
                  style="width: 100%;"><?= esc_textarea($magick_image_operators); ?></textarea>
        "<?= $post->post_title ?>
        <select id="img_format" name="img_format">
            <option value="gif" <?= $img_format == 'gif' ? ' selected' : '' ?> >.gif</option>
            <option value="png" <?= $img_format == 'png' ? ' selected' : '' ?> >.png</option>
        </select>"
    </div>
	<?php

	}

	function render_compilation_meta_box()
	{
		submit_button("Compile LaTeX", 'primary', 'compile-latex');
		submit_button("Compile Image", 'primary', 'compile-image');
	}

	function add_meta_boxes()
	{
		add_meta_box(
			'latex_code_meta_box',
			'LaTeX Content',
			array($this, 'render_code_meta_box'),
			'compiled_figure'
		);

		add_meta_box(
			'latex_compilation_meta_box',
			'Compile',
			array($this, 'render_compilation_meta_box'),
			'compiled_figure',
			'side'
		);
	}



/**
 * @param WP_Post $post
 * @param string $latex_code
 * @param string $compiled_fig_path already has a trailing slash.
 * @return void
 */
function compile_latex(WP_Post $post, string $latex_code, string $compiled_fig_path)
{
	$tex_file = $compiled_fig_path . 'figure_' . $post->ID . '.tex';

	// WordPress adds slashes to $_POST, $_GET, $_REQUEST, $_COOKIE
	if (file_put_contents($tex_file, stripslashes($latex_code)) === false) {
		set_transient('latex_compilation_log_' . $post->ID, "Failed to write $tex_file.", MINUTE_IN_SECONDS * 5);
		return;
	}

	$xelatex_command = "xelatex -interaction=nonstopmode -output-directory=$compiled_fig_path $tex_file";

	$handle = proc_open($xelatex_command, [
		0 => ["pipe", "r"],  // stdin
		1 => ["pipe", "w"],  // stdout
		2 => ["pipe", "w"],  // stderr
	], $pipes, null, null, ['bypass_shell' => true]);

	if (!is_resource($handle)) {
		set_transient('latex_compilation_log_' . $post->ID, "Command line failed:\n" . $xelatex_command, MINUTE_IN_SECONDS * 5);
		return;
	}

		$result_code = gqqnbig\get_proc_output($handle, $pipes, $stdout, $stderr);

	if ($result_code != 0) {
		$message = "xelatex command line output:\n";
		if (strlen($stderr) > 1000)
			$stderr = "...\n" . substr($stderr, -1000);
		if (strlen($stdout) > 1000)
			$stderr = "...\n" . substr($stdout, -1000);

		if (strlen($stderr) > 0)
			$message .= $stderr;
		else
			$message .= $stdout;
		set_transient('latex_compilation_log_' . $post->ID, $message, MINUTE_IN_SECONDS * 5);
	}
}

	private function clean_up_command_arguments(string $args)
	{
		# remove comments
		$args = preg_replace('/#.+\n/', ' ', $args);
		# join lines
		$args = preg_replace('/[\r\n]/', ' ', $args);
		return $args;
	}

	function save_meta_boxes($post_id, $post)
	{
		$latex_code = null;
		if (isset($_POST['latex_code'])) {
			// don't call sanitize_textarea_field because it will remove angular brackets which is harmless in Latex.
			// TODO: call the file info extension to check the content.
			$latex_code = $_POST['latex_code'];
			update_post_meta($post_id, 'latex_code', $latex_code);

	}

	$img_format = null;
	if (isset($_POST['img_format'])) {
		$img_format = sanitize_text_field($_POST['img_format']);
		switch ($img_format) {
			case "gif":
				break;
			default:
				$img_format = "png";
		}
		update_post_meta($post_id, 'img_format', $img_format);
	}

	$magick_image_settings = null;
	if (isset($_POST['magick_image_settings'])) {
		$magick_image_settings = $_POST['magick_image_settings'];
		update_post_meta($post_id, 'magick_image_settings', $magick_image_settings);
	}
	$magick_image_operators = null;
	if (isset($_POST['magick_image_settings'])) {
		$magick_image_operators = $_POST['magick_image_operators'];
		update_post_meta($post_id, 'magick_image_operators', $magick_image_operators);
	}

	$upload_dir = wp_upload_dir();
	$upload_path = trailingslashit($upload_dir['basedir']) . 'compiled_figures/';
	if (is_dir($upload_path) === false)
		mkdir($upload_path, 0755);

		if (isset($_POST['compile-latex']) && !is_null($latex_code)) {
			$this->compile_latex($post, $latex_code, $upload_path);
		} elseif (isset($_POST['compile-image']) && !is_null($magick_image_settings) && !is_null($magick_image_operators)) {
			$source_file = $upload_path . 'figure_' . $post->ID . '.pdf';
			$target_file = $upload_path . 'figure_' . $post->ID . '.' . $img_format;


			if (file_exists($source_file)) {
				# proc_open can accept an array as the command, with each element as an argument.
				# But here user provides a group of arguments.
				# I either have to split them into individual options, or join everything into one string.
				# Here I choose to join them.
				$magick_command = implode(' ', array('magick',
					$this->clean_up_command_arguments($magick_image_settings),
					escapeshellarg($source_file),
					$this->clean_up_command_arguments($magick_image_operators),
					escapeshellarg($target_file),
				));

			// proc_open may print errors to a side channel, which is not an exception.
			// I can turn it off with `error_reporting(ERROR)` but I can't capture the error.
			// The error message is only available on stderr or the web page.
			$handle = proc_open($magick_command, [
				0 => ["pipe", "r"],  // stdin
				1 => ["pipe", "w"],  // stdout
				2 => ["pipe", "w"],  // stderr
			], $pipes, null, null, ['bypass_shell' => true]);


			if (!is_resource($handle)) {
				set_transient('latex_compilation_log_' . $post_id, "Command line failed:\n" . $magick_command, MINUTE_IN_SECONDS * 5);
				return;
			}

				$exit_code = gqqnbig\get_proc_output($handle, $pipes, $stdout, $stderr);

			if ($exit_code != 0) {
				$message = "magick command line error:\n";
				if (strlen($stderr) > 1000)
					$stderr = "...\n" . substr($stderr, -1000);
				if (strlen($stdout) > 1000)
					$stderr = "...\n" . substr($stdout, -1000);

				if (strlen($stderr) > 0)
					$message .= $stderr;
				else
					$message .= $stdout;
				set_transient('latex_compilation_log_' . $post->ID, $message, MINUTE_IN_SECONDS * 5);
			}
		} else {
			set_transient('latex_compilation_log_' . $post_id, "PDF hasn't been compiled.", MINUTE_IN_SECONDS * 5);
		}
	}

	}

	function display_compilation_log()
	{
//	if (isset($_GET['latex_compilation']) && $_GET['latex_compilation'] === 'true') {
	$post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
	$log = get_transient('latex_compilation_log_' . $post_id);

	if (!empty($log)) {
		echo '<div class="notice notice-error">' . nl2br(esc_html($log)) . '</p></div>';
		// Delete transient after displaying
		delete_transient('latex_compilation_log_' . $post_id);
	}
}


// Register custom template for compiled_figure custom post type
	function custom_plugin_register_templates($template)
	{
		$post_types = array('compiled_figure');

	if (is_singular($post_types)) {
		$template_path = plugin_dir_path(__FILE__) . 'templates/single-compiled_figure.php';
		if ($template_path) {
			return $template_path;
		}
	}

		return $template;
	}


}

new Tex_Viewer_Plugin();


if (is_admin()) {
	$tex_viewer_settings_page = new gqqnbig\TexViewerSettingsPage();
}

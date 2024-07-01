<?php
/*
 * Plugin Name: TEX Viewer
 */


// Register Custom Post Type for Compiled Figures
$WPTEX_custom_post_type_compiled_figures = function () {

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

};


add_action('init', $WPTEX_custom_post_type_compiled_figures, 0);


$WPTEX_render_meta_box = function (WP_Post $post) {
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

};

$WPTEX_render_compilation_meta_box = function () {
	submit_button("Compile LaTeX", 'primary', 'compile-latex');
	submit_button("Compile Image", 'primary', 'compile-image');
};

// Add Meta Box for LaTeX Code
$WPTEX_add_latex_code_meta_box = function () {
	global $WPTEX_render_meta_box;
	add_meta_box(
		'latex_code_meta_box',
		'LaTeX Content',
		$WPTEX_render_meta_box,
		'compiled_figure'
	);

	global $WPTEX_render_compilation_meta_box;
	add_meta_box(
		'latex_compilation_meta_box',
		'Compile',
		$WPTEX_render_compilation_meta_box,
		'compiled_figure',
		'side'
	);
};

add_action('add_meta_boxes', $WPTEX_add_latex_code_meta_box);

function get_proc_output($handle, $pipes, &$stdout, &$stderr): int
{
	$timeout_in_second = 60;
	$start = microtime(true);
	$status = null;
	while (microtime(true) - $start < $timeout_in_second) {
		$status = proc_get_status($handle);
		if (!$status['running'])
			break;

		usleep(1000);
	}

	if (is_null($status) == false && $status['running']) {
		proc_terminate($handle);
	}
	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	$exitcode = proc_close($handle);

	return $exitcode;
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

	$result_code = get_proc_output($handle, $pipes, $stdout, $stderr);
    
	if ($result_code != 0) {
		$message = "xelatex command line output:\n";
		if (count($stderr) > 200) {
			$message .= '...\n';
			$message .= implode("\n", array_slice($stderr, -200));
		} else
			$message .= implode("\n", $stderr);
		set_transient('latex_compilation_log_' . $post->ID, $message, MINUTE_IN_SECONDS * 5);
	}
}

function clean_up_command_arguments(string $args)
{
	# remove comments
	$args = preg_replace('/#.+\n/', ' ', $args);
	# join lines
	$args = preg_replace('/[\r\n]/', ' ', $args);
	return $args;
}

// Save Meta Box data
$WPTEX_save_latex_code_meta_box = function ($post_id, $post) {
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
		compile_latex($post, $latex_code, $upload_path);
	} elseif (isset($_POST['compile-image']) && !is_null($magick_image_settings) && !is_null($magick_image_operators)) {
		$source_file = $upload_path . 'figure_' . $post->ID . '.pdf';
		$target_file = $upload_path . 'figure_' . $post->ID . '.' . $img_format;


		if (file_exists($source_file)) {
			# proc_open can accept an array as the command, with each element as an argument.
			# But here user provides a group of arguments.
			# I either have to split them into individual options, or join everything into one string.
			# Here I choose to join them.
			$magick_command = implode(' ', array('magick',
				clean_up_command_arguments($magick_image_settings),
				escapeshellarg($source_file),
				clean_up_command_arguments($magick_image_operators),
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

			$exit_code = get_proc_output($handle, $pipes, $stdout, $stderr);

			if ($exit_code != 0) {
				$message = "magick command line error:\n";
				if (strlen($stderr) > 200) {
					$message .= '...\n';
					$message .= substr($stderr, -200);
				} else
					$message .= $stderr;
				set_transient('latex_compilation_log_' . $post->ID, $message, MINUTE_IN_SECONDS * 5);
			}
		} else {
			set_transient('latex_compilation_log_' . $post_id, "PDF hasn't been compiled.", MINUTE_IN_SECONDS * 5);
		}
	}

};

add_action('save_post', $WPTEX_save_latex_code_meta_box, 10, 2);

function display_latex_compilation_notice()
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

add_action('admin_notices', 'display_latex_compilation_notice');


// Register custom template for compiled_figure custom post type
function custom_plugin_register_templates($template) {
	$post_types = array('compiled_figure');

	if (is_singular($post_types)) {
		$template_path = plugin_dir_path(__FILE__) . 'templates/single-compiled_figure.php';
		if ($template_path) {
			return $template_path;
		}
	}

	return $template;
}

add_filter('single_template', 'custom_plugin_register_templates');



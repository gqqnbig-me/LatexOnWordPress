<?php
/*
 * Plugin Name: TEX Viewer
 * Description: Allow users to write LaTeX (mainly tikz) code in a post, and this plugin will convert the post to an image. 
 * Version: 0.1
 * Author: gqqnbig
 * Requires PHP: 7.4
 */

require_once 'utilities.php';

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

class TeXViewerSettingsPage
{
	private const page_slug = "tex-viewer";
	private const  options_name = 'tex-viewer-options';
	private ?string $xelatex_info;
	private ?string $magick_info;


	/**
	 * Start up
	 */
	public function __construct()
	{
		// Fires before the administration menu loads in the admin.
		add_action('admin_menu', array($this, 'add_plugin_page'));
		// Fires as an admin screen or script is being initialized.
		add_action('admin_init', array($this, 'page_init'));
	}

	public function populate_xelatex_info()
	{
		$handle = popen('xelatex --version', 'r');
		if ($handle !== false) {
			$this->xelatex_info = fread($handle, 2096);
			pclose($handle);

			if (strpos($this->xelatex_info, 'XeTeX') === false)
				$this->xelatex_info = null;
		}

	}

	public function populate_magick_info()
	{
		$handle = popen('magick --version', 'r');
		if ($handle !== false) {
			$this->magick_info = fread($handle, 2096);
			pclose($handle);

			if (strpos($this->magick_info, 'ImageMagick') === false)
				$this->magick_info = null;
		}
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page()
	{
		// This page will be under "Settings"
		add_options_page(
			'Tex Viewer Settings',
			'Tex Viewer Settings',
			'manage_options',
			'tex-viewer-settings',
			array($this, 'create_admin_page')
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page()
	{
		// check user capabilities
		if (!current_user_can('manage_options'))
			return;

		// add error/update messages

		// check if the user have submitted the settings
		// WordPress will add the "settings-updated" $_GET parameter to the url
		if (isset($_GET['settings-updated'])) {
			// add settings saved message with the class of "updated"
			add_settings_error('tex_viewer_messages', 'tex_viewer_messages', 'Settings Saved', 'updated');
		}

		// show error/update messages
		settings_errors('tex_viewer_messages');

		?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "wporg"
				settings_fields('tex-viewer');
				// output setting sections and their fields
				// (sections are registered for "wporg", each field is registered to a specific section)
				do_settings_sections($this::page_slug);
				// output save settings button
				submit_button('Save Settings');
				?>
            </form>

            <!--            <div>-->
            <!--                xelatex:-->
            <!--                <div>-->
            <!--					--><?php //= nl2br(esc_html($xelatex_output))
			?>
            <!--                </div>-->
            <!--            </div>-->
            <!--            <div>-->
            <!--                magick:-->
            <!--                <div>-->
            <!--					--><?php //= nl2br(esc_html($magick_output))
			?>
            <!--                </div>-->
            <!--            </div>-->
        </div>
		<?php
	}

	function page_init()
	{
		// Register a new setting for "wporg" page.
		register_setting('tex-viewer', $this::options_name);

		$setting_section_id = 'tex-viewer-path';
		// Register a new section in the "wporg" page.
		add_settings_section(
			$setting_section_id,
			'Path settings', array($this, 'path_section_preamble'),
			$this::page_slug
		);

		// Register a new field in the "wporg_section_developers" section, inside the "wporg" page.
		add_settings_field(
			'tex-viewer-xelatex-path',
			'xelatex',
			array($this, 'choose_xelatex_path'),
			$this::page_slug,
			$setting_section_id);

		add_settings_field(
			'tex-viewer-magick-path',
			'magick',
			array($this, 'choose_magick_path'),
			$this::page_slug,
			$setting_section_id);
	}

	function path_section_preamble()
	{
		?>
        <div>
            <p>On plugin activation, Tex Viewer tries to detect the path of xelatex and magick.
                If it fails, users are redirected to this page to specify the path.
            <p>The auto-detection is based on <?= PHP_OS_FAMILY === 'Windows' ? 'the Windows command <code>where</code>' : 'the Bash builtin <code>type</code>' ?>,
                which reads the <code>PATH</code> environment variable.
        </div>
		<?php
		if (PHP_OS_FAMILY !== 'Windows') {
            ?>
            <p>On Linux, the <code>PATH</code> environment variable may be sanitized by your web server (nginx or apache),
		        php-fpm (fastcgi), and php itself. Changing it is very difficult.
		    <p> Specifying a path is way easier.
            <?php
		}

		if (PHP_OS_FAMILY === 'Windows')
			echo '<div>$ set</div>';
		else
			echo '<div>$ env</div>';
		$env = getenv();

		$filtered_env = array_filter($env, function ($key) {
			if (stripos($key, 'http') !== false)
				return false;
			$output_keys = array('home', 'user', 'pwd', 'path');
			foreach ($output_keys as $output_key) {
				if (stripos($key, $output_key) !== false)
					return true;
			}
			return false;
		}, ARRAY_FILTER_USE_KEY);

		$env_output = '';
		foreach ($filtered_env as $key => $value) {
			$env_output .= "$key=$value\n";
		}
		if (array_key_exists(strtolower('path'), array_change_key_case($filtered_env, CASE_LOWER)) === false && PHP_OS_FAMILY !== 'Windows')
			$env_output .= "PATH=" . shell_exec('echo $PATH') . "\n";

		echo '<div style="color:#0550ae">';
		echo nl2br(esc_html($env_output));
		echo '</div>';


		$options = get_option($this::options_name);
		if ($options === false)
			$options = array();
		$xelatex_path = $options['xelatex-path'] ?? '';
		$this->print_command_info($xelatex_path, 'xelatex', $this->xelatex_info);

		$magick_path = $options['magick-path'] ?? '';
		$this->print_command_info($magick_path, 'magick', $this->magick_info);

		if (!is_null($this->xelatex_info) && !is_null($this->magick_info))
			echo '<div>Self-check passed</div>';
	}

	function print_command_info($folder, $command, $version_output)
	{
		$whereis = PHP_OS_FAMILY === 'Linux' ? 'whereis' : 'where';

		if (is_null($this->xelatex_info)) {
			echo '<div>$ ' . $whereis . ' ' . $folder . $command . ' && ' . $folder . $command . ' --version</div>';
			echo '<div>Command not found</div>';
		} else {
			if (is_null($folder) || strlen($folder) === 0) {
				$handle = popen("$whereis $command", 'r');
				$output = null;
				if ($handle !== false) {
					$output = fread($handle, 2096);
					pclose($handle);
				}

				if (!is_null($output)) {
					echo '<div>$ ' . $whereis . ' ' . $command . ' && ' . $command . ' --version</div>';
					echo '<div>' . esc_html($output) . '</div>';
				} else
					echo '<div>$ ' . $command . ' --version</div>';
			} else {
				echo '<div>$ ' . $command . ' --version</div>';
			}
			echo '<div>' . nl2br(esc_html($version_output)) . '</div>';
		}
	}

	function choose_xelatex_path()
	{
		// Get the value of the setting we've registered with register_setting()
		$options = get_option('xelatex-path');

		echo '<pre>$ xelatex --version <div style="color:#0550ae">' . esc_html($this->xelatex_info) . '</div></pre>';

		?>
        <label style="display: block"><input type="radio" name="xelatex-path-choice" value="user"/> Specify the path for
            xelatex</label>
        <div>
            <input style="width: 30em" type="text" name="xelatex-path"
                   placeholder="/opt/texlive2024/bin/x86_64-linux/" value="<?= esc_html($options) ?>"/>
        </div>
		<?php
	}

	function choose_magick_path()
	{
		// Get the value of the setting we've registered with register_setting()
		$options = get_option('magick-path');

		echo '<pre>' . esc_html($this->magick_info) . '</pre>';
		?>
        <label style="display: block"><input type="radio" name="magick-path-choice" value="user"/> Specify the path for
            magick</label>
        <div>
            <input style="width: 30em" type="text" name="magick-path"
                   placeholder="" value="<?= esc_html($options) ?>"/>
        </div>
		<?php
	}
}


if (is_admin()) {
	$tex_viewer_settings_page = new TeXViewerSettingsPage();
	$tex_viewer_settings_page->populate_xelatex_info();
	$tex_viewer_settings_page->populate_magick_info();

}
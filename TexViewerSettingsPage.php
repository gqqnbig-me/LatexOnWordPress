<?php

namespace gqqnbig;


require_once 'utilities.php';

class TexViewerSettingsPage
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

		add_action('update_option', array($this, 'option_changed'), 10, 3);
	}

	/**
     * This function is fired after init.
	 * @param $option_name
	 * @param $old_value
	 * @param $new_value
	 * @return void
	 */
	function option_changed($option_name, $old_value, $new_value)
	{
		if ($option_name === 'xelatex-path') {
			if ($new_value !== $old_value) {
				delete_transient('tex-viewer-settings-xelatex-version');
				delete_transient('tex-viewer-settings-xelatex-error');
			}
		}

		if ($option_name === 'magick-path') {
			if ($new_value !== $old_value) {
				delete_transient('tex-viewer-settings-magick-version');
				delete_transient('tex-viewer-settings-magick-error');
			}
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
//		if (isset($_GET['settings-updated'])) {
//			// add settings saved message with the class of "updated"
//			add_settings_error('tex_viewer_messages', 'tex_viewer_messages', 'Settings Saved', 'updated');
//		}

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


	function verify_executable($file, $keyword, string &$version_info): string
	{
		$handle = proc_open(array($file, '--version'), [
			0 => ["pipe", "r"],  // stdin
			1 => ["pipe", "w"],  // stdout
			2 => ["pipe", "w"],  // stderr
		], $pipes, null, null, ['bypass_shell' => true]);

		if (!is_resource($handle))
			return 'Failed to call <code>' . esc_html(stripslashes($file)) . '</code>';


		$result_code = get_proc_output($handle, $pipes, $stdout, $stderr);
		if ($result_code !== 0) {
			return 'Execution of  <code>' . esc_html(stripslashes($file)) . ' --version</code> failed.';
		} elseif (strpos($stdout, $keyword) !== false) {
			$version_info = $stdout;
			return '';
		} else {
			return 'Output of <code>' . esc_html(stripslashes($file)) . ' --version</code> is not recognized.';
		}
	}

	function verify_dependency(string $binary, string $version_keyword)
	{
		$path = isset($_POST["$binary-path"]) ? sanitize_text_field($_POST["$binary-path"]) : '';
		$version_info = '';
		$err = $this->verify_executable($path, $version_keyword, $version_info);
		if (!empty($err))
			set_transient("tex-viewer-settings-$binary-error", $err, MINUTE_IN_SECONDS * 5);
		else {
			set_transient("tex-viewer-settings-$binary-version", $version_info, MINUTE_IN_SECONDS * 5);
			add_settings_error('general', "run_$binary", "Run $binary successfully.", 'success');
		}
	}

	function page_init()
	{
		register_setting('tex-viewer', 'xelatex-path');
		register_setting('tex-viewer', 'magick-path');

		if (isset($_POST['verify-xelatex'])) {
			$this->verify_dependency('xelatex', 'XeTeX');
		}
		if (isset($_POST['verify-magick'])) {
			$this->verify_dependency('magick', 'ImageMagick');
		}


		$setting_section_id = 'tex-viewer-path';
		add_settings_section(
			$setting_section_id,
			'Path settings', array($this, 'path_section_preamble'),
			$this::page_slug
		);

		add_settings_field(
			'tex-viewer-xelatex-path',
			'path for xelatex',
			array($this, 'build_verifiable_path_inputbox'),
			$this::page_slug,
			$setting_section_id,
			array(
				'label_for' => 'tex_viewer_xelatex_input',
				'name' => 'xelatex',
			));

		add_settings_field(
			'tex-viewer-magick-path',
			'path for magick',
			array($this, 'build_verifiable_path_inputbox'),
			$this::page_slug,
			$setting_section_id,
			array(
				'label_for' => 'tex_viewer_magick_input',
				'name' => 'magick',
			));
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
            <p>On Linux, the <code>PATH</code> environment variable may be sanitized by your web server (nginx or apache), php-fpm (fastcgi), and php itself.
            Changing it is very difficult. In a nutshell, specifying a path is way easier.
			<?php
		}
	}


	function build_verifiable_path_inputbox($args)
	{
		$name = $args['name'];
		$option = get_option("$name-path");
		?>
        <input style="width: 30em" type="text" id="<?= esc_attr( $args['label_for'] ); ?>"
               name="<?= esc_attr("$name-path") ?>" value="<?= esc_attr($option) ?>"/>
        <input type="submit" name="<?= esc_attr("verify-$name") ?>" value="Verify">
		<?php
		$xelatex_version = get_transient("tex-viewer-settings-$name-version");
		if (!empty($xelatex_version)) {
			echo '<pre>' . esc_html($xelatex_version) . '</pre>';
		}
		$error = get_transient("tex-viewer-settings-$name-error");
		if (!empty($error)) {
			echo '<div>' . $error . '</div>';
		}
	}
}
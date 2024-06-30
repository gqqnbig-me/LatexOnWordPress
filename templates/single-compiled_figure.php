<?php

the_post();

//	$tex_file = $upload_path . 'figure_' . $post_id . '.tex';
$upload_dir = wp_upload_dir();
$upload_path = trailingslashit($upload_dir['basedir']) . 'compiled_figures/';
$upload_url = trailingslashit($upload_dir['baseurl']) . 'compiled_figures/';

$img_format = get_post_meta($post->ID, 'img_format', true);
switch ($img_format) {
	case "gif":
		break;
	default:
		$img_format = "png";
}

$image_file_name = 'figure_' . $post->ID . '.' . $img_format;

if (array_key_exists('img', $_GET)) {
	// Output image directly and set headers
	if (file_exists($upload_path . $image_file_name)) {
		switch ($img_format) {
			case "gif":
				header('Content-Type: image/gif');
				break;
			default:
				header('Content-Type: image/png');
		}
		readfile($upload_path . $image_file_name);
	}
	exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<body>
<div>
	<?php

	$content = null;
	// Check if it's a single post of the compiled_figure type
	if (is_singular('compiled_figure') && !is_admin()) {

		// image box

		$content .= '<div>';
		if (file_exists($upload_path . $image_file_name))
			$content .= '<img style="max-width: 90%;" src="' . esc_url($upload_url . $image_file_name) . '" alt="Compiled Figure">';
		else
			$content .= 'Image not generated';
		$content .= '</div>';


		//pdf box
		$content .= '<div>';
		if (file_exists($upload_path . 'figure_' . $post->ID . '.pdf'))
			$content .= '<a href="' . esc_url($upload_url . 'figure_' . $post->ID . '.pdf') . '">Download PDF</a>';
		else
			$content .= 'PDF not generated';
		$content .= '</div>';


		$latex_code = get_post_meta($post->ID, 'latex_code', true);
		$content .= '<div>';
		$content .= '<pre><code class="language-latex">' . esc_html($latex_code) . '</code></pre>';
		$content .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/vs.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/latex.min.js"></script>
<script>hljs.highlightAll();</script>';
		$content .= '</div>';
	}

	echo $content;

	?>
</div>
</body>
</html>

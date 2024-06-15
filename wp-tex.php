<?php
/*
 * Plugin Name: TEX Viewer
 */

function WPTEX_mime_types($mime_types)
{
	// WordPress uses PHP fileinfo extension (https://github.com/php/php-src/tree/f0fb9e34a5a2e0919d3264db5c3d69e3e2d2cd63/ext/fileinfo)
	// to verify the mapping from file extension to MIME,
	// so that if you write wrong key value pairs, such as `$mime_types['mp3'] = 'text/hello'`,
	// all .mp3 files will be rejected.

	// The fileinfo extension has a database data_file.c, which is generated from libmagic(3) on
	// https://github.com/file/file/tree/2fee91f1d3fe904c0f8b8298cc5a8dc70a05c59d/magic

	// https://github.com/file/file/blob/2fee91f1d3fe904c0f8b8298cc5a8dc70a05c59d/magic/Magdir/tex#L43
	// states that the MIME type of .tex file is usually "text/x-tex".
	$mime_types['tex'] = 'text/x-tex';
	return $mime_types;
}

add_filter('upload_mimes', 'WPTEX_mime_types');
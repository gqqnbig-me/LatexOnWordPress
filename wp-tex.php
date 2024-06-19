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

// Register Custom Post Type for Compiled Figures
function WPTEX_custom_post_type_compiled_figures() {

    $labels = array(
        'name'                  => _x( 'Compiled Figures', 'Post Type General Name', 'text_domain' ),
        'singular_name'         => _x( 'Compiled Figure', 'Post Type Singular Name', 'text_domain' ),
        'menu_name'             => __( 'Compiled Figures', 'text_domain' ),
        'name_admin_bar'        => __( 'Compiled Figure', 'text_domain' ),
        'archives'              => __( 'Compiled Figure Archives', 'text_domain' ),
        'attributes'            => __( 'Compiled Figure Attributes', 'text_domain' ),
        'parent_item_colon'     => __( 'Parent Compiled Figure:', 'text_domain' ),
        'all_items'             => __( 'All Compiled Figures', 'text_domain' ),
        'add_new_item'          => __( 'Add New Compiled Figure', 'text_domain' ),
        'add_new'               => __( 'Add New', 'text_domain' ),
        'new_item'              => __( 'New Compiled Figure', 'text_domain' ),
        'edit_item'             => __( 'Edit Compiled Figure', 'text_domain' ),
        'update_item'           => __( 'Update Compiled Figure', 'text_domain' ),
        'view_item'             => __( 'View Compiled Figure', 'text_domain' ),
        'view_items'            => __( 'View Compiled Figures', 'text_domain' ),
        'search_items'          => __( 'Search Compiled Figures', 'text_domain' ),
        'not_found'             => __( 'Not found', 'text_domain' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
        'featured_image'        => __( 'Featured Image', 'text_domain' ),
        'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
        'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
        'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
        'insert_into_item'      => __( 'Insert into compiled figure', 'text_domain' ),
        'uploaded_to_this_item' => __( 'Uploaded to this compiled figure', 'text_domain' ),
        'items_list'            => __( 'Compiled Figures list', 'text_domain' ),
        'items_list_navigation' => __( 'Compiled Figures list navigation', 'text_domain' ),
        'filter_items_list'     => __( 'Filter compiled figures list', 'text_domain' ),
    );
    $args = array(
        'label'                 => __( 'Compiled Figure', 'text_domain' ),
        'description'           => __( 'Post type for Compiled Figures', 'text_domain' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
    );
    register_post_type( 'compiled_figure', $args );

}
add_action( 'init', 'WPTEX_custom_post_type_compiled_figures', 0 );


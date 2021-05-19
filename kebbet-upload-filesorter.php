<?php
/**
 * Plugin Name: Kebbet plugins - Upload sorter
 * Plugin URI:  https://github.com/kebbet/kebbet-upload-filesorter
 * Description: Sort uploaded images and pdfs to folders in WP_Upload_DIR.
 * Version:     20210519.01
 * Author:      Erik Betshammar
 * Author URI:  https://verkan.se
 *
 * @package kebbet-upload-filesorter
 * @author Erik Betshammar
 *
 * NOTE!
 * Based on this question: http://wordpress.stackexchange.com/questions/47415/change-upload-directory-for-pdf-files
 * Main function snitched from: https://wordpress.org/plugins/custom-upload-dir/
 */

namespace kebbet\muplugin\uploadsorter;

add_filter( 'wp_handle_upload_prefilter', __NAMESPACE__ . '\pre_upload' );
add_filter( 'wp_handle_upload', __NAMESPACE__ . '\post_upload' );

/**
 * Change upload direcotory temporarly while uploading files
 *
 * @param array $file The temporary file that should be stored.
 */
function pre_upload( $file ) {
	add_filter( 'upload_dir', __NAMESPACE__ . '\custom_upload_dir' );
	return $file;
}

/**
 * Change upload direcotory temporarly while uploading files
 *
 * @param array $fileinfo The uploaded files' info.
 */
function post_upload( $fileinfo ) {
	remove_filter( 'upload_dir', __NAMESPACE__ . '\custom_upload_dir' );
	return $fileinfo;
}

/**
 * New paths for files on upload
 *
 * @param array $path All the default paths for the file.
 * @return array $path All new paths for the file.
 */
function custom_upload_dir( $path ) {

	// If error, do nothing.
	if ( ! empty( $path['error'] ) ) {
		return $path;
	}
	// If no name, do nothing.
	if ( ! isset( $_POST['name'] ) ) {
		return $path;
	}
	// Set custom folder name for listed extensions.
	$customdir = define_directory( sanitize_file_name( wp_unslash( $_POST['name'] ) ) );

	// Update paths if there should be a new path.
	if ( $customdir ) {

		$subl = strlen( $path['subdir'] );
		if ( $subl > 0 ) {
			// Remove default subdir (year/month).
			$path['path'] = substr( $path['path'], 0, 0 - $subl );
			$path['url']  = substr( $path['url'], 0, 0 - $subl );
		}
		$path['subdir'] = $customdir;
		$path['path']  .= $customdir;
		$path['url']   .= $customdir;

	}

	// Allways return the $path, even if not updated.
	return $path;

}

/**
 * Define the custom storage parts, depending on file-type
 * Store docs, images, media in separate folders.
 *
 * @param string $filename The file full name, from $_POST object.
 * @return string Folder name to be placed last in path.
 */
function define_directory( $filename ) {

	$wp_filetype = wp_check_filetype( $filename ); 
	$extension   = ( ! empty( $wp_filetype['ext'] ) ) ? $wp_filetype['ext'] : '';
	$customdir   = null;

	switch ( $extension ) {
		case 'pdf':
		case 'doc':
		case 'docx':
		case 'pages':
		case 'txt':
		case 'xls':
		case 'xlsx':
		case 'csv':
		case 'xml':
		case 'json':
			$customdir = '/documents';
			break;

		case 'jpg':
		case 'jpeg':
		case 'png':
		case 'gif':
		case 'tif':
		case 'tiff':
		case 'svg':
		case 'webp':
			$customdir = '/images';
			break;

		case 'mp3':
		case 'mp4':
		case 'mov':
			$customdir = '/media';
			break;

		default:
			break;
	}
	return $customdir;
}

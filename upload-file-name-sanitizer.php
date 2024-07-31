<?php
/*
Plugin Name: Upload File Name Sanitizer
Description: Sanitize uploaded file names to ensure they are safe and compatible.
Version: 1.0
Author: Jaroslaw Herisz
License: Mit
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Upload_File_Name_Sanitizer {

    public $plugin_settings = array(
        'mime_types' => array(
            'image/gif',
            'image/jpeg',
            'image/jpg',
            'image/pjpeg',
            'image/png',
            'image/tiff',
            'image/avif',
            'image/webp',
        ),
    );

    public $charsReplaced = array(
        'ą' => 'a',
        'ś' => 's',
        'ć' => 'c',
        'ę' => 'e',
        'ł' => 'l',
        'ń' => 'n',
        'ó' => 'o',
        'ż' => 'z',
        'ź' => 'z',
    );

    public function __construct() {
        register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
        add_action( 'wp_handle_upload_prefilter', array( $this, 'upload_filter' ) );
        add_action( 'wp_handle_sideload_prefilter', array( $this, 'upload_filter' ) );
        add_action( 'add_attachment', array( $this, 'update_attachment_title' ) );
    }

    public function plugin_activation() {
    }

    public function upload_filter( $file )
    {
        $file_pathinfo = pathinfo( $file['name'] );

        $mime_types = $this->plugin_settings['mime_types'];
        $chars_replaced = $this->charsReplaced;

        if(in_array($file['type'], $mime_types)) {
            set_transient( '_clean_image_filenames_original_filename', $file_pathinfo['filename'], 60 );

            // Replace whitespaces with dashes.
            $cleaned_filename = str_replace( [' ','_'], '-', $file_pathinfo['filename'] );

            // Convert filename to lowercase.
            $cleaned_filename = strtolower( $cleaned_filename );

            // Replace specific characters.
            $cleaned_filename = str_replace( array_keys( $chars_replaced ), array_values( $chars_replaced ), $cleaned_filename );

            // Remove characters that are not a-z, 0-9, or - (dash).
            $cleaned_filename = preg_replace( '/[^a-z0-9-]/', '', $cleaned_filename );

            // Remove multiple dashes in a row.
            $cleaned_filename = preg_replace( '/-+/', '-', $cleaned_filename );

            // Trim potential leftover dashes at each end of filename.
            $cleaned_filename = trim( $cleaned_filename, '-' );

            // Replace original filename with cleaned filename.
            $file['name'] = $cleaned_filename . '.' . $file_pathinfo['extension'];

        }

        return $file;
    }

    public function update_attachment_title( $attachment_id ) {

        $original_filename = get_transient( '_clean_image_filenames_original_filename' );

        if ( $original_filename ) {

            $original_filename = str_replace( ['_', '-'], ' ', $original_filename );

            // Update attachment post.
            wp_update_post(
                array(
                    'ID'         => $attachment_id,
                    'post_title' => $original_filename,
                )
            );

            // Delete transient.
            delete_transient( '_clean_image_filenames_original_filename' );
        }
    }

}

new Upload_File_Name_Sanitizer();
<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFC_Image_Handler {

    public static function process_upload( $base64_data, $mime_type = 'image/jpeg' ) {
        if ( preg_match( '/^data:image\/(\w+);base64,/', $base64_data, $m ) ) {
            $mime_type = 'image/' . $m[1];
            $base64_data = substr( $base64_data, strpos( $base64_data, ',' ) + 1 );
        }

        $raw_bytes = base64_decode( $base64_data );
        if ( empty( $raw_bytes ) ) {
            return null;
        }

        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/smart-fridge';
        $target_url = $upload_dir['baseurl'] . '/smart-fridge';

        if ( ! file_exists( $target_dir ) ) {
            wp_mkdir_p( $target_dir );
        }

        $src_img = @imagecreatefromstring( $raw_bytes );
        if ( ! $src_img ) {
            return null;
        }

        $orig_w = imagesx( $src_img );
        $orig_h = imagesy( $src_img );

        $hash = md5( $raw_bytes . microtime( true ) );
        $full_filename  = $hash . '.webp';
        $thumb_filename = $hash . '_thumb.webp';

        $full_path  = $target_dir . '/' . $full_filename;
        $thumb_path = $target_dir . '/' . $thumb_filename;

        $max_dim = 1600;
        if ( $orig_w > $max_dim || $orig_h > $max_dim ) {
            $ratio = min( $max_dim / $orig_w, $max_dim / $orig_h );
            $new_w = max( 1, round( $orig_w * $ratio ) );
            $new_h = max( 1, round( $orig_h * $ratio ) );

            $full_img = imagecreatetruecolor( $new_w, $new_h );
            imagealphablending( $full_img, false );
            imagesavealpha( $full_img, true );
            imagecopyresampled( $full_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h );
            imagewebp( $full_img, $full_path, 82 );
            imagedestroy( $full_img );
        } else {
            imagewebp( $src_img, $full_path, 82 );
        }

        $thumb_w = 400;
        $thumb_h = 300;
        $ratio = max( $thumb_w / $orig_w, $thumb_h / $orig_h );
        $crop_w = round( $thumb_w / $ratio );
        $crop_h = round( $thumb_h / $ratio );
        $crop_x = max( 0, round( ( $orig_w - $crop_w ) / 2 ) );
        $crop_y = max( 0, round( ( $orig_h - $crop_h ) / 2 ) );

        $thumb_img = imagecreatetruecolor( $thumb_w, $thumb_h );
        imagealphablending( $thumb_img, false );
        imagesavealpha( $thumb_img, true );
        imagecopyresampled( $thumb_img, $src_img, 0, 0, $crop_x, $crop_y, $thumb_w, $thumb_h, $crop_w, $crop_h );
        imagewebp( $thumb_img, $thumb_path, 78 );

        imagedestroy( $thumb_img );
        imagedestroy( $src_img );

        return [
            'full_url'   => $target_url . '/' . $full_filename,
            'thumb_url'  => $target_url . '/' . $thumb_filename,
            'full_path'  => $full_path,
            'thumb_path' => $thumb_path,
            'hash'       => $hash,
        ];
    }
}

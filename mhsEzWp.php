<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('mhsEzWp')) {

    class mhsEzWp {

        private $wpdb = null;
        private $post = null;

        function __construct() {
            global $wpdb, $post;

            if (isset($wpdb)) {
                $this->wpdb = $wpdb;
            }
            if (isset($post)) {
                $this->post = $post;
            }
        }

        function get_permalink_by_slug($slug, $post_type = '') {
            $permalink = null;
            $args = array(
                'name' => $slug,
                'max_num_posts' => 1
            );
            if ('' != $post_type) {
                $args = array_merge($args, array('post_type' => $post_type));
            }
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                $query->the_post();
                $permalink = get_permalink(get_the_ID());
            }
            wp_reset_postdata();

            return $permalink;
        }

        function upload_file($uploadedfile = array(), $post_id = 0) {
            $post_id = (int) $post_id;
            $attach_id = 0;
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            if (!empty($uploadedfile)) {
                $upload_overrides = array('test_form' => false);
                $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
                if ($movefile) {
                    $wp_filetype = $movefile['type'];
                    $filename = $movefile['file'];
                    $wp_upload_dir = wp_upload_dir();
                    $attachment = array(
                        'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
                        'post_mime_type' => $wp_filetype,
                        'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    $attach_id = wp_insert_attachment($attachment, $filename, $post_id);
                }
            }

            return $attach_id;
        }

        function attach_image($post_id = 0, $uploadedfile = array(), $image_type = 'file') {
            $attach_id = 0;
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            if (!empty($post_id) && !empty($uploadedfile)) {
                if ($image_type == 'file') {
                    $upload_overrides = array('test_form' => false);
                    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
                    if ($movefile) {
                        $wp_filetype = $movefile['type'];
                        $filename = $movefile['file'];
                        $wp_upload_dir = wp_upload_dir();
                        $attachment = array(
                            'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
                            'post_mime_type' => $wp_filetype,
                            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                            'post_content' => '',
                            'post_status' => 'inherit'
                        );
                        $attach_id = wp_insert_attachment($attachment, $filename, $post_id);
                    }
                } elseif ($image_type == 'url') {
                    $media = media_sideload_image($uploadedfile, $post_id);
                    // therefore we must find it so we can set it as featured ID
                    if (!empty($media) && !is_wp_error($media)) {
                        $args = array(
                            'post_type' => 'attachment',
                            'posts_per_page' => -1,
                            'post_status' => 'any',
                            'post_parent' => $post_id
                        );

                        $attachments = get_posts($args);

                        if (isset($attachments) && is_array($attachments)) {
                            foreach ($attachments as $attachment) {
                                $image = wp_get_attachment_image_src($attachment->ID, 'full');
                                if (strpos($media, $image[0]) !== false) {
                                    $attach_id = $attachment->ID;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            return $attach_id;
        }

        function set_feature_image($post_id = 0, $uploadedfile = array(), $image_type = 'file') {
            $thumbnail_id = $this->attach_image($post_id, $uploadedfile, $image_type);
            if ($thumbnail_id) {
                set_post_thumbnail($post_id, $thumbnail_id);
            }

            return $thumbnail_id;
        }

        function user_add($data = array(),$meta_data = array()) {
            $userdata = array(
                'user_email' => $data['email'],
                'user_login' => $data['username'],
                'user_pass' => $data['password']
            );

            if (isset($data['first_name']) && $data['first_name']) {
                $userdata['first_name'] = $data['first_name'];
            }

            if (isset($data['last_name']) && $data['last_name']) {
                $userdata['last_name'] = $data['last_name'];
            }

            if (isset($data['role']) && $data['role']) {
                $userdata['role'] = $data['role'];
            }

            $user_id = wp_insert_user($userdata);
            
            if($user_id && is_array($meta_data) && $meta_data){
                foreach($meta_data as $meta_key => $meta_val){
                    add_user_meta($user_id, $meta_key, $meta_val);
                }
            }

            return $user_id;
        }

    }

}

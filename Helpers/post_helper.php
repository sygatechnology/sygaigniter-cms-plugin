<?php

    function get_default_comment_status($post_type, $default = 0){
        $optionObject = \Config\Services::options();
        $comment_status = $optionObject->get( 'cms_default_comment_status', [] );
        return (! is_null($comment_status)) ? (isset($comment_status[$post_type]) ? $comment_status[$post_type] : $default) : $default;
    }

    function set_unique_post_slug($slug, $post_status, $post_id = 0){
        if ( in_array( $post_status, array( 'draft', 'pending', 'auto-draft' ) ) || empty($slug) ) {
            return $slug;
        }
        $alt_post_name = $slug;
        $db = \Config\Database::connect();
        $builder = $db->table('posts');
        $builder->select('post_name')->like('post_name', $alt_post_name);
        if( (int) $post_id > 0 ){
            $builder->where("post_id <>", $post_id);
        }
        $post_names = [];
        foreach ($builder->get()->getResult() as $post) {
            $post_names[] = $post->post_name;
        }
        $post_name_check = count($post_names) > 0 ? true : false;
        if( $post_name_check ){
            $suffix = 1;
            do {
                $alt_post_name   = _truncate_slug( $alt_post_name, 200 ). "-$suffix";
                $post_name_check = in_array($alt_post_name, $post_names) ? true : false;
                $suffix++;
            } while ( $post_name_check );
        }
  			return $alt_post_name;
    }

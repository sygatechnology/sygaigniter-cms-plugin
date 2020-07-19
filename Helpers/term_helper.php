<?php

    function get_default_term_term($term_type, $taxonomy, $default = 0){
        $optionObject = \Config\Services::options();
        $default_terms = $optionObject->get( 'cms_get_default_term_term', [] );
        return (! is_null($default_terms)) ? (isset($default_terms[$term_type][$taxonomy]) ? $default_terms[$term_type][$taxonomy] : $default) : $default;
    }

    function set_unique_term_slug($slug, $term_id = 0){
        $alt_name = $slug;
        $db = \Config\Database::connect();
        $builder = $db->table('terms');
        $builder->select('slug')->like('slug', $alt_name);
        if( (int) $term_id > 0 ){
            $builder->where("term_id <>", $term_id);
        }
        $names = [];
        foreach ($builder->get()->getResult() as $term) {
            $names[] = $term->name;
        }
        $name_check = count($names) > 0 ? true : false;
        if( $name_check ){
            $suffix = 1;
            do {
                $alt_name   = _truncate_slug( $alt_name, 200 ). "-$suffix";
                $name_check = in_array($alt_name, $names) ? true : false;
                $suffix++;
            } while ( $name_check );
        }
  			return $alt_name;
    }

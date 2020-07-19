<?php namespace Plugin\cms\Services;

use CodeIgniter\Config\BaseService;

class TermsService extends BaseService
{
    public static function getWhere($taxonomy, $terms = '')
    {
        $db = \Config\Database::connect();
        $q = $db->table('term_taxonomies')->where('taxonomy', $taxonomy);
        if(! empty($terms) ){
            $terms = (array) $terms;
            $q->whereIn('term_id', $terms);
        }
        $rows = $q->get()->getResult();
        return count($rows) > 0 ? $rows : false;
    }
}

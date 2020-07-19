<?php namespace Plugin\cms\Entities;

/**
* @package    App\Entities
* @author     SygaTechnology Dev Team
* @copyright  2019 SygaTechnology Foundation
*/

use \App\Core\SY_Entity;
use CodeIgniter\I18n\Time;
use \Plugin\cms\Services\TaxonomiesService;

/**
 * Class User
 *
 * @todo Users Resource Entity
 *
 * @package App\Entities
 */
final class Post extends SY_Entity {

    private $hasError = false;
    private $error = '';
    private $postFromDb = null;

    protected $attributes = [
        'post_author'           => 0,
        'post_content'          => '',
        'post_excerpt'          => '',
        'post_content_filtered' => '',
        'post_title'            => '',
        'post_name'            => '',
        'post_status'           => 'draft',
        'post_type'             => 'post',
        'comment_status'        => '',
        'post_parent'           => 0
    ];

    protected $datamap = [
        'ID' => 'post_id'
    ];

    protected $casts = [
        "post_id" => "int",
        "post_parent" => "int",
        "comment_count" => "int",
        "post_author" => "int"
    ];

    protected $dates = ['post_date', 'post_modified', 'post_deleted'];

    /**
     * Allows filling in Entity parameters during construction.
     *
     * @param array|null $data
     */

    protected $now;
    protected $nowPlusTwoDays;

    /**
     * Contructor
     *
     * @param array|int      $postData The post data or the post ID for get before post data.
     */
    public function __construct($postData = null)
    {
        if(! is_null($postData) && is_numeric($postData)){
            $this->setFromDb((int) $postData);
        } else {
            parent::__construct($postData);
        }
    }

    public function fillArgs(array $data){
        if(! array_key_exists('post_args', $data)){
            throw new \InvalidArgumentException('post_args key must be specified in data array param.');
        }
        $this->post_args = $data['post_args'];
        $keys = [
            'postmeta_args',
            'post_term_args'
        ];
        foreach ($keys as $key) {
            if(! array_key_exists($key, $data)){
                $data[$key] = [];
            }
            $this->$key = (array) $data[$key];
        }
    }

    private function setFromDb($postID){
        $db = \Config\Database::connect();
        $row = $db->table('posts')->where('post_id', $postID)->get()->findAll();
        if(! empty($row)){
            $this->postFromDb = $row;
        } else {
            $this->postFromDb = null;
            $this->errorsList = 'Invalid post ID.';
        }
    }

    public function thePost(){
        if($this->isNull()){
            throw new \InvalidArgumentException('The post is null.');
        }
        return $this->postFromDb;
    }

    public function isNull(){
        return is_null($this->postFromDb);
    }

    public function errors(){
        return $this->error;
    }

    public function getField($fieldName){
        if($this->isNull()){
            throw new \InvalidArgumentException('The post is null.');
        }
        return isset($this->postFromDb[$fieldName]) ? $this->postFromDb[$fieldName] : null;
    }

    public function getTaxonomies(){
        $taxonomies = [];
        foreach (TaxonomiesService::get() as $taxonomy) {
            $taxonomy->terms = $this->getTerms($taxonomy->name);
            $taxonomies[] = $taxonomy;
        }
        return $taxonomies;
    }

    private function getTerms(string $taxonomy){
        $db = \Config\Database::connect();
        return $db->table('term_relationships')
            ->where('object_id', $this->attributes['post_id'])
            ->where('term_taxonomies.taxonomy', $taxonomy)
            ->join("term_taxonomies", "term_taxonomies.term_taxonomy_id = term_relationships.term_taxonomy_id")
            ->join("terms", "terms.term_id = term_taxonomies.term_id")
            ->get()->getresult();
    }

}

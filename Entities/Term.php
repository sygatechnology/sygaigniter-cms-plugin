<?php namespace Plugin\cms\Entities;

/**
* @package    App\Entities
* @author     SygaTechnology Dev Team
* @copyright  2019 SygaTechnology Foundation
*/

use \App\Core\SY_Entity;
use CodeIgniter\I18n\Time;

/**
 * Class User
 *
 * @todo Users Resource Entity
 *
 * @package App\Entities
 */
final class Term extends SY_Entity {

    private $hasError = false;
    private $error = '';
    private $termFromDb = null;

    protected $attributes = [
        'name'          => '',
        'slug'          => '',
        'taxonomy'       => '',
        'description'   => '',
        'parent'        => ''
    ];

    /**
     * Allows filling in Entity parameters during construction.
     *
     * @param array|null $data
     */

    protected $now;
    protected $nowPlusTwoDays;

    protected $casts = [
        "object_id" => "int"
    ];

    /**
     * Contructor
     *
     * @param array|int      $termData The term data or the term ID for get before term data.
     */
    public function __construct($termData = null)
    {
        if(! is_null($termData) && is_numeric($termData)){
            $this->setFromDb((int) $termData);
        } else {
            parent::__construct($termData);
        }
    }

    public function fillArgs(array $data){
        if(! array_key_exists('term_args', $data)){
            throw new \InvalidArgumentException('term_args key must be specified in data array param.');
        }
        $this->term_args = $data['term_args'];
        $keys = [
            'termmeta_args'
        ];
        foreach ($keys as $key) {
            if(! array_key_exists($key, $data)){
                $data[$key] = [];
            }
            $this->$key = (array) $data[$key];
        }
    }

    private function setFromDb($termID){
        $db = \Config\Database::connect();
        $row = $db->table('terms')->join('term_taxonomies', 'term_taxonomies.term_id = terms.term_id')->where('terms.term_id', $termID)->limit(1)->get()->getRowArray();
        if(! empty($row)){
            $this->termFromDb = (array) $row;
        } else {
            $this->termFromDb = null;
            $this->errorsList = 'Invalid term ID.';
        }
    }

    public function theTerm(){
        if($this->isNull()){
            throw new \InvalidArgumentException('The term is null.');
        }
        return $this->termFromDb;
    }

    public function isNull(){
        return is_null($this->termFromDb);
    }

    public function errors(){
        return $this->error;
    }

    public function getField($fieldName){
        if($this->isNull()){
            throw new \InvalidArgumentException('The term is null.');
        }
        return isset($this->termFromDb[$fieldName]) ? (is_numeric($this->termFromDb[$fieldName]) ? (int) $this->termFromDb[$fieldName] : $this->termFromDb[$fieldName]): null;
    }

}

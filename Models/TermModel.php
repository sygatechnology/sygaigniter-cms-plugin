<?php

namespace Plugin\cms\Models;

/**
 * @package    Plugin\cms\Models
 * @author     SygaTechnology Dev Team
 * @copyright  2019 SygaTechnology Foundation
 */

use \App\Core\SY_Model;

/**
 * Class TermModel
 *
 * @todo Terms Resource Model
 *
 * @package Plugin\cms\Models
 */

class TermModel extends SY_Model
{
    protected $table                = 'terms';
    protected $primaryKey           = 'term_id';
    protected $joinTable            = 'term_taxonomies';
    protected $joinKey              = 'term_id';

    protected $returnType           = '\Plugin\cms\Entities\Term';
    protected $useSoftDeletes       = false;

    protected $allowedFields        = [
        'term_id',
        'name',
        'slug',
        'taxonomy',
        'description',
        'parent'
    ];

    protected $skipValidation       = false;
    protected $validationRules      = [
        'name'           => 'required',
        'taxonomy'       => 'required'
    ];

    public function getResult($taxonomy = '*', $limit = 0, $numPage = 1, $order = '', $order_sens = '')
    {
        $this->join($this->joinTable, $this->joinTable.'.'.$this->joinKey.' = '.$this->table.'.'.$this->primaryKey);
        if (! empty($taxonomy) && $taxonomy !== '*') {
            if(\is_array($taxonomy)){
                $this->whereIn($$this->joinTable.'.taxonomy', $taxonomy);
            } else {
                $this->where($$this->joinTable.'.taxonomy', $taxonomy);
            }
        }
        if (! empty($order) && ! empty($order_sens)) $this->orderBy($order, $order_sens);
        $dbResult = ($limit > 0 && $numPage > 0) ? $this->findAll($limit, (((int)$numPage-1)*$limit)) : $this->findAll();
        $rows = [];
        foreach ($dbResult as $term) {
            $uResult = $term->getResult();
            $rows[] = $uResult;
            unset($uResult);
        }
        $apiResult = \Config\Services::ApiResult();
        return $apiResult->set($rows, $this->countAllCompiledResults(true), $limit, $numPage);
    }

    /**
     * Inserts data into the current table. If an object is provided,
     * it will attempt to convert it to an array.
     *
     * @param array|object $data
     * @param boolean      $returnID Whether insert ID should be returned or not.
     *
     * @return integer|string|boolean
     * @throws \ReflectionException
     */
    public function insert($data = NULL, bool $returnID = true){

        if (empty($data))
        {
          $data           = $this->tempData['data'] ?? null;
          $escape         = $this->tempData['escape'] ?? null;
          $this->tempData = [];
        }

        if (empty($data))
        {
          throw DataException::forEmptyDataset('insert');
        }

        if(! $data instanceof \Plugin\cms\Entities\Term ){
            throw new \InvalidArgumentException('data must be an instance of \Plugin\cms\Entities\Term.');
        }

        $termID = $this->insertTerm($data->term_args);
        if( $termID ){
            if($this->insertTermTaxonomy($data->term_args)){
                helper('functions');
                $termmeta_args = [];
                foreach ($data->termmeta_args as $key => $value) {
                    $termmeta_args[] = [
                        $this->primaryKey => $termID,
                        'meta_key' => $key,
                        'meta_value' => is_array($value) ? serialize_args($value) : $value
                    ];
                }

                if(! empty($termmeta_args) ){
                    if(! $this->updateTermMeta($termID, $termmeta_args)){
                        return false;
                    }
                }
                
                return $termID;
            } else {
                $this->delete(['term_id' => $termID]);
                return false;
            }
        }
        return false;
    }

    private function insertTerm($termData){

        $data = [
            'name' => $termData['name'],
            'slug' => $termData['slug']
        ];

        unset($termData);

        $escape = null;

        $this->insertID = 0;

        // If $data is using a custom class with public or protected
        // properties representing the table elements, we need to grab
        // them as an array.
        if (is_object($data) && ! $data instanceof stdClass)
        {
          $data = static::classToArray($data, $this->primaryKey, $this->dateFormat, false);
        }

        // If it's still a stdClass, go ahead and convert to
        // an array so doProtectFields and other model methods
        // don't have to do special checks.
        if (is_object($data))
        {
          $data = (array) $data;
        }

        // Validate data before saving.
        if ($this->skipValidation === false)
        {
          if ($this->validate($data) === false)
          {
            return false;
          }
        }

        // Must be called first so we don't
        // strip out created_at values.
        $data = $this->doProtectFields($data);

        // Set created_at and updated_at with same time
        $date = $this->setDate();

        if ($this->useTimestamps && ! empty($this->createdField) && ! array_key_exists($this->createdField, $data))
        {
          $data[$this->createdField] = $date;
        }

        if ($this->useTimestamps && ! empty($this->updatedField) && ! array_key_exists($this->updatedField, $data))
        {
          $data[$this->updatedField] = $date;
        }

        $data = $this->trigger('beforeInsert', ['data' => $data]);

        // Must use the set() method to ensure objects get converted to arrays
        $result = $this->builder()
            ->set($data['data'], '', $escape)
            ->insert();

        // If insertion succeeded then save the insert ID
        if ($result)
        {
            $this->insertID = $this->db->insertID();
        }

        // Trigger afterInsert events with the inserted data and new ID
        $this->trigger('afterInsert', ['id' => $this->insertID, 'data' => $data, 'result' => $result]);

        // If insertion failed, get out of here
        if (! $result)
        {
          return $result;
        }

        // return the insertID.
        return $this->insertID;
    }

    private function insertTermTaxonomy($data){
        unset($data['name']);
        unset($data['slug']);
        $data['term_id'] = $this->insertID;
        // If it's still a stdClass, go ahead and convert to
        // an array so doProtectFields and other model methods
        // don't have to do special checks.
        if (is_object($data))
        {
          $data = (array) $data;
        }

        // Validate data before saving.
        if ($this->skipValidation === false)
        {
          if ($this->validate($data) === false)
          {
            return false;
          }
        }

        $data = $this->trigger('beforeInsert', ['data' => $data]);

        $db      = \Config\Database::connect();
        $result = $db->table('term_taxonomies')
                ->insert($data['data']);

        // Trigger afterInsert events with the inserted data and number of rows
        $this->trigger('afterInsert', ['id' => $result, 'data' => $data, 'result' => $result]);

        // return the result.
        return $result;
    }

    /**
  	 * Updates a single record in $this->table. If an object is provided,
  	 * it will attempt to convert it into an array.
  	 *
  	 * @param integer|array|string $id
  	 * @param array|object         $data
  	 *
  	 * @return boolean
  	 * @throws \ReflectionException
  	 */
  	public function update($id = null, $data = null): bool
  	{
      if (is_numeric($id) || is_string($id))
  		{
  			$id = [$id];
  		}

  		if (empty($data))
  		{
  			$data           = $this->tempData['data'] ?? null;
  			$escape         = $this->tempData['escape'] ?? null;
  			$this->tempData = [];
  		}

  		if (empty($id) || empty($data))
  		{
  			throw DataException::forEmptyDataset('update');
  		}

      if(! $data instanceof \Plugin\cms\Entities\Term ){
          throw new \InvalidArgumentException('data must be an instance of \Plugin\cms\Entities\Term.');
      }

      $result = $this->updateTerm($id, $data->term_args);
      if( $result ){

        $this->updateID = $id;

        if($this->updateTermTaxonomy($data->term_args)){
            helper('functions');
            $termmeta_args = [];
            foreach ($data->termmeta_args as $key => $value) {
                $termmeta_args[] = [
                    $this->primaryKey => $id,
                    'meta_key' => $key,
                    'meta_value' => is_array($value) ? serialize_args($value) : $value
                ];
            }

            if(! empty($termmeta_args) ){
                if(! $this->updateTermMeta($termID, $termmeta_args)){
                    return false;
                }
            }
            
            return true;
        } else {
            $this->delete(['term_id' => $termID]);
            return false;
        }

          helper('functions');
          $termmeta_args = [];
          foreach ($data->termmeta_args as $key => $value) {
              $termmeta_args[] = [
                  $this->primaryKey => $id,
                  'meta_key' => $key,
                  'meta_value' => is_array($value) ? serialize_args($value) : $value
              ];
          }
          if(! empty($termmeta_args) ){
              $rows = $this->updateTermMeta($id, $termmeta_args);
              if(! $rows){
                  return false;
              }
          }

          return true;
      }
      return false;
  	}

    private function updateTerm($id, $termData){

        $data = [
            'name' => $termData['name'],
            'slug' => $termData['slug']
        ];

        $escape = null;

        // If $data is using a custom class with public or protected
        // properties representing the table elements, we need to grab
        // them as an array.
        if (is_object($data) && ! $data instanceof stdClass)
        {
          $data = static::classToArray($data, $this->primaryKey, $this->dateFormat);
        }

        // If it's still a stdClass, go ahead and convert to
        // an array so doProtectFields and other model methods
        // don't have to do special checks.
        if (is_object($data))
        {
          $data = (array) $data;
        }

        // Validate data before saving.
        if ($this->skipValidation === false)
        {
          if ($this->validate($data) === false)
          {
            return false;
          }
        }

        // Must be called first so we don't
        // strip out updated_at values.
        $data = $this->doProtectFields($data);

        $data = $this->trigger('beforeUpdate', ['id' => $id, 'data' => $data]);

        $builder = $this->builder();

        if ($id)
        {
          $builder = $builder->whereIn($this->table . '.' . $this->primaryKey, $id);
        }

        // Must use the set() method to ensure objects get converted to arrays
        $result = $builder
            ->set($data['data'], '', $escape)
            ->update();

        $this->trigger('afterUpdate', ['id' => $id, 'data' => $data, 'result' => $result]);

        return $result;
    }

    private function updateTermTaxonomy($data){
        unset($data['name']);
        unset($data['slug']);
        // If it's still a stdClass, go ahead and convert to
        // an array so doProtectFields and other model methods
        // don't have to do special checks.
        if (is_object($data))
        {
          $data = (array) $data;
        }

        // Validate data before saving.
        if ($this->skipValidation === false)
        {
          if ($this->validate($data) === false)
          {
            return false;
          }
        }

        $data = $this->trigger('beforeUpdate', ['data' => $data]);

        $db      = \Config\Database::connect();
        $dbBuilder = $db->table('term_taxonomies');
        $dbBuilder->where($this->primaryKey, $this->updateID);
        $result = $dbBuilder->update($data['data']);

        // Trigger afterUpdate events with the inserted data and number of rows
        $this->trigger('afterUpdate', ['row' => $result, 'data' => $data, 'result' => $result]);

        // return the result.
        return $result;
    }

    private function updateTermMeta($termID, $data){
        // If it's still a stdClass, go ahead and convert to
        // an array so doProtectFields and other model methods
        // don't have to do special checks.
        if (is_object($data))
        {
          $data = (array) $data;
        }

        $data = $this->trigger('beforeInsert', ['data' => $data]);

        $deleteResult = $this->deleteTermMeta($termID);

        if(! $deleteResult){
            return $deleteResult;
        }

        $db      = \Config\Database::connect();
        $result = $db->table('termmeta')
                ->insertBatch($data['data']);

        // Trigger afterInsert events with the inserted data and number of rows
        $this->trigger('afterInsert', ['row' => $result, 'data' => $data, 'result' => $result]);

        // return the result.
        return $result;
    }

    public function deleteTermRelationships($termTaxonomyId){
        $db      = \Config\Database::connect();
        return $db->table('term_relationships')
                    ->where('term_taxonomy_id', $termTaxonomyId)
                    ->delete();
    }

    public function deleteTermTaxonomy($termID){
        $db      = \Config\Database::connect();
        return $db->table('term_taxonomies')
                    ->where($this->primaryKey, $termID)
                    ->delete();
    }

    public function deleteTermMeta($termID){
        $db      = \Config\Database::connect();
        return $db->table('termmeta')
                    ->where($this->primaryKey, $termID)
                    ->delete();
    }
}

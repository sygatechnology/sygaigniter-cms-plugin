<?php namespace Plugin\cms\Args;

use \App\API\v1\Services\ApiRequestService;
use \Plugin\cms\Services\TaxonomiesService;
use \Plugin\cms\Services\TermsService;
use Plugin\cms\Entities\Term;

class TermArgs
{
    private $termArgs = [];
    private $hasError = false;
    private $errorsList = [];
    private $args = [];

    /**
    * Params Setter
    *
    * @param ApiRequestService      $request The api request service.
    */
    public function fill(ApiRequestService $request, $term_id = null){
        $this->termArgs = (array) $request->params();
        if(! is_null($term_id) && (int) $term_id > 0) {
            if(! empty($this->termArgs) ){
                $this->termArgs['term_id'] = (int) $term_id;
                $isNew = false;
            } else {
                $this->setErrors('Term data empty');
                return;
            }
        } else {
            unset($this->termArgs['term_id']);
            $isNew = true;
        }

        $this->validate($isNew);
    }

    private function validate($isNew = true){
        $this->args = [];
        /**
         * Check if request termArgs is valid
         */
        if ($isNew && (! isset($this->termArgs['name']) || ! isset($this->termArgs['taxonomy'])) ) {
            $this->setErrors('term taxonomy and term name params required');
            return;
        }

        if(isset($this->termArgs['taxonomy']) && ! TaxonomiesService::exists($this->termArgs['taxonomy'])){
            $this->setErrors('Term taxonomy ' . $this->termArgs['taxonomy'] . ' does not exists');
            return;
        }

        $this->parseArgs();
    }

    private function parseArgs(){
        helper('functions');
        $defaults = array(
            'name'          => '',
            'slug'          => '',
            'taxonomy'      => '',
            'description'   => '',
            'parent'        => 0,
            'count'         => 0
        );
        $termarr = parse_args( $this->termArgs, $defaults );
        unset($termarr['filter']);

        // Are we updating or creating?
        $term_ID = 0;
        $update  = false;

        if ( ! empty( $termarr['term_id'] ) ) {
            $update = true;

            // Get the term ID and GUID.
            $term_ID     = $termarr['term_id'];
            $term_before = new Term( $term_ID );
            if ( $term_before->isNull() ) {
                $this->setErrors("Term not found");
                return;
            }
            $termarr = parse_args( $this->termArgs, $term_before->theTerm() );
            $termarr['count'] = $term_before->getField( 'count' );
            if(isset($this->termArgs['taxonomy']) && $this->termArgs['taxonomy'] !== $termarr['taxonomy']){
                $termarr['taxonomy'] = $this->termArgs['taxonomy'];
            }
        } else {
            $termarr['slug'] = slugify( $termarr['name'] );
        }
        unset($termarr['term_id']);

        /*
        * Create a valid term slug.
        */
        $slug = $termarr['slug'];

        $termarr['slug'] = set_unique_term_slug($slug, $term_ID);

        $this->newTerm = ($update) ? false : true;
        $this->ID = $term_ID;

        $data = [ 'name', 'slug', 'taxonomy', 'description', 'parent', 'count' ];

        helper('array');
        $this->args = array_key_values($data, $termarr);
        unset($termarr);
        $this->setMetaArgs();
    }

    public function isNew(){
        if(! isset($this->newTerm)){
            throw new \InvalidArgumentException('TermArgs::setArgs() function must called before calling this function.');
        }
        return $this->newTerm;
    }

    public function getID(){
        if(! isset($this->ID)){
            throw new \InvalidArgumentException('TermArgs::setArgs() function must called before calling this function.');
        }
        return (int) $this->ID;
    }

    public function getArgs(){
        return $this->args;
    }

    /**
     * Sets errors
     *
     * @param string|array      $error The error description, accept an string or array of errors
     */
    private function setErrors($error){
        $this->hasError = true;
        $this->errorsList = (array) $error;
    }

    /**
     * Check if term is valid
     *
     * @return bool
     */
    public function isValidTerm(){
        return !$this->hasError;
    }

    /**
     * Check if term is valid
     *
     * @return array      The array of errors
     */
    public function errors(){
        return $this->errorsList;
    }

    public function getTaxonomyObject(){
        /**
         * Get term taxonomy object
         */
        return TaxonomiesService::getTaxonomyObject($this->termArgs['taxonomy']);
    }


    /**
    * Meta Params Setter
    */
    public function setMetaArgs(){
        $this->termMetaArgs = [];
        if( isset($this->termArgs['meta_input']) ){
            $this->termMetaArgs = (array) $this->termArgs['meta_input'];
        }
        $this->getMetaFiles();
    }

    public function getMetaArgs(){
        if(! isset($this->termMetaArgs)){
            throw new \InvalidArgumentException('TermArgs::setArgs() function must called before calling this function.');
        }
        return $this->termMetaArgs;
    }

    private function getMetaFiles(){
        $fileNames = [];
        helper('inflector');
        $request = \Config\Services::request();
        foreach ($request->getFiles() as $fileName => $file) {
            if(starts_with($fileName, 'metafile-')){
                $segment = explode($fileName, 'metafile-');
                $fileNames['_'.$segment[1]] = $file;
            }
        }
        $this->termMetaFileArgs = $fileNames;
    }

    public function getFiles(){
        if(! isset($this->termMetaFileArgs)){
            throw new \InvalidArgumentException('TermArgs::setArgs() function must called before calling this function.');
        }
        return $this->termMetaFileArgs;
    }

    public function getTermArgs(){
        if(! isset($this->termArgs)){
            throw new \InvalidArgumentException('TermArgs::setArgs() function must called before calling this function.');
        }
        return $this->termArgs;
    }
}

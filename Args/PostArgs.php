<?php namespace Plugin\cms\Args;

use \App\API\v1\Services\ApiRequestService;
use \Plugin\cms\Services\ObjectTypesService;
use \Plugin\cms\Services\TaxonomiesService;
use \Plugin\cms\Services\TermsService;
use Plugin\cms\Entities\Post;
use CodeIgniter\I18n\Time;

class PostArgs
{
    private $postArgs = [];
    private $hasError = false;
    private $errorsList = [];
    private $args = [];

    /**
    * Params Setter
    *
    * @param ApiRequestService      $request The api request service.
    */
    public function fill(ApiRequestService $request, $post_id = null){
        $this->postArgs = (array) $request->params();
        if(! is_null($post_id) && (int) $post_id > 0) {
            if(! empty($this->postArgs) ){
                $this->postArgs['post_id'] = (int) $post_id;
                $isNew = false;
            } else {
                $this->setErrors('Post data empty');
                return;
            }
        } else {
            unset($this->postArgs['post_id']);
            $isNew = true;
        }

        $this->validate($isNew);
    }

    private function validate($isNew = true){
        $this->args = [];
        /**
         * Check if request postArgs has a post type param
         */
        if ($isNew && ! isset($this->postArgs['post_type'])) {
            $this->setErrors('post_type param required');
            return;
        }

        if(isset($this->postArgs['post_type']) && ! ObjectTypesService::exists($this->postArgs['post_type'])){
            $this->setErrors('Post type ' . $this->postArgs['post_type'] . ' does not exists');
            return;
        }

        $this->parseArgs();
    }

    private function parseArgs(){
        helper('functions');
        $user_id = \Config\Services::currentUser()->getId();
        $defaults = array(
            'post_author'           => $user_id,
            'post_content'          => '',
            'post_excerpt'          => '',
            'post_content_filtered' => '',
            'post_title'            => '',
            'post_status'           => 'draft',
            'comment_status'        => '',
            'post_parent'           => 0
        );
        $postarr = parse_args( $this->postArgs, $defaults );
        unset($postarr['filter']);

        // Are we updating or creating?
        $post_ID = 0;
        $update  = false;
        $postarr['post_modified'] = (new Time('now'))->toDateTimeString();

        if ( ! empty( $postarr['post_id'] ) ) {
            $update = true;

            // Get the post ID and GUID.
            $post_ID     = $postarr['post_id'];
            $post_before = new Post( $post_ID );
            if ( $post_before->isNull() ) {
                $this->setErrors("Post not found");
                return;
            }
            $postarr = parse_args( $this->postArgs, $post_before->thePost() );
            if(isset($this->postArgs['post_status']) && $this->postArgs['post_status'] !== $postarr['post_status']){
                $postarr['post_status'] = $this->postArgs['post_status'];
            }
            unset($postarr['post_deleted']);
        } else {
            if(! isset($postarr['post_date'])){
                $postarr['post_date'] = $postarr['post_modified'];
            }
            $postarr['post_deleted'] = '0000-00-00 00:00:00';
        }
        unset($postarr['post_id']);

        // Validate the date.
        $month          = substr( $postarr['post_date'], 5, 2 );
        $day            = substr( $postarr['post_date'], 8, 2 );
        $year           = substr( $postarr['post_date'], 0, 4 );
        $valid_date = checkdate( $month, $day, $year );
        if ( ! $valid_date ) {
            $this->setErrors('Invalid date.');
            return;
        }

        $post_type   = $postarr['post_type'];
        $post_title   = $postarr['post_title'];
        $post_content = $postarr['post_content'];
        $post_excerpt = $postarr['post_excerpt'];
        if ( isset( $postarr['post_name'] ) ) {
            $post_name = $postarr['post_name'];
        }

        $post_status = empty( $postarr['post_status'] ) ? 'draft' : $postarr['post_status'];
        $gmdate = (new Time('now'))->toDateTimeString();
        if ( 'publish' === $post_status ) {
            // String comparison to work around far future dates (year 2038+) on 32-bit systems.
            if ($postarr['post_date'] > $gmdate ) {
                $post_status = 'future';
            }
        } elseif ( 'future' === $post_status ) {
            if ( $postarr['post_date'] <= $gmdate ) {
                $post_status = 'publish';
            }
        }
        $postarr['post_status'] = $post_status;

        /*
        * Don't allow contributors to set the post slug for pending review posts.
        *
        * For new posts check the primitive capability, for updates check the meta capability.
        */
        $post_type_object = ObjectTypesService::getPostTypeObject($postarr['post_type']);

        $user = \Config\Services::currentUser();

        if ( 'pending' === $post_status && ! $user->isAuthorized( $post_type_object->cap->publish_posts ) ) {
            $post_name = '';
        }

        /*
        * Create a valid post name. Drafts and pending posts are allowed to have
        * an empty post name.
        */
        if ( empty( $postarr['post_name'] ) ) {
            if ( ! in_array( $post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
                $post_name = slugify( $post_title );
            } else {
                $post_name = '';
            }
        } else {
            $post_name = $postarr['post_name'];
            // On updates, we need to check to see if it's using the old, fixed sanitization context.
            $check_name = slugify( $postarr['post_title'] );
            if ( $update && strtolower( urlencode( $post_name ) ) == $check_name && $post_before->getField( 'post_name' ) == $check_name ) {
                $post_name = $check_name;
            }
        }

        // Comment status.
        if ( empty( $postarr['comment_status'] ) ) {
            if ( $update ) {
                $postarr['comment_status'] = 'closed';
            } else {
                $postarr['comment_status'] = get_default_comment_status( $post_type, 'open' );
            }
        }

        if(! isset( $postarr['post_author'] ) ){
            $postarr['post_author'] = $user_id;
        }

        if (! isset( $postarr['post_parent'] ) ) {
      		  $postarr['post_parent'] = 0;
      	}

        $postarr['post_name'] = set_unique_post_slug($post_name, $postarr['post_status'], $post_ID);

        $this->newPost = ($update) ? false : true;
        $this->ID = $post_ID;

        $data = [ 'post_author', 'post_date', 'post_content', 'post_content_filtered', 'post_title', 'post_excerpt', 'post_status', 'post_type', 'comment_status', 'post_name', 'post_modified', 'post_parent' ];

        helper('array');
        $this->args = array_key_values($data, $postarr);
        unset($postarr);
        $this->setMetaArgs();
    }

    public function isNew(){
        if(! isset($this->newPost)){
            throw new \InvalidArgumentException('PostArgs::setArgs() function must called before calling this function.');
        }
        return $this->newPost;
    }

    public function getID(){
        if(! isset($this->ID)){
            throw new \InvalidArgumentException('PostArgs::setArgs() function must called before calling this function.');
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
     * Check if post is valid
     *
     * @return bool
     */
    public function isValidPost(){
        return !$this->hasError;
    }

    /**
     * Check if post is valid
     *
     * @return array      The array of errors
     */
    public function errors(){
        return $this->errorsList;
    }

    public function getPostTypeObject(){
        /**
         * Get object type object
         */
        return ObjectTypesService::getPostTypeObject($this->postArgs['post_type']);
    }


    /**
    * Meta Params Setter
    */
    public function setMetaArgs(){
        $this->postMetaArgs = [];
        if( isset($this->postArgs['meta_input']) ){
            $this->postMetaArgs = (array) $this->postArgs['meta_input'];
        }
        $this->getMetaFiles();
        $this->setTermArgs();
    }

    public function getMetaArgs(){
        if(! isset($this->postMetaArgs)){
            throw new \InvalidArgumentException('PostArgs::setArgs() function must called before calling this function.');
        }
        return $this->postMetaArgs;
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
        $this->postMetaFileArgs = $fileNames;
    }

    public function getFiles(){
        if(! isset($this->postMetaFileArgs)){
            throw new \InvalidArgumentException('PostArgs::setArgs() function must called before calling this function.');
        }
        return $this->postMetaFileArgs;
    }

    public function setTermArgs(){
        $this->termArgs = [];
        if( isset($this->postArgs['tax_input']) ){
            $this->termArgs = (array) $this->postArgs['tax_input'];

            $termIds = [];
            foreach ( (array) $this->postArgs['tax_input'] as $taxonomy => $terms ) {
                if ( ! TaxonomiesService::exists( $taxonomy ) ) {
                    $this->setErrors(sprintf('Invalid taxonomy: %s.', $taxonomy ));
                    return;
                }
                $termRows = TermsService::getWhere($taxonomy, (array)$terms);
                if( false === $termRows || count($termRows) !== count($terms) ){
                    $this->setErrors(['Invalid taxonomy ' . $taxonomy . ' terms: ', $terms]);
                    return;
                }
                foreach ($terms as $termId) {
                    if(! in_array($termId, $termIds)){
                        $termIds[] = (int) $termId;
                    }
                }
            }
            $this->termArgs = $termIds;
        }
    }

    public function getTermArgs(){
        if(! isset($this->termArgs)){
            throw new \InvalidArgumentException('PostArgs::setArgs() function must called before calling this function.');
        }
        return $this->termArgs;
    }
}

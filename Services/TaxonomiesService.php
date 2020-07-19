<?php namespace Plugin\cms\Services;

use CodeIgniter\Config\BaseService;
use \Plugin\cms\Entities\Taxonomy;

class TaxonomiesService extends BaseService
{
    static protected $taxonomies = [];

    public static function register($taxonomy, $objectType, $args)
    {
        helper('functions');
        $taxonomy = sanitize_key($taxonomy);
        if ( empty( $taxonomy ) || strlen( $taxonomy ) > 32 ) {
            throw new \InvalidArgumentException('Taxonomy names must be between 1 and 32 characters in length.');
            exit;
        }

        $args = parse_args( $args );

        $taxonomyObject = new Taxonomy( $taxonomy, $objectType, $args );

        self::$taxonomies[ $taxonomy ] = $taxonomyObject;
    }

    public static function get($taxonomy = null)
    {
        if(! is_null($taxonomy)){
            $taxonomy = trim($taxonomy);
            if(self::exists($taxonomy)){
                return self::$taxonomies[$taxonomy];
            }
            throw new \InvalidArgumentException('Taxonomy '. $taxonomy.' does not exists.');
            exit;
        }
        return self::$taxonomies;
    }

    public static function exists($taxonomy){
        return isset(self::$taxonomies[$taxonomy]);
    }

    public static function registerObjectType($taxonomy, $objectType){
        self::$taxonomies[$taxonomy]->object_type[] = $objectType;
    }

    /**
     * Return the names or objects of the taxonomies which are registered for the requested object or object type, such as
     * a post object or post type name.
     *
     * Example:
     *
     *     $taxonomies = get_object_taxonomies( 'post' );
     *
     * This results in:
     *
     *     Array( 'category', 'post_tag' )
     *
     * @param array|string $object Name of the type of taxonomy object, or an object (row from posts)
     * @param string               $output Optional. The type of output to return in the array. Accepts either
     *                                     taxonomy 'names' or 'objects'. Default 'names'.
     * @return array The names of all taxonomy of $object_type.
     */
    public static function getObjectTaxonomies( $object, $output = 'names' ) {
        $taxonomies = array();
        if(is_string($object)){
            $object = [$object];
        }
        foreach ( (array) self::$taxonomies as $tax_name => $tax_obj ) {
            if ( array_intersect( $object, (array) $tax_obj->object_type ) ) {
                if ( 'names' == $output ) {
                    $taxonomies[] = $tax_name;
                } else {
                    $taxonomies[ $tax_name ] = $tax_obj;
                }
            }
        }
        return $taxonomies;
    }

    /**
     * Retrieves a object taxonomy by name.
     *
     * @param string $taxonomy The name of a registered taxonomy.
     * @return Taxonomy|null Taxonomy object if it exists, null otherwise.
     */
    public static function getTaxonomyObject($taxonomy)
    {
        if (!is_scalar($taxonomy) || !self::exists($taxonomy)) {
            return null;
        }
        return self::get($taxonomy);
    }

    /**
     * Remove an already registered taxonomy from an object type.
     *
     * @param string $taxonomy    Name of taxonomy object.
     * @param string $object_type Name of the object type.
     * @return bool True if successful, false if not.
     */
    public static function unregisterTaxonomyForObjectType( $taxonomy, $object_type ) {
        if ( ! self::exists($taxonomy) ) {
            return false;
        }

        if ( ! \Plugin\cms\Services\ObjectTypesService::get( $object_type ) ) {
            return false;
        }

        $key = array_search( $object_type, self::$taxonomies[ $taxonomy ]->object_type, true );
        if ( false === $key ) {
            return false;
        }

        unset( self::$taxonomies[ $taxonomy ]->object_type[ $key ] );

        return true;
    }
}

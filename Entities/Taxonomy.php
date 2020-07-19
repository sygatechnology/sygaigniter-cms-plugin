<?php namespace Plugin\cms\Entities;

/**
* @package    App\Entities
* @author     SygaTechnology Dev Team
* @copyright  2019 SygaTechnology Foundation
*/

/**
 * Class Taxonomy
 *
 * @todo Taxonomies Resource Entity
 *
 * @package Plugin\cms\Entities
 */

final class Taxonomy
{
    /**
     * Taxonomy key.
     *
     * @var string
     */
    public $name;

    /**
     * Name of the taxonomy shown in the menu. Usually plural.
     *
     * @var string
     */
    public $label;

    /**
     * An array of labels for this taxonomy.
     *
     * @var object
     */
    public $labels = array();

    /**
     * A short descriptive summary of what the taxonomy is for.
     *
     * @var string
     */
    public $description = '';

    /**
     * Whether a taxonomy is intended for use publicly either via the admin interface or by front-end users.
     *
     * @var bool
     */
    public $public = true;

    /**
     * Whether the taxonomy is hierarchical.
     *
     * @var bool
     */
    public $hierarchical = false;

    /**
     * An array of object types this taxonomy is registered for.
     *
     * @var array
     */
    public $object_type = null;

    /**
     * Capabilities for this taxonomy.
     *
     * @var object
     */
    public $cap;

    /**
     * Constructor.
     *
     *
     * @param string       $taxonomy    Taxonomy key, must not exceed 32 characters.
     * @param array|string $object_type Name of the object type for the taxonomy object.
     * @param array|string $args        Optional. Array or query string of arguments for registering a taxonomy.
     *                                  Default empty array.
     */
    public function __construct( $taxonomy, $objectType, $args = array() ) {
        $this->name = $taxonomy;

        $this->setProps( $objectType, $args );
    }

    /**
     * Sets taxonomy properties.
     *
     *
     * @param array|string $object_type Name of the object type for the taxonomy object.
     * @param array|string $args        Array or query string of arguments for registering a taxonomy.
     * @return void
     */
    public function setProps( $objectType, $args ) {
        helper('functions');
        $args = parse_args( $args );

        $defaults = array(
            'labels'                => array(),
            'description'           => '',
            'public'                => true,
            'hierarchical'          => false,
            'capabilities'          => array()
        );

        $args = array_merge( $defaults, $args );

        $defaultCaps = array(
            'manage_terms',
            'edit_terms',
            'delete_terms',
            'assign_terms'
        );

        $args['cap'] = (object) array_merge( $defaultCaps, $args['capabilities'] );
        unset( $args['capabilities'] );

        $args['object_type'] = array_unique( (array) $objectType );

        $args['name'] = $this->name;

        foreach ( $args as $propertyName => $propertyValue ) {
            $this->$propertyName = $propertyValue;
        }

        $this->labels = $this->getLabels();
        $this->label  = $this->labels->name;
    }

    /**
     * Sets taxonomy labels.
     *
     * @return object
     */
    public function getLabels() {
        $this->labels = (array) $this->labels;

        $nohier_vs_hier_defaults = array(
            'name' => array( 'Etiquettes', 'Categories' )
        );

        if ( isset( $this->label ) && empty( $this->labels['name'] ) ) {
            $this->labels['name'] = $this->label;
        }

        $defaults = array();
        foreach ( $nohier_vs_hier_defaults as $key => $value ) {
            $defaults[ $key ] = $this->hierarchical ? $value[1] : $value[0];
        }
        $labels = array_merge( $defaults, $this->labels );

        $taxonomy = $this->name;

        return (object) $labels;
    }

    public function getName(){
        return $this->name;
    }

    public function getLabel(){
        return $this->label;
    }

    public function getDescription(){
        return $this->description;
    }

    public function isPublic(){
        return $this->public;
    }

    public function isHierarchical(){
        return $this->hierarchical;
    }

    public function getCapabilities(){
        return $this->cap;
    }
}

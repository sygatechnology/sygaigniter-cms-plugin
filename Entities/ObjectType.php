<?php namespace Plugin\cms\Entities;

/**
* @package    App\Entities
* @subpackage ObjectType
* @author     SygaTechnology Dev Team
* @copyright  2019 SygaTechnology Foundation
*/

final class ObjectType {
	/**
	 * Object type key.
	 *
	 
	 * @var string $name
	 */
	public $name;

	/**
	 * Name of the object type shown in the menu. Usually plural.
	 *
	 
	 * @var string $label
	 */
	public $label;

	/**
	 * Labels object for this object type.
	 *
	 * If not set, post labels are inherited for non-hierarchical types
	 * and page labels for hierarchical ones.
	 *
	 *
	 
	 * @var object $labels
	 */
	public $labels;

	/**
	 * A short descriptive summary of what the object type is.
	 *
	 * Default empty.
	 *
	 
	 * @var string $description
	 */
	public $description = '';

	/**
	 * Whether a object type is intended for use publicly either via the admin interface or by front-end users.
	 *
	 * While the default settings of $exclude_from_search, $publicly_queryable, $show_ui, and $show_in_nav_menus
	 * are inherited from public, each does not rely on this relationship and controls a very specific intention.
	 *
	 * Default false.
	 *
	 
	 * @var bool $public
	 */
	public $public = false;

	/**
	 * Whether the object type is hierarchical (e.g. page).
	 *
	 * Default false.
	 *
	 
	 * @var bool $hierarchical
	 */
	public $hierarchical = false;

	/**
	 * Whether to exclude posts with this object type from front end search
	 * results.
	 *
	 * Default is the opposite value of $public.
	 *
	 
	 * @var bool $exclude_from_search
	 */
	public $exclude_from_search = null;

	/**
	 * Whether queries can be performed on the front end for the object type as part of `parse_request()`.
	 *
	 * Endpoints would include:
	 * - `?object_type={object_type_key}`
	 * - `?{object_type_key}={single_post_slug}`
	 * - `?{object_type_query_var}={single_post_slug}`
	 *
	 * Default is the value of $public.
	 *
	 
	 * @var bool $publicly_queryable
	 */
	public $publicly_queryable = null;

	/**
	 * The string to use to build the read, edit, and delete capabilities.
	 *
	 * May be passed as an array to allow for alternative plurals when using
	 * this argument as a base to construct the capabilities, e.g.
	 * array( 'story', 'stories' ). Default 'post'.
	 *
	 
	 * @var string $capability_type
	 */
	public $capability_type = 'post';

	/**
	 * Whether to use the internal default meta capability handling.
	 *
	 * Default false.
	 *
	 
	 * @var bool $map_meta_cap
	 */
	public $map_meta_cap = false;

	/**
	 * An array of taxonomy identifiers that will be registered for the object type.
	 *
	 * Taxonomies can be registered later with `register_taxonomy()` or `register_taxonomy_for_object_type()`.
	 *
	 * Default empty array.
	 *
	 
	 * @var array $taxonomies
	 */
    public $taxonomies = array();
    
	/**
	 * Whether to delete posts of this type when deleting a user.
	 *
	 * If true, posts of this type belonging to the user will be moved to trash when then user is deleted.
	 * If false, posts of this type belonging to the user will *not* be trashed or deleted.
	 * If not set (the default), posts are trashed if object_type_supports( 'author' ).
	 * Otherwise posts are not trashed or deleted. Default null.
	 *
	 
	 * @var bool $delete_with_user
	 */
	public $delete_with_user = null;

	/**
	 * Object type capabilities.
	 *
	 
	 * @var object $cap
	 */
	public $cap;

	/**
	 * The features supported by the object type.
	 *
	 
	 * @var array $supports
	 */
    public $supports;
    

    /**
	 * The features supported fields by the object type.
	 *
	 
	 * @var array $support_fields
	 */
    public $support_fields;

	/**
	 * Constructor.
	 *
	 * Will populate object properties from the provided arguments and assign other
	 * default properties based on that information.
	 *
	 
	 *
	 * @see register_object_type()
	 *
	 * @param string       $objectType Object type key.
	 * @param array|string $args      Optional. Array or string of arguments for registering a object type.
	 *                                Default empty array.
	 */
	public function __construct( $objectType, $args = array() ) {
		$this->name = $objectType;

		$this->setProps( $args );
	}

	/**
	 * Sets object type properties.
	 *
	 *
	 * @param array|string $args Array or string of arguments for registering a object type.
	 */
	public function setProps( $args ) {
        helper('functions');
		$args = parse_args( $args );

		// Args prefixed with an underscore are reserved for internal use.
		$defaults = array(
			'labels'                => array(),
			'description'           => '',
			'public'                => false,
			'hierarchical'          => false,
			'exclude_from_search'   => null,
			'capability_type'       => 'post',
			'capabilities'          => array(),
			'map_meta_cap'          => null,
			'supports'              => array(),
			'taxonomies'            => array(),
			'delete_with_user'      => null
		);

		$args = array_merge( $defaults, $args );

		$args['name'] = $this->name;

		if ( empty( $args['capabilities'] ) && null === $args['map_meta_cap'] && in_array( $args['capability_type'], array( 'post', 'page' ) ) ) {
			$args['map_meta_cap'] = true;
		}

		// If not set, default to false.
		if ( null === $args['map_meta_cap'] ) {
			$args['map_meta_cap'] = false;
		}

		$this->cap = $this->getObjectTypeCapabilities( (object) $args );
		unset( $args['capabilities'] );

		if ( is_array( $args['capability_type'] ) ) {
			$args['capability_type'] = $args['capability_type'][0];
		}

		foreach ( $args as $property_name => $property_value ) {
			$this->$property_name = $property_value;
		}

		$this->labels = $this->getObjectTypeLabels();
		$this->label  = $this->labels->name;
    }

    /**
     * Build an object with all object type capabilities out of a object type object
     *
     * Object type capabilities use the 'capability_type' argument as a base, if the
     * capability is not set in the 'capabilities' argument array or if the
     * 'capabilities' argument is not supplied.
     *
     * The capability_type argument can optionally be registered as an array, with
     * the first value being singular and the second plural, e.g. array('story, 'stories')
     * Otherwise, an 's' will be added to the value for the plural form. After
     * registration, capability_type will always be a string of the singular value.
     *
     * By default, seven keys are accepted as part of the capabilities array:
     *
     * - edit_post, read_post, and delete_post are meta capabilities, which are then
     *   generally mapped to corresponding primitive capabilities depending on the
     *   context, which would be the post being edited/read/deleted and the user or
     *   role being checked. Thus these capabilities would generally not be granted
     *   directly to users or roles.
     *
     * - edit_posts - Controls whether objects of this object type can be edited.
     * - edit_others_posts - Controls whether objects of this type owned by other users
     *   can be edited. If the object type does not support an author, then this will
     *   behave like edit_posts.
     * - publish_posts - Controls publishing objects of this object type.
     * - read_private_posts - Controls whether private objects can be read.
     *
     * These four primitive capabilities are checked in core in various locations.
     * There are also seven other primitive capabilities which are not referenced
     * directly in core, except in map_meta_cap(), which takes the three aforementioned
     * meta capabilities and translates them into one or more primitive capabilities
     * that must then be checked against the user or role, depending on the context.
     *
     * - read - Controls whether objects of this object type can be read.
     * - delete_posts - Controls whether objects of this object type can be deleted.
     * - delete_private_posts - Controls whether private objects can be deleted.
     * - delete_published_posts - Controls whether published objects can be deleted.
     * - delete_others_posts - Controls whether objects owned by other users can be
     *   can be deleted. If the object type does not support an author, then this will
     *   behave like delete_posts.
     * - edit_private_posts - Controls whether private objects can be edited.
     * - edit_published_posts - Controls whether published objects can be edited.
     *
     * These additional capabilities are only used in map_meta_cap(). Thus, they are
     * only assigned by default if the object type is registered with the 'map_meta_cap'
     * argument set to true (default is false).
     *
     * @param object $args Object type registration arguments.
     * @return object Object with all the capabilities as member variables.
     */
    function getObjectTypeCapabilities( $args ) {
        if ( ! is_array( $args->capability_type ) ) {
            $args->capability_type = array( $args->capability_type, $args->capability_type . 's' );
        }

        // Singular base for meta capabilities, plural base for primitive capabilities.
        list( $singular_base, $plural_base ) = $args->capability_type;

        $default_capabilities = array(
            // Meta capabilities
            'edit_' . $singular_base,
            'read_' . $singular_base,
            'delete_' . $singular_base,
            // Primitive capabilities used outside of map_meta_cap():
            'edit_' . $plural_base,
            'edit_others_' . $plural_base,
            'publish_' . $plural_base,
            'read_private_' . $plural_base
        );

        // Primitive capabilities used within map_meta_cap():
        if ( $args->map_meta_cap ) {
            $default_capabilities_for_mapping = array(
                'read',
                'delete_' . $plural_base,
                'delete_private_' . $plural_base,
                'delete_published_' . $plural_base,
                'delete_others_' . $plural_base,
                'edit_private_' . $plural_base,
                'edit_published_' . $plural_base
            );
            $default_capabilities = array_merge( $default_capabilities, $default_capabilities_for_mapping );
        }

        $capabilities = array_merge( $default_capabilities, $args->capabilities );

        // Post creation capability simply maps to edit_posts by default:
        if ( ! in_array( 'create_posts', $capabilities ) ) {
            $capabilities[] = 'edit_posts';
        }

        // Remember meta capabilities for future reference.
        if ( $args->map_meta_cap ) {
            $this->_objectTypeMetaCapabilities( $capabilities );
        }

        return (object) $capabilities;
    }

    public function getCapabilities(){
        return $this->cap;
    }

    /**
     * Store or return a list of object type meta caps for map_meta_cap().
     *
     * @access private
     *
     * @param array $capabilities Object type meta capabilities.
     */
    private function _objectTypeMetaCapabilities( $capabilities = null ) {
        foreach ( $capabilities as $capability ) {
            if ( in_array( $capability, array( 'read_post', 'delete_post', 'edit_post' ) ) ) {
                \Plugin\cms\Services\ObjectTypesService::registerMetaCap($capability);
            }
        }
    }
    
    /**
     * Builds an object with all object type labels out of a object type object.
     *
     * Accepted keys of the label array in the object type object:
     *
     * - `name` - General name for the object type, usually plural. The same and overridden
     *          by `$object_type_object->label`. Default is 'Posts' / 'Pages'.
     *
     * Above, the first default value is for non-hierarchical object types (like posts)
     * and the second one is for hierarchical object types (like pages).
     *
     * @access private
     *
     * @param object|ObjectType $object_type_object Object type object.
     * @return object Object with all the labels as member variables.
     */
    function getObjectTypeLabels() {
        $nohier_vs_hier_defaults = array(
            'name' => array( 'Posts', 'Pages' )
        );
        $labels = $this->_getCustomObjectLabels( $nohier_vs_hier_defaults );

        $object_type = $this->name;

        $default_labels = clone $labels;

        // Ensure that the filtered labels contain all required default values.
        $labels = (object) array_merge( (array) $default_labels, (array) $labels );

        return $labels;
    }

    /**
     * Build an object with custom-something object (object type, taxonomy) labels
     * out of a custom-something object
     *
     * @access private
     *
     * @param object $object                  A custom-something object.
     * @param array  $nohier_vs_hier_defaults Hierarchical vs non-hierarchical default labels.
     * @return object Object containing labels for the given custom-something object.
     */
    private function _getCustomObjectLabels( $nohier_vs_hier_defaults ) {
        $this->labels = (array) $this->labels;

        if ( isset( $this->label ) && empty( $this->labels['name'] ) ) {
            $this->labels['name'] = $this->label;
        }

        $defaults = array();
        foreach ( $nohier_vs_hier_defaults as $key => $value ) {
            $defaults[ $key ] = $this->hierarchical ? $value[1] : $value[0];
        }
        $labels         = array_merge( $defaults, $this->labels );
        $this->labels = (object) $this->labels;

        return (object) $labels;
    }

	/**
	 * Sets the features support for the object type.
	 *
	 */
	public function addSupports() {
		if ( ! empty( $this->supports ) ) {
			$this->addObjectTypeSupport( $this->name, $this->supports );
			unset( $this->supports );
		} elseif ( false !== $this->supports ) {
			// Add default features.
			$this->addObjectTypeSupport( $this->name, array( 'title', 'editor' ) );
		}
    }

    /**
	 * Removes the features support for the object type.
	 *
	 */
	public function removeSupports() {
        \Plugin\cms\Services\ObjectTypesService::removeObjectTypeFeature($this->name);
	}
    
    /**
     * Register support of certain features for a object type.
     *
     * All core features are directly associated with a functional area of the edit
     * screen, such as the editor or a meta box. Features include: 'title', 'editor',
     * 'comments', 'revisions', 'trackbacks', 'author', 'excerpt', 'page-attributes',
     * 'thumbnail', 'custom-fields', and 'post-formats'.
     *
     * Additionally, the 'revisions' feature dictates whether the object type will
     * store revisions, and the 'comments' feature dictates whether the comments
     * count will show on the edit screen.
     *
     *
     * @param string       $object_type The object type for which to add the feature.
     * @param string|array $feature   The feature being added, accepts an array of
     *                                feature strings or a single string.
     */
    private function addObjectTypeSupport( $object_type, $feature ) {
        $features = (array) $feature;
        helper('array');

        if(is_associative($features)){
            foreach ( $features as $feature => $required ) {
                \Plugin\cms\Services\ObjectTypesService::setObjectTypeFeature($object_type, $feature, (bool) $required);
            }
        } else {
            foreach ( $features as $feature ) {
                if ( func_num_args() == 2 ) {
                    \Plugin\cms\Services\ObjectTypesService::setObjectTypeFeature($object_type, $feature, true);
                } else {
                    \Plugin\cms\Services\ObjectTypesService::setObjectTypeFeature($object_type, $feature, (bool) array_slice( func_get_args(), 2 ));
                }
            }
        }
    }
    
    public function supports($feature){
        $supports = self::getObjectTypeFeatures($this->name);
        return isset($supports[$feature]) && $supports[$feature] === true;
    }

    public function getFeatures(){
        return \Plugin\cms\Services\ObjectTypesService::getObjectTypeFeatures($this->name);
    }

	/**
	 * Registers the taxonomies for the object type.
	 *
	 */
	public function registerTaxonomies() {
		foreach ( $this->taxonomies as $taxonomy ) {
			$this->registerTaxonomyForObjectType( $taxonomy, $this->name );
		}
    }
    
    /**
     * Add an already registered taxonomy to an object type.
     *
     * @param string $taxonomy    Name of taxonomy object.
     * @param string $object_type Name of the object type.
     * @return bool True if successful, false if not.
     */
    private function registerTaxonomyForObjectType( $taxonomy, $object_type ) {

        if ( ! \Plugin\cms\Services\TaxonomiesService::exists($taxonomy) ) {
            return false;
        }

        if ( ! \Plugin\cms\Services\ObjectTypesService::getPostTypeObject( $object_type ) ) {
            return false;
        }

        if ( ! in_array( $object_type, \Plugin\cms\Services\TaxonomiesService::get( $taxonomy )->object_type ) ) {
            \Plugin\cms\Services\TaxonomiesService::registerObjectType( $taxonomy, $object_type );
        }

        return true;
    }

	/**
	 * Removes the object type from all taxonomies.
	 *
	 */
	public function unregisterTaxonomies() {
		foreach ( \Plugin\cms\Services\TaxonomiesService::getObjectTaxonomies( $this->name ) as $taxonomy ) {
			\Plugin\cms\Services\ObjectTypesService::unregisterTaxonomyForObjectType( $taxonomy, $this->name );
		}
    }
    
    /**
	 * Get the taxonomies for the object type.
	 *
	 */
	public function getTaxonomies() {
		return \Plugin\cms\Services\TaxonomiesService::getObjectTaxonomies($this->name);
    }

    /**
	 * Sets the features support fields for the object type.
	 *
	 */
	public function addSupportFields() {
		if ( ! empty( $this->support_fields ) ) {
			$this->addObjectTypeSupportField( $this->name, $this->supports );
			unset( $this->supports );
		} elseif ( false !== $this->supports ) {
			// Add default features.
			$this->addObjectTypeSupportField( $this->name, array( $this->name.'_title', $this->name.'_content' ) );
		}
    }
}

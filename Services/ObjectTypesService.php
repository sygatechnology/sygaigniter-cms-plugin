<?php namespace Plugin\cms\Services;

use CodeIgniter\Config\BaseService;
use \Plugin\cms\Entities\ObjectType;

class ObjectTypesService extends BaseService
{
    protected static $objectTypes = [];
    protected static $objectTypesMetaCaps = [];
    protected static $objectTypeFeatures = [];

    public static function register($objectType, $args)
    {
        helper('functions');
        $objectType = sanitize_key($objectType);

        if (empty($objectType) || strlen($objectType) > 20) {
            throw new \InvalidArgumentException('Object type names must be between 1 and 20 characters in length.');
            exit;
        }

        $taxonomyObject = new ObjectType($objectType, $args);
        $taxonomyObject->addSupports();
        self::$objectTypes[$objectType] = $taxonomyObject;
        $taxonomyObject->supports = self::getObjectTypeFeatures($objectType);
        $taxonomyObject->registerTaxonomies();
    }

    public static function get($objectType = null)
    {
        if (!is_null($objectType)) {
            $objectType = trim($objectType);
            if (self::exists($objectType)) {
                return self::$objectTypes[$objectType];
            }
            throw new \InvalidArgumentException('Object type ' . $objectType . ' does not exists.');
            exit;
        }
        return self::$objectTypes;
    }

    public static function exists($objectType)
    {
        return isset(self::$objectTypes[$objectType]);
    }

    public static function registerMetaCap($capability)
    {
        self::$objectTypesMetaCaps[] = $capability;
    }

    public static function getMetaCaps()
    {
        return self::$objectTypesMetaCaps;
    }

    public static function setObjectTypeFeature($objectType, $feature, $enabled)
    {
        self::$objectTypeFeatures[$objectType][$feature] = $enabled;
    }

    public static function getObjectTypeFeatures($objectType)
    {
        if (!self::exists($objectType)) {
            return null;
        }
        return self::$objectTypeFeatures[$objectType];
    }

    /**
     * Retrieves a object type object by name.
     *
     * @param string $object_type The name of a registered object type.
     * @return ObjectType|null ObjectType object if it exists, null otherwise.
     */
    public static function getPostTypeObject($object_type)
    {
        if (!is_scalar($object_type) || !self::exists($object_type)) {
            return null;
        }
        return self::get($object_type);
    }

    public static function removeObjectTypeFeature($objectType)
    {
        unset(self::$objectTypeFeatures[$objectType]);
    }

}

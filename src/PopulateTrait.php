<?php
namespace Dkd\Populate;

/**
 * This file belongs to the Dkd/Populate package
 *
 * Copyright (c) 2015, dkd Internet Service GmbH
 *
 * Released under the MIT license, of which the full text
 * was distributed with this package in file LICENSE.txt
 */

/**
 * Populate Trait
 *
 * Implement in classes to expose two new methods:
 *
 * - populate(PopulateTrait $source, array $optionalPropertyNameMap, boolean $onlyMappedProperties)
 * - export(array $optionalPropertyNameMap, boolean $onlyMappedProperties)
 *
 * Which either set or get all properties as determined
 * by get_class_properties OR by $optionalPropertyNameMap
 * if the $onlyMappedProperties parameter is TRUE,
 * and setting/getting properties using the appropriate
 * setter/getter methods.
 *
 * Both methods throw a Dkd\Populate\Exception
 * if any property passed in the property name map either
 * does not exist or has no getter/setter.
 */
trait PopulateTrait
{
    /**
     * Static list of method name prefixes for each type
     * of access - get/set. Suffixed by ucfirst($property),
     * i.e. `setIsAvailable`, `getAvailable` etc.
     *
     * @var array
     */
    private $populatableAccessorMethodNames = array(
        'get' => array('get', 'is', 'getIs'),
        'set' => array('set', 'setIs')
    );

    /**
     * Populates this instance, using standard references
     * (as opposed to cloning) when objects are encountered.
     *
     * @param  PopulateInterface|array $source
     * @param  array                   $propertyNameMap
     * @param  boolean                 $onlyMappedProperties
     * @throws Exception               Will pass through any Exception during populating
     */
    public function populate($source, array $propertyNameMap = array(), $onlyMappedProperties = false)
    {
        $this->populateInternal($source, $propertyNameMap, $onlyMappedProperties, false);
    }

    /**
     * Populates this instance, cloning any object values
     * that may be encountered.
     *
     * @param  PopulateInterface|array $source
     * @param  array                   $propertyNameMap
     * @param  boolean                 $onlyMappedProperties
     * @throws Exception               Will pass through any Exception during populating
     */
    public function populateWithClones($source, array $propertyNameMap = array(), $onlyMappedProperties = false)
    {
        $this->populateInternal($source, $propertyNameMap, $onlyMappedProperties, true);
    }

    /**
     * Populate this instance using data from $source,
     * optionally mapping properties from source to
     * this object using $propertyNameMap and if using
     * the property map, only populating those properties
     * whose names were passed in the property map.
     *
     * To selectively populate properties without mapping
     * their names, use a mirror array as property name
     * map and TRUE as third parameter, e.g.
     *
     *     $object->populate($source, array('test' => 'test'), TRUE);
     *
     * This causes $object to be populated using $only the
     * "test" property from $source.
     *
     * @param  PopulateInterface|array $source
     * @param  array                   $propertyNameMap
     * @param  boolean                 $onlyMappedProperties
     * @param  boolean                 $cloneObjects
     * @throws Exception
     */
    private function populateInternal($source, array $propertyNameMap, $onlyMappedProperties, $cloneObjects)
    {
        // decide where values come from or throw Exception if not retrievable
        if ($source instanceof PopulateInterface) {
            $source = $source->exportGettableProperties($propertyNameMap, $onlyMappedProperties);
            // ignore setter presence failures when values come from another object
        } elseif (!$onlyMappedProperties && $source instanceof \ArrayAccess && !$source instanceof \Iterator) {
            // fail if passing ArrayAccess without Iterator without explicit property list:
            // no way to perform the necessary iteration over $source later in this method.
            throw new Exception(
                'ArrayAccess without Iterator only supported with explicit property mapping',
                1422045181
            );
        } elseif (!$source instanceof \ArrayAccess && !is_array($source)) {
            throw new Exception(
                'Invalid source type: ' . gettype($source),
                1422045180
            );
        }

        $propertyNameMap = $this->convertPropertyMap($propertyNameMap);

        // loop values, skipping mapped properties, use Trait's internal setter to set value
        if (!$onlyMappedProperties) {
            foreach ($source as $propertyName => $propertyValue) {
                if (isset($propertyNameMap[$propertyName]) || $propertyValue === null) {
                    continue;
                }
                $this->setPopulatedProperty($propertyName, $propertyValue, $cloneObjects);
            }
        }

        // loop the mapped properties last to ensure mapped names override default names
        foreach ($propertyNameMap as $sourcePropertyName => $destinationPropertyName) {
            if (!isset($source[$sourcePropertyName]) || $source[$sourcePropertyName] === null) {
                // do not populate properties which were not passed in source values
                continue;
            }
            $propertyValue = $source[$sourcePropertyName];
            $this->setPopulatedProperty($destinationPropertyName, $propertyValue, $cloneObjects);
        }
    }

    /**
     * Exports properties from this object to a plain
     * array, optionally mapping property names to
     * array indices using a property name map - and
     * optionally only exporting those properties whose
     * names are included in the property name map.
     *
     * To selectively populate properties without mapping
     * their names, use a mirror array as property name
     * map and TRUE as third parameter, e.g.
     *
     *     $object->export(array('test' => 'test'), TRUE);
     *
     * This causes $object to be populated using $only the
     * "test" property from $source.
     *
     * @param  array   $propertyNameMap
     * @param  boolean $onlyMappedProperties
     * @throws Exception
     */
    public function exportGettableProperties(array $propertyNameMap = array(), $onlyMappedProperties = false)
    {
        $export = array();
        $propertyNameMap = $this->convertPropertyMap($propertyNameMap);

        // loop values, skipping mapped properties, use Trait's internal setter to set value
        if (!$onlyMappedProperties) {
            $sourcePropertyNames = array_keys(get_class_vars(__CLASS__));
            foreach ($sourcePropertyNames as $propertyName) {
                if (isset($propertyNameMap[$propertyName]) || $propertyName === 'populatableAccessorMethodNames') {
                    continue;
                }
                try {
                    $export[$propertyName] = $this->getPopulatedProperty($propertyName);
                } catch (Exception $error) {
                    continue;
                }
            }
        }

        // loop the mapped properties last to ensure mapping overrides defaults
        foreach ($propertyNameMap as $sourcePropertyName => $targetPropertyName) {
            $export[$targetPropertyName] = $this->getPopulatedProperty($sourcePropertyName);
        }

        return $export;
    }

    /**
     * Determines the function name used for property access
     * through a getter/setter. For a property named `employed`,
     * the following methods are checked:
     *
     * - setEmployed()
     * - getEmployed()
     *
     * If those aren't found and in order to accommodate booleans:
     *
     * - setIsEmployed()
     * - getIsEmployed()
     * - isEmployed()
     *
     * @param  string $propertyName The name of the property on this object
     * @param  string $method       Either `get` or `set`
     * @return string|FALSE Method name ready for property value getting or
     *         setting as determined by $method, FALSE if no function was found.
     */
    private function determinePropertyAccessFunctionName($propertyName, $method)
    {
        $methodSuffix = ucfirst($propertyName);
        foreach ($this->populatableAccessorMethodNames[$method] as $methodPrefix) {
            if (method_exists($this, $methodPrefix . $methodSuffix)) {
                return $methodPrefix . $methodSuffix;
            }
        }
        return false;
    }

    /**
     * Sets a single property via the resolved setter method,
     * throws a Dkd\Populate\Exception if no method could be found
     * and $ignoreFailures was false.
     *
     * @param  string  $propertyName
     * @param  mixed   $value
     * @param  boolean $cloneObjects
     * @throws AccessException
     */
    private function setPopulatedProperty($propertyName, $value, $cloneObjects)
    {
        if ($cloneObjects && is_object($value)) {
            $value = clone $value;
        }
        $method = $this->determinePropertyAccessFunctionName($propertyName, 'set');
        if (!$method) {
            throw new AccessException('No setter method can be determined for property ' . $propertyName, 1422021211);
        }
        $this->$method($value);
    }

    /**
     * Gets a single property via the resolved getter method,
     * throws a Dkd\Populate\Exception if no method could be found.
     *
     * @param  string $propertyName
     * @return array|NULL
     * @throws AccessException
     */
    private function getPopulatedProperty($propertyName)
    {
        $method = $this->determinePropertyAccessFunctionName($propertyName, 'get');
        if (!$method) {
            throw new AccessException('No getter method can be determined for property ' . $propertyName, 1422021212);
        }
        return $this->$method();
    }

    /**
     * Converts the input array if necessary: if the input array is
     * numerically indexed starting with zero, the array was created
     * without keys, e.g. array('property1', 'property2') and the
     * array must be re-created as a properly indexed array which
     * uses property names as both keys and values in the array, e.g.
     * array('property1' => 'property1', 'property2' => 'property2').
     *
     * The output array can then be consumed by populate/export methods.
     *
     * @param array $propertyNameMap
     * @return array
     */
    private function convertPropertyMap(array $propertyNameMap)
    {
        if (isset($propertyNameMap[0])) {
            $propertyNameMap = array_combine($propertyNameMap, $propertyNameMap);
        }
        return $propertyNameMap;
    }
}
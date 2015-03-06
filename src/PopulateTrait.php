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
 * Implement in classes to expose three new methods:
 *
 * - populate(PopulateTrait $source, array $optionalPropertyNameMap, boolean $onlyMappedProperties)
 * - populateWithClones(PopulateTrait $source, array $optionalPropertyNameMap, boolean $onlyMappedProperties)
 * - exportGettableProperties(array $optionalPropertyNameMap, boolean $onlyMappedProperties)
 *
 * Which either set or get all properties as determined
 * by get_class_properties OR by $optionalPropertyNameMap
 * if the $onlyMappedProperties parameter is <code>true</code>,
 * and setting/getting properties using the appropriate
 * setter/getter methods. The `populate()` method will
 * populate properties containing object instances using
 * references - the `populateWithClones()` will do so
 * with clones of any object instances that are passed.
 *
 * All methods throw a Dkd\Populate\Exception
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
    private $populatableAccessorMethodNamePrefixes = array(
        'get' => array('get', 'is', 'getIs'),
        'set' => array('set', 'setIs')
    );

    /**
     * Populates this instance, using standard references
     * (as opposed to cloning) when objects are encountered.
     *
     * @param  PopulateInterface|array $source A key=>value array or another
     *         PopulateInterface instance to use as data
     * @param  array                   $propertyNameMap Optional array of property
     *         names supporting mapping (see README)
     * @param  boolean                 $onlyMappedProperties If <code>true</code>
     *         will only populate properties contained in map
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
     * @param  PopulateInterface|array $source A key=>value array or another
     *         PopulateInterface instance to use as data
     * @param  array                   $propertyNameMap Optional array of property
     *         names supporting mapping (see README)
     * @param  boolean                 $onlyMappedProperties If <code>true</code>
     *         will only populate properties contained in map
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
     * map and <code>true</code> as third parameter, e.g.
     *
     *     $object->populate($source, array('test' => 'test'), true);
     *
     * This causes $object to be populated using $only the
     * "test" property from $source.
     *
     * @param  PopulateInterface|array $source A key=>value array or another
     *         PopulateInterface instance to use as data
     * @param  array                   $propertyNameMap Optional array of property
     *         names supporting mapping (see README)
     * @param  boolean                 $onlyMappedProperties If <code>true</code>
     *         will only populate properties contained in map
     * @param  boolean                 $cloneObjects If <code>true</code> will use
     *         <code>clone</coode> on any object instances
     * @throws Exception               Thrown on invalid or unsupported input data
     * @throws AccessException         Thrown on problems while setting properties
     */
    private function populateInternal($source, array $propertyNameMap, $onlyMappedProperties, $cloneObjects)
    {
        // decide where values come from or throw Exception if not retrievable
        if ($source instanceof PopulateInterface) {
            $data = $source->exportGettableProperties($propertyNameMap, $onlyMappedProperties);
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
        } else {
            $data = $source;
        }

        $propertyNameMap = $this->convertPropertyMap($propertyNameMap);

        // loop values, skipping mapped properties, use Trait's internal setter to set value
        if (!$onlyMappedProperties) {
            foreach ($data as $propertyName => $propertyValue) {
                if (!isset($propertyNameMap[$propertyName])) {
                    try {
                        // Note: $propertyValue is re-assigned directly from $data, again. We do
                        // this to make sure that if an Iterator+ArrayAccess instance is given,
                        // then the value returned from the ArrayAccess will replace the value
                        // returned from the Iterator portion. Re-assigning the variable this
                        // way looks redundant but serves a purpose.
                        $propertyValue = $data[$propertyName];
                        $this->setPopulatedProperty($propertyName, $propertyValue, $cloneObjects);
                    } catch (AccessException $error) {
                        // source was another Populate; $data may contain gettable but unsettable properties.
                        if (!$source instanceof PopulateInterface) {
                            throw $error;
                        }
                    }
                }
            }
        }

        // loop the mapped properties last to ensure mapped names override default names
        foreach ($propertyNameMap as $sourcePropertyName => $destinationPropertyName) {
            // only populate properties which were passed in source values
            if (isset($data[$sourcePropertyName])) {
                $propertyValue = $data[$sourcePropertyName];
                $this->setPopulatedProperty($destinationPropertyName, $propertyValue, $cloneObjects);
            }
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
     * map and <code>true</code> as third parameter, e.g.
     *
     *     $object->exportGettableProperties(array('test' => 'test'), true);
     *
     * This causes $object to be populated using $only the
     * "test" property from $source.
     *
     * @param  array   $propertyNameMap Optional array of property names
     *         supporting mapping (see README)
     * @param  boolean $onlyMappedProperties If <code>true</code> will only populate
     *         properties contained in map
     * @throws Exception If a property name map is passed and only mapped properties
     *         flag is <code>true</code>, any Exceptions will be passed through because
     *         this is considered an explicit attempt at access. However, if _all_
     *         properties are requested (which happens when the only mapped properties
     *         flag is <code>false</code>), Exceptions are suppressed and erroneous
     *         properties silently ignored and removed from the output array because
     *         in this case, it is likely that the list of property names came from a
     *         source like <code>get_class_vars</code> which does not care about the
     *         presence of getter/setter methods so we must tolerate and skip failures.
     */
    public function exportGettableProperties(array $propertyNameMap = array(), $onlyMappedProperties = false)
    {
        $export = array();
        $propertyNameMap = $this->convertPropertyMap($propertyNameMap);

        // Loop values, skipping mapped properties, use Trait's internal getter to get value.
        // We are suppressing Exceptions in this step in order to not disclose the reason why
        // a particular property could not be read. Possible causes are visibility, typos
        // in getter method name, non-standard getter method naming, or third-party Exceptions
        // being thrown from a getter.
        if (!$onlyMappedProperties) {
            $sourcePropertyNames = array_keys(get_class_vars(get_called_class()));
            foreach ($sourcePropertyNames as $propertyName) {
                if (isset($propertyNameMap[$propertyName]) || $propertyName === 'populatableAccessorMethodNames') {
                    continue;
                }
                try {
                    $export[$propertyName] = $this->getPopulatedProperty($propertyName);
                } catch (Exception $error) {
                    // @TODO: consider logging failures w/ reason
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
     * - employed()
     *
     * @param  string $propertyName The name of the property on this object
     * @param  string $method       Either `get` or `set`
     * @return string|boolean Method name ready for property value getting or
     *         setting as determined by $method, <code>false</code> if no function was found.
     */
    private function determinePropertyAccessFunctionName($propertyName, $method)
    {
        $methodSuffix = ucfirst($propertyName);
        $accessMethodNames = array();
        foreach ($this->populatableAccessorMethodNamePrefixes[$method] as $methodPrefix) {
            if (method_exists($this, $methodPrefix . $methodSuffix)) {
                $accessMethodNames[] = $methodPrefix . $methodSuffix;
            }
        }

        // special case for getter allowing the raw property name as getter function name;
        // see README.md about property name processing.
        if ($method === 'get' && method_exists($this, $propertyName)) {
            $accessMethodNames[] = $propertyName;
        }

        if (empty($accessMethodNames)) {
            throw new AccessException(
                'No "' . $method . '" method(s) can be determined for property ' . $propertyName,
                1422021212
            );
        } elseif (count($accessMethodNames) > 1) {
            throw new AccessException(
                'Found multiple "' . $method . '" access methods for property ' . $propertyName .
                ' (' . implode(', ', $accessMethodNames) . ') but there must be only one!',
                1424776261
            );
        }

        return (empty($accessMethodNames)) ? false : $accessMethodNames[0];
    }

    /**
     * Sets a single property via the resolved setter method,
     * throws a Dkd\Populate\Exception if no method could be found
     * and $ignoreFailures was false.
     *
     * @param  string  $propertyName Name of the property to set on this instance
     * @param  mixed   $value New value of the property
     * @param  boolean $cloneObjects If <code>true</code> and <code>$value</code>
     *         is an object instance, <code>clone</code> will be used on the value
     * @throws AccessException Thrown if a viable setter method cannot be determined
     */
    private function setPopulatedProperty($propertyName, $value, $cloneObjects)
    {
        $method = $this->determinePropertyAccessFunctionName($propertyName, 'set');
        if ($value !== null || $this->determineMethodParameterAllowsNull($method)) {
            if ($cloneObjects && is_object($value)) {
                $value = clone $value;
            }
            $this->$method($value);
        }
    }

    /**
     * Gets a single property via the resolved getter method,
     * throws a Dkd\Populate\Exception if no method could be found.
     *
     * @param  string $propertyName Name of the property to get from this instance
     * @return mixed The current value of the property
     * @throws AccessException Thrown if a viable getter method cannot be determined
     */
    private function getPopulatedProperty($propertyName)
    {
        $method = $this->determinePropertyAccessFunctionName($propertyName, 'get');
        return $this->$method();
    }

    /**
     * Converts the input array if necessary: check each property defined
     * in the array to ensure an output of an associative array regardless
     * of the input array structure. The array can be mixed associative
     * and numerically indexed - the output will always be associative;
     * any entries which have numeric indexes will be returned with the
     * property name as both index and value for that entry.
     *
     * The output array can then be consumed by populate/export methods.
     *
     * @param  array $propertyNameMap Optional array of property names
     *         supporting mapping (see README)
     * @return array The property map with expected name=>value format
     */
    private function convertPropertyMap(array $propertyNameMap)
    {
        $rebuilt = array();
        foreach ($propertyNameMap as $origin => $destination) {
            if (is_integer($origin)) {
                $origin = $destination;
            }
            $rebuilt[$origin] = $destination;
        }
        return $rebuilt;
    }

    /**
     * Returns <code>true</code> if the method designated in $methodName
     * supports <code>null</code> as value of the first/only parameter.
     * Used when checking if a setter method should allow <code>null</code>.
     *
     * @param string $methodName
     * @return boolean
     */
    private function determineMethodParameterAllowsNull($methodName)
    {
        $parameter = new \ReflectionParameter(array($this, $methodName), 0);
        return $parameter->allowsNull();
    }
}

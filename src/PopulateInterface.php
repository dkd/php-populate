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
 * Populate Interface
 *
 * Implement in classes to allow type-hinting your functions
 * to receive only populatable objects - and to allow your
 * populatable objects to be used as source when populating.
 *
 * To map one property to another property, pass a property
 * name map as second parameter:
 *
 *     $object->populate($source, array('orinal' => 'mappedName'));
 *
 * This populates the <code>mappedName</code> property on
 * <code>$object</code> with the value of the <code>original</code>
 * property on <code>$source</code>.
 *
 * The default behaviour is to map all properties from
 * <code>$source</code> onto <code>$object</code>. To selectively
 * populate properties that you decide, use a plain array as
 * property name map and <code>true</code> as third parameter, e.g.
 *
 *     $object->populate($source, array('test'), true);
 *
 * This causes <code>$object</code> to be populated using only
 * the <code>tes</code> property value from <code>$source</code>.
 *
 * By default, any property value that contains an object
 * instance will be populated with a _reference_ to the instance.
 * To change this behavior to populate with _clones_ instead,
 * use the <code>populateWithClones</code> method:
 *
 *     $object->populateWithClones($source);
 *
 * If you face a situation where you need a combination of
 * references and clones to be populated, perform two operations
 * instead of one:
 *
 *     $object->populate($source, array('referenceProperty'), true);
 *     $object->populateWithClones($source, array('cloneProperty'), true);
 *
 * Alternatively, let <code>populate()</code> insert references
 * first and then selectively overwrite the properties you wish
 * to have as clones:
 *
 *     $object->populate($source);
 *     $object->populateWithClones($source, array('someProperty'), true);
 *
 * The second operation will then replace the references with
 * cloned instances only for the properties you pass in the
 * property name array.
 */
interface PopulateInterface
{
    /**
     * Populate this instance using data from $source,
     * optionally mapping properties from source to
     * this object using $propertyNameMap and if using
     * the property map, only populating those properties
     * whose names were passed in the property map.
     *
     * @param  PopulateTrait|array $source
     * @param  array               $propertyNameMap
     * @param  boolean             $onlyMappedProperties
     * @throws Exception
     */
    public function populate($source, array $propertyNameMap = array(), $onlyMappedProperties = false);

    /**
     * @param  PopulateTrait|array $source
     * @param  array               $propertyNameMap
     * @param  boolean             $onlyMappedProperties
     * @throws Exception
     */
    public function populateWithClones($source, array $propertyNameMap = array(), $onlyMappedProperties = false);

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
     *     $object->export(array('test' => 'test'), true);
     *
     * This causes $object to be populated using $only the
     * "test" property from $source.
     *
     * @param  array   $propertyNameMap
     * @param  boolean $onlyMappedProperties
     * @throws Exception
     */
    public function exportGettableProperties(array $propertyNameMap = array(), $onlyMappedProperties = false);
}

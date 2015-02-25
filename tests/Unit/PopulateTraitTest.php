<?php
namespace Dkd\Populate\Tests\Unit;

/**
 * This file belongs to the Dkd/Populate package
 *
 * Copyright (c) 2015, dkd Internet Service GmbH
 *
 * Released under the MIT license, of which the full text
 * was distributed with this package in file LICENSE.txt
 */

use Dkd\Populate\Tests\Fixtures\ArrayAccessWithoutIterator;
use Dkd\Populate\Tests\Fixtures\ChildPopulateDummy;
use Dkd\Populate\Tests\Fixtures\PopulateDummy;

/**
 * Class PopulateTraitTest
 */
class PopulateTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $array
     * @return array
     */
    protected function removeNullValues(array $array)
    {
        foreach ($array as $key => $value) {
            if ($value === null) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    /**
     * @dataProvider getTestValues
     * @param mixed   $sourceData
     * @param array   $propertyNameMap
     * @param boolean $onlyMappedProperties
     * @param array   $expectedResult
     */
    public function testPopulate($sourceData, array $propertyNameMap, $onlyMappedProperties, array $expectedResult)
    {
        $target = new PopulateDummy();
        $target->populate($sourceData, $propertyNameMap, $onlyMappedProperties);
        $result = $target->exportGettableProperties();
        $result = $this->removeNullValues($result);
        $expectedResult = $this->removeNullValues($expectedResult);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider getTestValues
     * @param mixed   $sourceData
     * @param array   $propertyNameMap
     * @param boolean $onlyMappedProperties
     * @param array   $expectedResult
     */
    public function testPopulateWithClones(
        $sourceData,
        array $propertyNameMap,
        $onlyMappedProperties,
        array $expectedResult
    ) {
        $target = new PopulateDummy();
        $target->populateWithClones($sourceData, $propertyNameMap, $onlyMappedProperties);
        $result = $target->exportGettableProperties();
        $result = $this->removeNullValues($result);
        $expectedResult = $this->removeNullValues($expectedResult);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Confirm that the only case of supported ArrayAccess
     * implementations WITHOUT also having Iterator support
     * is working.
     *
     * @return void
     */
    public function testPopulateWithArrayAccessWithoutIterator()
    {
        $subject = new PopulateDummy();
        $subject->populate(new ArrayAccessWithoutIterator(array('property1' => 'test')), array('property1'), true);
        $this->assertEquals(array('property1' => 'test'), $subject->exportGettableProperties(array('property1'), true));
    }

    /**
     * @dataProvider getTestValues
     * @param mixed   $sourceData
     * @param array   $propertyNameMap
     * @param boolean $onlyMappedProperties
     * @param array   $expectedResult
     */
    public function testExportGettableProperties(
        $sourceData,
        array $propertyNameMap,
        $onlyMappedProperties,
        array $expectedResult
    ) {
        $subject = new PopulateDummy();
        $subject->populate($sourceData);
        $export = $subject->exportGettableProperties($propertyNameMap, $onlyMappedProperties);
        $export = $this->removeNullValues($export);
        $this->assertEquals($expectedResult, $export);
    }

    /**
     * @return array
     */
    public function getTestValues()
    {
        $object = new PopulateDummy();
        return array(

            // works with simple data sets without mapping
            array(
                array('property1' => 'test', 'boolean' => true),
                array(),
                false,
                array('property1' => 'test', 'boolean' => true)
            ),
            array(
                array('property1' => 'test', 'property2' => 'test2', 'boolean' => false),
                array(),
                false,
                array('property1' => 'test', 'property2' => 'test2', 'boolean' => false)
            ),

            // works with object-value getters/setters
            array(
                array('object' => $object),
                array(),
                false,
                array('object' => $object)
            ),

            // works with mapping
            array(
                array('property1' => 'test', 'boolean' => true),
                array('property1' => 'property2'),
                false,
                array('property2' => 'test', 'boolean' => true)
            ),

            // respects "only mapped properties" flag
            array(
                array('property1' => 'test', 'property2' => 'test2'),
                array('property1' => 'property1'),
                true,
                array('property1' => 'test')
            ),
            array(
                array('property1' => 'test', 'property2' => 'test2'),
                array('property1'),
                true,
                array('property1' => 'test')
            )
        );
    }

    public function testExportGettablePropertiesForInheritedClass()
    {
        $subject = new ChildPopulateDummy();
        $expectedResult = array(
            'property1' => null,
            'property2' => null,
            'childProperty1' => null,
            'boolean' => null,
            'object' => null,
            'withoutSetter' => null
        );
        $this->assertEquals($expectedResult, $subject->exportGettableProperties());
    }

    /**
     * @dataProvider getErrorTestValues
     * @param mixed   $sourceData
     * @param array   $propertyNameMap
     * @param boolean $onlyMappedProperties
     */
    public function testPopulateErrors($sourceData, array $propertyNameMap, $onlyMappedProperties)
    {
        $target = new PopulateDummy();
        $this->setExpectedException('Dkd\\Populate\\Exception');
        $target->populate($sourceData, $propertyNameMap, $onlyMappedProperties);
    }

    /**
     * @dataProvider getErrorTestValues
     * @param array   $sourceData
     * @param array   $propertyNameMap
     * @param boolean $onlyMappedProperties
     */
    public function testExportErrors($sourceData, array $propertyNameMap, $onlyMappedProperties)
    {
        $target = new PopulateDummy();
        $this->setExpectedException('Dkd\\Populate\\Exception');
        $target->populate($sourceData);
        $target->exportGettableProperties($propertyNameMap, $onlyMappedProperties);
    }

    /**
     * @return array
     */
    public function getErrorTestValues()
    {
        $dateTime = new \DateTime();
        return array(

            // fails with invalid property in property map regardless of "only mapped properties" flag
            array(array('property1' => 'test', 'invalidpropertyname' => 'test'), array('invalidpropertyname'), false),
            array(array('property1' => 'test', 'invalidpropertyname' => 'test'), array('invalidpropertyname'), true),

            // fails with invalid property in value list without property map
            array(array('property1' => 'test', 'invalidpropertyname' => 'test'), array(), false),
            array(array('property1' => 'test', 'withoutSetter' => 'test'), array(), false),

            // fails with invalid property in value list with property map without "only mapped properties"
            array(array('property1' => 'test', 'invalidpropertyname' => 'test'), array('property1'), false),
            array(array('property1' => 'test', 'withoutSetter' => 'test'), array('property1'), false),

            // fails with ArrayAccess without Iterator if not mapping only specified properties
            array(new ArrayAccessWithoutIterator(), array(), false),
            array(new ArrayAccessWithoutIterator(), array('property1'), false),

            // fails with invalid source data types regardless of property map composition
            array(null, array(), false),
            array(null, array(), true),
            array(null, array('property1'), false),
            array(null, array('property1'), true),
            array(1, array(), false),
            array(1, array(), true),
            array(1, array('property1'), false),
            array(1, array('property1'), true),
            array($dateTime, array(), false),
            array($dateTime, array(), true),
            array($dateTime, array('property1'), false),
            array($dateTime, array('property1'), true),
        );
    }
}

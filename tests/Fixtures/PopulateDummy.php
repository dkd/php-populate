<?php
namespace Dkd\Populate\Tests\Fixtures;

/**
 * This file belongs to the Dkd/Populate package
 *
 * Copyright (c) 2015, dkd Internet Service GmbH
 *
 * Released under the MIT license, of which the full text
 * was distributed with this package in file LICENSE.txt
 */

use Dkd\Populate\PopulateInterface;
use Dkd\Populate\PopulateTrait;

/**
 * Class PopulateDummy
 */
class PopulateDummy implements PopulateInterface
{
    use PopulateTrait;

    /**
     * @var string
     */
    protected $property1;

    /**
     * @var string
     */
    protected $property2;

    /**
     * @var boolean
     */
    protected $boolean;

    /**
     * @var boolean
     */
    protected $isBoolean2;

    /**
     * @var PopulateDummy
     */
    protected $object;

    /**
     * @var string
     */
    protected $withoutSetter;

    /**
     * @var string
     */
    protected $withoutGetter;

    /**
     * @return string
     */
    public function getProperty1()
    {
        return $this->property1;
    }

    /**
     * @param string $property1
     */
    public function setProperty1($property1)
    {
        $this->property1 = $property1;
    }

    /**
     * @return string
     */
    public function getProperty2()
    {
        return $this->property2;
    }

    /**
     * @param string $property2
     */
    public function setProperty2($property2)
    {
        $this->property2 = $property2;
    }

    /**
     * @return boolean
     */
    public function isBoolean()
    {
        return $this->boolean;
    }

    /**
     * @param boolean $boolean
     * @return void
     */
    public function setBoolean($boolean)
    {
        $this->boolean = $boolean;
    }

    /**
     * @return boolean
     */
    public function isBoolean2()
    {
        return $this->isBoolean2;
    }

    /**
     * @param boolean $isBoolean2
     * @return void
     */
    public function setIsBoolean2($isBoolean2)
    {
        $this->isBoolean2 = $isBoolean2;
    }

    /**
     * @return PopulateDummy
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param PopulateDummy $object
     * @return void
     */
    public function setObject(PopulateDummy $object)
    {
        $this->object = $object;
    }

    /**
     * @return string
     */
    public function getWithoutSetter()
    {
        return $this->withoutSetter;
    }

    /**
     * @param string $withoutGetter
     * @return void
     */
    public function setWithoutGetter($withoutGetter)
    {
        $this->withoutGetter = $withoutGetter;
    }
}

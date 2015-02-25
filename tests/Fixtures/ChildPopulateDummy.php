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

/**
 * Class ChildPopulateDummy
 */
class ChildPopulateDummy extends PopulateDummy
{
    /**
     * @var string
     */
    protected $childProperty1;

    /**
     * @return string
     */
    public function getChildProperty1()
    {
        return $this->childProperty1;
    }

    /**
     * @param string $childProperty1
     */
    public function setChildProperty1($childProperty1)
    {
        $this->childProperty1 = $childProperty1;
    }
}

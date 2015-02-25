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
class MultipleAccessMethodsPopulateDummy implements PopulateInterface
{
    use PopulateTrait;

    /**
     * @var boolean
     */
    protected $boolean;

    /**
     * @return boolean
     */
    public function isBoolean()
    {
        return $this->boolean;
    }

    /**
     * @return boolean
     */
    public function boolean()
    {
        return $this->boolean;
    }
}

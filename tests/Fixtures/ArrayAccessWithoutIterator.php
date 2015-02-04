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
 * Class ArrayAccessWithoutIterator
 */
class ArrayAccessWithoutIterator implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $array = array();

    /**
     * @param array $array
     */
    public function __construct(array $array = array())
    {
        $this->array = $array;
    }

    /**
     * @param mixed $offset
     * @return boolean true on success or false on failure
     */
    public function offsetExists($offset)
    {
        return isset($this->array[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->array[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->array[$offset] = $value;
    }

    /**
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->array[$offset]);
    }
}

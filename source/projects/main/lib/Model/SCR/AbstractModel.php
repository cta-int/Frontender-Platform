<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Prototype\Model\State\State;

use Frontender\Core\Object\ObjectArray;
use Prototype\Model\Traits\Translatable;
use Pimple\Container;

abstract class AbstractModel implements \ArrayAccess
{
    use Translatable;

    protected $name;
    protected $container;

    private $state;
    protected $data = [];
    protected $cached = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * If no name has been set extract it from the class name.
     *
     * @return string
     */
    public function getName() : string
    {
        if (!$this->name) {
            $path = explode('\\', get_called_class());
            $pieces = preg_split('/(?=[A-Z])/', end($path), -1, PREG_SPLIT_NO_EMPTY);

            $this->setName(strtolower(array_shift($pieces)));
        }

        return $this->name;
    }

    public function setState(array $values)
    {
        $this->getState()->setValues($values);

        return $this;
    }

    public function getState() : State
    {
        if (!$this->state instanceof State) {
            $this->state = new State($this->container);
        }

        return $this->state;
    }

    public function fetch($raw = false)
    {
        // This should never be called.

        return false;
    }

    public function setData($data = [])
    {
        $this->data = $data;

        return $this;
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return false;
        }

        $method = 'getProperty' . ucfirst($offset);
        if (is_callable([$this, $method])) {
            if (isset($this->cached[$offset])) {
                return $this->cached[$offset];
            }

            $this->cached[$offset] = $this->{$method}();

            return $this->cached[$offset];
        } else if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }

        return false;
    }

    public function offsetSet($offset, $value)
    {
        // Check if we have a set method.
        $method = 'setProperty' . ucfirst($offset);
        if (is_callable([$this, $method])) {
            $this->{$method}($value);
        } else if ($this->offsetExists($offset)) {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        $method = 'getProperty' . ucfirst($offset);
        if (is_callable([$this, $method])) {
            return true;
        } else if (isset($this->data[$offset])) {
            return true;
        }

        return false;
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->data[$offset]);
        }
    }
}

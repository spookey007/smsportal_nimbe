<?php
namespace DTS\eBaySDK\Types;

use \DTS\eBaySDK\Exceptions;

/**
 * Class to handle XML elements that are repeatable.
 *
 * Allows properties in an object to be treated as an array.
 */
class RepeatableType implements \ArrayAccess, \Countable, \Iterator
{
    /**
     * @var array The data to store as an array.
     */
    private $data = [];

    /**
     * @var integer The current position in the array.
     */
    private $position = 0;

    /**
     * @var string The name of the class that the property is a member of.
     */
    private $class;

    /**
     * @var string The name of the property that acts like an array.
     */
    private $property;

    /**
     * @var string The type that values assigned to the array should be.
     */
    private $expectedType;

    /**
     *
     * @param string $class The name of the class that the property is a member of.
     * @param string $property The name of the property that acts like an array.
     * @param string $expectedType The type that values assigned to the array should be.
     */
    public function __construct($class, $property, $expectedType)
    {
        $this->class = $class;
        $this->property = $property;
        $this->expectedType = $expectedType;
    }

    /**
     * Determines if the offset exists in the array.
     *
     * @param integer $offset The array index to check.
     *
     * @returns boolean Returns if the offset exists in the array.
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Returns the value of the given offset.
     *
     * @param integer $offset The array index.
     *
     * @returns mixed Returns the value for the given offset or null if it doesn't exist.
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : null;
    }

    /**
     * Sets a value for the given offset.
     *
     * @param mixed $offset The array index or null to add the value to the end of the array.
     * @throws InvalidPropertyTypeException If the value is the wrong type for the property.
     */
    public function offsetSet($offset, $value)
    {
        self::ensurePropertyType($value);

        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Unsets the value of the given offset.
     *
     * @param integer $offset The array index.
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @returns integer The number of array items.
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * @returns mixed The value of the current array index.
     */
    public function current()
    {
        return $this->offsetGet($this->position);
    }

    /**
     * @returns index The current array index.
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Move onto the next array index.
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Reset the array index to the start of the array.
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @returns boolean Return if the current array index is valid.
     */
    public function valid()
    {
        return $this->offsetExists($this->position);
    }

    /**
     * Determines if the value is the correct type to assign to a property.
     *
     * @param mixed $name The value to check the type of.
     * @throws InvalidPropertyTypeException If the value is the wrong type for the property.
     */
    private function ensurePropertyType($value)
    {
        if (\DTS\eBaySDK\checkPropertyType($this->expectedType)) {
            $actualType = gettype($value);
            if ('object' === $actualType) {
                $actualType = get_class($value);
            }

            if ($this->expectedType !== $actualType) {
                throw new Exceptions\InvalidPropertyTypeException($this->property, $this->expectedType, $actualType);
            }
        }
    }
}

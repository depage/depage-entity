<?php

/**
 * @file    Entity.php
 *
 * Entity
 *
 * Abstract class provides a base for building model objects.
 *
 * Inheriting classes provide table name and column information and override getters and setters.
 *
 * copyright (c) 2003-2014 Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Entity;

abstract class Entity implements \JsonSerializable
{
    // {{{ variables
    /**
     * Fields
     *
     * Array of table fields indexed on the column name.
     * Values provide the PDO data type for binding to markers.
     *
     * @var array
     */
    protected static $fields = [];

    /**
     * @brief initialized
     **/
    protected $initialized = false;

    /**
     * Data Array
     *
     * The data array accessed via the magic get / set functions.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Types Array
     *
     * The Types of the fields. Optional, add to enable strict type testing when
     * setting fields
     *
     * @var array
     */
    protected $types = [];

    /**
     * Dirty Data
     *
     * This array tracks which properties are dirty for saving.
     * Bool array value indicates column state.
     *
     * @var array
     */
    protected $dirty = [];
    // }}}

    // {{{ __constructor()
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        if (count($this->data) === 0) {
            // new empty object with no data -> set defaults
            foreach (static::$fields as $key => $value) {
                $this->data[$key] = $value;
            }

            $this->dirty = array_fill_keys(array_keys(static::$fields), true);
        } else {
            // object initiated through pdo fetch, so data is already set
            $this->dirty = array_fill_keys(array_keys(static::$fields), false);
        }

        $this->initialized = true;
    }
    // }}}

    // {{{ _magickError()
    /**
     * @brief _magickError
     * @param string $key
     * @param string $key
     * @return void
     * @throws \Exception
     */
    protected function _magickError(string $action, string $key): void
    {
        $file = "unknown file";
        $line = "unknown line";

        $trace = debug_backtrace();

        foreach ($trace as $t) {
            if (isset($t['file']) && isset($t['line']) && strpos($t['file'], __FILE__) === false) {
                $file = $t['file'];
                $line = $t['line'];
                break;
            }
        }
        trigger_error(
            'Undefined property via ' . $action . '(): ' . $key
            . ' in ' . $file
            . ' on line ' . $line,
            E_USER_WARNING,
        );
    }
    // }}}

    // {{{ __get()
    /**
     * Get
     *
     * Gets the propery from the data array if it exists.
     *
     * @param string $property
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        $k = $key;
        $getter = "get" . ucfirst($k);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        if (array_key_exists($k, $this->data)) {
            return $this->data[$k];
        }

        $this->_magickError("__get", $key);

        return null;
    }
    // }}}

    // {{{ __set()
    /**
     * Set
     *
     * Sets the data and dirty arrays if the data property exists and the data has changed.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function __set(string $key, mixed $val): void
    {
        $k = $key;
        $v = $val;
        $setter = "set" . ucfirst($k);
        if ($this->initialized && method_exists($this, $setter)) {
            $this->$setter($v);

            return;
        }
        if (array_key_exists($k, $this->data) || !$this->initialized) {
            // add value if property exists and is not primary
            if (!in_array($k, static::$primary) || !$this->initialized) {
                $this->dirty[$k] = (isset($this->dirty[$k]) && $this->dirty[$k] == true) || (
                    (isset($this->data[$k]) && $this->data[$k] != $v)
                    || !isset($this->data[$k])
                );
                $this->data[$k] = $v;
            }

            return;
        }

        $this->_magickError("__set", $key);
    }
    // }}}

    // {{{ __call()
    /**
     * Call
     *
     * Allows to set and get variables via setVarname and getVarname methods without
     * declaring them explicitly
     *
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    public function __call(string $name, ?array $arguments): mixed
    {
        $n = $name;
        $prefix = substr($n, 0, 3);
        $key = lcfirst(substr($n, 3));

        if ($prefix == "set") {
            if (array_key_exists($key, static::$fields)) {
                $this->$key = $arguments[0];
            }
            return $this;
        } elseif ($prefix == "get") {
            if (array_key_exists($key, static::$fields)) {
                return $this->$key;
            }
        }

        $this->_magickError("__call", $name);

        return false;
    }
    // }}}

    // {{{ __isset()
    /**
     * IsSet
     *
     * Checks that the property exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key): bool
    {
        $getter = "get" . ucfirst($key);
        if (method_exists($this, $getter)) {
            return true;
        }
        return isset($this->data[$key]);
    }
    // }}}

    // {{{ getFields()
    /**
     * @brief get field names that are defined in schema
     *
     * @param string $prefix = ""
     * @return array of field names
     **/
    protected static function getFields($prefix = ""): array
    {
        $fields = array_keys(static::$fields);

        if ($prefix !== "") {
            $fields = array_map(function ($val) use ($prefix) {
                return $prefix . "." . $val;
            }, $fields);
        }

        return $fields;
    }
    // }}}

    // {{{ setData()
    /**
     * @brief setData
     *
     * Sets object data with data array instead of setting properties explicitly
     *
     * @param array  $data
     *
     * @return self
     **/
    public function setData(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }
    // }}}

    // {{{ __sleep()
    /**
     * allows Depage\Db\Pdo-object to be serialized
     *
     * @return array of properties to serialize
     */
    public function __sleep(): array
    {
        return [
            'initialized',
            'data',
            'types',
            'dirty',
        ];
    }
    // }}}

    // {{{ jsonSerialize()
    /**
     * @brief jsonSerialize
     *
     * @return array
     **/
    public function jsonSerialize(): array
    {
        return $this->data;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

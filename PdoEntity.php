<?php

/**
 * @file    PdoEntity.php
 *
 * PdoEntity
 *
 * copyright (c) 2006-2017 Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Entity;

abstract class PdoEntity extends Entity
{
    /**
     * @brief pdo object for database access
     **/
    protected $pdo = null;

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $pdo, $user
     * @return void
     **/
    public function __construct(\Depage\Db\Pdo $pdo)
    {
        parent::__construct($pdo);

        $this->pdo = $pdo;
    }
    // }}}

    // abstract functions
    // {{{ loadBy()
    /**
     * @brief loadBy
     *
     * @param mixed $pdo
     * @param array $search
     * @param array $order
     *
     * @return attay of loaded entities
     **/
    public static function loadBy(\Depage\Db\Pdo $pdo, array $search, array $order = []): array {}
    // }}}
    // {{{ save()
    /**
     * @brief save
     *
     * @param mixed
     * @return void
     **/
    abstract public function save();
    // }}}

    // empty overridable class functions
    // {{{ onLoad()
    /**
     * @brief onLoad
     *
     * @return void
     **/
    protected function onLoad(): void {}
    // }}}
    // {{{ onSave()
    /**
     * @brief onSave
     *
     * @return void
     **/
    protected function onSave(): void {}
    // }}}

    // helpers
    // {{{ sqlConditionFor()
    /**
     * @brief sqlConditionFor
     *
     * @param string $name
     * @param array $values
     * @param array $params
     *
     * @return string
     **/
    protected static function sqlConditionFor(string $name, string|array $values, &$params): string
    {
        $escapedName = str_replace(".", "_", $name);
        if (!is_array($values)) {
            $params[$escapedName] = $values;
            return "$name = :$escapedName";
        }

        $where = "$name IN (";
        foreach ($values as $key => $val) {
            $params["$escapedName$key"] = $val;
            $where .= ":$escapedName$key,";
        }
        return rtrim($where, ",") . ")";
    }
    // }}}
    // {{{ dateTimestamp()
    /**
     * @brief
     *
     * @param mixed $timestamp = null
     *
     * @return string
     **/
    public static function dateTimestamp($timestamp = null): string
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
    // }}}
    // {{{ escapeLike()
    /**
     * @brief escapeLike
     *
     * @param string $s
     * @param string $e
     *
     * @return string
     **/
    public static function escapeLike($s, $e): string
    {
        return str_replace([$e, '_', '%'], ["{$e}{$e}", "{$e}_", "{$e}%"], $s);
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
            'pdo',
            'initialized',
            'data',
            'types',
            'dirty',
        ];
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

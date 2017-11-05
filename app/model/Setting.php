<?php

namespace App\Model;

use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\Database\UniqueConstraintViolationException;

class Setting
{
    /** @var Context $context */
    private $context;

    /**
     * @param $context Context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @return Selection
    */
    public function getTable()
    {
        return $this->context->table('setting');
    }

    /**
     * @param $key string
     * @return string|bool
    */
    public function getValueByKey($key)
    {
        $table = $this->getTable();
        $fetch = $table->select('Value')
            ->where('Key = ?', $key)
            ->fetch();
        return $fetch['Value'];
    }

    /**
     * @param $key string
     * @param $value string
    */
    public function insert($key, $value)
    {
        try{
            $this->context->query('INSERT INTO setting VALUES (?, ?)', $key, $value);
        }catch(UniqueConstraintViolationException $ex)
        {
            $this->updateValueByKey($key, $value);
        }
    }

    public function updateValueByKey($key, $value)
    {
        $this->context->query('UPDATE setting SET Value = ? WHERE Key = ?', $value, $key);
    }

}
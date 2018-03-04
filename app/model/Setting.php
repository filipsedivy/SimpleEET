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
     *
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
     * @param string      $key
     * @param string|null $value
     * @param string|null $group
     * @param string|null $type
     * @param string|null $translate
     * @param int         $visible
     * @param array       $additionalData
     *
     * @throws \Exception
     */
    public function insert($key, $value, $group, $type, $translate = null, $visible = 1, array $additionalData = [])
    {
        $this->context->beginTransaction();
        try
        {
            $this->context->query('INSERT INTO setting', [
                'Key'            => $key,
                'Value'          => $value,
                'Group'          => $group,
                'Type'           => $type,
                'Translate'      => $translate,
                'Visible'        => $visible,
                'AdditionalData' => json_encode($additionalData)
            ]);
            $this->context->commit();
        }
        catch (\Exception $e)
        {
            $this->context->rollBack();
            throw $e;
        }
    }

    public function updateValueByKey($key, $value)
    /**
     * @param string $column
     * @param string $key
     * @param string $value
     *
     * @throws \Exception
     */
    public function updateColumn($column, $key, $value)
    {
        $this->context->beginTransaction();
        try
        {
            $this->context->query('UPDATE setting SET ?name = ? WHERE Key = ?', $column, $value, $key);
            $this->context->commit();
        }
        catch (\Exception $e)
        {
            $this->context->rollBack();
            throw $e;
        }
    }

}
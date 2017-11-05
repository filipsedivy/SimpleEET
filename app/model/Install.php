<?php

namespace App\Model;

use Nette\Database\Connection;
use Nette\Database\IRow;
use Nette\Database\Row;
use Nette\Database\Table\ActiveRow;

class Install
{
    /** @var Connection $database */
    private $database;

    /** @var Setting */
    private $setting;

    /**
     * @param $connection Connection
     * @param $setting Setting
     */
    public function __construct(Connection $connection, Setting $setting)
    {
        $this->database = $connection;
        $this->setting = $setting;
    }

    /**
     * Get EET head
     * @return IRow[]
     */
    public function getHead()
    {
        return $this->database->query('SELECT * FROM eet_head')->fetchAll();
    }

    public function setValueHeadByXmlParam($param, $value)
    {
        $this->setValueHeadByParam('XMLParam', $param, $value);
    }

    public function setValueHeadByParam($param, $key, $value)
    {
        $head = $this->getHead();
        foreach ($head as $row) {
            if ($row instanceof Row) {
                if ($row[$param] === $key) {
                    $id = $row['ID'];
                    $this->database->query('UPDATE eet_head SET Value = ? WHERE ID = ?', $value, $id);
                }
            }
        }
    }

    /**
     * @return bool
    */
    public function isInstalled()
    {
        $service = $this->setting->getValueByKey('service');
        return !is_null($service);
    }

}
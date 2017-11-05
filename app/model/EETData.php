<?php

namespace App\Model;

use FilipSedivy\EET\Receipt;
use Nette\Database\Context;
use Nette\Database\Table\Selection;

class EETData
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
        return $this->context->table('eet_data');
    }

    /**
     * Uložení dat
    */
    public function save(Receipt $receipt, array $codes, $parent_id = NULL)
    {
        $repeat = 0;
        if(isset($codes['fik'])) { $repeat = -1; }

        $this->context->table('eet_data')->insert(array(
            'Receipt'       => serialize($receipt),
            'Response'      => json_encode($codes),
            'TotalPrice'    => $receipt->celk_trzba,
            'ParentID'      => $parent_id,
            'Repeat'        => $repeat,
            'Timestamp'     => $receipt->dat_trzby->getTimestamp()
        ));
    }

    public function getById($id)
    {
        return $this->getTable()->where('ID = ?', $id)
            ->fetch();
    }

    public function updateById($id, Receipt $receipt, array $codes, $repeat = NULL, $parentID = NULL)
    {
        $data = array(
            'Receipt'   => serialize($receipt),
            'Response'  => json_encode($codes)
        );

        if(!is_null($repeat)) $data['Repeat'] = $repeat;
        if(!is_null($parentID)) $data['ParentID'] = $parentID;

        $this->getTable()->where('ID = ?', $id)->update($data);
    }

    public function lastId($increment = false)
    {
        $fetch = $this->getTable()->order('ID DESC')->limit(1)->fetch();
        return is_null($fetch['ID']) ? 1 : ($increment ? $fetch['ID'] + 1 : $fetch['ID']);
    }
}
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
    public function save(Receipt $receipt, array $codes, $parent_id = null)
    {
        $repeat = 0;
        if (isset($codes['fik'])) $repeat = -1;

        $data = array(
            'Receipt'    => serialize($receipt),
            'TotalPrice' => $receipt->celk_trzba,
            'ParentID'   => $parent_id,
            'Repeat'     => $repeat,
            'TimeStamp'  => $receipt->dat_trzby->getTimestamp()
        );

        if (isset($codes['pkp'])) $data['PKP'] = $codes['pkp'];
        if (isset($codes['fik'])) $data['FIK'] = $codes['fik'];
        if (isset($codes['bkp'])) $data['BKP'] = $codes['bkp'];

        $this->context->table('eet_data')->insert($data);
    }

    public function getById($id)
    {
        return $this->getTable()->where('ID = ?', $id)
            ->fetch();
    }

    public function updateById($id, Receipt $receipt, array $codes, $repeat = null, $parentID = null)
    {
        $data = array(
            'Receipt' => serialize($receipt)
        );

        if (!is_null($repeat)) $data['Repeat'] = $repeat;
        if (!is_null($parentID)) $data['ParentID'] = $parentID;

        $this->getTable()->where('ID = ?', $id)->update($data);
    }

    public function updateColumnByPaymentId($paymentId, $data)
    {
        $this->getTable()->where('ID = ?', $paymentId)->update($data);
    }

    public function lastId($increment = false)
    {
        $fetch = $this->getTable()->order('ID DESC')->limit(1)->fetch();
        return is_null($fetch['ID']) ? 1 : ($increment ? $fetch['ID'] + 1 : $fetch['ID']);
    }

    public function getReceiptByParentId($parentId)
    {
        return $this->getTable()->select('*')
            ->where('ParentID = ?', $parentId)
            ->fetch();
    }
}
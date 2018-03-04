<?php

namespace App\Presenters;

use App\Component\TableRender;
use FilipSedivy\EET\Receipt;
use Nette\Utils\Html;
use Ublaboo\DataGrid\DataGrid;

class HistoryPresenter extends BasePresenter
{
    public function renderShowReceipt()
    {
        $id = $this->getParameter('id');
        $payment = $this->eetDataModel->getById($id);
        if (!$payment)
        {
            $this->flashMessage('Platba nebyla nelezena', 'alert-info');
            $this->redirect('History:default');
        }

        /** @var Receipt $receipt */
        $receipt = unserialize($payment['Receipt']);

        $this->template->receipt = $receipt;
        $this->template->codes = array(
            'fik' => $payment['FIK'],
            'bkp' => $payment['BKP'],
            'pkp' => $payment['PKP']
        );
        $this->template->errors = is_null($payment['Exception']) ? array() : json_decode($payment['Exception']);
        $this->template->id = $id;
    }

    public function createComponentTableRender()
    {
        return new TableRender();
    }

    public function createComponentDataGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setPrimaryKey('ID');
        $grid->setDataSource($this->eetDataModel->getTable());
        $grid->addColumnText('TimeStamp', 'Datum tržby')
            ->setRenderer(function ($row) {
                return date('d.m.Y H:i:s', $row['TimeStamp']);
            })->setSortable();

        $grid->addColumnNumber('TotalPrice', 'Celková cena')
            ->setRenderer(function ($row) {
                return $row['TotalPrice'] . ' Kč';
            })->setSortable();

        $grid->addColumnText('FIK', 'FIK');

        $grid->addColumnText('Register', 'Status')
            ->setRenderer(function ($row) {
                $fik = isset($row->FIK) ? true : false;
                $html = Html::el('span');
                if (!is_null($row['Exception']))
                {
                    $html->setAttribute('class', 'label label-warning')
                        ->setText('Nelze registrovat');
                }
                elseif ($fik)
                {
                    $r = Html::el('span')
                        ->setAttribute('class', 'label label-success')
                        ->setText('Registrována');

                    $html->addHtml($r);

                    $parent = $this->eetDataModel->getReceiptByParentId($row->ID);

                    if ($parent !== false && $parent->TotalPrice <= 0)
                    {
                        $s = Html::el('a')
                            ->setAttribute('class', 'label label-info')
                            ->setAttribute('style', 'margin-left: 5px;')
                            ->href($this->link('History:showReceipt', array(
                                'id' => $parent->ID
                            )))
                            ->setText('Stornována');

                        $html->addHtml($s);
                    }
                    elseif ($row->TotalPrice <= 0)
                    {
                        $s = Html::el('span')
                            ->setAttribute('class', 'label label-primary')
                            ->setAttribute('style', 'margin-left: 5px;')
                            ->setText('Storno');

                        $html->addHtml($s);
                    }
                }
                else
                {
                    $html->setAttribute('class', 'label label-danger')
                        ->setText('Neregistrována');
                }
                return $html;
            });

        $grid->addAction('id', 'Detail', 'ShowReceipt', ['id' => 'ID'])
            ->setClass('btn btn-primary btn-xs');

        $grid->addAction('storno', 'Storno', 'StornoPayment')
            ->setRenderer(function ($row) {
                $btn = '';

                if (!is_null($row->FIK) &&
                    $row->TotalPrice > 0 &&
                    $this->eetDataModel->getReceiptByParentId($row->ID) === false
                )
                {
                    $btn = Html::el('a')
                        ->setText('Storno')
                        ->setAttribute('class', 'btn btn-xs btn-danger')
                        ->setAttribute('href', $this->link('Shortcuts:default', array(
                            'id' => $row->ID,
                            'do' => 'storno'
                        )));
                }

                return $btn;
            });
    }
}
<?php

namespace App\Presenters;

use App\Component\TableRender;
use FilipSedivy\EET\Receipt;
use Nette\Utils\Html;
use Ublaboo\DataGrid\Column\Column;
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
                $fik = isset($row['FIK']) ? true : false;
                $html = Html::el('span');
                if ($fik)
                {
                    $html->setAttribute('class', 'label label-success')
                        ->setText('Registrována');
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

                if (!is_null($row['FIK']))
                {
                    $total_price = $row['TotalPrice'];
                    $btn = Html::el('a')
                        ->setText('Storno')
                        ->setAttribute('class', 'btn btn-xs btn-danger');

                    if ($total_price <= 0 OR !is_null($row['ParentID']))
                    {
                        $btn->setAttribute('disabled', 'disabled');
                    }
                    else
                    {
                        $btn->setAttribute('href', $this->link('Shortcuts:default', array(
                            'id' => $row['ID'],
                            'do' => 'storno'
                        )));
                    }
                }

                return $btn;
            });
    }
}
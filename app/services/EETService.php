<?php

namespace App\Services;

use App\Model\EETData;
use App\Model\Setting;
use FilipSedivy\EET\Certificate;
use FilipSedivy\EET\Dispatcher;
use FilipSedivy\EET\Exceptions\ClientException;
use FilipSedivy\EET\Exceptions\EetException;
use FilipSedivy\EET\Exceptions\ServerException;
use FilipSedivy\EET\Receipt;
use FilipSedivy\EET\Utils\UUID;
use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;

class EETService
{
    /** @var EETData $eetDataModel */
    private $eetDataModel;

    /** @var Setting $settingModel */
    private $settingModel;

    public function __construct(EETData $eetData, Setting $setting)
    {
        $this->eetDataModel = $eetData;
        $this->settingModel = $setting;
    }

    /**
     * Celková cena
     * @param $storno bool Zahrnout i stornované faktury
     * @return double
    */
    public function sum($storno = false)
    {
        $table = $this->eetDataModel->getTable();
        if($storno === false)
        {
            $table->where('ParentID IS NULL');
        }
        $sum = $table->sum('TotalPrice');
        return is_null($sum) ? 0 : $sum;
    }

    /**
     * Celkový počet záznamů
     * @return integer
    */
    public function count()
    {
        return $this->eetDataModel->getTable()->count('ID');
    }


    /** @return Selection */
    public function unsendPayments()
    {
        return $this->eetDataModel->getTable()
            ->where('Repeat >= 0');
    }


    /**
     * Vytvoří platbu, kterou zašle do EET
     * @param ArrayHash $values
     * @return bool
     */
    public function createPayment(ArrayHash $values)
    {
        $service = $this->settingModel->getValueByKey('service');

        $receipt = new Receipt;
        $receipt->uuid_zpravy = UUID::v4();

        if($service === 'playground')
        {
            $receipt->id_provoz = '1';
            $receipt->id_pokl = 'PlaygroundCash';
            $receipt->dat_trzby = new \DateTime();
            $receipt->dic_popl = 'CZ1212121218';
        }

        $receipt = $this->mapEETReceipt($values, $receipt);

        return $this->send($receipt);
    }

    public function resendPayment($id)
    {
        $payment = $this->eetDataModel->getById($id);

        /** @var Receipt $receipt */
        $receipt = unserialize($payment['Receipt']);

        $codes = json_decode($payment['Response']);

        $receipt->uuid_zpravy = UUID::v4();
        $receipt->prvni_zaslani = false;
        $receipt->bkp = $codes->bkp;
        $receipt->pkp = base64_decode($codes->pkp);

        return $this->send($receipt, $id);
    }

    public function stornoByIdPayment($id)
    {
        $payment = $this->eetDataModel->getById($id);
        if($payment === false)
        {
            throw new \Exception('Položka nebyla nelezena');
        }

        if($payment['TotalPrice'] <= 0)
        {
            throw new \Exception('Částka musí být větší než 0 Kč');
        }

        /** @var Receipt $receipt */
        $receipt = unserialize($payment['Receipt']);

        foreach($receipt as $key => $value)
        {
            if(in_array($key, array(
                'porad_cis', 'rezim', 'uuid_zpravy',
                'prvni_zaslani', 'dic_popl', 'dic_poverujiciho',
                'id_provoz', 'id_pokl', 'dat_trzby', 'bkp', 'pkp'
            ))) { continue; }

            if(is_double($value) OR is_int($value))
            {
                if($value > 0)
                {
                    $receipt->{$key} = $value * -1;
                }
            }
        }

        $this->send($receipt, null, $id);
    }

    /** @return Receipt */
    private function mapEETReceipt(ArrayHash $values, Receipt $receipt)
    {
        if(isset($values->porad_cis)) $receipt->porad_cis = intval($values->porad_cis);
        // @TODO dat_trzby
        if(isset($values->celk_trzba)) $receipt->celk_trzba = doubleval($values->celk_trzba);
        if(isset($values->zakl_nepodl_dph)) $receipt->zakl_nepodl_dph = doubleval($values->zakl_nepodl_dph);
        if(isset($values->zakl_dan1)) $receipt->zakl_dan1 = doubleval($values->zakl_dan1);
        if(isset($values->dan1)) $receipt->dan1 = doubleval($values->dan1);
        if(isset($values->zakl_dan2)) $receipt->zakl_dan2 = doubleval($values->zakl_dan2);
        if(isset($values->dan2)) $receipt->dan2 = doubleval($values->dan2);
        if(isset($values->zakl_dan3)) $receipt->zakl_dan3 = doubleval($values->zakl_dan3);
        if(isset($values->dan3)) $receipt->dan3 = doubleval($values->dan3);
        if(isset($values->cest_sluz)) $receipt->cest_sluz = doubleval($values->cest_sluz);
        if(isset($values->pouzit_zboz1)) $receipt->pouzit_zboz1 = doubleval($values->pouzit_zboz1);
        if(isset($values->pouzit_zboz2)) $receipt->pouzit_zboz2 = doubleval($values->pouzit_zboz2);
        if(isset($values->pouzit_zboz3)) $receipt->pouzit_zboz3 = doubleval($values->pouzit_zboz3);
        if(isset($values->urceno_cerp_zuct)) $receipt->urceno_cerp_zuct = doubleval($values->urceno_cerp_zuct);
        if(isset($values->cerp_zuct)) $receipt->cerp_zuct = doubleval($values->cerp_zuct);
        // @TODO rezim
        return $receipt;
    }

    /**
     * @return Dispatcher
     * @throws \Exception
     */
    private function getDispatcher()
    {
        $service = $this->settingModel->getValueByKey('service');

        switch($service)
        {
            case 'production':
            case 'playground':
                $certificate = new Certificate(
                    __DIR__.'/../../vendor/filipsedivy/php-eet/examples/EET_CA1_Playground-CZ00000019.p12',
                    'eet'
                );
                $dispatcher = new Dispatcher($certificate);
                $dispatcher->setPlaygroundService();
                break;

            default:
                throw new \Exception('Sluzba nebyla nalezna');
                break;
        }

        return $dispatcher;
    }

    /**
     * @TODO Je třeba upravit funkci
    */
    private function send(Receipt $receipt, $id = null, $parentID = null)
    {
        $dispatcher = $this->getDispatcher();
        $repeat = 0;
        if(!is_null($id))
        {
            $payment = $this->eetDataModel->getById($id);
            $repeat = $payment['Repeat'] + 1;
        }

        try {
            $dispatcher->send($receipt);

            if (is_null($id))
            {
                $this->eetDataModel->save($receipt, [
                    'fik' => $dispatcher->getFik(),
                    'bkp' => $dispatcher->getBkp()
                ]);
            } else {
                $this->eetDataModel->updateById($id, $receipt, array(
                    'fik' => $dispatcher->getFik(),
                    'bkp' => $dispatcher->getBkp()
                ), -1);
            }

            if(!is_null($parentID))
            {
                // Je navázáno storno
                exit($this->eetDataModel->getByFik($dispatcher->getFik()));
            }
            return true;
        }
        catch (EetException $ex)
        {
            if (is_null($id))
            {
                $this->eetDataModel->save($receipt, [
                    'bkp' => $dispatcher->getBkp(),
                    'pkp' => $dispatcher->getPkp()
                ]);
            } else {
                $this->eetDataModel->updateById($id, $receipt, array(
                    'pkp' => $dispatcher->getPkp(),
                    'bkp' => $dispatcher->getBkp()
                ), $repeat);
            }
        }
        catch (ClientException $ex)
        {
            if (is_null($id))
            {
                $this->eetDataModel->save($receipt, [
                    'bkp' => $dispatcher->getBkp(),
                    'pkp' => $dispatcher->getPkp()
                ]);
            } else {
                $this->eetDataModel->updateById($id, $receipt, array(
                    'pkp' => $dispatcher->getPkp(),
                    'bkp' => $dispatcher->getBkp()
                ), $repeat);
            }
        }
        return false;
    }
}
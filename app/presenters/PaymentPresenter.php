<?php

namespace App\Presenters;

use App\UI\Form;
use FilipSedivy\EET\Exceptions\ServerException;

class PaymentPresenter extends BasePresenter
{
    public function createComponentPayment()
    {
        $form = new Form();

        $items = $this->settingModel->getVisibleByGroup('setting');
        foreach ($items as $item)
        {
            $control = null;
            $translate = is_null($item->Translate) ? $item->Key : $item->Translate;
            $additionalData = json_decode($item->AdditionalData);

            switch ($item->Type)
            {
                case 'string':
                    $control = $form->addText($item->Key, $translate);
                    break;

                case 'numeric':
                    $control = $form->addIntegerDouble($item->Key, $translate);
                    break;
            }

            if (isset($additionalData->required) && $additionalData->required === true)
            {
                $control->setRequired('Položka \'' . $translate . '\' je povinná');
            }

            if ($item->Key === 'porad_cis')
            {
                $control->setDefaultValue($this->eetDataModel->lastId())
                    ->setAttribute('readonly');
            }
        }


        $form->addSubmit('pay', 'Zaevidovat platbu');

        $form->onSuccess[] = array($this, 'createPayment');

        return $form;
    }

    public function createPayment(Form $form)
    {
        try
        {
            $this->eetService->createPayment($form->getValues());
            $this->flashMessage('Tržba byla zaevidována', 'alert-success');
            $this->redirect('Homepage:default');
        }
        catch (ServerException $ex)
        {
            $this->flashMessage('Zpráva nebyla odeslána z důvodu: ' . $ex->getMessage(), 'alert-danger');
        }
    }

}

<?php

namespace App\Presenters;

use App\UI\Form;
use FilipSedivy\EET\Exceptions\ServerException;

class PaymentPresenter extends BasePresenter
{
    public function createComponentPayment()
    {
        $parameters = $this->context->parameters['eet_params'];

        $form = new Form();

        foreach($parameters as $xml => $object)
        {
            $visible = $this->settingModel->getValueByKey('visible_'.$xml);
            if($visible === 'hide') continue;

            $element = null;

            if(isset($object['items']))
            {
                // @TODO Dodělat výpis ITEMS
            }
            else
            {
                $element = $form->addText($xml, $object['caption']);
            }

            if(isset($object['required']))
            {
                $element->setRequired();
            }

            // -= Pořadové číslo =-
            if($xml === 'porad_cis')
            {
                $element->setDefaultValue($this->eetDataModel->lastId(true));
            }
        }

        $form->addSubmit('save', 'Uložit');

        $form->onSubmit[] = array($this, 'createPayment');

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

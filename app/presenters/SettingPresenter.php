<?php

namespace App\Presenters;

use Nette\Application\UI\Form;

class SettingPresenter extends BasePresenter
{


    public function createComponentElements()
    {
        $parameters = $this->context->parameters['eet_params'];

        $form = new Form();

        foreach($parameters as $xml => $object)
        {
            if(isset($object['required'])) { continue; }

            if(isset($object['items']))
            {
                $form->addRadioList($xml, $object['caption'], $object['items']);
            }
            else
            {
                $form->addRadioList($xml, $object['caption'], array(
                    'hide' => 'Skrýt',
                    'show' => 'Zobrazit'
                ));
            }
        }

        $form->addSubmit('save', 'Uložit');

        $form->onSubmit[] = array($this, 'submitElementsForm');

        return $form;
    }

    public function submitElementsForm(Form $form)
    {
        foreach($form->getValues() as $key => $value)
        {
            $this->settingModel->insert('visible_'.$key, $value);
        }
        $this->flashMessage('Nastavení elementů bylo uloženo', 'alert-success');
        $this->redirect('this');
    }

    /**
     * @param $key  string Klíč -> dic,...
     * @param $name string hide, show
     *
     * @return bool
     */
    public function checked($key, $name)
    {
        $value = $this->settingModel->getValueByKey($key);
        return (is_null($value) && $name == 'show') || $name === $value;
    }
}
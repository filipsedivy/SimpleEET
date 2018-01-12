<?php

namespace App\Presenters;

use Nette\Application\UI\Form;

class SettingPresenter extends BasePresenter
{

    public function actionLog()
    {
        static $allow_slugs = array('system', 'user');
        $slug = $this->getParameter('slug');

        if(!in_array($slug, $allow_slugs) || is_null($slug))
        {
            $this->redirect('this', array('slug' => 'user'));
        }
    }

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
     * @param $key string Klíč -> dic,...
     * @param $name string hide, show
     * @return bool
    */
    public function checked($key, $name)
    {
        $value = $this->settingModel->getValueByKey('visible_'.$key);
        return (is_null($value) && $name == 'show') OR $name === $value;
    }
}
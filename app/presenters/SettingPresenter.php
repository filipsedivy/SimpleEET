<?php

namespace App\Presenters;

use App\UI\Form;
use Nette\Utils\Html;

class SettingPresenter extends BasePresenter
{
    public function createComponentElements()
    {
        $parameters = $this->settingModel->getByGroup('setting');

        $form = new Form();

        foreach ($parameters as $parameter)
        {
            $additionalData = json_decode($parameter->AdditionalData);

            if (isset($additionalData->required) && $additionalData->required === true) continue;

            if (isset($additionalData->items))
            {
                $items = array();
                foreach ($additionalData->items as $key => $value)
                {
                    $items[$key] = $value;
                }

                $form->addRadioList(
                    $parameter->Key,
                    is_null($parameter->Translate) ? $parameter->Key : $parameter->Translate,
                    $items
                )->setDefaultValue($parameter->Value);
            }
            else
            {
                $caption = $parameter->Translate;

                if(is_null($caption))
                {
                    $caption = Html::el('code')
                        ->setAttribute('title', 'Překlad není k dispozici, a proto byl použit klíč')
                        ->setText($parameter->Key);
                }

                $form->addCheckbox(
                    $parameter->Key,
                    $caption
                )->setDefaultValue($parameter->Visible);
            }
        }

        $form->addSubmit('save', 'Uložit');

        $form->onSuccess[] = array($this, 'submitElementsForm');

        return $form;
    }

    public function submitElementsForm(Form $form)
    {
        foreach ($form->getValues() as $key => $value)
        {
            if (is_bool($value))
            {
                $this->settingModel->updateColumn('Visible', $key, +$value);
            }
            else
            {
                $this->settingModel->updateColumn('Value', $key, $value);
            }
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
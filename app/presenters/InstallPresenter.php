<?php

namespace App\Presenters;

use App\Model\Install;
use App\Model\Setting;
use Nette\Application\UI\Form;
use Nette\Database\Row;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

class InstallPresenter extends BasePresenter
{
    /** @var Install @inject */
    public $installModel;

    /** @var Setting @inject */
    public $settingModel;

    public function renderDefault()
    {
        $this->template->db_path = __DIR__ . '/../private/database.db';
    }

    public function createComponentInstallForm()
    {
        $form = new Form();

        $form->addRadioList('service', null, array(
            'playground' => Html::el('span')->addHtml(
                Html::el('b')->setText('Playground')
            )->addText(' - testovací prostředí'),

            'production' => Html::el('span')->addHtml(
                Html::el('b')->setText('Production')
            )->addText(' - ostré prostředí'),
        ));

        $eet_head = $form->addContainer('eet_head');
        $head = $this->installModel->getHead();

        foreach ($head as $row)
        {
            if ($row instanceof Row)
            {
                $eet_head->addText($row['XMLParam'], $row['Description'])
                    ->setAttribute('data-helper', $row['Helper']);
            }
        }

        $form->addSubmit('save', 'Uložit nastavení');

        $form->onSubmit[] = [$this, 'installSubmitForm'];

        return $form;
    }

    public function installSubmitForm(Form $form)
    {
        $values = $form->getValues();

        if ($values->service === 'production')
        {
            $this->flashMessage('Tato služba je nyní nefunkční', 'alert-danger');
            $this->redirect('this');

            foreach ($values->eet_head as $key => $value)
            {
                if (empty($value)) continue;
                $this->installModel->setValueHeadByXmlParam($key, $value);
            }
        }

        $this->settingModel->insert('service', $values->service, 'install', 'string');

        /** @TODO Možnost vybrat z formuláře */
        $this->settingModel->updateColumn('Value', 'rezim', 'bezny');

        $this->flashMessage('Systém byl nastaven', 'alert-success');
        $this->redirect('Homepage:default');
    }

}

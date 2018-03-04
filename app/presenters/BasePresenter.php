<?php

namespace App\Presenters;

use App\Model\EETData;
use App\Model\Install;
use App\Model\Setting;
use App\Services\EETService;
use Nette;
use Nette\Utils\Finder;
use Tracy\Debugger;
use Tracy\ILogger;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    /** @var Install */
    protected $install;

    /** @var EETData */
    protected $eetDataModel;

    /** @var Setting */
    protected $settingModel;

    /** @var EETService */
    protected $eetService;

    public function __construct(Install $install, EETData $eetData, Setting $setting, EETService $eetService)
    {
        parent::__construct();
        $this->install = $install;
        $this->eetDataModel = $eetData;
        $this->settingModel = $setting;
        $this->eetService = $eetService;
    }

    public function beforeRender()
    {
        try
        {
            if (!$this->install->isInstalled() && $this->getAction(true) !== ':Install:default')
            {
                $this->flashMessage('Nastavte prosím vstupní soubory a data', 'alert-info');
                $this->redirect('Install:default');
            }
            elseif ($this->install->isInstalled() && $this->getAction(true) === ':Install:default')
            {
                $this->flashMessage('Systém již je nainstalován', 'alert-warning');
                $this->redirect('Homepage:default');
            }
        }
        catch (\Exception $e)
        {
            if ($e instanceof Nette\Application\AbortException)
            {
                throw $e;
            }

            $this->showError($e);
        }

        $this->template->installed = $this->install->isInstalled();
        $this->template->service = $this->settingModel->getValueByKey('service');

        $parameters = $this->context->getParameters();
        $this->template->version = $parameters['version'];
    }

    /**
     * @return bool|string
     */
    protected function getCertificationPath()
    {
        $finder = Finder::findFiles('*.p12')->in(__DIR__ . '/../private');
        if ($finder->count() === 0) return false;
        foreach ($finder as $key => $file)
        {
            return $key;
        }
        return false;
    }

    private function showError(\Exception $e)
    {
        Debugger::log($e, ILogger::EXCEPTION);
        $errorOptions = array(
            'database' => 'Databáze je poškozena'
        );

        foreach ($errorOptions as $errorOption => $reason)
        {
            if (preg_match('#' . $errorOption . '#', $e->getMessage()) ||
                preg_match('#' . $errorOption . '#', $e->getFile())
            )
            {
                $this->template->reason = $reason;
                break;
            }
        }

        $this->template->setFile(__DIR__ . '/templates/Error/Error.latte');
        $this->sendTemplate();
    }
}

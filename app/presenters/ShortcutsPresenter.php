<?php

namespace App\Presenters;

use Tracy\Debugger;

class ShortcutsPresenter extends BasePresenter
{
    public function renderResend()
    {
        $this->template->payments = $this->eetService->unsendPayments()->count();
    }

    public function handleRun()
    {
        $unsendPayments = $this->eetService->unsendPayments()->fetchAll();
        $statsSuccess = 0;
        $statsUnuccess = 0;
        foreach($unsendPayments as $unsendPayment)
        {
            $payment = null;

            try
            {
                $payment = $this->eetService->resendPayment($unsendPayment['ID']);
            }
            catch(\Exception $e)
            {
                Debugger::log($e);
                $this->flashMessage('Nastala chyba v sub-systému pro znovuzaslání plateb', 'alert-danger');
                $this->redirect('Homepage:default');
            }

            if($payment) $statsSuccess++;
            else $statsUnuccess++;
        }

        $this->flashMessage('Platby byly znovu odeslány. Úspěšných: '.$statsSuccess.', Neúspěšných: '.$statsUnuccess,
            'alert-success');
        $this->redirect('Homepage:default');
    }

    public function handleStorno()
    {
        $id = $this->getParameter('id');
        try{
            $this->eetService->stornoByIdPayment($id);
            $this->flashMessage('Položka byla stornovaná', 'alert-success');
        }
        catch (\Exception $ex)
        {
            $this->flashMessage($ex->getMessage(), 'alert-danger');
        }
        finally
        {
            $this->redirect('History:default');
        }
    }
}
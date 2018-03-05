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
            $paymentId = $unsendPayment['ID'];

            try
            {
                $payment = $this->eetService->resendPayment($paymentId);
                if($payment)
                {
                    $statsSuccess++;
                }
            }
            catch(\Exception $e)
            {
                Debugger::log($e);
                $this->eetService->updateExceptionByPaymentId($paymentId, $e);
                $statsUnuccess++;
            }
        }

        $this->flashMessage('Platby byly znovu odeslány. Úspěšných: '.$statsSuccess.', Neúspěšných: '.$statsUnuccess,
            'alert-info');
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
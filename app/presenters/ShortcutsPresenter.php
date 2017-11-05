<?php

namespace App\Presenters;

class ShortcutsPresenter extends BasePresenter
{
    public function handleRun()
    {
        $unsendPayments = $this->eetService->unsendPayments()->fetchAll();
        $statsSuccess = 0;
        $statsUnuccess = 0;
        foreach($unsendPayments as $unsendPayment)
        {
            $payment = $this->eetService->resendPayment($unsendPayment['ID']);
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
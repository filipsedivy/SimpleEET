<?php

namespace App\Presenters;


class HomepagePresenter extends BasePresenter
{
    public function renderDefault()
    {
        $this->template->payments = $this->eetService->count();
        $this->template->sum = $this->eetService->sum();
        $this->template->unpaid = $this->eetService->unsendPayments()->count();
    }

}

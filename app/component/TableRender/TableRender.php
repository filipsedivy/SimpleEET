<?php

namespace App\Component;

use Nette\Application\UI\Control;

class TableRender extends Control
{
    public function render($data)
    {
        $template = $this->template;
        $template->setFile(__DIR__.'/template.latte');
        $template->data = $this->fixData($data);
        $template->render();
    }

    /** @return array */
    private function fixData($data)
    {
        $output = array();
        foreach($data as $key => $value)
        {
            // DateTime objekt
            if($value instanceof \DateTime)
            {
                /** @var \DateTime $value */
                $value = $value->format('j. n. Y H:i:s');
            }
            // Binární vstup
            elseif(preg_match('~[^\x20-\x7E\t\r\n]~', $value) > 0)
            {
                $value = base64_encode($value);
            }
            $output[$key] = $value;
        }
        return $output;
    }
}
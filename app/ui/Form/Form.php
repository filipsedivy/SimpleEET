<?php

namespace App\UI;

use App\UI\Form\Render;
use Nette;
use Nette\Forms\Controls;

class Form extends Nette\Application\UI\Form
{

    /**
     * @param Nette\ComponentModel\IContainer|null $parent
     * @param string|null                          $name
     */
    public function __construct(Nette\ComponentModel\IContainer $parent = null, $name = null)
    {
        parent::__construct($parent, $name);
        $this->setRenderer(new Render());
    }


    /**
     * @param string      $name
     * @param string|null $label
     *
     * @return Controls\TextInput
     */
    public function addIntegerDouble($name, $label = null)
    {
        static $pattern = '\\d+(\\.\\d+)?';
        static $message = 'Vstupní hodnota musí být celé nebo desetinné číslo';

        return $this[$name] = (new Controls\TextInput($label))
            ->setRequired(false)
            ->addRule($this::PATTERN, $message, $pattern);
    }

}
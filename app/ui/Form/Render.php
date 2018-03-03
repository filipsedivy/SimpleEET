<?php

namespace App\UI\Form;

use Nette;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Forms\Controls;

class Render extends DefaultFormRenderer
{
    public $wrappers = array(
        'form'     => array(
            'container' => null,
        ),
        'error'    => array(
            'container' => 'div class="alert alert-danger"',
            'item'      => 'p',
        ),
        'group'    => array(
            'container'   => 'fieldset',
            'label'       => 'legend',
            'description' => 'p',
        ),
        'controls' => array(
            'container' => 'div',
        ),
        'pair'     => array(
            'container' => 'div class=form-group',
            '.required' => 'required',
            '.optional' => null,
            '.odd'      => null,
            '.error'    => 'has-error',
        ),
        'control'  => array(
            'container'      => 'div class=col-sm-9',
            '.odd'           => null,
            'description'    => 'span class=help-block',
            'requiredsuffix' => '',
            'errorcontainer' => 'span class=help-block',
            'erroritem'      => '',
            '.required'      => 'required',
            '.text'          => 'text',
            '.password'      => 'text',
            '.file'          => 'text',
            '.submit'        => 'btn btn-success ',
            '.image'         => 'imagebutton',
            '.button'        => 'button',
        ),
        'label'    => array(
            'container'      => 'div class="col-sm-3 control-label"',
            'suffix'         => null,
            'requiredsuffix' => '',
        ),
        'hidden'   => array(
            'container' => 'div',
        ),
    );

    public function render(Nette\Forms\Form $form, $mode = null)
    {
        $form->getElementPrototype()->addClass('form-horizontal');

        foreach ($form->getControls() as $control)
        {
            if ($control instanceof Controls\TextBase ||
                $control instanceof Controls\SelectBox ||
                $control instanceof Controls\MultiSelectBox)
            {
                $control->getControlPrototype()->addClass('form-control');
            }
            elseif ($control instanceof Controls\Checkbox ||
                $control instanceof Controls\CheckboxList ||
                $control instanceof Controls\RadioList)
            {
                $control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
            }
        }

        return parent::render($form, $mode);
    }
}
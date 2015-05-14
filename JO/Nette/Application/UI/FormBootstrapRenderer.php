<?php

namespace JO\Nette\Application\UI;

use Nette\Forms\Form;
use Nette\Forms\Rendering\DefaultFormRenderer;

/**
 * Description of FormBootstrapRenderer
 *
 * Form renderer for twitter bootstrap
 *
 * @author Jan Oliva
 */
class FormBootstrapRenderer extends DefaultFormRenderer
{

	protected $bootstraped = false;

	protected function setupWrapers()
	{
		$this->wrappers['form']['container'] = "div class=row";
		$this->wrappers['group']['container'] = "div  class=col-lg-5";
		$this->wrappers['group']['label'] = "h1 class='label label-primary form-gruop-label' ";
		$this->wrappers['group']['description'] = 'strong';

	}

	public function render(Form $form, $mode = NULL)
	{

		if ($this->form !== $form) {
			$this->form = $form;

		}
		$this->setupWrapers();
		$this->fieldsBootstraping($this->form);
		$ret = parent::render($form, $mode);
		return $ret;
	}

	/**
	 * Add required css classes
	 * @param \Nette\Forms\Form $form
	 *
	 */
	public function fieldsBootstraping(Form $form)
	{
		if($this->bootstraped){
			return;
		}

		foreach ($form->getControls() as $control) {
			/* @var $control \Nette\Forms\Controls\TextInput */

			$el = $control->getControlPrototype();
			/* @var $el \Nette\Utils\Html */
			if ($control instanceof \Nette\Forms\Controls\TextInput || $control instanceof \Nette\Forms\Controls\SelectBox || $control instanceof \Nette\Forms\Controls\TextArea ) {
				//$control->setAttribute('class', 'form-control');
				$control->getControlPrototype()->class[] = 'form-control';
			} elseif ($control instanceof \Nette\Forms\Controls\Button) {

				$control->getControlPrototype()->class[] = 'btn';
				$control->getControlPrototype()->class[] = 'btn-default';

			}

			//btn btn-default
		}
		$this->bootstraped = true;
	}
}

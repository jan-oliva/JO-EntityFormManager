<?php

namespace JO\Nette\Application\UI;

use Nette\Application\UI\Form;
use Nette\ComponentModel\IContainer;
use Nette\DI\IContainer as IDIContainer;

/**
 * Description of FormBuilder
 *
 * @author Jan Oliva
 */
class FormFactory
{

	const SUBMIT_ADD = 'submit_add';
	const SUBMIT_EDIT = 'submit_edit';
	const SUBMIT_STORNO = 'submit_storno';

	/**
	 *
	 * @var
	 */
	protected $context;

	/**
	 *
	 * @var Form
	 */
	protected $form;

	/**
	 *
	 * @param Nette\DI\IContainer $context
	 * @param Form $form - pokud neni predan je vytvoren
	 * @param IContainer $parent - ignorovano, pokud je predan form
	 * @param string $name - ignorovano, pokud je predan form
	 */
	function __construct($context,Form $form=null,  IContainer $parent=null,$name=null)
	{
		$this->context = $context;
		if(!$form instanceof Form){
			$form = new Form($parent,$name);
			$this->inject($form);
		}
		$this->form = $form;

		//pridani metody addDatePicker
		Form::extensionMethod('addDatePicker', function(Form $_this, $name, $label, $cols = NULL, $maxLength = NULL){
			$control = new \Nette\Forms\Controls\TextInput($label,$maxLength);
			$control->getControlPrototype()->class[] = 'bootstrap-datepicker';
			return $_this[$name] = $control;
		});

		\Nette\Forms\Container::extensionMethod('addDatePicker', function(\Nette\Forms\Container $_this, $name, $label, $cols = NULL, $maxLength = NULL){
			$control = new \Nette\Forms\Controls\TextInput($label,$maxLength);
			$control->getControlPrototype()->class[] = 'bootstrap-datepicker';
			return $_this[$name] = $control;
		});
	}

	public function getForm()
	{
		return $this->form;
	}

	public function inject(Form $form)
	{
		$translator = $this->context->getService('ITranslator');
		if($translator instanceof \Nette\Localization\ITranslator){
			$form->setTranslator($translator);

		}
	}
}

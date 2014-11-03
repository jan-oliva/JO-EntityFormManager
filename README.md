# Form manager Nette Forms

Tested on nette kdyby doctrine

## Description

Fuctions :

* register form fields to nette form
* fill form field labels
* create entity from form data
* modify entity by form data
* create entity form associated fields
* automatically generate required field text by tpl " some str %_caption_% ... "

### field registration

Rules
* @ORM\ManyToOne(targetEntity="Entity\Company\CompanyProduction") for associated entity fields register select box.
* @ORM\Column(type="date") - register date column
* @ORM\Column(type="text") - regiter text area
* @ORM\Column(type="boolean") - register check box
* other - register input text

Automatically generated from labels by #formLabel="some label"
```
	**
	 * #formLabel="materiál" UI form label
	 *
	 * @ORM\ManyToOne(targetEntity="\Entity\Jobs\Material")
	 * @ORM\JoinColumn(name="material_id",referencedColumnName="id",nullable=false,onDelete="CASCADE")
	 *
	 */
```

### interface description

	 /**
	 * Creates entity and fill fomm values
	 * @param array $excludeInputs
	 * @param \Nette\Forms\Container $container
	 * @return object Entity
	 */
	public function createEntityFromForm($excludeInputs = array(), \Nette\Forms\Container $container = null)

	/**
	 * Register form fields by entity.
	 * For associed fileds creates input select
	 * For boolean fields creates checkbox
	 * For datetime fields try call addDatePicker for show date picker
	 * For other columns creates input text
	 *
	 * @param array $exclude
	 * @param bool $itemsIsWhitelist - determine wheter items are white list or blacklist
	 * @param \Nette\Forms\Container $container If given, fields will be registered into container
	 * @param bool $autoRequire  - allow automatically set required fields with message (EntityFormManager::setRequiredMsgTpl())
	 */
	public function createFields($items = array(), $itemsIsWhitelist = false, \Nette\Forms\Container $container = null,$autoRequire=false)

	/**
	 * Fill entity by form values
	 * Attention - Fill only fields which has noassociation and scalar values
	 *
	 * @param object $entity
	 * @param array $excludeInputs
	 * @return object Entity
	 */
	public function fillEntityFromForm($entity, $excludeInputs = array(), \Nette\Forms\Container $container = null)

	/**
	 * Fill form values from given entity.
	 *
	 * Accept
	 *  - scalar  values
	 *  - \DateTime object
	 * Other typs are skipped and form fileds not filled
	 *
	 * @param object $entity
	 * @param \Nette\Forms\Container $container
	 * @param bool $fillAssoc fill fields with association
	 * @param string $locale - locale for \DateTime object. Example cs_CZ|en_US|sk_SK|hu_HU ...
	 *
	 */
	public function fillFormFromEntity($entity, \Nette\Forms\Container $container = null, $fillAssoc = true, $locale = 'cs_CZ')

	/**
	 * Fill form field with association to other entity.
	 * Usage for example on selectbox, id will filled form given entity.
	 * Given entity class will be checked oposite to associated target entity.
	 * On target entity is called method getId()
	 *
	 * @param string $elementName
	 * @param object $entity - doctrine entity
	 * @param \Nette\Forms\Container $container
	 */
	public function fillFormFromEntityAssoc($elementName,$entity, \Nette\Forms\Container $container = null)

	/**
	 *
	 * @param string $elementName
	 * @param \Nette\Forms\Container $container
	 * @return type
	 */
	public function getElementValue($elementName,\Nette\Forms\Container $container=null)

	/**
	 * Return target entity instance of associated property.
	 * Value of form (or Container) element is used as id of target entity.
	 * Internaly use EntityManager::find(targetEnityClass,elemet::getValue())
	 *
	 * @param string $elementName
	 * @param \Nette\Forms\Container $container
	 * @return type
	 */
	public function getEntityFromAssoc($elementName, \Nette\Forms\Container $container = null)


	/**
	 *
	 * @param string $prefix
	 * @param array $exclude
	 */
	public function getFormCols($exclude = array())

	/**
	 * Return form or container element
	 * @param string $elementName - element name without prefix
	 * @param \Nette\Forms\Container $container
	 * @return \Nette\Forms\Controls\BaseControl
	 */
	public function getFormElement($elementName,\Nette\Forms\Container $container=null)

	/**
	 * Set value of form element
	 * @param string $elementName - element name without prefix
	 * @param mixed $value
	 * @param \Nette\Forms\Container $container
	 * @return \JO\Nette\Doctrine\EntityFormManager
	 */
	public function setElementValue($elementName,$value,\Nette\Forms\Container $container=null)

	/**
	 * Template for reuired  fields
	 *
	 * @param string $requiredTpl - " some str %_caption_% ... "
	 * @return \JO\Nette\Doctrine\EntityFormManager
	 */
	public function setRequiredMsgTpl($requiredTpl)

### Form manager and form builder Example

Doctrine entity

```
/**
 * Surcharge fro material
 *
 * @ORM\Entity(repositoryClass="MaterialSurchargeRepository")
 * @ORM\Table(name="`jobsMaterialSurcharge`")
 *
 *
 * @author Jan Oliva
 */
class MaterialSurcharge extends \Entity\BaseEntity
{
	/**
	 * #formLabel="materiál"
	 * @ORM\ManyToOne(targetEntity="\Entity\Jobs\Material")
	 * @ORM\JoinColumn(name="material_id",referencedColumnName="id",nullable=false,onDelete="CASCADE")
	 *
	 */
	protected $material;

	/**
	 * #formLabel="přirážka %"
	 * @ORM\Column(type="float",nullable=false)
	 */
	protected $surcharge;

	/**
	 * #formLabel="firma"
	 * @ORM\ManyToOne(targetEntity="Entity\Company\CompanyProduction")
	 * @ORM\JoinColumn(name="companyProduction_id",referencedColumnName="id",nullable=false)
	 *
	 */
	protected $companyProduction;

	/**
	 * #formLabel="poznámka"
	 * @ORM\Column(type="text",nullable=true)
	 */
	protected $note;

}
```

Form builder class

```
<?php
use JO\Nette\Application\UI\FormBootstrapRenderer;
use JO\Nette\Application\UI\FormFactory;
use JO\Nette\Doctrine\EntityFormManager;

/**
 * Description of FormBuilder
 *
 * @author Jan Oliva
 */
class FormBuilder extends FormFactory
{
	public function registerFields()
	{
		//instance of form manager
		$man = new EntityFormManager($this->form,'\Entity\Jobs\MaterialSurcharge, $this->context->EntityManager);

		//register fields of entity
		$man->createFields(array('surcharge','companyProduction','note'), $itemsIsWhitelist=true,$container=null,$autoRequire=true);

		$man->getFormElement('surcharge')
				->addRule(\Nette\Forms\Form::FLOAT,"Vyplňte pole přirážka % prosím");

		//get form element by form manager
		$companySelect = $man->getFormElement('companyProduction');
		/* @var $companySelect \Nette\Forms\Controls\SelectBox */

		$companySelect
				->setRequired("Vyber firmu");

		return $this->getForm();
	}

}
```

Presenter

````
use JO\Nette\Application\UI\FormBootstrapRenderer;
use JO\Nette\Application\UI\FormFactory;
use JO\Nette\Doctrine\EntityFormManager;

	/**
	 *
	 * @var EntityFormManager
	 */
	protected $formManager;

	protected $entityName = '\Entity\Jobs\MaterialSurcharge';

protected function createComponentMaterialSurchargeForm($name)
{
		$builder = new FormBuilder($this->context, new Form($this, $name));
		$form = $builder->registerFields();
		return $form;
}

public function actionAdd($material_id=null)
{
		$form = $this->getComponent('materialSurchargeForm');
		/* @var $form Form */

		//create instance of entity form manager
		$this->formManager = new EntityFormManager($form, $this->entityName, $this->entityManager);

		$form->addSubmit(FormBuilder::SUBMIT_ADD, "add surcharge")
				->onClick[] = callback($this,'add');

		some other code
}

/**
 * Form callback
 * Create entity from form data and save entity
 */
public function add(SubmitButton $button)
{
		//create entity of asscociated field by association metadata of entity
		$company = $this->formManager->getEntityFromAssoc('companyProduction',$button->getForm());
		/* @var $company \Entity\Company\CompanyProduction */

		//create entity from submited form
		$surcharge = $this->formManager->createEntityFromForm();
		/* @var $surcharge MaterialSurcharge */

		$surcharge
				->setMaterial($material)
				->setCompanyProduction($company);

		//save entity now
}

```
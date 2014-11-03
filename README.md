# Form manager Nette Forms

Tested on nette kdydby doctrine




## Form manager and form builder Example

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
				->addRule(\Nette\Forms\Form::FLOAT,"Fill surcharge");

		//get form element by form manager
		$companySelect = $man->getFormElement('companyProduction');
		/* @var $companySelect \Nette\Forms\Controls\SelectBox */

		$companySelect
				->setRequired("Choose company");

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
		$this->formManager = $this->getFormManager($form, $this->entityName);

		$form->addSubmit(FormBuilder::SUBMIT_ADD, "add surcharge")
				->onClick[] = callback($this,'add');

		some other code
}

/**
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


BasePresenter
	/**
	 *
	 * @param Form $form
	 * @param string $entitytClassName
	 * @return EntityFormManager
	 */
	protected function getFormManager($form,$entitytClassName,$prefix=  EntityFormManager::FORM_FIELD_PREFIX)
	{
		$man = new EntityFormManager($form, $entitytClassName, $this->entityManager,$prefix);
		$man->setRequiredMsgTpl("Vyplňte ".EntityFormManager::REQUIRE_VAR." prosím");
		return $man;
	}

```
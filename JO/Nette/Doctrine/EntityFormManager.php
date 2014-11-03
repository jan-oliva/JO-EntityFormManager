<?php

namespace JO\Nette\Doctrine;

use Doctrine\ORM\EntityManager;
use Nette\Application\UI\Form;
use Nette\Diagnostics\Debugger;

/**
 * Description of EntityFormManager
 *
 * Creates form elements by entity fileds.
 * Creates form labels by #formLabel="xxx"
 *
 * Example
 *  @column(type="integer") #formLabel="Unit amount"
 * 	protected $count;
 *
 * Create0 instance of entity a and fill them by form values.
 * Don't fill fields with asociations.
 *
 * Works with extension Kdyby/Doctrine
 * @see http://travis-ci.org/Kdyby/Doctrine
 *
 * @author Jan Oliva
 */
class EntityFormManager
{

	const FORM_FIELD_PREFIX = 'F_';
	const REQUIRE_VAR = '%_caption_%';
	const DFL_REQUIRE_MSG_TPL = "Fill %_caption_% please";

	/**
	 *
	 * @var Form
	 */
	protected $form;

	/**
	 *
	 * @var string
	 */
	protected $entity;

	/**
	 *
	 * @var \ReflectionClass
	 */
	protected $reflection;

	/**
	 *
	 * @var string
	 */
	protected $formFieldPrefix;

	/**
	 *
	 * @var EntityManager
	 */
	protected $em;

	protected $requiredMsgTpl = self::DFL_REQUIRE_MSG_TPL;

	/**
	 *
	 * @var \Doctrine\ORM\Mapping\ClassMetadata
	 */
	protected $metaData;
	private $dateType = \IntlDateFormatter::MEDIUM;
	private $timeType = \IntlDateFormatter::NONE;
	private $timeZone = 'Europe/Prague';

	/**
	 *
	 * @param \Nette\Application\UI\Form $form
	 * @param object $entity
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param string $formFieldPrefix - prefix pro nazev pole ve formulari. DFL 'F_'
	 * @throws \BadMethodCallException
	 */
	function __construct(Form $form, $entity, EntityManager $em, $formFieldPrefix = self::FORM_FIELD_PREFIX)
	{
		$this->form = $form;
		if (!$this->isEntity($entity)) {
			throw new \BadMethodCallException("Second argumet '{$entity}' is not valid Doctrine Entiry class");
		}
		$this->entity = $entity;
		$this->em = $em;
		$this->formFieldPrefix = $formFieldPrefix;
		$this->metaData = $this->em->getClassMetadata($this->entity);
	}

	/**
	 * Template for reuired  fields
	 *
	 * @param string $requiredTpl - " some str %_caption_% ... "
	 * @return \JO\Nette\Doctrine\EntityFormManager
	 */
	public function setRequiredMsgTpl($requiredTpl)
	{
		$this->requiredMsgTpl = $requiredTpl;
		return $this;
	}


	private function isEntity($entity)
	{
		$this->reflection = new \ReflectionClass($entity);
		$comment = $this->reflection->getDocComment();
		$stdEntity = (bool) strpos($comment, '@entity');
		$ormEntity = (bool) strpos($comment, '@ORM\Entity');
		$ormEntity1 = (bool) strpos($comment, '@Doctrine\ORM\Mapping\Entity');

		return $stdEntity || $ormEntity || $ormEntity1;
	}

	/**
	 *
	 * @param string $prefix
	 * @param array $exclude
	 */
	public function getFormCols($exclude = array())
	{
		$props = $this->reflection->getProperties();
		$ret = array();

		foreach ($props as $prop) {
			/* @var $prop \ReflectionProperty */
			if ($prop->isProtected() && !in_array($prop->getName(), $exclude)) {

				$ret[] = $prop;
			}
		}
		return $ret;
	}

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
	{
		$object = ($container instanceof \Nette\Forms\Container) ? $container : $this->form;

		//prepare fields for iterate
		$entityFields = array();
		//associed filed  data->relace
		$assoc = $this->metaData->getAssociationNames();
		if (!empty($assoc)) {
			$tmp = array_combine($assoc, $assoc);
			$entityFields += $tmp;
		}
		//base fields
		$std = $this->metaData->getFieldNames();
		if (!empty($std)) {
			$tmp1 = array_combine($std, $std);
			$entityFields += $tmp1;
		}

		if ($itemsIsWhitelist) { //whitelist
			$itemsTmp = array_combine($items, $items);
			//intersect required and real entity fields. Skip non existing fields
			$fields = array_intersect_key($itemsTmp, $entityFields);
		} else { //blacklist
			//will iterate all entity fields
			$fields = $entityFields;
		}

		//columns of db table
		foreach ($fields as $prop) {
			$fieldName = $this->formFieldPrefix . $prop;
			//white list|black list property
			if (in_array($prop, $items) !== $itemsIsWhitelist) {
				continue;
			}
			$caption = $this->parseFormLabel($prop);
			//for associed fields creates select box
			if ($this->metaData->hasAssociation($prop)) {
				$el = $object->addSelect($fieldName, $caption);
				$this->makeElementRequired($el, $prop, $autoRequire,$caption);
				continue;
			}

			$fieldType = $this->metaData->getTypeOfColumn($prop);
			//elemets by data type
			switch ($fieldType) {
				case 'boolean' :
					$el = $object->addCheckbox($fieldName, $caption);
					$this->makeElementRequired($el, $prop, $autoRequire,$caption);
					break;
				case 'date' :
				case 'datetime' :
					if (is_callable(array($this->form, 'addDatePicker'))) {
						//rozsireni zakladniho nette formulare v JO\Nette\Application\UI\FormFactory
						$object->addDatePicker($fieldName, $caption)
										->getControlPrototype()->attrs['size'] = 10;
					} else {
						//zakladni form bez metodu addDatePicker
						$object->addText($fieldName, $caption)
										->getControlPrototype()->attrs['size'] = 10;
					}
					$this->makeElementRequired($object[$fieldName], $prop, $autoRequire,$caption);
					break;
				case 'text':
					$el = $object->addTextArea($fieldName, $caption);
					$this->makeElementRequired($el, $prop, $autoRequire,$caption);
					break;
				default:
					$el = $object->addText($fieldName, $caption);
					$this->makeElementRequired($el, $prop, $autoRequire,$caption);
					break;
			}
		}
	}

	private function makeElementRequired(\Nette\Forms\Controls\BaseControl $object,$prop,$autoRequire,$caption)
	{
		if($autoRequire && $this->getAttrIsRequired($prop)){
			$object->setRequired(str_replace(self::REQUIRE_VAR, $caption, $this->requiredMsgTpl));
		}
	}

	private function getAttrIsRequired($prop)
	{
		if($this->metaData->hasAssociation($prop)){
			//\Nette\Diagnostics\Debugger::barDump($this->metaData->getAssociationMapping($prop), __METHOD__, array(\Nette\Diagnostics\Dumper::DEPTH => 5));
			//u assoc prop to zatim neni implmentovano
			return false;
		}
		$mappingAttrs = $this->metaData->getFieldMapping($prop);

		if(array_key_exists('nullable', $mappingAttrs) && $mappingAttrs['nullable'] === true){
			return false;
		}
		return true;
	}

	private function isExcluded($item, $exclude)
	{
		return in_array($item, $exclude);
	}

	/**
	 * Find annotation #formLabel="UI form label of field"
	 * @param type $prop
	 * @return string
	 */
	private function parseFormLabel($prop)
	{
		$rp = $this->metaData->getReflectionProperty($prop);
		/* @var $rp \ReflectionProperty */
		$comment = $rp->getDocComment();
		if (preg_match('/#formLabel="(.*)"/', $comment, $matches)) {
			return $matches[1];
		}
		return $this->formFieldPrefix . $prop;
	}

	/**
	 * Creates entity and fill fomm values
	 * @param array $excludeInputs
	 * @param \Nette\Forms\Container $container
	 * @return object Entity
	 */
	public function createEntityFromForm($excludeInputs = array(), \Nette\Forms\Container $container = null)
	{
		$entity = $this->reflection->newInstance();
		return $this->fillEntityFromForm($entity, $excludeInputs, $container);
	}

	/**
	 * Fill entity by form values
	 * Attention - Fill only fields which has noassociation and scalar values
	 *
	 * @param object $entity
	 * @param array $excludeInputs
	 * @return object Entity
	 */
	public function fillEntityFromForm($entity, $excludeInputs = array(), \Nette\Forms\Container $container = null)
	{
		$instance = $entity;
		$object = ($container instanceof \Nette\Forms\Container) ? $container : $this->form;
		foreach ((array) $object->getValues() as $key => $val) {

			if ($this->isExcluded($key, $excludeInputs)) {
				continue;
			}
			//form field name
			$prop = str_replace($this->formFieldPrefix, '', $key);
			if ($this->metaData->hasAssociation($prop)) {
				continue;
			}
			//no scalar values are not supported now
			if (!is_scalar($val)) {
				continue;
			}

			//fom can have fields for more entities
			$keyTest = str_replace($this->formFieldPrefix, '', $key);
			$method = $this->composeSetterName($keyTest);

			if (!$this->metaData->hasField($keyTest)) {
				continue;
			}
			$type = $this->metaData->getTypeOfColumn($keyTest);

			//datetime musi byt instance DateTime nebo null
			if (($type === 'datetime' || $type === 'date') && $val === '') {
				$val = null;
			} elseif (($type === 'datetime' || $type === 'date') && $val !== '') {
				$val = new \DateTime($val);
			}
			//call entity setter
			if (is_callable(array($instance, $method))) {
				call_user_func(array($instance, $method), $val);
			}
		}
		return $instance;
	}

	private function itemName2prop($key)
	{
		return str_replace($this->formFieldPrefix, '', $key);
	}

	private function nameHasPrefix($name)
	{
		return preg_match("/^{$this->formFieldPrefix}/", $name);
	}


	private function composeGetterName($prop)
	{
		return $method = "get" . ucfirst($prop);
	}

	private function composeSetterName($prop)
	{
		return $method = "set" . ucfirst($prop);
	}

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
	{
		$object = ($container instanceof \Nette\Forms\Container) ? $container : $this->form;
		foreach ($object->getControls() as $element) {
			/* @var $element \Nette\Forms\Controls\BaseControl */
			$prop = $this->itemName2prop($element->getName());
			$getter = $this->composeGetterName($prop);

			if ($element instanceof \Nette\Forms\Controls\SubmitButton) {
				continue;
			}

			//data from assciated entity
			if($fillAssoc){
				$this->fillFormFromEntityAssoc($element->getName(), $entity, $container);
			}
			//data form entity
			if (!$this->metaData->hasField($prop)) {

				continue;
			}

			if (is_callable(array($entity, $getter))) {

				$val = call_user_func(array($entity, $getter));
				//\Nette\Diagnostics\Debugger::barDump($val);
				if ($val instanceof \DateTime) {
					$fmt = new \IntlDateFormatter($locale, $this->dateType, $this->timeType, $this->timeZone);
					$element->setValue($fmt->format($val));
				} elseif (is_scalar($val)) {
					$element->setValue($val);
				}
			}
		}
	}

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
	{
		$object = ($container instanceof \Nette\Forms\Container) ? $container : $this->form;
		//try to use element name with form fileld prefix
		if(!isset($object[$elementName]) && !$this->nameHasPrefix($elementName)){
			$elementName = $this->formFieldPrefix."".$elementName;
		}
		$element = $object[$elementName];
		$prop = $this->itemName2prop($element->getName());
		if ($this->metaData->hasAssociation($prop)) {
			$relClass = $this->metaData->associationMappings[$prop]['targetEntity'];
			if ($entity->{$prop} instanceof $relClass) {
				$e1 = $entity->{$prop};
				/* @var $e1 ClassName */
				if (is_callable(array($e1, 'getId'))) {
					$element->setValue($e1->getId());
					return true;
				}
				return false;
			}
		}
		return false;
	}

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
	{
		$object = ($container instanceof \Nette\Forms\Container) ? $container : $this->form;
		$elementNamePref = $this->formFieldPrefix."".$elementName;
		$element = $object[$elementNamePref];
		$prop = $this->itemName2prop($element->getName());

		$relClass = $this->metaData->associationMappings[$prop]['targetEntity'];

		return $this->em->find($relClass, $this->getElementValue($elementName,$container));
	}

	/**
	 * Return form or container element
	 * @param string $elementName - element name without prefix
	 * @param \Nette\Forms\Container $container
	 * @return \Nette\Forms\Controls\BaseControl
	 */
	public function getFormElement($elementName,\Nette\Forms\Container $container=null)
	{
		$object = ($container instanceof \Nette\Forms\Container) ? $container : $this->form;
		//compose elemnt name with prefix prefix
		$elementName = $this->formFieldPrefix."".$elementName;

		return $object[$elementName];
	}

	/**
	 * Set value of form element
	 * @param string $elementName - element name without prefix
	 * @param mixed $value
	 * @param \Nette\Forms\Container $container
	 * @return \JO\Nette\Doctrine\EntityFormManager
	 */
	public function setElementValue($elementName,$value,\Nette\Forms\Container $container=null)
	{
		$this->getFormElement($elementName, $container)->setValue($value);
		return $this;
	}

	/**
	 *
	 * @param string $elementName
	 * @param \Nette\Forms\Container $container
	 * @return type
	 */
	public function getElementValue($elementName,\Nette\Forms\Container $container=null)
	{
		return $this->getFormElement($elementName, $container)->getValue();
	}
}

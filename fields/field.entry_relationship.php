<?php
	/*
	Copyright: Deux Huit Huit 2014
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/class.field.php');
	require_once(EXTENSIONS . '/entry_relationship_field/lib/class.cacheablefetch.php');

	/**
	 *
	 * Field class that will represent relationships between entries
	 * @author Deux Huit Huit
	 *
	 */
	class FieldEntry_relationship extends Field
	{

		/**
		 *
		 * Name of the field table
		 *  @var string
		 */
		const FIELD_TBL_NAME = 'tbl_fields_entry_relationship';

		/**
		 *
		 * Separator char for values
		 *  @var string
		 */
		const SEPARATOR = ',';


		/**
		 *
		 * Current recursive level of output
		 *  @var int
		 */
		protected $recursiveLevel = 1;

		/**
		 *
		 * Parent's maximum recursive level of output
		 *  @var int
		 */
		protected $recursiveDeepness = null;

		/**
		 *
		 * Constructor for the oEmbed Field object
		 */
		public function __construct()
		{
			// call the parent constructor
			parent::__construct();
			// set the name of the field
			$this->_name = __('Entry Relationship');
			// permits to make it required
			$this->_required = true;
			// permits the make it show in the table columns
			$this->_showcolumn = true;
			// permits association
			$this->_showassociation = true;
			// current recursive level
			$this->recursiveLevel = 1;
			// parent's maximum recursive level of output
			$this->recursiveDeepness = null;
			// set as not required by default
			$this->set('required', 'no');
			// show association by default
			$this->set('show_association', 'yes');
			// no max deepness
			$this->set('deepness', null);
			// no included elements
			$this->set('elements', null);
			// no limit
			$this->set('min_entries', null);
			$this->set('max_entries', null);
			// all permissions
			$this->set('allow_new', 'yes');
			$this->set('allow_edit', 'yes');
			$this->set('allow_link', 'yes');
			$this->set('allow_delete', 'no');
		}

		public function isSortable()
		{
			return false;
		}

		public function canFilter()
		{
			return true;
		}

		public function canPublishFilter()
		{
			return false;
		}

		public function canImport()
		{
			return false;
		}

		public function canPrePopulate()
		{
			return false;
		}

		public function mustBeUnique()
		{
			return false;
		}

		public function allowDatasourceOutputGrouping()
		{
			return false;
		}

		public function requiresSQLGrouping()
		{
			return false;
		}

		public function allowDatasourceParamOutput()
		{
			return true;
		}

		/**
		 * @param string $name
		 */
		public function getInt($name)
		{
			return General::intval($this->get($name));
		}

		/**
		 * Check if a given property is == 'yes'
		 * @param string $name
		 * @return bool
		 *  True if the current field's value is 'yes'
		 */
		public function is($name)
		{
			return $this->get($name) == 'yes';
		}

		/**
		 * @return bool
		 *  True if the current field is required
		 */
		public function isRequired()
		{
			return $this->is('required');
		}

		public static function getEntries(array $data)
		{
			return array_map(array('General', 'intval'), array_filter(array_map(trim, explode(self::SEPARATOR, $data['entries']))));
		}

		/* ********** INPUT AND FIELD *********** */


		/**
		 *
		 * Validates input
		 * Called before <code>processRawFieldData</code>
		 * @param $data
		 * @param $message
		 * @param $entry_id
		 */
		public function checkPostFieldData($data, &$message, $entry_id=NULL)
		{
			$message = NULL;
			$required = $this->isRequired();

			if ($required && (!is_array($data) || count($data) == 0 || strlen($data['entries']) < 1)) {
				$message = __("'%s' is a required field.", array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}

			$entries = $data['entries'];

			if (!is_array($entries)) {
				$entries = static::getEntries($data);
			}

			// enforce limits only if required or it contains data
			if ($required || count($entries) > 0) {
				if ($this->getInt('min_entries') > 0 && $this->getInt('min_entries') > count($entries)) {
					$message = __("'%s' requires a minimum of %s entries.", array($this->get('label'), $this->getInt('min_entries')));
					return self::__INVALID_FIELDS__;
				} else if ($this->getInt('max_entries') > 0 && $this->getInt('max_entries') < count($entries)) {
					$message = __("'%s' can not contains more than %s entries.", array($this->get('label'), $this->getInt('max_entries')));
					return self::__INVALID_FIELDS__;
				}
			}

			return self::__OK__;
		}


		/**
		 *
		 * Process data before saving into database.
		 *
		 * @param array $data
		 * @param int $status
		 * @param boolean $simulate
		 * @param int $entry_id
		 *
		 * @return Array - data to be inserted into DB
		 */
		public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
		{
			$status = self::__OK__;
			$entries = null;

			if (!is_array($data) && !is_string($data)) {
				return null;
			}

			if (isset($data['entries'])) {
				$entries = $data['entries'];
			}
			else if (is_string($data)) {
				$entries = $data;
			}

			$row = array(
				'entries' => $entries
			);

			// return row
			return $row;
		}

		/**
		 * This function permits parsing different field settings values
		 *
		 * @param array $settings
		 *	the data array to initialize if necessary.
		 */
		public function setFromPOST(Array $settings = array())
		{
			// call the default behavior
			parent::setFromPOST($settings);

			// declare a new setting array
			$new_settings = array();

			// set new settings
			$new_settings['sections'] = is_array($settings['sections']) ?
				implode(self::SEPARATOR, $settings['sections']) :
				(is_string($settings['sections']) ? $settings['sections'] : null);

			$new_settings['show_association'] = $settings['show_association'] == 'yes' ? 'yes' : 'no';
			$new_settings['deepness'] = General::intval($settings['deepness']);
			$new_settings['deepness'] = $new_settings['deepness'] < 1 ? null : $new_settings['deepness'];
			$new_settings['elements'] = empty($settings['elements']) ? null : $settings['elements'];
			$new_settings['mode'] = empty($settings['mode']) ? null : $settings['mode'];
			$new_settings['allow_new'] = $settings['allow_new'] == 'yes' ? 'yes' : 'no';
			$new_settings['allow_edit'] = $settings['allow_edit'] == 'yes' ? 'yes' : 'no';
			$new_settings['allow_link'] = $settings['allow_link'] == 'yes' ? 'yes' : 'no';
			$new_settings['allow_delete'] = $settings['allow_delete'] == 'yes' ? 'yes' : 'no';

			// save it into the array
			$this->setArray($new_settings);
		}


		/**
		 *
		 * Validates the field settings before saving it into the field's table
		 */
		public function checkFields(Array &$errors, $checkForDuplicates = true)
		{
			$parent = parent::checkFields($errors, $checkForDuplicates);
			if ($parent != self::__OK__) {
				return $parent;
			}

			$sections = $this->get('sections');

			if (empty($sections)) {
				$errors['sections'] = __('At least one section must be chosen');
			}

			return (!empty($errors) ? self::__ERROR__ : self::__OK__);
		}

		/**
		 *
		 * Save field settings into the field's table
		 */
		public function commit()
		{
			// if the default implementation works...
			if(!parent::commit()) return false;

			$id = $this->get('id');

			// exit if there is no id
			if($id == false) return false;

			// we are the child, with multiple parents
			$child_field_id = $id;

			// delete associations, only where we are the child
			self::removeSectionAssociation($child_field_id);

			$sections = $this->getSelectedSectionsArray();

			foreach ($sections as $key => $sectionId) {
				if (empty($sectionId)) {
					continue;
				}
				$parent_section_id = General::intval($sectionId);
				$parent_section = SectionManager::fetch($sectionId);
				$fields = $parent_section->fetchVisibleColumns();
				if (empty($fields)) {
					// no visible field, revert to all
					$fields = $parent_section->fetchFields();
				}
				$parent_field_id = current(array_keys($fields));
				// create association
				SectionManager::createSectionAssociation(
					$parent_section_id,
					$child_field_id,
					$parent_field_id,
					$this->get('show_association') == 'yes'
				);
			}

			// declare an array contains the field's settings
			$settings = array(
				'sections' => $this->get('sections'),
				'show_association' => $this->get('show_association'),
				'deepness' => $this->get('deepness'),
				'elements' => $this->get('elements'),
				'mode' => $this->get('mode'),
				'min_entries' => $this->get('min_entries'),
				'max_entries' => $this->get('max_entries'),
				'allow_new' => $this->get('allow_new'),
				'allow_edit' => $this->get('allow_edit'),
				'allow_link' => $this->get('allow_link'),
				'allow_delete' => $this->get('allow_delete'),
			);

			return FieldManager::saveSettings($id, $settings);
		}

		/**
		 *
		 * This function allows Fields to cleanup any additional things before it is removed
		 * from the section.
		 * @return boolean
		 */
		public function tearDown()
		{
			self::removeSectionAssociation($this->get('id'));
			return parent::tearDown();
		}

		/**
		 * Generates the where filter for searching by entry id
		 *
		 * @param string $value
		 * @param @optional string $col
		 * @param @optional boolean $andOperation
		 */
		public function generateWhereFilter($value, $col = 'd', $andOperation = true)
		{
			$junction = $andOperation ? 'AND' : 'OR';
			if (!$value) {
				return "{$junction} (`{$col}`.`entries` IS NULL)";
			}
			return " {$junction} (`{$col}`.`entries` = '{$value}' OR
					`{$col}`.`entries` LIKE '{$value},%' OR
					`{$col}`.`entries` LIKE '%,{$value}' OR
					`{$col}`.`entries` LIKE '%,{$value},%')";
		}

		/**
		 * Fetch the number of associated entries for a particular entry id
		 *
		 * @param string $value
		 */
		public function fetchAssociatedEntryCount($value)
		{
			if (!$value) {
				return 0;
			}
			$join = sprintf(" INNER JOIN `tbl_entries_data_%s` AS `d` ON `e`.id = `d`.`entry_id`", $this->get('id'));
			$where = $this->generateWhereFilter($value);

			$entries = EntryManager::fetch(null, $this->get('parent_section'), null, 0, $where, $join, false, false, array());

			return count($entries);
		}

		public function fetchAssociatedEntrySearchValue($data, $field_id = null, $parent_entry_id = null)
		{
			return $parent_entry_id;
		}

		public function findRelatedEntries($entry_id, $parent_field_id)
		{
			$joins = '';
			$where = '';
			$this->buildDSRetrievalSQL(array($entry_id), $joins, $where, true);

			$entries = EntryManager::fetch(null, $this->get('parent_section'), null, 0, $where, $joins, false, false, array());

			$ids = array();
			foreach ($entries as $key => $e) {
				$ids[] = $e['id'];
			}
			return $ids;
		}

		public function prepareAssociationsDrawerXMLElement(Entry $e, array $parent_association, $prepolutate = '')
		{
			$currentSection = SectionManager::fetch($parent_association['child_section_id']);
			$visibleCols = $currentSection->fetchVisibleColumns();
			$outputFieldId = current(array_keys($visibleCols));
			$outputField = FieldManager::fetch($outputFieldId);

			$value = $outputField->prepareReadableValue($e->getData($outputFieldId), $e->get('id'), true, __('None'));

			$li = new XMLElement('li');
			$li->setAttribute('class', 'field-' . $this->get('type'));
			$a = new XMLElement('a', strip_tags($value));
			$a->setAttribute('href', SYMPHONY_URL . '/publish/' . $parent_association['handle'] . '/edit/' . $e->get('id') . '/');
			$li->appendChild($a);

			return $li;
		}

		/**
		 * @param string $joins
		 * @param string $where
		 */
		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false)
		{
			$field_id = $this->get('id');

			// REGEX filtering is a special case, and will only work on the first item
			// in the array. You cannot specify multiple filters when REGEX is involved.
			if (self::isFilterRegex($data[0])) {
				return $this->buildRegexSQL($data[0], array('entries'), $joins, $where);
			}

			$this->_key++;

			$where .= ' AND (1=' . ($andOperation ? '1' : '0') . ' ';

			$joins .= "
				INNER JOIN
					`tbl_entries_data_{$field_id}` AS `t{$field_id}_{$this->_key}`
					ON (`e`.`id` = `t{$field_id}_{$this->_key}`.`entry_id`)
			";

			foreach ($data as $value) {
				$where .= $this->generateWhereFilter($this->cleanValue($value), "t{$field_id}_{$this->_key}", $andOperation);
			}

			$where .= ')';

			return true; // this tells the DS Manager that filters are OK!!
		}

		/* ******* DATA SOURCE ******* */

		private function parseElements()
		{
			$elements = array();
			$exElements = array_map(trim, explode(self::SEPARATOR, $this->get('elements')));

			foreach ($exElements as $value) {
				if (!$value) {
					continue;
				}
				$parts = array_map(trim, explode('.', $value));
				if (!isset($elements[$parts[0]])) {
					$elements[$parts[0]] = array();
				}
				if (isset($parts[1]) && !!$parts[1]) {
					$elements[$parts[0]][] = $parts[1];
				}
			}

			return $elements;
		}

		private function fetchEntry($eId, $elements = array())
		{
			$entry = EntryManager::fetch($eId, null, 1, 0, null, null, false, true, $elements, false);
			if (!is_array($entry) || count($entry) !== 1) {
				return null;
			}
			return $entry[0];
		}

		public function fetchIncludableElements()
		{
			$label = $this->get('element_name');
			$elements = array_filter(array_map(trim, explode(self::SEPARATOR, trim($this->get('elements')))));
			$includedElements = array($label . ': *');
			foreach ($elements as $elem) {
				$elem = trim($elem);
				if ($elem !== '*') {
					$includedElements[] = $label . ': ' . $elem;
				}
			}
			return $includedElements;
		}

		/**
		 * Appends data into the XML tree of a Data Source
		 * @param $wrapper
		 * @param $data
		 */
		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
		{
			if(!is_array($data) || empty($data)) return;

			// root for all values
			$root = new XMLElement($this->get('element_name'));

			// selected items
			$entries = static::getEntries($data);

			// current linked entries
			$root->setAttribute('entries', $data['entries']);

			// available sections
			$root->setAttribute('sections', $this->get('sections'));

			// included elements
			$elements = $this->parseElements();

			// cache
			$sectionsCache = new CacheableFetch('SectionManager');

			// DS mode
			if (!$mode) {
				$mode = '*';
			}

			$parentDeepness = General::intval($this->recursiveDeepness);
			$deepness = General::intval($this->get('deepness'));

			// both deepnesses are defined and parent restricts more
			if ($parentDeepness > 0 && $deepness > 0 && $parentDeepness < $deepness) {
				$deepness = $parentDeepness;
			}
			// parent is defined, current is not
			else if ($parentDeepness > 0 && $deepness < 1) {
				$deepness = $parentDeepness;
			}

			// cache recursive level because recursion might
			// change its value later on.
			$recursiveLevel = $this->recursiveLevel;

			// build entries
			foreach ($entries as $eId) {
				$item = new XMLElement('item');
				// output id
				$item->setAttribute('id', $eId);
				// output recursive level
				$item->setAttribute('level', $recursiveLevel);
				$item->setAttribute('max-level', $deepness);

				// max recursion check
				if ($deepness < 1 || $recursiveLevel < $deepness) {
					// current entry, without data
					$entry = $this->fetchEntry($eId);

					// entry not found...
					if (!$entry || empty($entry)) {
						$error = new XMLElement('error');
						$error->setAttribute('id', $eId);
						$error->setValue(__('Error: entry `%s` not found', array($eId)));
						$root->prependChild($error);
						continue;
					}

					// fetch section infos
					$sectionId = $entry->get('section_id');
					$item->setAttribute('section-id', $sectionId);
					$section = $sectionsCache->fetch($sectionId);
					$sectionName = $section->get('handle');
					$item->setAttribute('section', $sectionName);

					// adjust the mode for the current section
					$curMode = $mode;
					if ($curMode) {
						// remove section name from current mode, i.e sectionName.field
						if (preg_match('/^(' . $sectionName . '\.)(.*)$/sU', $curMode)) {
							$curMode = preg_replace('/^' . $sectionName . '\./sU', '', $curMode);
						}
						// remove section name from current mode, i.e sectionName
						else if (preg_match('/^(' . $sectionName . ')$/sU', $curMode)) {
							$curMode = null;
						}
						// mode forbids this section, bail out
						else if (preg_match('/\./sU', $curMode)) {
							$item->setAttribute('forbidden', 'yes');
							$root->appendChild($item);
							continue;
						}
					}
					$item->setAttribute('section', $section->get('handle'));

					// Get the valid elements for this section only
					$sectionElements = $elements[$sectionName];

					// get all if no mode is set or section element is empty
					// or mode is * and * is allowed
					if (!$curMode || empty($sectionElements) ||
						($curMode === '*' && in_array('*', $sectionElements))) {
						// setting null = get all
						$sectionElements = null;
					}
					// everything is allowed but we have a mode
					else if (in_array('*', $sectionElements) && !!$curMode) {
						// get only the mode
						$sectionElements = array($curMode);
					}
					// filter out what is allowed with the current DS mode.
					// this may leave the array empty (mode is not allowed).
					// do the filtering only if data-source ask for anything else than *,
					// if not, let the array as-is
					else if ($curMode !== '*') {
						foreach ($sectionElements as $secElemIndex => $sectionElement) {
							if ($curMode != $sectionElement) {
								unset($sectionElements[$secElemIndex]);
							}
						}
					}

					// current entry again, but with data and the allowed schema
					$entry = $this->fetchEntry($eId, $sectionElements);

					// cache fields info
					if (!isset($section->er_field_cache)) {
						$section->er_field_cache = $section->fetchFields();
					}

					// cache the entry data
					$entryData = $entry->getData();

					// for each field returned for this entry...
					foreach ($entryData as $fieldId => $data) {
						$filteredData = array_filter($data, function ($value) {
							return $value != null;
						});

						if (empty($filteredData)) {
							continue;
						}
						$field = $section->er_field_cache[$fieldId];
						$fieldName = $field->get('element_name');

						// Increment recursive level
						if ($field instanceof FieldEntry_relationship) {
							$field->recursiveLevel = $recursiveLevel + 1;
							$field->recursiveDeepness = $deepness;
						}
						// filter out elements per what's allowed
						if (self::isFieldIncluded($fieldName, $sectionElements)) {
							$parentIncludableElement = self::getSectionElementName($fieldName, $sectionElements);
							$fieldIncludableElements = null;
							// if the includable element is not just the field name
							if ($parentIncludableElement != $fieldName) {
								// use the includable element's mode
								$curMode = preg_replace('/^' . $fieldName . '\s*\:\s*/i', '', $parentIncludableElement , 1);
							} else {
								// revert to the field's includable elements
								$fieldIncludableElements = $field->fetchIncludableElements();
							}

							// do not use includable elements
							if ($field instanceof FieldEntry_relationship) {
								$fieldIncludableElements = null;
							}

							// include children
							if (!empty($fieldIncludableElements) && count($fieldIncludableElements) > 1) {
								// append each includable element
								foreach ($fieldIncludableElements as $fieldIncludableElement) {
									// remove field name from mode
									$submode = preg_replace('/^' . $fieldName . '\s*\:\s*/i', '', $fieldIncludableElement, 1);
									$field->appendFormattedElement($item, $data, $encode, $submode, $eId);
								}
							} else {
								$field->appendFormattedElement($item, $data, $encode, $curMode, $eId);
							}
						} else {
							$item->appendChild(new XMLElement('error', __('Field "%s" not allowed', array($fieldName))));
						}
					}
					// output current mode
					$item->setAttribute('matched-element', $curMode);
				}
				// append item when done
				$root->appendChild($item);
			}

			// output mode for this field
			$root->setAttribute('data-source-mode', $mode);

			// add all our data to the wrapper;
			$wrapper->appendChild($root);

			// clean up
			$this->recursiveLevel = 1;
			$this->recursiveDeepness = null;
		}

		public function getParameterPoolValue(array $data, $entry_id = null)
		{
			if(!is_array($data) || empty($data)) return;
			return static::getEntries($data);
		}

		/* ********* Utils *********** */

		/**
		 * Return true if $fieldName is allowed in $sectionElements
		 * @param string $fieldName
		 * @param string $sectionElements
		 * @return bool
		 */
		public static function isFieldIncluded($fieldName, $sectionElements)
		{
			if ($sectionElements === null) {
				return true;
			}
			return self::getSectionElementName($fieldName, $sectionElements) !== null;
		}

		public static function getSectionElementName($fieldName, $sectionElements)
		{
			if (is_array($sectionElements)) {
				foreach ($sectionElements as $element) {
					if ($fieldName == $element || preg_match('/^' . $fieldName . '\s*:/', $element) == 1) {
						return $element;
					}
				}
			}
			return null;
		}

		/**
		 * @param string $prefix
		 * @param string $name
		 * @param @optional bool $multiple
		 */
		private function createFieldName($prefix, $name, $multiple = false)
		{
			$name = "fields[$prefix][$name]";
			if ($multiple) {
				$name .= '[]';
			}
			return $name;
		}

		/**
		 * @param string $name
		 */
		private function createSettingsFieldName($name, $multiple = false)
		{
			return $this->createFieldName($this->get('sortorder'), $name, $multiple);
		}

		/**
		 * @param string $name
		 */
		private function createPublishFieldName($name, $multiple = false)
		{
			return $this->createFieldName($this->get('element_name'), $name, $multiple);
		}

		private function getSelectedSectionsArray()
		{
			$selectedSections = $this->get('sections');
			if (!is_array($selectedSections)) {
				if (is_string($selectedSections) && strlen($selectedSections) > 0) {
					$selectedSections = explode(self::SEPARATOR, $selectedSections);
				}
				else {
					$selectedSections = array();
				}
			}
			return $selectedSections;
		}

		private function buildSectionSelect($name)
		{
			$sections = SectionManager::fetch();
			$options = array();
			$selectedSections = $this->getSelectedSectionsArray();

			foreach ($sections as $section) {
				$driver = $section->get('id');
				$selected = in_array($driver, $selectedSections);
				$options[] = array($driver, $selected, $section->get('name'));
			}

			return Widget::Select($name, $options, array('multiple' => 'multiple'));
		}

		private function appendSelectionSelect(&$wrapper)
		{
			$name = $this->createSettingsFieldName('sections', true);

			$input = $this->buildSectionSelect($name);
			$input->setAttribute('class', 'entry_relationship-sections');

			$label = Widget::Label();
			$label->setAttribute('class', 'column');

			$label->setValue(__('Available sections %s', array($input->generate())));

			$wrapper->appendChild($label);
		}

		private function createEntriesList($entries)
		{
			$wrap = new XMLElement('div');
			$wrap->setAttribute('class', 'frame collapsible orderable' . (count($entries) > 0 ? '' : ' empty'));

			$list = new XMLElement('ul');
			$list->setAttribute('class', '');

			$wrap->appendChild($list);

			return $wrap;
		}

		private function createEntriesHiddenInput($data)
		{
			$hidden = new XMLElement('input', null, array(
				'type' => 'hidden',
				'name' => $this->createPublishFieldName('entries'),
				'value' => $data['entries']
			));

			return $hidden;
		}

		private function createPublishMenu($sections)
		{
			$wrap = new XMLElement('fieldset');
			$wrap->setAttribute('class', 'single');

			if ($this->is('allow_new') || $this->is('allow_link')) {
				$selectWrap = new XMLElement('div');
				$selectWrap->appendChild(new XMLElement('span', __('Related section: ')));
				$options = array();
				foreach ($sections as $section) {
					$options[] = array($section->get('handle'), false, $section->get('name'));
				}
				$select = Widget::Select('', $options, array('class' => 'sections'));
				$selectWrap->appendChild($select);
				$wrap->appendChild($selectWrap);
			}
			if ($this->is('allow_new')) {
				$wrap->appendChild(new XMLElement('button', __('Create new'), array(
					'type' => 'button',
					'class' => 'create',
					'data-create' => '',
				)));
			}
			if ($this->is('allow_link')) {
				$wrap->appendChild(new XMLElement('button', __('Link to entry'), array(
					'type' => 'button',
					'class' => 'link',
					'data-link' => '',
				)));
			}

			return $wrap;
		}

		/* ********* UI *********** */

		/**
		 *
		 * Builds the UI for the field's settings when creating/editing a section
		 * @param XMLElement $wrapper
		 * @param array $errors
		 */
		public function displaySettingsPanel(XMLElement &$wrapper, $errors=NULL)
		{
			/* first line, label and such */
			parent::displaySettingsPanel($wrapper, $errors);

			// sections
			$sections = new XMLElement('div');
			$sections->setAttribute('class', '');

			$this->appendSelectionSelect($sections);
			if (is_array($errors) && isset($errors['sections'])) {
				$sections = Widget::Error($sections, $errors['sections']);
			}
			$wrapper->appendChild($sections);

			// xsl mode
			$xslmode = Widget::Label();
			$xslmode->setValue(__('XSL mode applied in the backend xsl file'));
			$xslmode->setAttribute('class', 'column');
			$xslmode->appendChild(Widget::Input($this->createSettingsFieldName('mode'), $this->get('mode'), 'text'));

			// deepness
			$deepness = Widget::Label();
			$deepness->setValue(__('Maximum level of recursion in Data Sources'));
			$deepness->setAttribute('class', 'column');
			$deepness->appendChild(Widget::Input($this->createSettingsFieldName('deepness'), $this->get('deepness'), 'number', array(
				'min' => 0,
				'max' => 99
			)));

			// association
			$assoc = new XMLElement('div');
			$assoc->setAttribute('class', 'three columns');
			$this->appendShowAssociationCheckbox($assoc);
			$assoc->appendChild($xslmode);
			$assoc->appendChild($deepness);
			$wrapper->appendChild($assoc);

			// elements
			$elements = new XMLElement('div');
			$elements->setAttribute('class', '');
			$element = Widget::Label();
			$element->setValue(__('Included elements in Data Sources and Backend Templates'));
			$element->setAttribute('class', 'column');
			$element->appendChild(Widget::Input($this->createSettingsFieldName('elements'), $this->get('elements'), 'text', array(
				'class' => 'entry_relationship-elements'
			)));
			$elements->appendChild($element);
			$elements_choices = new XMLElement('ul', null, array('class' => 'tags singular entry_relationship-field-choices'));

			$elements->appendChild($elements_choices);
			$wrapper->appendChild($elements);

			// limit entries
			$limits = new XMLElement('fieldset');
			$limits->setAttribute('class', 'two columns');
			// min
			$limit_min = Widget::Label();
			$limit_min->setValue(__('Minimum count of entries in this field'));
			$limit_min->setAttribute('class', 'column');
			$limit_min->appendChild(Widget::Input($this->createSettingsFieldName('min_entries'), $this->get('min_entries'), 'number', array(
				'min' => 0,
				'max' => 99999
			)));
			$limits->appendChild($limit_min);
			// max
			$limit_max = Widget::Label();
			$limit_max->setValue(__('Maximum count of entries in this field'));
			$limit_max->setAttribute('class', 'column');
			$limit_max->appendChild(Widget::Input($this->createSettingsFieldName('max_entries'), $this->get('max_entries'), 'number', array(
				'min' => 0,
				'max' => 99999
			)));
			$limits->appendChild($limit_max);

			$wrapper->appendChild($limits);

			// permissions
			$permissions = new XMLElement('fieldset');
			$permissions->setAttribute('class', 'two columns');
			$permissions->appendChild($this->createCheckbox('allow_new', 'Show new button'));
			$permissions->appendChild($this->createCheckbox('allow_edit', 'Show edit button'));
			$permissions->appendChild($this->createCheckbox('allow_link', 'Show link button'));
			$permissions->appendChild($this->createCheckbox('allow_delete', 'Show delete button'));

			$wrapper->appendChild($permissions);

			// footer
			$this->appendStatusFooter($wrapper);
		}

		/**
		 * @param string $fieldName
		 * @param string $text
		 */
		private function createCheckbox($fieldName, $text) {
			$chk = Widget::Label();
			$chk->setAttribute('class', 'column');
			$attrs = null;
			if ($this->get($fieldName) == 'yes') {
				$attrs = array('checked' => 'checked');
			}
			$chk->appendChild(Widget::Input($this->createSettingsFieldName($fieldName), 'yes', 'checkbox', $attrs));
			$chk->setValue(__($text));
			return $chk;
		}

		/**
		 *
		 * Builds the UI for the publish page
		 * @param XMLElement $wrapper
		 * @param mixed $data
		 * @param mixed $flagWithError
		 * @param string $fieldnamePrefix
		 * @param string $fieldnamePostfix
		 */
		public function displayPublishPanel(XMLElement &$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL, $entry_id = null)
		{
			$entriesId = array();
			$sectionsId = $this->getSelectedSectionsArray();

			if ($data['entries'] != null) {
				$entriesId = static::getEntries($data);
			}

			$sectionsId = array_map(array('General', 'intval'), $sectionsId);
			$sections = SectionManager::fetch($sectionsId);

			$label = Widget::Label($this->get('label'));
			$notes = '';

			// min note
			if ($this->getInt('min_entries') > 0) {
				$notes .= __('Minimum number of entries: <b>%s</b>. ', array($this->get('min_entries')));
			}
			// max note
			if ($this->getInt('max_entries') > 0) {
				$notes .= __('Maximum number of entries: <b>%s</b>. ', array($this->get('max_entries')));
			}
			// not required note
			if (!$this->isRequired()) {
				$notes .= __('Optional');
			}
			// append notes
			if ($notes) {
				$label->appendChild(new XMLElement('i', $notes));
			}

			// label error management
			if ($flagWithError != NULL) {
				$wrapper->appendChild(Widget::Error($label, $flagWithError));
			} else {
				$wrapper->appendChild($label);
			}

			$wrapper->appendChild($this->createEntriesList($entriesId));
			$wrapper->appendChild($this->createPublishMenu($sections));
			$wrapper->appendChild($this->createEntriesHiddenInput($data));
			$wrapper->setAttribute('data-value', $data['entries']);
			$wrapper->setAttribute('data-field-id', $this->get('id'));
			$wrapper->setAttribute('data-field-label', $this->get('label'));
			$wrapper->setAttribute('data-min', $this->get('min_entries'));
			$wrapper->setAttribute('data-max', $this->get('max_entries'));
			if (isset($_REQUEST['debug'])) {
				$wrapper->setAttribute('data-debug', true);
			}
		}

		/**
		 * @param integer $count
		 */
		private static function formatCount($count)
		{
			if ($count == 0) {
				return __('No item');
			} else if ($count == 1) {
				return __('1 item');
			}
			return __('%s items', array($count));
		}

		/**
		 *
		 * Return a plain text representation of the field's data
		 * @param array $data
		 * @param int $entry_id
		 */
		public function prepareTextValue($data, $entry_id = null)
		{
			if ($entry_id == null || empty($data)) {
				return __('None');
			}
			$entries = static::getEntries($data);
			$realEntries = array();
			foreach ($entries as $entryId) {
				$e = EntryManager::fetch($entryId);
				if (is_array($e) && !empty($e)) {
					$realEntries = array_merge($realEntries, $e);
				}
			}
			$count = count($entries);
			$realCount = count($realEntries);
			if ($count === $realCount) {
				return self::formatCount($count);
			}
			return self::formatCount($realCount) . ' (' . self::formatCount($count - $realCount) . ' not found)';
		}



		/* ********* SQL Data Definition ************* */

		/**
		 *
		 * Creates table needed for entries of individual fields
		 */
		public function createTable()
		{
			$id = $this->get('id');

			return Symphony::Database()->query("
				CREATE TABLE `tbl_entries_data_$id` (
					`id` int(11) 		unsigned NOT NULL AUTO_INCREMENT,
					`entry_id` 			int(11) unsigned NOT NULL,
					`entries` 			text COLLATE utf8_unicode_ci NULL,
					PRIMARY KEY  (`id`),
					UNIQUE KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

		/**
		 * Creates the table needed for the settings of the field
		 */
		public static function createFieldTable()
		{
			$tbl = self::FIELD_TBL_NAME;

			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `$tbl` (
					`id` 			int(11) unsigned NOT NULL AUTO_INCREMENT,
					`field_id` 		int(11) unsigned NOT NULL,
					`sections`		varchar(255) NULL COLLATE utf8_unicode_ci,
					`show_association` enum('yes','no') NOT NULL COLLATE utf8_unicode_ci DEFAULT 'yes',
					`deepness` 		int(2) unsigned NULL,
					`elements` 		varchar(1024) NULL COLLATE utf8_unicode_ci,
					`mode`			varchar(50) NULL COLLATE utf8_unicode_ci,
					`min_entries`	int(5) unsigned NULL,
					`max_entries`	int(5) unsigned NULL,
					`allow_edit` 	enum('yes','no') NOT NULL COLLATE utf8_unicode_ci DEFAULT 'yes',
					`allow_new` 	enum('yes','no') NOT NULL COLLATE utf8_unicode_ci DEFAULT 'yes',
					`allow_link` 	enum('yes','no') NOT NULL COLLATE utf8_unicode_ci DEFAULT 'yes',
					`allow_delete` 	enum('yes','no') NOT NULL COLLATE utf8_unicode_ci DEFAULT 'no',
					PRIMARY KEY (`id`),
					UNIQUE KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

		public static function update_102()
		{
			$tbl = self::FIELD_TBL_NAME;
			$sql = "
				ALTER TABLE `$tbl`
					ADD COLUMN `allow_edit` enum('yes','no') NOT NULL COLLATE utf8_unicode_ci  DEFAULT 'yes',
					ADD COLUMN `allow_new` enum('yes','no') NOT NULL COLLATE utf8_unicode_ci  DEFAULT 'yes',
					ADD COLUMN `allow_link` enum('yes','no') NOT NULL COLLATE utf8_unicode_ci  DEFAULT 'yes'
					AFTER `max_entries`
			";
			$addColumns = Symphony::Database()->query($sql);
			if (!$addColumns) {
				return false;
			}

			$fields = FieldManager::fetch(null, null, null, 'id', 'entry_relationship');
			if (!empty($fields) && is_array($fields)) {
				foreach ($fields as $fieldId => $field) {
					$sql = "ALTER TABLE `tbl_entries_data_$fieldId` MODIFY `entries` TEXT";
					if (!Symphony::Database()->query($sql)) {
						throw new Exception(__('Could not update table `tbl_entries_data_%s`.', array($fieldId)));
					}
				}
			}
			return true;
		}

		public static function update_103()
		{
			$tbl = self::FIELD_TBL_NAME;
			$sql = "
				ALTER TABLE `$tbl`
					ADD COLUMN `allow_delete` enum('yes','no') NOT NULL COLLATE utf8_unicode_ci  DEFAULT 'no'
					AFTER `allow_link`
			";
			return Symphony::Database()->query($sql);
		}

		/**
		 *
		 * Drops the table needed for the settings of the field
		 */
		public static function deleteFieldTable()
		{
			$tbl = self::FIELD_TBL_NAME;

			return Symphony::Database()->query("
				DROP TABLE IF EXISTS `$tbl`
			");
		}

		private static function removeSectionAssociation($child_field_id)
		{
			return Symphony::Database()->delete('tbl_sections_association', "`child_section_field_id` = {$child_field_id}");
		}
	}

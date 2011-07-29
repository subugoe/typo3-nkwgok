<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Xavier Perseguers (typo3@perseguers.ch)
*  (c) 2011 domainfactory GmbH (Stefan Galinski <sgalinski@df.eu>)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This class provides a wizard used in EM to prepare a configuration based on flexforms.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Stefan Galinski <sgalinski@df.eu>
 * @package TYPO3
 * @subpackage df_tools
 */
class tx_Nkwgok_Tools_FlexForm {

	const virtualTable = 'tx_nkwgok_configuration';
	const virtualRecordId = 1;
	const virtualConfigurationFile = 'tools/VirtualConfigurationTable.php';

	/**
	 * @var string
	 */
	protected $extKey = 'nkwgok';

	/**
	 * @var array
	 */
	protected $expertKey = 'nkwgok';

	/**
	 * @var t3lib_TCEforms
	 */
	protected $tceforms;

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * Returns the default configuration.
	 *
	 * @return array
	 */
	protected function getDefaultConfiguration() {
		return array(
			'excludedTables' => 'tx_dftools_domain_model_redirecttest,tx_dftools_domain_model_linkcheck,tx_dftools_domain_model_contentcomparisontest',
		);
	}

	/**
	 * Default constructor
	 */
	public function __construct() {
		$this->initTCEForms();

		$config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->expertKey];
		$this->config = $config ? unserialize($config) : $this->getDefaultConfiguration();
	}

	/**
	 * Renders a FlexForm configuration form.
	 *
	 * @param array	Parameter array. Contains fieldName and fieldValue.
	 * @param t3lib_tsStyleConfig $pObj Parent object
	 * @return string HTML wizard
	 */
	public function display(array $params, t3lib_tsStyleConfig $pObj) {
		if (t3lib_div::_GP('form_submitted')) {
			$this->processData();
		}

		$row = $this->config;
		if ($row['rulesets']) {
			$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
			/** @var $flexObj t3lib_flexformtools */
			$row['rulesets'] = $flexObj->flexArray2Xml($row['rulesets'], TRUE);
		}

		$this->content .= $this->tceforms->printNeededJSFunctions_top();
		$this->content .= $this->buildForm($row);
		$this->content .= $this->tceforms->printNeededJSFunctions();

		return $this->content;
	}

	/**
	 * Builds the expert configuration form.
	 *
	 * @param array $row
	 * @return string
	 */
	protected function buildForm(array $row) {
		$content = '';

		global $TCA;
		require_once(t3lib_extMgm::extPath('nkwgok') . self::virtualConfigurationFile);

		$rec['uid'] = self::virtualRecordId;
		$rec['pid'] = 0;
		$rec = array_merge($rec, $row);

			// Create form
		$form = '';
		$form .= $this->tceforms->getMainFields(self::virtualTable, $rec);
		$form .= '<input type="hidden" name="form_submitted" value="1" />';
		$form = $this->tceforms->wrapTotal($form, $rec, self::virtualTable);

			// Remove header and footer
		$form = preg_replace('/<h2>.*<\/h2>/', '', $form);
		$startFooter = strrpos($form, '<tr class="typo3-TCEforms-recHeaderRow">');
		$endFooter = strpos($form, '</tr>', $startFooter);
		$form = substr($form, 0, $startFooter) . substr($form, $endFooter + 5);

			// Combine it all:
		$content .= $form;
		return $content;
	}

	/**
	 * Processes submitted data and stores it to localconf.php.
	 *
	 * @return void
	 */
	protected function processData() {
		$table = self::virtualTable;
		$id    = self::virtualRecordId;
		$field = 'rulesets';

		$inputData_tmp = t3lib_div::_GP('data');
		$data = $inputData_tmp[$table][$id];
		$newConfig = t3lib_div::array_merge_recursive_overrule($this->config, (array) $data);

			// Action commands (sorting order and removals of FlexForm elements)
		$ffValue =& $data[$field];
		if ($ffValue) {
			$actionCMDs = t3lib_div::_GP('_ACTION_FLEX_FORMdata');
			if (is_array($actionCMDs[$table][$id][$field]['data']))	{
				/** @var $tce t3lib_TCEmain */
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				// Officially internal but not declared as such...
				$tce->_ACTION_FLEX_FORMdata($ffValue['data'], $actionCMDs[$table][$id][$field]['data']);
			}
				// Renumber all FlexForm temporary ids
			$this->persistFlexForm($ffValue['data']);

				// Keep order of FlexForm elements
			$newConfig[$field] = $ffValue;
		}

			// Write back configuration to localconf.php
		$key = '$TYPO3_CONF_VARS[\'EXT\'][\'extConf\'][\'' . $this->expertKey . '\']';
		$localconfConfig = $newConfig;
		$value = '\'' . serialize($localconfConfig) . '\'';

		if ($this->writeToLocalconf($key, $value)) {
			$this->config = $newConfig;
			t3lib_extMgm::removeCacheFiles();
		}
	}

	/**
	 * Writes a configuration line to localconf.php.
	 * We don't use the <code>tx_install</code> methods as they add unneeded
	 * comments at the end of the file.
	 *
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 */
	protected function writeToLocalconf($key, $value) {
		$localconfFile = PATH_site . 'typo3conf/localconf.php';
		$lines = explode("\n", file_get_contents($localconfFile));
		$marker = '## INSTALL SCRIPT EDIT POINT TOKEN';
		$format = '%s = %s;';

		$insertPos = count($lines);
		$pos = 0;
		for ($i = count($lines) - 1; $i > 0 && !t3lib_div::isFirstPartOfStr($lines[$i], $marker); $i--) {
			if (t3lib_div::isFirstPartOfStr($lines[$i], '?>')) {
				$insertPos = $i;
			}
			if (t3lib_div::isFirstPartOfStr($lines[$i], $key)) {
				$pos = $i;
				break;
			}
		}
		if ($pos) {
			$lines[$pos] = sprintf($format, $key, $value);
		} else {
			$lines[$insertPos] = sprintf($format, $key, $value);
			$lines[] = '?>';
		}

		return t3lib_div::writeFile($localconfFile, implode("\n", $lines));
	}

	/**
	 * Initializes <code>t3lib_TCEform</code> class for use in this module.
	 *
	 * @return void
	 */
	protected function initTCEForms() {
		$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
		$this->tceforms->initDefaultBEMode();
		$this->tceforms->formName = 'tsStyleConfigForm';
		$this->tceforms->backPath = $GLOBALS['BACK_PATH'];
		$this->tceforms->doSaveFieldName = 'doSave';
		$this->tceforms->localizationMode = '';
		$this->tceforms->palettesCollapsed = 0;
		$this->tceforms->disableRTE = 0;
		$this->tceforms->enableClickMenu = TRUE;
		$this->tceforms->enableTabMenu = TRUE;
	}

	/**
	 * Persists FlexForm items by removing 'ID-' in front of new
	 * items.
	 *
	 * @param array &$valueArray: by reference
	 * @return void
	 */
	protected function persistFlexForm(array &$valueArray) {
		foreach ($valueArray as $key => $value) {
			if ($key === 'el') {
				foreach ($value as $idx => $v) {
					if ($v && substr($idx, 0, 3) === 'ID-') {
						$valueArray[$key][substr($idx, 3)] = $v;
						unset($valueArray[$key][$idx]);
					}
				}
			} elseif (isset($valueArray[$key])) {
				$this->persistFlexForm($valueArray[$key]);
			}
		}
	}
}

?>
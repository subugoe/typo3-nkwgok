<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Nils K. Windisch <windisch@sub.uni-goettingen.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Changes 2011-2012 by Sven-S. Porst <porst@sub.uni-goettingen.de>
 * See the ChangeLog or git repository for details.
 */
require_once(t3lib_extMgm::extPath('nkwgok') . 'lib/class.tx_nkwgok.php');


/**
 * @package TYPO3
 * @subpackage tx_nkwgok
 */
class tx_nkwgok_pi1 extends tslib_pibase {
	
	/**
	 * Main method of the PlugIn
	 *
	 * @author	Nils K. Windisch <windisch@sub.uni-goettingen.de>
	 * @author	Sven-S. Porst <porst@sub.uni-goettingen.de>
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		// basic
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexform();
		$this->pi_USER_INT_obj = 1;

		// CSS
		$this->addStylesheet();

		// get getvars
		$arguments = t3lib_div::_GET('tx_nkwgok');
		
		// get flexform
		$arguments['gok'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'source', 'sDEF');
		$altSource = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'altSource', 'sDEF'));
		// alternative source overrides first definition
		if ($altSource) {
			$arguments['gok'] = $altSource;
		}

		// unique expand array
		if (array_key_exists('expand', $arguments)) {
			$arguments['expand'] = array_unique($arguments['expand']);
		}

		$arguments['style'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'style', 'sDEF');
		$arguments['showGOKID'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showGOKID');
		$arguments['omitXXX'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'omitXXX');
		$arguments['objectID'] = $this->cObj->data['uid'];
		$arguments['pageLink'] = $this->pi_getPageLink($GLOBALS['TSFE']->id);
		$arguments['language'] = $GLOBALS['TSFE']->lang;

		$nkwgok = tx_nkwgok::instantiateSubclassFor($arguments);
		$doc = $nkwgok->getMarkup();		
		$content .= $doc->saveHTML();
		
		return $content;
	}



	/**
	 * Helper function to add our default stylesheet or the one at the path
	 * set up in Extension Manager configuration to the page’s head.
	 *
	 * @author Sven-S. Porst
	 * @return void
	 */
	protected function addStylesheet () {
		$nkwgokGlobalConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nkwgok']);
		$cssPath = $nkwgokGlobalConf['CSSPath'];
		if (!$cssPath) {
			$cssPath = 'EXT:nkwgok/res/nkwgok.css';
		}

		$GLOBALS['TSFE']->pSetup['includeCSS.']['nkwgok'] = $cssPath;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/pi1/class.tx_nkwgok_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/pi1/class.tx_nkwgok_pi1.php']);
}
?>
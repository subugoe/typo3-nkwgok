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
 * Changed by Sven-S. Porst <porst@sub.uni-goettingen.de>
 * 2011-02-10: fix image and Ajax paths to work with sites not at /
 * 2011-02-10: pass language code to eID
 */
require_once(t3lib_extMgm::extPath('nkwgok') . 'lib/class.tx_nkwgok.php');

/**
 * Plugin 'GOK I' for the 'nkwgok' extension.
 *
 * laengster moeglicher Link:
 * http://w3d.typo3.work/test-nils/gok/?no_cache=1&tx_nkwgok[expand]=621711713-621915254-621916862-621920185-621921890-621927139-621928283-621928887-621930628-621931284-621931322-621931438
 * @author	Nils K. Windisch <windisch@sub.uni-goettingen.de>
 * @package	TYPO3
 * @subpackage	tx_nkwgok
 */
class tx_nkwgok_pi1 extends tx_nkwgok {

	var $prefixId = 'tx_nkwgok_pi1';
	var $scriptRelPath = 'pi1/class.tx_nkwgok_pi1.php';
	var $extKey = 'nkwgok';
	var $pi_checkCHash = true;

	
	/**
	 * Main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		// basic
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexform();
		$this->pi_USER_INT_obj = 1;

		//  get getvars
		$conf['getVars'] = t3lib_div::_GET('tx_' . $this->extKey);
		
		// get flexform
		$conf['gok'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'source', 'sDEF');
		$altSource = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'altSource', 'sDEF'));
		// alternative source overrides first definition
		if ($altSource) {
			$conf['gok'] = $altSource;
		}

		// unique expand array
		if ($conf['getVars']['expand']) {
			$tmpArr = explode('-', $conf['getVars']['expand']);
			$conf['getVars']['expand'] = array_unique($tmpArr);
		}

//		$doc = $this->GOKTree($conf);
		$doc = $this->GOKMenus($conf);
		$content .= $doc->saveHTML();
		return $content;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/pi1/class.tx_nkwgok_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/pi1/class.tx_nkwgok_pi1.php']);
}
?>
<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Ingo Pfennigstorf <pfennigstorf@sub.uni-goettingen.de>
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
***************************************************************/

/**
 * Additional Parameters like PageId for the Scheduler
 * Thanks to Steffen Mueller for the template
 *
 * @author Ingo Pfennigstorf <pfennigstorf@sub.uni-goettingen.de>
 * @package TYPO3
 * @subpackage nkwgok
 */
class tx_nkwgok_scheduler_convertcsvadditionalparameters implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * Flexible Method to add and edit fields for the csv import task
	 *
	 * @param array $taskInfo reference to the array containing the info used in the add/edit form
	 * @param tx_scheduler_Task $task when editing, reference to the current task object. Null when adding.
	 * @param tx_scheduler_module1 $schedulerModule: reference to the calling object (Scheduler's BE module)
	 * @return array Array containg all the information pertaining to the additional fields
	 * 				The array is multidimensional, keyed to the task class name and each field's id
	 *				For each field it provides an associative sub-array with the following:
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule) {
		$fields = array('startPageId');

		if ($schedulerModule->CMD == 'add') {
			$taskInfo['nkwgokStartPageId'] = 1;
		}

		if ($schedulerModule->CMD == 'edit') {
			$taskInfo['nkwgokStartPageId'] = $task->startPageId;
		}

		$additionalFields = array();
		foreach ($fields as $field) {
			$fieldId = 'task_nkwgok' . ucfirst($field);
			$fieldHtml = '<input type="text" name="tx_scheduler[nkwgok'
				. ucfirst($field) . ']" id="' . $fieldId . '" value="'
				. $taskInfo[tx_nkwgok_utility::extKey . ucfirst($field)] . '" size="10" />';

			$additionalFields[$fieldId] = array(
				'code'     => $fieldHtml,
				'label'    => 'LLL:EXT:nkwgok/locallang.xml:scheduler.convertCSV.' . $field,
				'cshKey'   => '',
				'cshLabel' => $fieldId
			);
		}

		return $additionalFields;
	}

	/**
	 * Checks any additional data that is relevant to this task. If the task
	 * class is not relevant, the method is expected to return TRUE
	 *
	 * @param array $submittedData reference to the array containing the data submitted by the user
	 * @param tx_scheduler_module1 $parentObject: reference to the calling object (Scheduler's BE module)
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule) {
		$result = FALSE;

		$submittedData['nkwgokStartPageId'] = intval($submittedData['nkwgokStartPageId']);

		if ($submittedData['nkwgokStartPageId'] <= 0) {
			$schedulerModule->addMessage('Invalid Page ID given', 3);
		} else {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * Saves any additional input into the current task object if the task class matches.
	 *
	 * @param array $submittedData: array containing the data submitted by the user
	 * @param tx_scheduler_Task $task reference to the current task object
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->nkwgokStartPageId = $submittedData['nkwgokStartPageId'];
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/scheduler/class.tx_nkwgok_scheduler_convertcsv.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/scheduler/class.tx_nkwgok_scheduler_convertcsv.php']);
}

?>
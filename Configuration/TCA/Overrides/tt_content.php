<?php

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Subugoe.nkwgok',
    'Pi1',
    'Nkwgok'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['nkwgok_pi1'] = 'layout,select_key,pages';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['nkwgok_pi1'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('nkwgok_pi1', 'FILE:EXT:nkwgok/Configuration/FlexForms/flexform.xml');

<?php

########################################################################
# Extension Manager/Repository config file for ext "nkwgok".
#
# Auto generated 01-09-2010 07:58
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'GOK',
	'description' => '',
	'category' => 'plugin',
	'author' => 'Nils K. Windisch',
	'author_email' => 'windisch@sub.uni-goettingen.de',
	'shy' => '',
	'dependencies' => 'nkwlib,ke_stats',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'Goettingen State and University Library, Germany http://www.sub.uni-goettingen.de',
	'version' => '0.0.24',
	'constraints' => array(
		'depends' => array(
			'nkwlib' => '',
			'ke_stats' => '',
			't3jquery' => '1.8.15-0.0.0'
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:23:{s:9:"ChangeLog";s:4:"8a0a";s:10:"README.txt";s:4:"68d3";s:16:"ext_autoload.php";s:4:"e4c2";s:21:"ext_conf_template.txt";s:4:"1f13";s:12:"ext_icon.gif";s:4:"c8ca";s:17:"ext_localconf.php";s:4:"b36a";s:14:"ext_tables.php";s:4:"fade";s:14:"ext_tables.sql";s:4:"e1b7";s:23:"icon_tx_nkwgok_data.gif";s:4:"475a";s:13:"locallang.xml";s:4:"0533";s:17:"locallang_csh.xml";s:4:"4d15";s:16:"locallang_db.xml";s:4:"2929";s:7:"tca.php";s:4:"cfd9";s:19:"doc/wizard_form.dat";s:4:"5c2a";s:20:"doc/wizard_form.html";s:4:"08b5";s:23:"lib/class.tx_nkwgok.php";s:4:"f2a9";s:26:"lib/class.tx_nkwgok_ff.php";s:4:"a175";s:31:"lib/class.tx_nkwgok_loadxml.php";s:4:"0cd8";s:11:"lib/get.php";s:4:"387f";s:15:"lib/loading.gif";s:4:"7b97";s:27:"pi1/class.tx_nkwgok_pi1.php";s:4:"c231";s:16:"pi1/flexform.xml";s:4:"fd81";s:17:"pi1/locallang.xml";s:4:"76c3";}',
	'suggests' => array(
	),
);

?>
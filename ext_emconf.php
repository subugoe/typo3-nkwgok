<?php

########################################################################
# Extension Manager/Repository config file for ext "nkwgok".
#
# Auto generated 11-11-2011 12:43
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'GOK',
	'description' => 'Displays Göttingen Local Classification (GOK) information on the page as a tree or a series of menus.',
	'category' => 'plugin',
	'author' => 'Nils K. Windisch, Sven-S. Porst',
	'author_email' => 'windisch@sub.uni-goettingen.de, porst@sub.uni-goettingen.de',
	'shy' => '',
	'dependencies' => 't3jquery',
	'conflicts' => '',
	'suggests' => 'ke_stats',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => 'fileadmin/gok,fileadmin/gok/xml,fileadmin/gok/hitcounts,fileadmin/gok/csv,',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'Göttingen State and University Library, Germany http://www.sub.uni-goettingen.de',
	'version' => '1.1.0',
	'constraints' => array(
		'depends' => array(
			't3jquery' => '1.8.15-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'ke_stats' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:29:{s:9:"ChangeLog";s:4:"5b63";s:15:"README.markdown";s:4:"e02e";s:16:"ext_autoload.php";s:4:"ea75";s:21:"ext_conf_template.txt";s:4:"059d";s:12:"ext_icon.gif";s:4:"c8ca";s:17:"ext_localconf.php";s:4:"696c";s:14:"ext_tables.php";s:4:"bea0";s:14:"ext_tables.sql";s:4:"3946";s:23:"icon_tx_nkwgok_data.gif";s:4:"614f";s:13:"locallang.xml";s:4:"49d8";s:16:"locallang_db.xml";s:4:"9ece";s:12:"t3jquery.txt";s:4:"1bfe";s:7:"tca.php";s:4:"0fcc";s:23:"lib/class.tx_nkwgok.php";s:4:"7613";s:26:"lib/class.tx_nkwgok_ff.php";s:4:"2c7b";s:11:"lib/get.php";s:4:"5cd5";s:27:"pi1/class.tx_nkwgok_pi1.php";s:4:"1af8";s:16:"pi1/flexform.xml";s:4:"07f8";s:17:"pi1/locallang.xml";s:4:"ec68";s:14:"res/nkwgok.css";s:4:"0c43";s:41:"scheduler/class.tx_nkwgok_checknewcsv.php";s:4:"fe87";s:40:"scheduler/class.tx_nkwgok_convertcsv.php";s:4:"7557";s:39:"scheduler/class.tx_nkwgok_importall.php";s:4:"8aef";s:42:"scheduler/class.tx_nkwgok_loadfromopac.php";s:4:"27f2";s:37:"scheduler/class.tx_nkwgok_loadxml.php";s:4:"28ca";s:70:"scheduler/class.tx_nkwgok_scheduler_convertcsvadditionalparameters.php";s:4:"343b";s:39:"scheduler/class.tx_nkwgok_updatecsv.php";s:4:"12b2";s:23:"scheduler/locallang.xml";s:4:"2d37";s:16:"static/setup.txt";s:4:"2ac2";}',
);

?>
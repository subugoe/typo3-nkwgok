<?php

########################################################################
# Extension Manager/Repository config file for ext "nkwgok".
#
# Auto generated 25-03-2011 11:50
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
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'Göttingen State and University Library, Germany http://www.sub.uni-goettingen.de',
	'version' => '0.9.0',
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
	'_md5_values_when_last_written' => 'a:24:{s:9:"ChangeLog";s:4:"f804";s:10:"README.txt";s:4:"2718";s:16:"ext_autoload.php";s:4:"23a8";s:21:"ext_conf_template.txt";s:4:"1ab4";s:12:"ext_icon.gif";s:4:"c8ca";s:17:"ext_localconf.php";s:4:"c583";s:14:"ext_tables.php";s:4:"aed5";s:14:"ext_tables.sql";s:4:"88d3";s:23:"icon_tx_nkwgok_data.gif";s:4:"614f";s:13:"locallang.xml";s:4:"373b";s:16:"locallang_db.xml";s:4:"ff22";s:12:"t3jquery.txt";s:4:"1bfe";s:7:"tca.php";s:4:"7586";s:23:"lib/class.tx_nkwgok.php";s:4:"6a17";s:26:"lib/class.tx_nkwgok_ff.php";s:4:"d673";s:33:"lib/class.tx_nkwgok_importall.php";s:4:"bf7b";s:36:"lib/class.tx_nkwgok_loadfromopac.php";s:4:"a0c4";s:35:"lib/class.tx_nkwgok_loadhistory.php";s:4:"539c";s:31:"lib/class.tx_nkwgok_loadxml.php";s:4:"7e40";s:11:"lib/get.php";s:4:"42e3";s:27:"pi1/class.tx_nkwgok_pi1.php";s:4:"18ce";s:16:"pi1/flexform.xml";s:4:"ac68";s:17:"pi1/locallang.xml";s:4:"cd42";s:14:"res/nkwgok.css";s:4:"99a9";}',
);

?>
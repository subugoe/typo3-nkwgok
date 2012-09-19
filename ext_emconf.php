<?php

########################################################################
# Extension Manager/Repository config file for ext "nkwgok".
#
# Auto generated 19-09-2012 19:13
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Subject Hierarchy / GOK',
	'description' => 'Displays subject hierarchies as trees, menus or columns',
	'category' => 'plugin',
	'author' => 'Nils K. Windisch, Sven-S. Porst',
	'author_email' => 'windisch@sub.uni-goettingen.de, porst@sub.uni-goettingen.de',
	'shy' => '',
	'dependencies' => 't3jquery',
	'conflicts' => '',
	'suggests' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => 'fileadmin/gok,fileadmin/gok/xml,fileadmin/gok/hitcounts,fileadmin/gok/csv,',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'Göttingen State and University Library, Germany http://www.sub.uni-goettingen.de',
	'version' => '2.2.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.6.0-0.0.0',
			't3jquery' => '1.8.15-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'php' => '5.3.8-0.0.0',
			'typo3' => '4.6.7-0.0.0',
		),
	),
	'_md5_values_when_last_written' => 'a:30:{s:9:"ChangeLog";s:4:"9f01";s:16:"ext_autoload.php";s:4:"83bf";s:21:"ext_conf_template.txt";s:4:"7399";s:12:"ext_icon.gif";s:4:"c8ca";s:17:"ext_localconf.php";s:4:"799a";s:14:"ext_tables.php";s:4:"e7e1";s:14:"ext_tables.sql";s:4:"7ad0";s:23:"icon_tx_nkwgok_data.gif";s:4:"614f";s:13:"locallang.xml";s:4:"b29c";s:16:"locallang_db.xml";s:4:"bca4";s:15:"README.markdown";s:4:"ce3d";s:12:"t3jquery.txt";s:4:"1bfe";s:7:"tca.php";s:4:"0fcc";s:23:"lib/class.tx_nkwgok.php";s:4:"576d";s:26:"lib/class.tx_nkwgok_ff.php";s:4:"2c7b";s:28:"lib/class.tx_nkwgok_menu.php";s:4:"42aa";s:28:"lib/class.tx_nkwgok_tree.php";s:4:"1b7e";s:11:"lib/get.php";s:4:"b972";s:27:"pi1/class.tx_nkwgok_pi1.php";s:4:"e326";s:16:"pi1/flexform.xml";s:4:"20e9";s:17:"pi1/locallang.xml";s:4:"2bca";s:14:"res/nkwgok.css";s:4:"13c6";s:41:"scheduler/class.tx_nkwgok_checknewcsv.php";s:4:"40eb";s:40:"scheduler/class.tx_nkwgok_convertcsv.php";s:4:"2bd3";s:39:"scheduler/class.tx_nkwgok_importall.php";s:4:"d240";s:42:"scheduler/class.tx_nkwgok_loadfromopac.php";s:4:"1636";s:37:"scheduler/class.tx_nkwgok_loadxml.php";s:4:"dea1";s:70:"scheduler/class.tx_nkwgok_scheduler_convertcsvadditionalparameters.php";s:4:"343b";s:39:"scheduler/class.tx_nkwgok_updatecsv.php";s:4:"1e16";s:16:"static/setup.txt";s:4:"6144";}',
);

?>
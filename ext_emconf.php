<?php

########################################################################
# Extension Manager/Repository config file for ext "nkwgok".
#
# Auto generated 05-02-2013 13:53
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
	'version' => '3.4.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.8-0.0.0',
			'typo3' => '4.7.4-0.0.0',
			't3jquery' => '1.8.15-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:31:{s:16:"ext_autoload.php";s:4:"8bd5";s:21:"ext_conf_template.txt";s:4:"ffe5";s:12:"ext_icon.gif";s:4:"614f";s:17:"ext_localconf.php";s:4:"ed37";s:14:"ext_tables.php";s:4:"779e";s:14:"ext_tables.sql";s:4:"2723";s:13:"locallang.xml";s:4:"43b6";s:16:"locallang_db.xml";s:4:"cc98";s:10:"Readme.rst";s:4:"01ae";s:12:"t3jquery.txt";s:4:"1bfe";s:7:"tca.php";s:4:"d552";s:15:"doc/Changes.rst";s:4:"e13a";s:14:"doc/manual.sxw";s:4:"7e5b";s:14:"doc/Readme.rst";s:4:"01ae";s:23:"lib/class.tx_nkwgok.php";s:4:"f7dd";s:26:"lib/class.tx_nkwgok_ff.php";s:4:"8076";s:28:"lib/class.tx_nkwgok_menu.php";s:4:"8137";s:28:"lib/class.tx_nkwgok_tree.php";s:4:"5fb9";s:31:"lib/class.tx_nkwgok_utility.php";s:4:"e94d";s:11:"lib/get.php";s:4:"9156";s:27:"pi1/class.tx_nkwgok_pi1.php";s:4:"0f67";s:16:"pi1/flexform.xml";s:4:"20e9";s:17:"pi1/locallang.xml";s:4:"2bca";s:14:"res/nkwgok.css";s:4:"13c6";s:41:"scheduler/class.tx_nkwgok_checknewcsv.php";s:4:"6935";s:40:"scheduler/class.tx_nkwgok_convertcsv.php";s:4:"9f3b";s:39:"scheduler/class.tx_nkwgok_importall.php";s:4:"2b75";s:42:"scheduler/class.tx_nkwgok_loadfromopac.php";s:4:"72b6";s:37:"scheduler/class.tx_nkwgok_loadxml.php";s:4:"5541";s:39:"scheduler/class.tx_nkwgok_updatecsv.php";s:4:"2ae1";s:16:"static/setup.txt";s:4:"e2aa";}',
);

?>
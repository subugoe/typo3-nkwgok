<?php

########################################################################
# Extension Manager/Repository config file for ext "nkwgok".
#
# Auto generated 28-02-2011 15:19
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
	'dependencies' => 'nkwlib,ke_stats,t3jquery',
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
	'version' => '0.7.0',
	'constraints' => array(
		'depends' => array(
			'nkwlib' => '',
			'ke_stats' => '',
			't3jquery' => '1.8.15-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:24:{s:9:"ChangeLog";s:4:"0e5a";s:10:"README.txt";s:4:"68d3";s:16:"ext_autoload.php";s:4:"e4c2";s:21:"ext_conf_template.txt";s:4:"05a8";s:12:"ext_icon.gif";s:4:"c8ca";s:17:"ext_localconf.php";s:4:"b36a";s:14:"ext_tables.php";s:4:"83aa";s:14:"ext_tables.sql";s:4:"88d3";s:23:"icon_tx_nkwgok_data.gif";s:4:"475a";s:13:"locallang.xml";s:4:"0533";s:17:"locallang_csh.xml";s:4:"4d15";s:16:"locallang_db.xml";s:4:"597e";s:12:"t3jquery.txt";s:4:"1bfe";s:7:"tca.php";s:4:"150b";s:19:"doc/wizard_form.dat";s:4:"5c2a";s:20:"doc/wizard_form.html";s:4:"08b5";s:23:"lib/class.tx_nkwgok.php";s:4:"8b21";s:26:"lib/class.tx_nkwgok_ff.php";s:4:"d673";s:31:"lib/class.tx_nkwgok_loadxml.php";s:4:"c8b6";s:11:"lib/get.php";s:4:"df54";s:27:"pi1/class.tx_nkwgok_pi1.php";s:4:"3f25";s:16:"pi1/flexform.xml";s:4:"3fab";s:17:"pi1/locallang.xml";s:4:"92e2";s:23:"scripts/getHitCounts.py";s:4:"fc00";}',
	'suggests' => array(
	),
);

?>
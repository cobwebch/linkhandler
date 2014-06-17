<?php

########################################################################
# Extension Manager/Repository config file for ext: "linkhandler"
#
# Auto generated 20-04-2009 11:43
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'AOE link handler',
	'description' => 'Enables userfriendly links to records like tt_news etc... Configure new Tabs to the link-wizard. (by AOE media GmbH)',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '1.1.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Daniel Poetzinger, Michael Klapper',
	'author_email' => 'mylastname@aoemedia.de',
	'author_company' => 'AOE media GmbH',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '6.0.0-6.2.99',
		),
		'conflicts' => array(
			'ch_rterecords',
			'tinymce_rte',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:23:{s:9:"ChangeLog";s:4:"05f9";s:10:"README.txt";s:4:"ee2d";s:41:"class.tx_linkhandler_browselinkshooks.php";s:4:"c32e";s:32:"class.tx_linkhandler_handler.php";s:4:"d45f";s:21:"ext_conf_template.txt";s:4:"6e68";s:12:"ext_icon.gif";s:4:"f19a";s:17:"ext_localconf.php";s:4:"cc5b";s:14:"ext_tables.php";s:4:"64cd";s:42:"classes/class.tx_linkhandler_recordTab.php";s:4:"3272";s:47:"classes/interface.tx_linkhandler_tabHandler.php";s:4:"1ca3";s:50:"classes/record/class.TBE_browser_recordListRTE.php";s:4:"d7f9";s:51:"classes/record/class.tx_linkhandler_recordsTree.php";s:4:"5148";s:14:"doc/manual.sxw";s:4:"653d";s:19:"doc/wizard_form.dat";s:4:"cfc2";s:20:"doc/wizard_form.html";s:4:"c70c";s:35:"patch/4.1/class.ux_browse_links.php";s:4:"6c5a";s:43:"patch/4.1/class.ux_t3lib_parsehtml_proc.php";s:4:"d0de";s:36:"patch/4.1/class.ux_tslib_content.php";s:4:"c23e";s:50:"patch/4.1/class.ux_tx_rtehtmlarea_browse_links.php";s:4:"4328";s:39:"patch/4.1/class.ux_ux_tslib_content.php";s:4:"df20";s:52:"patch/interfaces/interface.t3lib_browselinkshook.php";s:4:"258f";s:33:"static/link_handler/constants.txt";s:4:"1e06";s:29:"static/link_handler/setup.txt";s:4:"f86a";}',
	'suggests' => array(
	),
);

?>

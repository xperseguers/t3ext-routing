<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "routing".
 *
 * Auto generated 29-05-2014 12:20
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Request Routing Service',
	'description' => 'Service to route HTTP/REST requests to your own controller/actions.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '0.1.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'alpha',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Xavier Perseguers (Causal)',
	'author_email' => 'xavier@causal.ch',
	'author_company' => 'Causal Sàrl',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:12:{s:12:"ext_icon.png";s:4:"8468";s:15:"ext_icon@2x.png";s:4:"3579";s:17:"ext_localconf.php";s:4:"3f92";s:10:"README.rst";s:4:"a38e";s:40:"Classes/Controller/RoutingController.php";s:4:"6ca3";s:29:"Classes/Library/Spyc/Spyc.php";s:4:"73bb";s:26:"Documentation/Includes.txt";s:4:"ef74";s:23:"Documentation/Index.rst";s:4:"7509";s:26:"Documentation/Settings.yml";s:4:"da37";s:39:"Documentation/DeveloperManual/Index.rst";s:4:"b5bb";s:32:"Documentation/Images/headers.png";s:4:"dfb3";s:36:"Documentation/Introduction/Index.rst";s:4:"83ed";}',
);

?>
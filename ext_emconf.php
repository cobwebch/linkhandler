<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Generic record link handler',
    'description' => 'Create links to any record (e.g. news)',
    'category' => 'plugin',
    'version' => '3.1.2',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'author' => 'Francois Suter',
    'author_email' => 'typo3@cobweb.ch',
    'author_company' => 'Cobweb Development Sarl',
    'constraints' => array(
        'depends' => array(
            'typo3' => '7.6.1-7.9.99',
        ),
        'conflicts' => array(
            'ch_rterecords' => '',
            'tinymce_rte' => '',
        ),
        'suggests' => array(),
    ),
);

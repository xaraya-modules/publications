<?php

/**
 * Publications Module
 *
 * @package modules
 * @subpackage publications module
 * @category Third Party Xaraya Module
 * @version 2.0.0
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author mikespub
 */

/**
 * Initial setup for the publications module
 */

// TODO: load configuration from file(s) ?

// Configuration of the different publication type fields
// An empty label means it's (currently) not used for that type
$config = [];

$config['news'] = [
    'title' => ['label'  => xarMLS::translate('Title'),
        'format' => 'textbox',
        'input'  => 1, ],
    'summary' => ['label'  => xarMLS::translate('Introduction'),
        'format' => 'textarea_medium',
        'input'  => 1, ],
    'body' => ['label'  => xarMLS::translate('Body'),
        'format' => 'textarea_large',
        'input'  => 1, ],
    'notes' => ['label'  => xarMLS::translate('Notes'),
        'format' => 'textarea',
        'input'  => 0, ],
    'owner' => ['label'  => xarMLS::translate('Author'),
        'format' => 'username',
        'input'  => 0, ],
    'pubdate' => ['label'  => xarMLS::translate('Publication Date'),
        'format' => 'calendar',
        'input'  => 1, ],
    'status' => ['label'  => xarMLS::translate('Status'),
        'format' => 'status',
        'input'  => 0, ],
];
$config['docs'] = [
    'title' => ['label'  => xarMLS::translate('Subject'),
        'format' => 'textbox',
        'input'  => 1, ],
    'summary' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
    'body' => ['label'  => xarMLS::translate('Content'),
        'format' => 'textupload',
        'input'  => 1, ],
    'notes' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
    'owner' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
    'pubdate' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
    'status' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
];
// TODO: adapt/evaluate for reviews
$config['reviews'] = [
    'title' => ['label'  => xarMLS::translate('Title'),
        'format' => 'textbox',
        'input'  => 1, ],
    'summary' => ['label'  => xarMLS::translate('Review'),
        'format' => 'textarea_large',
        'input'  => 1, ],
    'body' => ['label'  => xarMLS::translate('Related Link'),
        'format' => 'urltitle',
        'input'  => 1, ],
    'notes' => ['label'  => xarMLS::translate('Image'),
        'format' => 'image',
        'input'  => 1, ],
    'owner' => ['label'  => xarMLS::translate('Reviewer'),
        'format' => 'username',
        'input'  => 0, ],
    'pubdate' => ['label'  => xarMLS::translate('Date'),
        'format' => 'calendar',
        'input'  => 0, ],
    'status' => ['label'  => xarMLS::translate('Status'),
        'format' => 'status',
        'input'  => 0, ],
];
$config['faqs'] = [
    'title' => ['label'  => xarMLS::translate('Question'),
        'format' => 'textbox',
        'input'  => 1, ],
    'summary' => ['label'  => xarMLS::translate('Details'),
        'format' => 'textarea',
        'input'  => 1, ],
    'body' => ['label'  => xarMLS::translate('Answer'),
        'format' => 'textarea_large',
        'input'  => 0, ],
    'notes' => ['label'  => xarMLS::translate('Submitted by'),
        'format' => 'textbox',
        'input'  => 1, ],
    'owner' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
    'pubdate' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
    'status' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
];
$config['pictures'] = [
    'title' => ['label'  => xarMLS::translate('Title'),
        'format' => 'textbox',
        'input'  => 1, ],
    'summary' => ['label'  => xarMLS::translate('Thumbnail'),
        'format' => 'image',
        'input'  => 1, ],
    'body' => ['label'  => xarMLS::translate('Picture'),
        'format' => 'image',
        'input'  => 1, ],
    'notes' => ['label'  => xarMLS::translate('Comments'),
        'format' => 'textarea',
        'input'  => 1, ],
    'owner' => ['label'  => xarMLS::translate('Author'),
        'format' => 'username',
        'input'  => 0, ],
    'pubdate' => ['label'  => xarMLS::translate('Publication Date'),
        'format' => 'calendar',
        'input'  => 0, ],
    'status' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
];
// TODO: add fields for editorials etc.
$config['weblinks'] = [
    'title' => ['label'  => xarMLS::translate('Title'),
        'format' => 'textbox',
        'input'  => 1, ],
    'summary' => ['label'  => xarMLS::translate('Description'),
        'format' => 'textarea',
        'input'  => 1, ],
    'body' => ['label'  => xarMLS::translate('Website'),
        'format' => 'url',
        'input'  => 1, ],
    'notes' => ['label'  => xarMLS::translate('Source'),
        'format' => 'textbox',
        'input'  => 1, ],
    'owner' => ['label'  => xarMLS::translate('Submitter'),
        'format' => 'username',
        'input'  => 0, ],
    'pubdate' => ['label'  => xarMLS::translate('Submitted on'),
        'format' => 'calendar',
        'input'  => 0, ],
    'status' => ['label'  => xarMLS::translate('Status'),
        'format' => 'status',
        'input'  => 0, ],
];

$config['quotes'] = [
    'title' => ['label'  => xarMLS::translate('Author'),
        'format' => 'textbox',
        'input'  => 1, ],
    'summary' => ['label'  => xarMLS::translate('Quotes'),
        'format' => 'textarea',
        'input'  => 1, ],
    'body' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
    'notes' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
    'owner' => ['label'  => xarMLS::translate('Submitted By'),
        'format' => 'username',
        'input'  => 0, ],
    'pubdate' => ['label'  => xarMLS::translate('Submitted On'),
        'format' => 'calendar',
        'input'  => 0, ],
    'status' => ['label'  => xarMLS::translate('Status'),
        'format' => 'status',
        'input'  => 0, ],
];

$config['downloads'] = [
    'title' => ['label'  => xarMLS::translate('Title'),
        'format' => 'textbox',
        'input'  => 1, ],
    'summary' => ['label'  => xarMLS::translate('Summary'),
        'format' => 'textarea',
        'input'  => 1, ],
    'body' => ['label'  => '',
        'format' => 'static',
        'input'  => 0, ],
    'notes' => ['label'  => xarMLS::translate('Upload File'),
        'format' => 'fileupload',
        'input'  => 1, ],
    'owner' => ['label'  => xarMLS::translate('Author'),
        'format' => 'username',
        'input'  => 0, ],
    'pubdate' => ['label'  => xarMLS::translate('Submitted On'),
        'format' => 'calendar',
        'input'  => 0, ],
    'status' => ['label'  => xarMLS::translate('Status'),
        'format' => 'status',
        'input'  => 0, ],
];
/*
    $config['generic'] = array(
        'title' => array('label'  => xarMLS::translate('Title'),
                         'format' => 'textbox',
                         'input'  => 1),
        'summary' => array('label'  => xarMLS::translate('Summary'),
                         'format' => 'textarea_medium',
                         'input'  => 1),
        'body' => array('label'  => xarMLS::translate('Body'),
                         'format' => 'textarea_large',
                         'input'  => 1),
        'notes' => array('label'  => xarMLS::translate('Notes'),
                         'format' => 'textarea',
                         'input'  => 0),
        'owner' => array('label'  => xarMLS::translate('Author'),
                         'format' => 'username',
                         'input'  => 0),
        'pubdate' => array('label'  => xarMLS::translate('Publication Date'),
                         'format' => 'calendar',
                         'input'  => 0),
        'status' => array('label'  => xarMLS::translate('Status'),
                         'format' => 'status',
                         'input'  => 0),
    );
*/

// The list of currently supported publication types
$pubtypes = [
    [1, 'news', 'News Publications',
        serialize($config['news']), ],
    [2, 'docs', 'Documents',
        serialize($config['docs']), ],
    [3, 'reviews', 'Reviews',
        serialize($config['reviews']), ],
    [4, 'faqs', 'FAQs',
        serialize($config['faqs']), ],
    [5, 'pictures', 'Pictures',
        serialize($config['pictures']), ],
    [6, 'weblinks', 'Web Links',
        serialize($config['weblinks']), ],
    [7, 'quotes', 'Random Quotes',
        serialize($config['quotes']), ],
    [8, 'downloads', 'Downloads',
        serialize($config['downloads']), ],
];

// Some starting categories as an example
$categories = [];

$categories[] = ['name' => 'Generic1',
    'description' => 'Generic Category 1',
    'children' => ['Generic1 Sub1',
        'Generic1 Sub2', ], ];
$categories[] = ['name' => 'Generic2',
    'description' => 'Generic Category 2',
    'children' => ['Generic2 Sub1',
        'Generic2 Sub2', ], ];
$categories[] = ['name' => 'Topics',
    'description' => 'News Topics',
    'children' => ['Topic 1',
        'Topic 2', ], ];
$categories[] = ['name' => 'Categories',
    'description' => 'News Categories',
    'children' => ['Category 1',
        'Category 2', ], ];
$categories[] = ['name' => 'Sections',
    'description' => 'Document Sections',
    'children' => ['Section 1',
        'Section 2', ], ];
$categories[] = ['name' => 'FAQ',
    'description' => 'Frequently Asked Questions',
    'children' => ['FAQ Type 1',
        'FAQ Type 2', ], ];
$categories[] = ['name' => 'Gallery',
    'description' => 'Picture Gallery',
    'children' => ['Album 1',
        'Album 2', ], ];
$categories[] = ['name' => 'Web Links',
    'description' => 'Web Link Categories',
    'children' => ['Link Category 1',
        'Link Category 2', ], ];
$categories[] = ['name' => 'Random Quotes',
    'description' => 'Random Quote Categories',
    'children' => ['Quote Category 1',
        'Quote Category 2', ], ];
$categories[] = ['name' => 'Downloads',
    'description' => 'Download Categories',
    'children' => ['Download Category 1',
        'Download Category 2', ], ];

// publications settings for each publication type
$settings = [];

// TODO: split into content- & publication-related settings in the future ?

// news publications can be in old-style Topics & Categories, and in new Generic1
$settings[1] = ['number_of_columns'    => 2,
    'items_per_page'       => 10,
    'defaultview'          => 1,
    'show_categories'      => 1,
    'show_catcount'        => 0,
    'show_prevnext'        => 0,
    'show_comments'        => 1,
    'show_keywords'        => 1,
    'show_hitcount'        => 1,
    'show_ratings'         => 0,
    'show_archives'        => 1,
    'show_map'             => 1,
    'show_publinks'        => 0,
    'show_pubcount'        => 1,
    'do_transform'         => 0,
    'title_transform'      => 0,
    'usealias'             => 0,
    'page_template'        => '',
    'defaultstatus'        => 0,
    'defaultsort'          => 'date',
    // category names - will be replaced by cids in xarinit.php
    'categories'           => ['Topics',
        'Categories',
        'Generic1', ], ];

// section documents can be in old-style Sections, and in new Generic1
$settings[2] = ['number_of_columns'    => 0,
    'items_per_page'       => 20,
    // category name - will be replaced by 'c' . cid in xarinit.php
    'defaultview'          => 'Sections',
    'show_categories'      => 0,
    'show_catcount'        => 0,
    'show_prevnext'        => 1,
    'show_comments'        => 0,
    'show_keywords'        => 1,
    'show_hitcount'        => 0,
    'show_ratings'         => 0,
    'show_archives'        => 0,
    'show_map'             => 1,
    'show_publinks'        => 0,
    'show_pubcount'        => 1,
    'do_transform'         => 0,
    'title_transform'      => 0,
    'usealias'             => 0,
    'page_template'        => '',
    'defaultstatus'        => 2,
    'defaultsort'          => 'title',
    // category names - will be replaced by cids in xarinit.php
    'categories'           => ['Sections',
        'Generic1', ], ];

// reviews can be in new Generic1 (no categories in old-style reviews ?)
$settings[3] = ['number_of_columns'    => 2,
    'items_per_page'       => 20,
    'defaultview'          => 1,
    'show_categories'      => 1,
    'show_catcount'        => 0,
    'show_prevnext'        => 1,
    'show_comments'        => 0,
    'show_keywords'        => 1,
    'show_hitcount'        => 1,
    'show_ratings'         => 1,
    'show_archives'        => 1,
    'show_map'             => 1,
    'show_publinks'        => 0,
    'show_pubcount'        => 1,
    'do_transform'         => 0,
    'title_transform'      => 0,
    'usealias'             => 0,
    'page_template'        => '',
    'defaultstatus'        => 0,
    'defaultsort'          => 'date',
    // category names - will be replaced by cids in xarinit.php
    'categories'           => ['Generic1'], ];

// faqs can be in old-style FAQs, and in new Generic1
$settings[4] = ['number_of_columns'    => 0,
    'items_per_page'       => 20,
    // category name - will be replaced by 'c' . cid in xarinit.php
    'defaultview'          => 'FAQ',
    'show_categories'      => 1,
    'show_catcount'        => 0,
    'show_prevnext'        => 0,
    'show_comments'        => 0,
    'show_keywords'        => 1,
    'show_hitcount'        => 0,
    'show_ratings'         => 0,
    'show_archives'        => 0,
    'show_map'             => 1,
    'show_publinks'        => 0,
    'show_pubcount'        => 1,
    'do_transform'         => 0,
    'title_transform'      => 0,
    'usealias'             => 0,
    'page_template'        => '',
    'defaultstatus'        => 2,
    'defaultsort'          => 'title',
    // category names - will be replaced by cids in xarinit.php
    'categories'           => ['FAQ',
        'Generic1', ], ];

// pictures can be in Gallery and new Generic1
$settings[5] = ['number_of_columns'    => 3,
    'items_per_page'       => 12,
    'defaultview'          => 1,
    'show_categories'      => 0,
    'show_catcount'        => 0,
    'show_prevnext'        => 1,
    'show_comments'        => 0,
    'show_keywords'        => 1,
    'show_hitcount'        => 0,
    'show_ratings'         => 1,
    'show_archives'        => 0,
    'show_map'             => 1,
    'show_publinks'        => 0,
    'show_pubcount'        => 1,
    'do_transform'         => 0,
    'title_transform'      => 0,
    'usealias'             => 0,
    'page_template'        => '',
    'defaultstatus'        => 2,
    'defaultsort'          => 'date',
    // category names - will be replaced by cids in xarinit.php
    'categories'           => ['Gallery',
        'Generic1', ], ];

// weblinks can be in old-style Web Links, and in new Generic1
$settings[6] = ['number_of_columns'    => 0,
    'items_per_page'       => 20,
    'defaultview'          => 1,
    'show_categories'      => 1,
    'show_catcount'        => 0,
    'show_prevnext'        => 0,
    'show_comments'        => 0,
    'show_keywords'        => 1,
    'show_hitcount'        => 1,
    'show_ratings'         => 1,
    'show_archives'        => 0,
    'show_map'             => 1,
    'show_publinks'        => 0,
    'show_pubcount'        => 1,
    'do_transform'         => 0,
    'title_transform'      => 0,
    'usealias'             => 0,
    'page_template'        => '',
    'defaultstatus'        => 0,
    'defaultsort'          => 'date ASC',
    // category names - will be replaced by cids in xarinit.php
    'categories'           => ['Web Links',
        'Generic1', ], ];

// quotes can be in Random Quotes and in new Generic1
$settings[7] = ['number_of_columns'    => 0,
    'items_per_page'       => 20,
    'defaultview'          => 1,
    'show_categories'      => 1,
    'show_catcount'        => 0,
    'show_prevnext'        => 0,
    'show_comments'        => 0,
    'show_keywords'        => 1,
    'show_hitcount'        => 1,
    'show_ratings'         => 1,
    'show_archives'        => 0,
    'show_map'             => 1,
    'show_publinks'        => 0,
    'show_pubcount'        => 1,
    'do_transform'         => 0,
    'title_transform'      => 0,
    'usealias'             => 0,
    'page_template'        => '',
    'defaultstatus'        => 0,
    'defaultsort'          => 'date ASC',
    // category names - will be replaced by cids in xarinit.php
    'categories'           => ['Random Quotes',
        'Generic1', ], ];

// downloads can be in Downloads and in new Generic1
$settings[8] = ['number_of_columns'    => 0,
    'items_per_page'       => 20,
    'defaultview'          => 1,
    'show_categories'      => 1,
    'show_catcount'        => 0,
    'show_prevnext'        => 0,
    'show_comments'        => 0,
    'show_keywords'        => 1,
    'show_hitcount'        => 1,
    'show_ratings'         => 1,
    'show_archives'        => 0,
    'show_map'             => 1,
    'show_publinks'        => 0,
    'show_pubcount'        => 1,
    'do_transform'         => 0,
    'title_transform'      => 0,
    'usealias'             => 0,
    'page_template'        => '',
    'defaultstatus'        => 0,
    'defaultsort'          => 'date ASC',
    // category names - will be replaced by cids in xarinit.php
    'categories'           => ['Downloads',
        'Generic1', ], ];

// default settings
$settings[0] = ['number_of_columns'    => 0,
    'items_per_page'       => 20,
    'defaultview'          => 1,
    'show_categories'      => 1,
    'show_catcount'        => 0,
    'show_prevnext'        => 0,
    'show_comments'        => 1,
    'show_keywords'        => 1,
    'show_hitcount'        => 1,
    'show_ratings'         => 0,
    'show_archives'        => 1,
    'show_map'             => 1,
    'show_publinks'        => 0,
    'show_pubcount'        => 1,
    'do_transform'         => 0,
    'title_transform'      => 0,
    'usealias'             => 0,
    'page_template'        => '',
    'defaultstatus'        => 0,
    'defaultsort'          => 'date',
    // category names - will be replaced by cids in xarinit.php
    'categories'           => ['Generic1',
        'Generic2', ], ];

// default publication type is news publications
$defaultpubtype = 1;

<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Articles Module
 * @link http://xaraya.com/index.php/release/151.html
 * @author mikespub
 */
/**
 * Manage the tables in articles
 *
 * @return array with the tables used in articles
 */
function articles_xartables()
{
    // Initialise table array
    $xartable = array();
    // Get Site Prefix
    $sitePrefix = xarDB::getPrefix();
    // Name for articles database entities
    $articles = $sitePrefix . '_articles';

    // Table name
    $xartable['articles'] = $articles;

    // Column names
    $xartable['articles_column'] = array('id'      => $articles . '.id',
                                        'title'    => $articles . '.title',
                                        'summary'  => $articles . '.summary',
                                        'authorid' => $articles . '.authorid',
                                        'pubdate'  => $articles . '.pubdate',
                                        'pubtypeid' => $articles . '.pubtypeid',
                                        'pages'    => $articles . '.pages',
                                        'body'     => $articles . '.body',
                                        'notes'    => $articles . '.notes',
                                        'status'   => $articles . '.status',
                                        'language' => $articles . '.language');

    // Name for publication types table
    $publicationtypes = $sitePrefix . '_publication_types';

    // Table name
    $xartable['publication_types'] = $publicationtypes;

    // Column names
    $xartable['publication_types_column'] = array(
                'pubtypeid'      => $publicationtypes . '.pubtypeid',
                'pubtypename'    => $publicationtypes . '.pubtypename',
                'pubtypedescr'   => $publicationtypes . '.pubtypedescr',
                'pubtypeconfig'   => $publicationtypes . '.pubtypeconfig');

    // Return table information
    return $xartable;
}

?>

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
 * show monthly archive (Archives-like)
 */
function publications_user_archive(array $args = [], $context = null)
{
    // Xaraya security
    if (!xarSecurity::check('ModeratePublications')) {
        return;
    }

    // Get parameters from user
    if (!xarVar::fetch('ptid', 'id', $ptid, xarModVars::get('publications', 'defaultpubtype'), xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('sort', 'enum:d:t:1:2', $sort, 'd', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('month', 'str', $month, '', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('cids', 'array', $cids, null, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('catid', 'str', $catid, '', xarVar::NOT_REQUIRED)) {
        return;
    }

    // Override if needed from argument array
    extract($args);

    // Get publication types
    $pubtypes = xarMod::apiFunc('publications', 'user', 'get_pubtypes');

    // Check that the publication type is valid
    if (empty($ptid) || !isset($pubtypes[$ptid])) {
        $ptid = null;
    }

    if (empty($ptid)) {
        if (!xarSecurity::check('ViewPublications', 0, 'Publication', 'All:All:All:All')) {
            return xarML('You have no permission to view these items');
        }
    } elseif (!xarSecurity::check('ViewPublications', 0, 'Publication', $ptid . ':All:All:All')) {
        return xarML('You have no permission to view these items');
    }

    $state = [PUBLICATIONS_STATE_FRONTPAGE,PUBLICATIONS_STATE_APPROVED];

    $seencid = [];
    $andcids = false;
    // turn $catid into $cids array and set $andcids flag
    if (!empty($catid)) {
        if (strpos($catid, ' ')) {
            $cids = explode(' ', $catid);
            $andcids = true;
        } elseif (strpos($catid, '+')) {
            $cids = explode('+', $catid);
            $andcids = true;
        } elseif (strpos($catid, '-')) {
            $cids = explode('-', $catid);
            $andcids = false;
        } else {
            $cids = [$catid];
            if (strstr($catid, '_')) {
                $andcids = false; // don't combine with current category
            } else {
                $andcids = true;
            }
        }
    }
    if (isset($cids) && is_array($cids)) {
        foreach ($cids as $cid) {
            if (!empty($cid) && preg_match('/^_?[0-9]+$/', $cid)) {
                $seencid[$cid] = 1;
            }
        }
        $cids = array_keys($seencid);
        sort($cids, SORT_NUMERIC);
        if (empty($catid) && count($cids) > 1) {
            $andcids = true;
        }
    } else {
        $cids = null;
    }

    // QUESTION: work with user-dependent time settings or not someday ?
    // Set the start and end date for that month
    if (!empty($month) && preg_match('/^(\d{4})-(\d+)$/', $month, $matches)) {
        $startdate = gmmktime(0, 0, 0, $matches[2], 1, $matches[1]);
        // PHP allows month > 12 :-)
        $enddate = gmmktime(0, 0, 0, $matches[2] + 1, 1, $matches[1]);
        if ($enddate > time()) {
            $enddate = time();
        }
    } else {
        $startdate = '';
        $enddate = time();
        if (!empty($month) && $month != 'all') {
            $month = '';
        }
    }

    // Load API
    if (!xarMod::apiLoad('publications', 'user')) {
        return;
    }

    if (!empty($ptid) && !empty($pubtypes[$ptid]['config']['pubdate']['label'])) {
        $showdate = 1;
    } else {
        $showdate = 0;
        foreach (array_keys($pubtypes) as $pubid) {
            if (!empty($pubtypes[$pubid]['config']['pubdate']['label'])) {
                $showdate = 1;
                break;
            }
        }
    }

    // Get monthly statistics
    $monthcount = xarMod::apiFunc(
        'publications',
        'user',
        'getmonthcount',
        ['ptid' => $ptid,
                                     'state' => $state,
                                     'enddate' => time(), ]
    );
    if (empty($monthcount)) {
        $monthcount = [];
    }
    krsort($monthcount);
    reset($monthcount);
    $months = [];
    $total = 0;
    foreach ($monthcount as $thismonth => $count) {
        if ($thismonth == $month) {
            $mlink = '';
        } else {
            $mlink = xarController::URL(
                'publications',
                'user',
                'archive',
                ['ptid' => $ptid,
                                    'month' => $thismonth, ]
            );
        }
        $months[] = ['month' => $thismonth,
                          'mcount' => $count,
                          'mlink' => $mlink, ];
        $total += $count;
    }
    if (empty($ptid)) {
        $thismonth = xarML('All Publications');
    } else {
        $thismonth = xarML('All') . ' ' . $pubtypes[$ptid]['description'];
    }
    if ($month == 'all') {
        $mlink = '';
    } else {
        $mlink = xarController::URL(
            'publications',
            'user',
            'archive',
            ['ptid' => $ptid,
                                'month' => 'all', ]
        );
    }
    $months[] = ['month' => $thismonth,
                      'mcount' => $total,
                      'mlink' => $mlink, ];

    // Load API
    if (!xarMod::apiLoad('categories', 'user')) {
        return;
    }

    // Get the list of root categories for this publication type
    if (!empty($ptid)) {
        $rootcats = xarMod::apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications','itemtype' => $ptid]);
    } else {
        $rootcats = xarMod::apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications','itemtype' => 0]);
    }
    $catlist = [];
    $catinfo = [];
    $catsel = [];
    if (!empty($rootcats)) {
        // TODO: do this in categories API ?
        $count = 1;
        foreach ($rootcats as $cid) {
            if (empty($cid)) {
                continue;
            }
            // save the name and root category for each child
            $cats = xarMod::apiFunc(
                'categories',
                'user',
                'getcat',
                ['cid' => $cid['category_id'],
                                       'return_itself' => true,
                                       'getchildren' => true, ]
            );
            foreach ($cats as $info) {
                $item = [];
                $item['name'] = $info['name'];
                $item['root'] = $cid['category_id'];
                $catinfo[$info['cid']] = $item;
            }
            // don't allow sorting by category when viewing all publications
            //if ($sort == $count || $month == 'all') {
            if ($sort == $count) {
                $link = '';
            } else {
                $link = xarController::URL(
                    'publications',
                    'user',
                    'archive',
                    ['ptid' => $ptid,
                                       'month' => $month,
                                       'sort' => $count, ]
                );
            }
            // catch more faulty categories assignments
            if (isset($catinfo[$cid['category_id']])) {
                $catlist[] = ['cid' => $cid['category_id'],
                                   'name' => $catinfo[$cid['category_id']]['name'],
                                   'link' => $link, ];
                $catsel[] = xarMod::apiFunc(
                    'categories',
                    'visual',
                    'makeselect',
                    ['cid' => $cid['category_id'],
                                                'return_itself' => true,
                                                'select_itself' => true,
                                                'values' => &$seencid,
                                                'multiple' => 0, ]
                );
                $count++;
            }
        }
    }

    // Get publications
    if ($month == 'all' || ($startdate && $enddate)) {
        $publications = xarMod::apiFunc(
            'publications',
            'user',
            'getall',
            ['ptid' => ($ptid ?? null),
                                       'startdate' => $startdate,
                                       'enddate' => $enddate,
                                       'state' => $state,
                                       'cids' => $cids,
                                       'andcids' => $andcids,
                                       'fields' => ['id','title',
                                                  'start_date','pubtype_id','cids', ],
                                      ]
        );
        if (!is_array($publications)) {
            $msg = xarML('Failed to retrieve publications in #(3)_#(1)_#(2).php', 'user', 'getall', 'publications');
            throw new DataNotFoundException(null, $msg);
        }
    } else {
        $publications = [];
    }

    // TODO: add print / recommend_us link for each article ?
    // TODO: add view count to table/query/template someday ?
    foreach ($publications as $key => $article) {
        $publications[$key]['link'] = xarController::URL(
            'publications',
            'user',
            'display',
            ['ptid' => isset($ptid) ? $publications[$key]['pubtype_id'] : null,
                                     'id' => $publications[$key]['id'], ]
        );
        if (empty($publications[$key]['title'])) {
            $publications[$key]['title'] = xarML('(none)');
        }
        /* TODO: move date formatting to template, delete this code after testing
                if ($showdate && !empty($publications[$key]['pubdate'])) {
                    $publications[$key]['date'] = xarLocale::formatDate("%Y-%m-%d %H:%M:%S",
                                                       $publications[$key]['pubdate']);
                } else {
                    $publications[$key]['date'] = '';
                }
        */
        // TODO: find some better way to do this...
        $list = [];
        // get all the categories for that article and put them under the
        // right root category
        if (!isset($publications[$key]['cids'])) {
            $publications[$key]['cids'] = [];
        }
        foreach ($publications[$key]['cids'] as $cid) {
            // skip unknown categories (e.g. when not under root categories)
            if (!isset($catinfo[$cid])) {
                continue;
            }
            if (!isset($list[$catinfo[$cid]['root']])) {
                $list[$catinfo[$cid]['root']] = [];
            }
            array_push($list[$catinfo[$cid]['root']], $cid);
        }
        // fill in the column corresponding to each root category
        $publications[$key]['cats'] = [];
        foreach ($catlist as $cat) {
            if (isset($list[$cat['cid']])) {
                $descr = '';
                // TODO: add links to category someday ?
                foreach ($list[$cat['cid']] as $cid) {
                    if (!empty($descr)) {
                        $descr .= '<br />';
                    }
                    $descr .= $catinfo[$cid]['name'];
                }
                $publications[$key]['cats'][] = ['list' => $descr];
            } else {
                $publications[$key]['cats'][] = ['list' => '-'];
            }
        }
    }

    // sort publications as requested
    if ($sort == 2 && count($catlist) > 1) {
        usort($publications, 'publications_archive_sortbycat10');
    } elseif ($sort == 1) {
        if (count($catlist) > 1) {
            usort($publications, 'publications_archive_sortbycat01');
        } elseif (count($catlist) > 0) {
            usort($publications, 'publications_archive_sortbycat0');
        }
    } elseif ($sort == 't') {
        usort($publications, 'publications_archive_sortbytitle');
    } else {
        $sort = 'd';
        // default sort by date is already done in getall() function
    }

    // add title header
    if ($sort == 't') {
        $link = '';
    } else {
        $link = xarController::URL(
            'publications',
            'user',
            'archive',
            ['ptid' => $ptid,
                               'month' => $month,
                               'sort' => 't', ]
        );
    }
    $catlist[] = ['cid' => 0,
                       'name' => xarML('Title'),
                       'link' => $link, ];
    $catsel[] = '<input type="submit" value="' . xarML('Filter') . '" />';

    if ($showdate) {
        // add date header
        if ($sort == 'd') {
            $link = '';
        } else {
            $link = xarController::URL(
                'publications',
                'user',
                'archive',
                ['ptid' => $ptid,
                                   'month' => $month, ]
            );
        }
        $catlist[] = ['cid' => 0,
                           'name' => xarML('Date'),
                           'link' => $link, ];
        $catsel[] = '&#160;';
    }

    // Save some variables to (temporary) cache for use in blocks etc.
    xarCoreCache::setCached('Blocks.publications', 'ptid', $ptid);
    if (!empty($cids)) {
        xarCoreCache::setCached('Blocks.publications', 'cids', $cids);
    }
    //if ($shownavigation) {
    xarCoreCache::setCached('Blocks.categories', 'module', 'publications');
    xarCoreCache::setCached('Blocks.categories', 'itemtype', $ptid);
    if (!empty($ptid) && !empty($pubtypes[$ptid]['description'])) {
        xarCoreCache::setCached('Blocks.categories', 'title', $pubtypes[$ptid]['description']);
        xarTpl::setPageTitle(xarML('Archive'), $pubtypes[$ptid]['description']);
    } else {
        xarTpl::setPageTitle(xarML('Archive'));
    }
    //}
    if (!empty($ptid)) {
        $settings = unserialize(xarModVars::get('publications', 'settings.' . $ptid));
    } else {
        $string = xarModVars::get('publications', 'settings');
        if (!empty($string)) {
            $settings = unserialize($string);
        }
    }
    if (!isset($show_publinks)) {
        if (!empty($settings['show_publinks'])) {
            $show_publinks = 1;
        } else {
            $show_publinks = 0;
        }
    }
    // show the number of publications for each publication type
    if (!isset($show_pubcount)) {
        if (!isset($settings['show_pubcount']) || !empty($settings['show_pubcount'])) {
            $show_pubcount = 1; // default yes
        } else {
            $show_pubcount = 0;
        }
    }
    //    $show_catcount = 0; // unused here

    // return template out
    $data = ['months' => $months,
                 'publications' => $publications,
                 'catlist' => $catlist,
                 'catsel' => $catsel,
                 'ptid' => $ptid,
                 'month' => $month,
                 'curlink' => xarController::URL(
                     'publications',
                     'user',
                     'archive',
                     ['ptid' => $ptid,
                                              'month' => $month,
                                               'sort' => $sort, ]
                 ),
                 'showdate' => $showdate,
                 'show_publinks' => $show_publinks,
                 'publabel' => xarML('Publication'),
                 'publinks' => xarMod::apiFunc(
                     'publications',
                     'user',
                     'getpublinks',
                     ['ptid' => $ptid,
                                                  'state' => [PUBLICATIONS_STATE_FRONTPAGE,PUBLICATIONS_STATE_APPROVED],
                                                  'count' => $show_pubcount,
                                                  // override default 'view'
                                                  'func' => 'archive', ]
                 ),
                 'maplabel' => xarML('View Publication Map'),
                 'maplink' => xarController::URL(
                     'publications',
                     'user',
                     'viewmap',
                     ['ptid' => $ptid]
                 ),
                 'viewlabel' => (empty($ptid) ? xarML('Back to Publications') : xarML('Back to') . ' ' . $pubtypes[$ptid]['description']),
                 'viewlink' => xarController::URL(
                     'publications',
                     'user',
                     'view',
                     ['ptid' => $ptid]
                 ), ];

    if (!empty($ptid)) {
        $template = $pubtypes[$ptid]['name'];
    } else {
        // TODO: allow templates per category ?
        $template = null;
    }

    $data['context'] ??= $context;
    return xarTpl::module('publications', 'user', 'archive', $data, $template);
}

/**
 * sorting functions for archive
 */

function publications_archive_sortbycat0($a, $b)
{
    return strcmp($a['cats'][0]['list'], $b['cats'][0]['list']);
}

function publications_archive_sortbycat01($a, $b)
{
    if ($a['cats'][0]['list'] == $b['cats'][0]['list']) {
        return strcmp($a['cats'][1]['list'], $b['cats'][1]['list']);
    } else {
        return strcmp($a['cats'][0]['list'], $b['cats'][0]['list']);
    }
}

function publications_archive_sortbycat10($a, $b)
{
    if ($a['cats'][1]['list'] == $b['cats'][1]['list']) {
        return strcmp($a['cats'][0]['list'], $b['cats'][0]['list']);
    } else {
        return strcmp($a['cats'][1]['list'], $b['cats'][1]['list']);
    }
}

function publications_archive_sortbytitle($a, $b)
{
    return strcmp($a['title'], $b['title']);
}

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
 * get an array of child categories with links and optional counts
 *
 * @param $args['state'] array of requested status(es) for the publications
 * @param $args['ptid'] publication type ID
 * @param $args['cid'] parent category ID
 * @param $args['showcid'] false (default) means skipping the parent cid
 * @param $args['count'] true (default) means counting the number of publications
 * @param $args['filter'] additional categories we're filtering on (= catid)
 * @return array
 */
function publications_userapi_getchildcats(array $args = [], $context = null)
{
    extract($args);

    if (!isset($cid) || !is_numeric($cid)) {
        return [];
    }
    if (empty($ptid)) {
        $ptid = null;
    }
    if (!isset($state)) {
        // frontpage or approved
        $state = [PUBLICATIONS_STATE_FRONTPAGE,PUBLICATIONS_STATE_APPROVED];
    }
    if (!isset($showcid)) {
        $showcid = false;
    }
    if (!isset($count)) {
        $count = true;
    }
    if (!isset($filter)) {
        $filter = '';
    }

    if (!xarMod::apiLoad('categories', 'visual')) {
        return;
    }

    // TODO: make sure permissions are taken into account here !
    $list = xarMod::apiFunc(
        'categories',
        'visual',
        'listarray',
        ['cid' => $cid]
    );
    // get the counts for all child categories
    if ($count) {
        if (empty($filter)) {
            $seencid = [];
            foreach ($list as $info) {
                $seencid[$info['id']] = 1;
            }
            $childlist = array_keys($seencid);
            $andcids = false;
        } else {
            // we'll combine the parent cid with the filter here
            $childlist = ['_' . $cid,$filter];
            $andcids = true;
        }

        $pubcatcount = xarMod::apiFunc(
            'publications',
            'user',
            'getpubcatcount',
            // frontpage or approved
            ['state' => [PUBLICATIONS_STATE_FRONTPAGE,PUBLICATIONS_STATE_APPROVED],
                  'cids' => $childlist,
                  'andcids' => $andcids,
                  'ptid' => $ptid,
                  'reverse' => 1, ]
        );
        if (!empty($ptid)) {
            $curptid = $ptid;
        } else {
            $curptid = 'total';
        }
    }

    $cats = [];
    foreach ($list as $info) {
        if ($info['id'] == $cid && !$showcid) {
            continue;
        }
        if (!empty($filter)) {
            $catid = $filter . '+' . $info['id'];
        } else {
            $catid = $info['id'];
        }
        // TODO: show icons instead of (or in addition to) a link if available ?
        $info['link'] = xarController::URL(
            'publications',
            'user',
            'view',
            ['ptid' => $ptid,
                                       'catid' => $catid, ]
        );
        $info['name'] = xarVar::prepForDisplay($info['name']);
        if ($count) {
            if (isset($pubcatcount[$info['id']][$curptid])) {
                $info['count'] = $pubcatcount[$info['id']][$curptid];
            } elseif (!empty($filter) && isset($pubcatcount[$filter . '+' . $info['id']][$curptid])) {
                $info['count'] = $pubcatcount[$filter . '+' . $info['id']][$curptid];
            } elseif (!empty($filter) && isset($pubcatcount[$info['id'] . '+' . $filter][$curptid])) {
                $info['count'] = $pubcatcount[$info['id'] . '+' . $filter][$curptid];
            } else {
                $info['count'] = '';
            }
        } else {
            $info['count'] = '';
        }
        $cats[] = $info;
    }
    return $cats;
}

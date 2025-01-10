<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\UserApi;

use Xaraya\Modules\MethodClass;
use xarMod;
use xarController;
use xarVar;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getparentcats function
 */
class GetparentcatsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get an array of parent categories with links and counts
     * @param mixed $args ['state'] array of requested status(es) for the publications
     * @param mixed $args ['ptid'] publication type ID
     * @param mixed $args ['cids'] array of category IDs
     * @param mixed $args ['showcids'] true (default) means keeping a link for the cids
     * @param mixed $args ['sort'] currently used only to override default start view
     * @param mixed $args ['count'] true (default) means counting the number of publications
     * @return array
     * // TODO: specify return format
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($cids) || !is_array($cids) || count($cids) == 0) {
            return [];
        }
        if (empty($ptid)) {
            $ptid = null;
        }
        if (!isset($state)) {
            // frontpage or approved
            $state = [PUBLICATIONS_STATE_FRONTPAGE,PUBLICATIONS_STATE_APPROVED];
        }
        if (!isset($showcids)) {
            $showcids = true;
        }
        if (!isset($sort)) {
            $sort = null;
        }
        if (!isset($count)) {
            $count = true;
        }

        // get the counts for all child categories
        if ($count) {
            $pubcatcount = xarMod::apiFunc(
                'publications',
                'user',
                'getpubcatcount',
                ['state' => $state,
                    'cids' => $cids,
                    'ptid' => $ptid,
                    'reverse' => 1, ]
            );
        }

        if (!empty($ptid)) {
            $curptid = $ptid;
        } else {
            $curptid = 'total';
        }

        $trails = [];
        foreach ($cids as $cid) {
            $trailitem = [];
            $trailitem['cid'] = $cid;
            // TODO : retrieve all parents in 1 call ?
            $trail = xarMod::apiFunc(
                'categories',
                'user',
                'getcat',
                ['cid' => $cid,
                    'return_itself' => true,
                    'getparents' => true, ]
            );

            if ($count && isset($pubcatcount[$cid][$curptid])) {
                $trailitem['cidcount'] = $pubcatcount[$cid][$curptid];
            } else {
                $trailitem['cidcount'] = '';
            }

            $trailitem['parentlinks'] = [];
            $item = [];
            $item['plink'] = xarController::URL(
                'publications',
                'user',
                'view',
                ['ptid' => $ptid,
                    'sort' => $sort, ]
            );
            $item['ptitle'] = xarML('All');
            $item['pjoin'] = ' &gt; ';
            $trailitem['parentlinks'][] = $item;
            // TODO: make sure permissions are taken into account here !
            foreach ($trail as $info) {
                $item['plink'] = xarController::URL(
                    'publications',
                    'user',
                    'view',
                    ['ptid' => $ptid,
                        'catid' => $info['cid'], ]
                );
                $item['ptitle'] = xarVar::prepForDisplay($info['name']);
                if ($info['cid'] == $cid) {
                    // TODO: test for neighbourhood
                    $trailitem['info'] = $info;

                    $item['pjoin'] = '';
                    // remove link again in this case :-)
                    if (!$showcids) {
                        $item['plink'] = '';
                    }
                    // TODO: improve the case where we have several icons :)
                    if (!empty($info['image'])) {
                        $trailitem['icon'] = ['image' => $info['image'],
                            'text' => $item['ptitle'],
                            'link' =>
                              xarController::URL(
                                  'publications',
                                  'user',
                                  'view',
                                  ['ptid' => $ptid,
                                      'catid' => $info['cid'], ]
                              ), ];
                    }
                } else {
                    $item['pjoin'] = ' &gt; ';
                }
                $trailitem['parentlinks'][] = $item;
            }
            $trails[] = $trailitem;
        }
        return $trails;
    }
}

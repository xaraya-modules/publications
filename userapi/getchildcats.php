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

use Xaraya\Modules\Publications\Defines;
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;

/**
 * publications userapi getchildcats function
 * @extends MethodClass<UserApi>
 */
class GetchildcatsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get an array of child categories with links and optional counts
     * @param array<mixed> $args
     * @var mixed $state array of requested status(es) for the publications
     * @var mixed $ptid publication type ID
     * @var mixed $cid parent category ID
     * @var mixed $showcid false (default) means skipping the parent cid
     * @var mixed $count true (default) means counting the number of publications
     * @var mixed $filter additional categories we're filtering on (= catid)
     * @return array|void
     * @see UserApi::getchildcats()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        if (!isset($cid) || !is_numeric($cid)) {
            return [];
        }
        if (empty($ptid)) {
            $ptid = null;
        }
        if (!isset($state)) {
            // frontpage or approved
            $state = [Defines::STATE_FRONTPAGE,Defines::STATE_APPROVED];
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

        if (!$this->mod()->apiLoad('categories', 'visual')) {
            return;
        }

        // TODO: make sure permissions are taken into account here !
        $list = $this->mod()->apiFunc(
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

            $pubcatcount = $userapi->getpubcatcount(// frontpage or approved
                ['state' => [Defines::STATE_FRONTPAGE,Defines::STATE_APPROVED],
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
            $info['link'] = $this->mod()->getURL(
                'user',
                'view',
                ['ptid' => $ptid,
                    'catid' => $catid, ]
            );
            $info['name'] = $this->prep()->text($info['name']);
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
}

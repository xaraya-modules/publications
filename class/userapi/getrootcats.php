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


use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;
use xarModUserVars;
use xarModVars;
use xarMod;
use xarVar;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getrootcats function
 * @extends MethodClass<UserApi>
 */
class GetrootcatsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get an array of root categories with links
     * @param array<mixed> $args
     * @var int $ptid publication type ID
     * @var mixed $all boolean if we need to return all root categories when
     * ptid is empty (default false)
     * @return array|void
     * @TODO specify return format
     * @see UserApi::getrootcats()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        if (empty($ptid) || !is_numeric($ptid)) {
            $ptid = null;
        }

        // see which root categories we need to handle
        $rootcats = [];
        if (!empty($ptid)) {
            $rootcats = unserialize(xarModUserVars::get('publications', 'basecids', $ptid));
        } elseif (empty($all)) {
            $rootcats = unserialize($this->mod()->getVar('basecids'));
        } else {
            // Get publication types
            $pubtypes = $userapi->get_pubtypes();
            // get base categories for all publication types here
            $publist = array_keys($pubtypes);
            // add the defaults too, in case we have other base categories there
            $publist[] = '';
            // build the list of root categories for all required publication types
            $catlist = [];
            foreach ($publist as $pubid) {
                if (empty($pubid)) {
                    $cidstring = $this->mod()->getVar('basecids');
                } else {
                    $cidstring = xarModUserVars::get('publications', 'basecids', $pubid);
                }
                if (!empty($cidstring)) {
                    $rootcats = unserialize($cidstring);
                } else {
                    $rootcats = [];
                }
                foreach ($rootcats as $cid) {
                    $catlist[$cid] = 1;
                }
            }
            if (count($catlist) > 0) {
                $rootcats = array_keys($catlist);
            }
        }
        if (empty($rootcats)) {
            $rootcats = [];
        }

        if (count($rootcats) < 1) {
            return [];
        }

        if (!$this->mod()->apiLoad('categories', 'user')) {
            return;
        }

        $isfirst = 1;
        $catlinks = [];
        $catlist = $this->mod()->apiFunc(
            'categories',
            'user',
            'getcatinfo',
            ['cids' => $rootcats]
        );
        if (empty($catlist)) {
            return $catlinks;
        }
        // preserve order of root categories if possible
        foreach ($rootcats as $cid) {
            if (!isset($catlist[$cid])) {
                continue;
            }
            $info = $catlist[$cid];
            $item = [];
            $item['catid'] = $info['cid'];
            $item['cattitle'] = $this->var()->prep($info['name']);
            $item['catlink'] = $this->mod()->getURL(
                'user',
                'view',
                ['ptid' => $ptid,
                    'catid' => $info['cid'], ]
            );
            if ($isfirst) {
                $item['catjoin'] = '';
                $isfirst = 0;
            } else {
                $item['catjoin'] = ' | ';
            }
            $item['catleft'] = $info['left'];
            $item['catright'] = $info['right'];
            $catlinks[] = $item;
        }
        return $catlinks;
    }
}

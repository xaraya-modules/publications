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
use xarMod;
use xarSecurity;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getpublinks function
 * @extends MethodClass<UserApi>
 */
class GetpublinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get array of links and counts for publication types
     * @param mixed $args ['ptid'] optional publication type ID for which you *don't*
     * want a link (e.g. for the current publication type)
     * @param mixed $args ['all'] optional flag (1) if you want to include publication
     * types that don't have publications too (default 0)
     * @param mixed $args ['state'] array of requested status(es) for the publications
     * @param mixed $args ['func'] optional function to be called with the link
     * @param mixed $args ['count'] true (default) means counting the number of publications
     * @return array of array('pubtitle' => descr,
     * 'pubid' => id,
     * 'publink' => link,
     * 'pubcount' => count)
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (!isset($ptid)) {
            $ptid = null;
        }
        if (!isset($all)) {
            $all = 0;
        }
        if (!isset($func)) {
            $func = 'view';
        }
        if (!isset($typemod)) {
            $typemod = 'user';
        }
        if (!isset($count)) {
            $count = true;
        }
        if (!isset($state)) {
            $state = [0];
        }
        if (!$count) {
            $all = 1;
        }

        // Get publication types
        $pubtypes = xarMod::apiFunc('publications', 'user', 'get_pubtypes');

        if ($count) {
            if (isset($state)) {
                $pubcount = xarMod::apiFunc(
                    'publications',
                    'user',
                    'getpubcount',
                    ['state' => $state]
                );
            } else {
                $pubcount = xarMod::apiFunc('publications', 'user', 'getpubcount');
            }
        }

        $publinks = [];
        $isfirst = 1;
        foreach ($pubtypes as $id => $pubtype) {
            if (!xarSecurity::check('ViewPublications', 0, 'Publication', $id . ':All:All:All')) {
                continue;
            }
            if ($all || (isset($pubcount[$id]) && $pubcount[$id] > 0)) {
                $item['pubtitle'] = $pubtype['description'];
                $item['pubid'] = $id;
                if (isset($ptid) && $ptid == $id) {
                    $item['publink'] = '';
                } else {
                    $item['publink'] = xarController::URL('publications', $typemod, $func, ['ptid' => $id]);
                }
                if ($count && isset($pubcount[$id])) {
                    $item['pubcount'] = $pubcount[$id];
                } else {
                    $item['pubcount'] = 0;
                }
                if ($isfirst) {
                    $isfirst = 0;
                    $item['pubjoin'] = '';
                } else {
                    $item['pubjoin'] = ' - ';
                }
                $publinks[] = $item;
            }
        }

        return $publinks;
    }
}

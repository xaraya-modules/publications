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
use xarVar;
use xarCoreCache;
use xarDB;
use Query;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getpubcount function
 * @extends MethodClass<UserApi>
 */
class GetpubcountMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get the number of publications per publication type
     * @param array<mixed> $args
     * @var mixed $state array of requested status(es) for the publications
     * @return array|void array(id => count), or false on failure
     */
    public function __invoke(array $args = [])
    {
        if (!empty($args['state'])) {
            $statestring = 'all';
        } elseif (is_array($args['state'])) {
            sort($args['state']);
            $statestring = join('+', $args['state']);
        } else {
            $statestring = $args['state'];
        }

        if (xarVar::isCached('Publications.PubCount', $statestring)) {
            return xarCoreCache::getCached('Publications.PubCount', $statestring);
        }

        $pubcount = [];

        $tables = & xarDB::getTables();
        sys::import('xaraya.structures.query');
        $q = new Query('SELECT', $tables['publications']);
        $q->addfield('pubtype_id');
        $q->addfield('COUNT(state) AS count');
        $q->addgroup('pubtype_id');
        if (!empty($args['state'])) {
        } elseif (is_array($args['state'])) {
            $q->in('state', $args['state']);
        } else {
            $q->eq('state', $args['state']);
        }
        //    $q->qecho();
        if (!$q->run()) {
            return;
        }
        $pubcount = [];
        foreach ($q->output() as $key => $value) {
            $pubcount[$value['pubtype_id']] = $value['count'];
        }
        xarCoreCache::setCached('Publications.PubCount', $statestring, $pubcount);
        return $pubcount;
    }
}

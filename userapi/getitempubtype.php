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
use Query;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getitempubtype function
 * @extends MethodClass<UserApi>
 */
class GetitempubtypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Given an itemid, get the publication type
     * CHECKME: use get in place of this function?
     * @see UserApi::getitempubtype()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['itemid'])) {
            throw new BadParameterException('itemid');
        }

        sys::import('xaraya.structures.query');
        $xartables = & $this->db()->getTables();
        $q = new Query('SELECT', $xartables['publications']);
        $q->addfield('pubtype_id');
        $q->eq('id', $args['itemid']);
        if (!$q->run()) {
            return;
        }
        $result = $q->row();
        if (empty($result)) {
            return 0;
        }
        return $result['pubtype_id'];
    }
}

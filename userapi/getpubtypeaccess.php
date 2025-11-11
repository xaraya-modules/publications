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
use BadParameterException;

/**
 * publications userapi getpubtypeaccess function
 * @extends MethodClass<UserApi>
 */
class GetpubtypeaccessMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Given an itemid, get the publication type
     * CHECKME: use get in place of this function?
     * @see UserApi::getpubtypeaccess()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['name'])) {
            throw new BadParameterException('name');
        }

        $xartables = & $this->db()->getTables();
        $q = new Query('SELECT', $xartables['publications_types']);
        $q->addfield('access');
        $q->eq('name', $args['name']);
        if (!$q->run()) {
            return;
        }
        $result = $q->row();
        if (empty($result)) {
            return "a:0:{}";
        }
        return $result['access'];
    }
}

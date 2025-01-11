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
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getitemfields function
 * @extends MethodClass<UserApi>
 */
class GetitemfieldsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to pass item field definitions to whoever
     * @param mixed $args ['itemtype'] item type (optional)
     * @return array Array containing the item field definitions
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $itemfields = [];

        $pubtypes = xarMod::apiFunc('publications', 'user', 'get_pubtypes');

        if (!empty($itemtype) && !empty($pubtypes[$itemtype])) {
            $fields = $pubtypes[$itemtype]['config'];
        } else {
            $fields = xarMod::apiFunc('publications', 'user', 'getpubfields');
        }
        foreach ($fields as $name => $info) {
            if (empty($info['label'])) {
                continue;
            }
            $itemfields[$name] = ['name'  => $name,
                'label' => $info['label'],
                'type'  => $info['format'], ];
        }

        return $itemfields;
    }
}

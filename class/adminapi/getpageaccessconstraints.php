<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\AdminApi;


use Xaraya\Modules\Publications\AdminApi;
use Xaraya\Modules\MethodClass;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * publications adminapi getpageaccessconstraints function
 * @extends MethodClass<AdminApi>
 */
class GetpageaccessconstraintsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        if (!isset($args['property'])) {
            throw new Exception($this->ml('Missing property param in publications_adminapi_getpageaccessconstraints'));
        }

        $constraints = [
            'display' => ['level' => 800, 'group' => 0, 'failure' => 1],
            'add'     => ['level' => 800, 'group' => 0, 'failure' => 1],
            'modify'  => ['level' => 800, 'group' => 0, 'failure' => 1],
            'delete'  => ['level' => 800, 'group' => 0, 'failure' => 1],
        ];

        $unpacked_constraints = $args['property']->getValue();
        if (empty($unpacked_constraints)) {
            return $constraints;
        }
        try {
            // Check the array structure
            if (isset($unpacked_constraints['display'])) {
                $constraints = $unpacked_constraints;
            }
        } catch (Exception $e) {
        }

        return $constraints;
    }
}

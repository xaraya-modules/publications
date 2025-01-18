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
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getstates function
 * @extends MethodClass<UserApi>
 */
class GetstatesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * return an array with coded states
     * @return array
     */
    public function __invoke(array $args = [])
    {
        // Simplistic getstates function
        // Obviously needs to be smarter along with the other state functions
        return [0 => $this->ml('Deleted'),
            1 => $this->ml('Inactive'),
            2 => $this->ml('Draft'),
            3 => $this->ml('Active'),
            4 => $this->ml('Frontpage'),
            5 => $this->ml('Empty'),
        ];
    }
}

<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\AdminGui;


use Xaraya\Modules\Publications\AdminGui;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin view_pubtypes function
 * @extends MethodClass<AdminGui>
 */
class ViewPubtypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Manage publication types (all-in-one function for now)
     */
    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('AdminPublications')) {
            return;
        }

        // Return the template variables defined in this function
        return [];
    }
}

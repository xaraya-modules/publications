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

/**
 * publications admin overview function
 * @extends MethodClass<AdminGui>
 */
class OverviewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Overview displays standard Overview page
     * @see AdminGui::overview()
     */
    public function __invoke(array $args = [])
    {
        /* Security Check */
        if (!$this->sec()->checkAccess('EditPublications', 0)) {
            return;
        }

        $data = [];

        /* if there is a separate overview function return data to it
         * else just call the main function that usually displays the overview
         */

        return $this->render('main', $data);
    }
}

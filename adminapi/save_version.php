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
use Exception;

/**
 * publications adminapi save_version function
 * @extends MethodClass<AdminApi>
 */
class SaveVersionMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Publications Module
     * @package modules
     * @subpackage publications module
     * @category Third Party Xaraya Module
     * @version 2.0.0
     * @copyright (C) 2012 Netspan AG
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @author Marc Lutolf <mfl@netspan.ch>
     * @see AdminApi::saveVersion()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['object'])) {
            throw new Exception($this->ml('Missing object arg for saving version'));
        }

        $entries = $this->data()->getObject(['name' => 'publications_versions']);
        $entries->properties['content']->value = serialize($args['object']->getFieldValues([], 1));
        $entries->properties['operation']->value = $args['operation'];
        $entries->properties['version']->value = $args['object']->properties['version']->value;
        $entries->properties['page_id']->value = $args['object']->properties['id']->value;
        $entries->createItem();
        return true;
    }
}

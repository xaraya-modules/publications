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

use Xaraya\Modules\MethodClass;
use xarMod;
use DataObjectFactory;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi fieldoutput function
 */
class FieldoutputMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Show some predefined form field in a template
     * @param mixed $args array containing the definition of the field (object, itemid, property, value, ...)
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (!isset($object) || !isset($itemid) || !isset($field)) {
            return '';
        }
        sys::import('modules.dynamicdata.class.objects.factory');
        $object = DataObjectFactory::getObject(['name' => $object]);
        $itemid = xarMod::apiFunc('publications', 'user', 'gettranslationid', ['id' => $itemid]);
        $object->getItem(['itemid' => $itemid]);
        $field = $object->properties[$field]->getValue();
        return $field;
    }
}

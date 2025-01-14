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
use DataObjectFactory;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * publications adminapi getpubtypeaccess function
 * @extends MethodClass<AdminApi>
 */
class GetpubtypeaccessMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        if (!isset($args['ptid'])) {
            throw new Exception($this->translate('Missing ptid param in publications_adminapi_getpubtypeaccess'));
        }

        $pubtypeobject = DataObjectFactory::getObject(['name' => 'publications_types']);
        if (null == $pubtypeobject) {
            return false;
        }

        $pubtypeobject->getItem(['itemid' => $args['ptid']]);
        if (empty($pubtypeobject->properties['access']->value)) {
            return "a:0:{}";
        }

        return $pubtypeobject->properties['access']->value;
    }
}

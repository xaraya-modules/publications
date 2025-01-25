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
use xarCoreCache;
use DataObjectFactory;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi get_pubtypes function
 * @extends MethodClass<UserApi>
 */
class GetPubtypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see UserApi::getPubtypes()
     */

    public function __invoke(array $args = [])
    {
        if ($this->var()->isCached('Publications.Data', 'producttypes')) {
            return $this->var()->getCached('Publications.Data', 'producttypes');
        }
        $object = $this->data()->getObjectList(['name' => 'publications_types']);
        $items = $object->getItems();
        $this->var()->setCached('Publications.Data', 'producttypes', $items);
        return $items;
    }
}

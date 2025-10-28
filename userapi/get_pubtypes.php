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
        if ($this->mem()->has('Publications.Data', 'producttypes')) {
            return $this->mem()->get('Publications.Data', 'producttypes');
        }
        $object = $this->data()->getObjectList(['name' => 'publications_types']);
        $items = $object->getItems();
        $this->mem()->set('Publications.Data', 'producttypes', $items);
        return $items;
    }
}

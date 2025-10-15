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
 * publications userapi getitemtypes function
 * @extends MethodClass<UserApi>
 */
class GetitemtypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to retrieve the list of item types of this module (if any)
     * @return array Array containing the item types and their description
     * @see UserApi::getitemtypes()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $itemtypes = [];

        $itemtypes[300] = ['label' => $this->ml('Bare Publication'),
            'title' => $this->ml('View Bare Publication'),
            'url'   => $this->mod()->getURL('user', 'view'),
        ];
        // Get publication types
        $pubtypes = $userapi->get_pubtypes();

        foreach ($pubtypes as $id => $pubtype) {
            $itemtypes[$id] = ['label' => $this->var()->prep($pubtype['description']),
                'title' => $this->var()->prep($this->ml('Display #(1)', $pubtype['description'])),
                'url'   => $this->mod()->getURL( 'user', 'view', ['ptid' => $id]),
            ];
        }

        $extensionitemtypes = $this->mod()->apiFunc('dynamicdata', 'user', 'getmoduleitemtypes', ['moduleid' => 30065, 'native' => false]);

        /* TODO: activate this code when we move to php5
        $keys = array_merge(array_keys($itemtypes),array_keys($extensionitemtypes));
        $values = array_merge(array_values($itemtypes),array_values($extensionitemtypes));
        return array_combine($keys,$values);
        */

        $types = [];
        foreach ($itemtypes as $key => $value) {
            $types[$key] = $value;
        }
        foreach ($extensionitemtypes as $key => $value) {
            $types[$key] = $value;
        }
        return $types;
    }
}

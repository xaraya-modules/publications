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
use xarSecurity;
use xarController;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getmenulinks function
 * @extends MethodClass<UserApi>
 */
class GetmenulinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function pass individual menu items to the main menu
     * @return array Array containing the menulinks for the main menu items.
     */
    public function __invoke(array $args = [])
    {
        $menulinks = [];
        if (!xarSecurity::check('ViewPublications', 0)) {
            return $menulinks;
        }

        $menulinks[] = ['url'   => xarController::URL(
            'publications',
            'user',
            'main'
        ),
            'title' => xarML('Highlighted Publications'),
            'label' => xarML('Front Page'), ];

        $items = xarMod::apiFunc('publications', 'user', 'get_menu_pages');
        foreach ($items as $item) {
            $menulinks[] = ['url'   => xarController::URL('publications', 'user', 'display', ['itemid' => $item['id']]),
                'title' => xarML('Display #(1)', $item['description']),
                'label' => $item['title'], ];
        }

        $menulinks[] = ['url'   => xarController::URL(
            'publications',
            'user',
            'viewmap'
        ),
            'title' => xarML('Displays a map of all published content'),
            'label' => xarML('Publication Map'), ];

        return $menulinks;
    }
}

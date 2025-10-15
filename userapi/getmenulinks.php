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
 * publications userapi getmenulinks function
 * @extends MethodClass<UserApi>
 */
class GetmenulinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function pass individual menu items to the main menu
     * @return array Array containing the menulinks for the main menu items.
     * @see UserApi::getmenulinks()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $menulinks = [];
        if (!$this->sec()->checkAccess('ViewPublications', 0)) {
            return $menulinks;
        }

        $menulinks[] = ['url'   => $this->mod()->getURL('user', 'main'),
            'title' => $this->ml('Highlighted Publications'),
            'label' => $this->ml('Front Page'), ];

        $items = $userapi->get_menu_pages();
        foreach ($items as $item) {
            $menulinks[] = ['url'   => $this->mod()->getURL( 'user', 'display', ['itemid' => $item['id']]),
                'title' => $this->ml('Display #(1)', $item['description']),
                'label' => $item['title'], ];
        }

        $menulinks[] = ['url'   => $this->mod()->getURL('user', 'viewmap'),
            'title' => $this->ml('Displays a map of all published content'),
            'label' => $this->ml('Publication Map'), ];

        return $menulinks;
    }
}

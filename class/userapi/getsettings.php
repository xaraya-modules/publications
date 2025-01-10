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
use xarCore;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getsettings function
 */
class GetsettingsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * retrieve the settings of a publication type
     * @param mixed $args array containing the publication type
     * @return array of setting keys and values
     */
    public function __invoke($data)
    {
        if (empty($data['ptid'])) {
            throw new Exception('Missing publication type for caching');
        }

        // If already cached, then get that
        if (xarCore::isCached('publications', 'settings_' . $data['ptid'])) {
            return xarCore::getCached('publications', 'settings_' . $data['ptid']);
        }

        sys::import('modules.dynamicdata.class.objects.factory');
        $pubtypeobject = DataObjectFactory::getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $data['ptid']]);

        $pubtypesettings = [];
        try {
            $pubtypesettings = unserialize($pubtypeobject->properties['configuration']->getValue());
        } catch (Exception $e) {
        }

        $globalsettings = $this->getglobalsettings();
        if (is_array($pubtypesettings)) {
            $settings = $pubtypesettings + $globalsettings;
        } else {
            $settings = $globalsettings;
        }

        xarCore::setCached('publications', 'settings_' . $data['ptid'], $settings);
        return $settings;
    }

    public function getglobalsettings(array $args = [])
    {
        $settings = [
            'number_of_columns'     => 1,
            'items_per_page'        => 20,
            'defaultview'           => "Sections",
            'defaultsort'           => "name",
            'show_categories'       => false,
            'show_catcount'         => false,
            'namestring'            => 'pub',
            'show_prevnext'         => true,
            'show_keywords'         => false,
            'show_comments'         => false,
            'show_hitcount'         => false,
            'show_ratings'          => false,
            'show_archives'         => false,
            'show_map'              => false,
            'show_publinks'         => false,
            'show_pubcount'         => true,
            'do_transform'          => true,
            'title_transform'       => true,
            'usealias'              => false,                //CHECKME
            'defaultstate'          => 2,
            'defaultprocessstate'   => 0,
            'showsubmit'            => false,              //CHECKME
            'summary_template'      => '',
            'detail_template'       => '',
            'page_template'         => "",
            'theme'                 => '',
        ];
        return $settings;
    }
}

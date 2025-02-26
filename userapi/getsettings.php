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
use Exception;

sys::import('xaraya.modules.method');

/**
 * publications userapi getsettings function
 * @extends MethodClass<UserApi>
 */
class GetsettingsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * retrieve the settings of a publication type
     * @param mixed $data array containing the publication type
     * @return array of setting keys and values
     * @see UserApi::getsettings()
     */
    public function __invoke(array $data = [])
    {
        if (empty($data['ptid'])) {
            throw new Exception('Missing publication type for caching');
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // If already cached, then get that
        if ($this->var()->isCached('publications', 'settings_' . $data['ptid'])) {
            return $this->var()->getCached('publications', 'settings_' . $data['ptid']);
        }

        sys::import('modules.dynamicdata.class.objects.factory');
        $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $data['ptid']]);

        $pubtypesettings = [];
        try {
            $pubtypesettings = unserialize($pubtypeobject->properties['configuration']->getValue());
        } catch (Exception $e) {
        }

        $globalsettings = $userapi->getglobalsettings();
        if (is_array($pubtypesettings)) {
            $settings = $pubtypesettings + $globalsettings;
        } else {
            $settings = $globalsettings;
        }

        $this->var()->setCached('publications', 'settings_' . $data['ptid'], $settings);
        return $settings;
    }
}

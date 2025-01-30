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
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications adminapi getconfighook function
 * @extends MethodClass<AdminApi>
 */
class GetconfighookMethod extends MethodClass
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
     * @see AdminApi::getconfighook()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (!isset($extrainfo['tabs'])) {
            $extrainfo['tabs'] = [];
        }
        $module = 'publications';
        $tabinfo = [
            'module'  => $module,
            'configarea'  => 'general',
            'configtitle'  => $this->ml('Publications'),
            'configcontent' => $this->mod()->guiFunc(
                $module,
                'admin',
                'modifyconfig_general'
            ),
        ];
        $extrainfo['tabs'][] = $tabinfo;
        return $extrainfo;
    }
}

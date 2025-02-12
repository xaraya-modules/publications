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
use xarDB;
use Query;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications adminapi delete function
 * @extends MethodClass<AdminApi>
 */
class DeleteMethod extends MethodClass
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
     * @see AdminApi::delete()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (!isset($itemid)) {
            $msg = $this->ml(
                'Invalid #(1) for #(2) function #(3)() in module #(4)',
                'publication ID',
                'admin',
                'delete',
                'Publications'
            );
            throw new BadParameterException(null, $msg);
        }
        $ids = !is_array($itemid) ? explode(',', $itemid) : $itemid;
        if (!isset($deletetype)) {
            $deletetype = 0;
        }

        sys::import('xaraya.structures.query');
        $table = & $this->db()->getTables();

        switch ($deletetype) {
            case 0:
            default:
                $q = new Query('UPDATE', $table['publications']);
                $q->addfield('state', 0);
                break;

            case 10:
                $q = new Query('DELETE', $table['publications']);
                break;
        }

        $q->in('id', $ids);
        if (!$q->run()) {
            return false;
        }
        return true;
    }
}

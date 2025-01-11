<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\AdminGui;


use Xaraya\Modules\Publications\AdminGui;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin stats function
 * @extends MethodClass<AdminGui>
 */
class StatsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * view statistics
     */
    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('AdminPublications')) {
            return;
        }

        if (!xarVar::fetch('group', 'isset', $group, [], xarVar::NOT_REQUIRED)) {
            return;
        }
        extract($args);

        if (!empty($group)) {
            $newgroup = [];
            foreach ($group as $field) {
                if (empty($field)) {
                    continue;
                }
                $newgroup[] = $field;
            }
            $group = $newgroup;
        }
        if (empty($group)) {
            $group = ['pubtype_id', 'state', 'owner'];
        }

        $data = [];
        $data['group'] = $group;
        $data['stats'] = xarMod::apiFunc(
            'publications',
            'admin',
            'getstats',
            ['group' => $group]
        );
        $data['pubtypes'] = xarMod::apiFunc('publications', 'user', 'get_pubtypes');
        $data['statelist'] = xarMod::apiFunc('publications', 'user', 'getstates');
        $data['fields'] = ['pubtype_id'     => xarML('Publication Type'),
            'state'        => xarML('Status'),
            'owner'      => xarML('Author'),
            'pubdate_year'  => xarML('Publication Year'),
            'pubdate_month' => xarML('Publication Month'),
            'pubdate_day'   => xarML('Publication Day'),
            'locale'      => xarML('Language'), ];
        return $data;
    }
}

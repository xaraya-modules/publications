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
use Xaraya\Modules\Publications\AdminApi;
use Xaraya\Modules\Publications\UserApi;
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
     * @see AdminGui::stats()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->sec()->checkAccess('AdminPublications')) {
            return;
        }

        $this->var()->find('group', $group, 'isset', []);
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
        $data['stats'] = $adminapi->getstats(['group' => $group]
        );
        $data['pubtypes'] = $userapi->get_pubtypes();
        $data['statelist'] = $userapi->getstates();
        $data['fields'] = ['pubtype_id'     => $this->ml('Publication Type'),
            'state'        => $this->ml('Status'),
            'owner'      => $this->ml('Author'),
            'pubdate_year'  => $this->ml('Publication Year'),
            'pubdate_month' => $this->ml('Publication Month'),
            'pubdate_day'   => $this->ml('Publication Day'),
            'locale'      => $this->ml('Language'), ];
        return $data;
    }
}

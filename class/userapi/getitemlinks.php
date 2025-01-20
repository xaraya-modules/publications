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
use xarDB;
use xarController;
use Query;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getitemlinks function
 * @extends MethodClass<UserApi>
 */
class GetitemlinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to pass individual item links to a caller
     * @param array<mixed> $args
     * @var mixed $itemids array of item ids to get
     * @return array Array containing the itemlink(s) for the item(s).
     */
    public function __invoke(array $args = [])
    {
        $itemlinks = [];

        sys::import('xaraya.structures.query');
        $xartable = & $this->db()->getTables();
        $q = new Query('SELECT', $xartable['publications']);
        $q->addfield('id');
        $q->addfield('title');
        $q->addfield('description');
        $q->addfield('pubtype_id');
        $q->addfield('modify_date AS modified');
        $q->in('state', [3,4]);
        if (!empty($args['itemids'])) {
            if (is_array($args['itemids'])) {
                $itemids = $args['itemids'];
            } else {
                $itemids = explode(',', $args['itemids']);
            }
            $q->in('id', $itemids);
        }
        $q->addorder('title');
        $q->run();
        $result = $q->output();

        if (empty($result)) {
            return $itemlinks;
        }

        foreach ($result as $item) {
            if (empty($item['title'])) {
                $item['title'] = $this->ml('Display Publication');
            }
            $itemlinks[$item['id']] = ['url'   => $this->mod()->getURL(
                'user',
                'display',
                ['itemid' => $item['id']]
            ),
                'title' => $item['title'],
                'label' => $item['description'],
                'modified' => $item['modified'],
            ];
        }
        return $itemlinks;
    }
}

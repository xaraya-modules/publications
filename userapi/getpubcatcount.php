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
 * publications userapi getpubcatcount function
 * @extends MethodClass<UserApi>
 */
class GetpubcatcountMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get the number of publications per publication type and category
     * @param array<mixed> $args
     * @var mixed $state array of requested status(es) for the publications
     * @var mixed $ptid publication type ID
     * @var mixed $cids array of category IDs (OR/AND)
     * @var mixed $andcids true means AND-ing categories listed in cids
     * @var mixed $groupcids the number of categories you want items grouped by
     * @var mixed $reverse default is ptid => cid, reverse (1) is cid => ptid
     * @return array|void array( $ptid => array( $cid => $count) ),
     * or false on failure
     * @see UserApi::getpubcatcount()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /*
            static $pubcatcount = array();

            if (count($pubcatcount) > 0) {
                return $pubcatcount;
            }
        */
        $pubcatcount = [];

        // Get database setup
        $dbconn = $this->db()->getConn();

        // Get the LEFT JOIN ... ON ...  and WHERE parts from publications
        $publicationsdef = $userapi->leftjoin($args);

        // Load API
        if (!$this->mod()->apiLoad('categories', 'user')) {
            return;
        }

        $args['modid'] = $this->mod()->getRegID('publications');
        if (isset($args['ptid']) && !isset($args['itemtype'])) {
            $args['itemtype'] = $args['ptid'];
        }
        // Get the LEFT JOIN ... ON ...  and WHERE parts from categories
        $categoriesdef = $this->mod()->apiFunc('categories', 'user', 'leftjoin', $args);

        // Get count
        $query = 'SELECT ' . $publicationsdef['pubtype_id'] . ', ' . $categoriesdef['category_id']
               . ', COUNT(*)
                FROM ' . $publicationsdef['table'] . '
                LEFT JOIN ' . $categoriesdef['table'] . '
                ON ' . $categoriesdef['field'] . ' = ' . $publicationsdef['field'] .
                $categoriesdef['more'] . '
                WHERE ' . $categoriesdef['where'] . ' AND ' . $publicationsdef['where'] . '
                GROUP BY ' . $publicationsdef['pubtype_id'] . ', ' . $categoriesdef['category_id'];

        $result = $dbconn->Execute($query);
        if (!$result) {
            return;
        }
        if ($result->EOF) {
            if (!empty($args['ptid']) && empty($args['reverse'])) {
                $pubcatcount[$args['ptid']] = [];
            }
            return $pubcatcount;
        }
        while ($result->next()) {
            // we may have 1 or more cid fields here, depending on what we're
            // counting (cfr. AND in categories)
            $fields = $result->fields;
            $ptid = array_shift($fields);
            $count = array_pop($fields);
            // TODO: use multi-level array for multi-category grouping ?
            $cid = join('+', $fields);
            if (empty($args['reverse'])) {
                $pubcatcount[$ptid][$cid] = $count;
            } else {
                $pubcatcount[$cid][$ptid] = $count;
            }
        }
        foreach ($pubcatcount as $id1 => $val) {
            $total = 0;
            foreach ($val as $id2 => $count) {
                $total += $count;
            }
            $pubcatcount[$id1]['total'] = $total;
        }

        return $pubcatcount;
    }
}

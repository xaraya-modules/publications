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
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi countitems function
 * @extends MethodClass<UserApi>
 */
class CountitemsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * count number of items depending on additional module criteria
     * @param array<mixed> $args
     * @var mixed $catid string of category id(s) that we're counting in, or
     * @var mixed $cids array of cids that we are counting in (OR/AND)
     * @var mixed $andcids true means AND-ing categories listed in cids
     * @var mixed $owner the ID of the author
     * @var mixed $ptid publication type ID (for news, sections, reviews, ...)
     * @var mixed $state array of requested status(es) for the publications
     * @var mixed $startdate publications published at startdate or later
     * (unix timestamp format)
     * @var mixed $enddate publications published before enddate
     * (unix timestamp format)
     * @return int|void number of items
     * @see UserApi::countitems()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Database information
        $dbconn = $this->db()->getConn();

        // Get the field names and LEFT JOIN ... ON ... parts from publications
        // By passing on the $args, we can let leftjoin() create the WHERE for
        // the publications-specific columns too now
        $publicationsdef = $userapi->leftjoin($args);

        // TODO: make sure this is SQL standard
        // Start building the query
        if ($dbconn->databaseType == 'sqlite') {
            $query = 'SELECT COUNT(*)
                      FROM ( SELECT DISTINCT ' . $publicationsdef['field'] . '
                             FROM ' . $publicationsdef['table']; // WATCH OUT, UNBALANCED
        } else {
            $query = 'SELECT COUNT(DISTINCT ' . $publicationsdef['field'] . ')';
            $query .= ' FROM ' . $publicationsdef['table'];
        }

        if (!isset($args['cids'])) {
            $args['cids'] = [];
        }
        if (!isset($args['andcids'])) {
            $args['andcids'] = false;
        }
        if (count($args['cids']) > 0 || !empty($args['catid'])) {
            // Load API
            if (!$this->mod()->apiLoad('categories', 'user')) {
                return;
            }

            // Get the LEFT JOIN ... ON ...  and WHERE (!) parts from categories
            $args['modid'] = $this->mod()->getRegID('publications');
            if (isset($args['ptid']) && !isset($args['itemtype'])) {
                $args['itemtype'] = $args['ptid'];
            }
            $categoriesdef = $this->mod()->apiFunc('categories', 'user', 'leftjoin', $args);

            $query .= ' LEFT JOIN ' . $categoriesdef['table'];
            $query .= ' ON ' . $categoriesdef['field'] . ' = '
                    . $publicationsdef['id'];
            $query .= $categoriesdef['more'];
            $docid = 1;
        }

        // Create the WHERE part
        $where = [];
        // we rely on leftjoin() to create the necessary publications clauses now
        if (!empty($publicationsdef['where'])) {
            $where[] = $publicationsdef['where'];
        }
        if (!empty($docid)) {
            // we rely on leftjoin() to create the necessary categories clauses
            $where[] = $categoriesdef['where'];
        }
        if (count($where) > 0) {
            $query .= ' WHERE ' . join(' AND ', $where);
        }

        // Balance parentheses
        if ($dbconn->databaseType == 'sqlite') {
            $query .= ')';
        }
        // Run the query - finally :-)
        $result = $dbconn->Execute($query);
        if (!$result) {
            return;
        }

        if (!$result->first()) {
            return 0;
        }

        $num = $result->fields[0];
        $result->Close();

        return $num;
    }
}

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
 * publications userapi getmonthcount function
 * @extends MethodClass<UserApi>
 */
class GetmonthcountMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * count the number of items per month
     * @param mixed $args ['cids'] not supported here (yet ?)
     * @param mixed $args ['ptid'] publication type ID we're interested in
     * @param mixed $args ['state'] array of requested status(es) for the publications
     * @return array array(month => count), or false on failure
     */
    public function __invoke(array $args = [])
    {
        // Get database setup
        $dbconn = xarDB::getConn();

        // Get the field names and LEFT JOIN ... ON ... parts from publications
        // By passing on the $args, we can let leftjoin() create the WHERE for
        // the publications-specific columns too now
        $publicationsdef = xarMod::apiFunc('publications', 'user', 'leftjoin', $args);

        // Bug 1590 - Create custom query supported by each database.
        $dbtype = xarDB::getType();
        switch ($dbtype) {
            case 'mysql':
                $query = "SELECT LEFT(FROM_UNIXTIME(start_date),7) AS mymonth, COUNT(*) FROM " . $publicationsdef['table'];
                //            echo $query;exit;
                break;
            case 'postgres':
                $query = "SELECT TO_CHAR(ABSTIME(pubdate),'YYYY-MM') AS mymonth, COUNT(*) FROM " . $publicationsdef['table'];
                break;
            case 'mssql':
                $query = "SELECT LEFT(CONVERT(VARCHAR,DATEADD(ss,pubdate,'1/1/1970'),120),7) as mymonth, COUNT(*) FROM " . $publicationsdef['table'];
                break;
                // TODO:  Add SQL queries for Oracle, etc.
            default:
                return;
        }
        if (!empty($publicationsdef['where'])) {
            $query .= ' WHERE ' . $publicationsdef['where'];
        }
        switch ($dbtype) {
            case 'mssql':
                $query .= " GROUP BY LEFT(CONVERT(VARCHAR,DATEADD(ss,pubdate,'1/1/1970'),120),7)";
                break;
            default:
                $query .= ' GROUP BY mymonth';
                break;
        }
        $result = $dbconn->Execute($query);
        if (!$result) {
            return;
        }

        $months = [];
        while (!$result->EOF) {
            [$month, $count] = $result->fields;
            $months[$month] = $count;
            $result->MoveNext();
        }

        return $months;
    }
}

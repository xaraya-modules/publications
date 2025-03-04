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
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications adminapi getstats function
 * @extends MethodClass<AdminApi>
 */
class GetstatsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * count number of items depending on additional module criteria
     * @param array<mixed> $args with group
     * @return array<mixed>|void number of items with descriptors
     * @see AdminApi::getstats()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $allowedfields = ['pubtype_id', 'state', 'owner', 'locale', 'pubdate_year', 'pubdate_month', 'pubdate_day'];
        if (empty($group)) {
            $group = [];
        }
        $newfields = [];
        $newgroups = [];
        foreach ($group as $field) {
            if (empty($field) || !in_array($field, $allowedfields)) {
                continue;
            }
            if ($field == 'pubdate_year') {
                $dbtype = $this->db()->getType();
                switch ($dbtype) {
                    case 'mysql':
                        $newfields[] = "LEFT(FROM_UNIXTIME(start_date),4) AS myyear";
                        $newgroups[] = "myyear";
                        break;
                    case 'postgres':
                        $newfields[] = "TO_CHAR(ABSTIME(start_date),'YYYY') AS myyear";
                        // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                        $newgroups[] = "myyear";
                        break;
                    case 'mssql':
                        $newfields[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,start_date,'1/1/1970'),120),4) as myyear";
                        $newgroups[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,start_date,'1/1/1970'),120),4)";
                        break;
                        // TODO:  Add SQL queries for Oracle, etc.
                    default:
                        continue 2;
                }
            } elseif ($field == 'pubdate_month') {
                $dbtype = $this->db()->getType();
                switch ($dbtype) {
                    case 'mysql':
                        $newfields[] = "LEFT(FROM_UNIXTIME(start_date),7) AS mymonth";
                        $newgroups[] = "mymonth";
                        break;
                    case 'postgres':
                        $newfields[] = "TO_CHAR(ABSTIME(start_date),'YYYY-MM') AS mymonth";
                        // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                        $newgroups[] = "mymonth";
                        break;
                    case 'mssql':
                        $newfields[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,start_date,'1/1/1970'),120),7) as mymonth";
                        $newgroups[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,start_date,'1/1/1970'),120),7)";
                        break;
                        // TODO:  Add SQL queries for Oracle, etc.
                    default:
                        continue 2;
                }
            } elseif ($field == 'pubdate_day') {
                $dbtype = $this->db()->getType();
                switch ($dbtype) {
                    case 'mysql':
                        $newfields[] = "LEFT(FROM_UNIXTIME(start_date),10) AS myday";
                        $newgroups[] = "myday";
                        break;
                    case 'postgres':
                        $newfields[] = "TO_CHAR(ABSTIME(start_date),'YYYY-MM-DD') AS myday";
                        // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                        $newgroups[] = "myday";
                        break;
                    case 'mssql':
                        $newfields[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,start_date,'1/1/1970'),120),10) as myday";
                        $newgroups[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,start_date,'1/1/1970'),120),10)";
                        break;
                        // TODO:  Add SQL queries for Oracle, etc.
                    default:
                        continue 2;
                }
            } else {
                $newfields[] = $field;
                $newgroups[] = $field;
            }
        }
        if (empty($newfields) || count($newfields) < 1) {
            $newfields = ['pubtype_id', 'state', 'owner'];
            $newgroups = ['pubtype_id', 'state', 'owner'];
        }

        // Database information
        $dbconn = $this->db()->getConn();
        $xartables = & $this->db()->getTables();

        $query = 'SELECT ' . join(', ', $newfields) . ', COUNT(*)
                  FROM ' . $xartables['publications'] . '
                  GROUP BY ' . join(', ', $newgroups) . '
                  ORDER BY ' . join(', ', $newgroups);

        $result = $dbconn->Execute($query);
        if (!$result) {
            return;
        }

        $stats = [];
        while ($result->next()) {
            if (count($newfields) > 3) {
                [$field1, $field2, $field3, $field4, $count] = $result->fields;
                $stats[$field1][$field2][$field3][$field4] = $count;
            } elseif (count($newfields) == 3) {
                [$field1, $field2, $field3, $count] = $result->fields;
                $stats[$field1][$field2][$field3] = $count;
            } elseif (count($newfields) == 2) {
                [$field1, $field2, $count] = $result->fields;
                $stats[$field1][$field2] = $count;
            } elseif (count($newfields) == 1) {
                [$field1, $count] = $result->fields;
                $stats[$field1] = $count;
            }
        }
        $result->Close();

        return $stats;
    }
}

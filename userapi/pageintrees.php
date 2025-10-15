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
 * publications userapi pageintrees function
 * @extends MethodClass<UserApi>
 */
class PageintreesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see UserApi::pageintrees()
     */

    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($pid) || !is_numeric($pid) || !isset($tree_roots) || !is_array($tree_roots)) {
            return false;
        }

        $xartable = & $this->db()->getTables();
        $dbconn = $this->db()->getConn();

        // For the page to be somewhere in a tree, identified by the root of that tree,
        // it's xar_left column must be between the xar_left and xar_right columns
        // of the tree root.
        $query = 'SELECT COUNT(*)'
            . ' FROM ' . $xartable['publications'] . ' AS testpage'
            . ' INNER JOIN ' . $xartable['publications'] . ' AS testtrees'
            . ' ON testpage.leftpage_id BETWEEN testtrees.leftpage_id AND testtrees.rightpage_id'
            . ' AND testtrees.id IN (?' . str_repeat(',?', count($tree_roots) - 1) . ')'
            . ' WHERE testpage.id = ?';

        // Add the pid onto the tree roots to form the full bind variable set.
        $tree_roots[] = $pid;
        $result = $dbconn->execute($query, $tree_roots);

        if (!$result || !$result->first()) {
            return false;
        }

        [$count] = $result->fields;

        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }
}

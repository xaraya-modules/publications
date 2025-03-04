<?php

/*
 * Get the parent/left/right values for a single item.
 * Will include the virtual item '0' if necessary.
 * id: ID of the item.
 * tablename: name of table
 * idname: name of the ID column
 */

function publications_treeapi_getleftright(array $args = [], $context = null)
{
    // Expand the arguments.
    extract($args);

    // Database.
    $dbconn = xarDB::getConn();

    if ($id <> 0) {
        // Insert point is a real item.
        $query = 'SELECT xar_parent, xar_left, xar_right'
            . ' FROM ' . $tablename
            . ' WHERE ' . $idname . ' = ?';
        $result = $dbconn->execute($query, [(int) $id]);
        if ($result->first()) {
            [$parent, $left, $right] = $result->fields;
            $return = ['parent' => (int) $parent, 'left' => (int) $left, 'right' => (int) $right];
        } else {
            // Item not found.
            // TODO: raise error.
            return;
        }
    } else {
        // Insert point is the virtual root.
        // This query should return EOF when the table is empty,
        // but it doesn't (on MySQL, at least - I'm sure a MAX() of
        // no rows returns no rows in Oracle).
        $query = 'SELECT 0, MIN(xar_left)-1 as xar_left, MAX(xar_right)+1 as xar_right'
            . ' FROM ' . $tablename;
        $result = $dbconn->execute($query);
        $parent = 0;
        if ($result->first()) {
            [$parent, $left, $right] = $result->fields;
            $return = ['parent' => (int) $parent, 'left' => (int) $left, 'right' => (int) $right];
            // Hack for MySQL where EOF does not work on MIN/MAX group functions.
            if (!isset($left)) {
                $return = ['parent' => 0, 'left' => 1, 'right' => 2];
            }
        } else {
            $return = ['parent' => 0, 'left' => 1, 'right' => 2];
        }
    }

    return $return;
}

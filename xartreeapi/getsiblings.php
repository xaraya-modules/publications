<?php

/*
 * Get the sibling values for a single item.
 * Will include the virtual item '0' if necessary.
 * id: ID of the item.
 * tablename: name of table
 * idname: name of the ID column
 * includeself: bool to include the given pid in the result (default false)
 */

function publications_treeapi_getsiblings(array $args = [], $context = null)
{
    // Expand the arguments.
    extract($args);

    // Database.
    $dbconn = xarDB::getConn();

    if ($id <> 0) {
        // Insert point is a real item.
        $query = "SELECT 
                    parent.$idname
                  FROM 
                    $tablename AS node, 
                    $tablename AS parent 
                  WHERE 
                    parent.xar_parent = node.xar_parent
                    AND 
                    node.$idname = ?";
        if (!isset($includeself) || $includeself != true) {
            $query .= " AND parent.$idname != ?";
        }
        $query .= " ORDER BY parent.xar_left";

        $siblings = [];

        // return results in proper order
        while ($result->next()) {
            [$pid] = $result->fields;
            $siblings[] = $pid;
        }
        if (count($siblings) > 0) {
            return $siblings;
        } else {
            return;
        }
    } else {
        // Insert point is the virtual root.
        // Virtual root has no siblings
        if (isset($includeself) && $includeself == true) {
            return [0];
        } else {
            return;
        }
    }
}

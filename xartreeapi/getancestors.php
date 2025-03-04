<?php

/*
 * Get the ancestor values for a single item.
 * Will include the virtual item '0' if necessary.
 * id: ID of the item.
 * tablename: name of table
 * idname: name of the ID column
 * rootonly: (bool) whether to retrieve all ancestors or just the root (default false)
 */

function publications_treeapi_getancestors(array $args = [], $context = null)
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
                    node.xar_left BETWEEN parent.xar_left AND parent.xar_right 
                    AND 
                    node.$idname = ? 
                  ORDER BY 
                    parent.xar_left";
        if (isset($rootonly) && $rootonly == true) {
            $query .= " ASC LIMIT 1";
        }
        $result = $dbconn->execute($query, [(int) $id]);

        $ancestors = [];

        // return results in order from root to leaf
        while ($result->next()) {
            [$pid] = $result->fields;
            $ancestors[] = $pid;
        }
        if (count($ancestors) > 0) {
            return $ancestors;
        } else {
            return;
        }
    } else {
        // Insert point is the virtual root, return it
        return [0];
    }
}

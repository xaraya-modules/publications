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

/**
 * publications userapi getauthors function
 * @extends MethodClass<UserApi>
 */
class GetauthorsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get a list of article authors depending on additional module criteria
     * @param array<mixed> $args
     * @var mixed $cids array of cids that we are counting for (OR/AND)
     * @var mixed $andcids true means AND-ing categories listed in cids
     * @var mixed $owner the ID of the author
     * @var mixed $ptid publication type ID (for news, sections, reviews, ...)
     * @var mixed $state array of requested status(es) for the publications
     * @var mixed $startdate publications published at startdate or later
     * (unix timestamp format)
     * @var mixed $enddate publications published before enddate
     * (unix timestamp format)
     * @return array|void of author id => author name
     * @see UserApi::getauthors()
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

        // Load API
        if (!$this->mod()->apiLoad('roles', 'user')) {
            return;
        }

        // Get the field names and LEFT JOIN ... ON ... parts from users
        $usersdef = $this->mod()->apiFunc('roles', 'user', 'leftjoin');

        // TODO: make sure this is SQL standard
        // Start building the query
        $query = 'SELECT DISTINCT ' . $publicationsdef['owner'] . ', ' . $usersdef['name'];
        $query .= ' FROM ' . $publicationsdef['table'];

        // Add the LEFT JOIN ... ON ... parts from users
        $query .= ' LEFT JOIN ' . $usersdef['table'];
        $query .= ' ON ' . $usersdef['field'] . ' = ' . $publicationsdef['owner'];

        if (!isset($args['cids'])) {
            $args['cids'] = [];
        }
        if (!isset($args['andcids'])) {
            $args['andcids'] = false;
        }
        if (count($args['cids']) > 0) {
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

        // Order by author name
        $query .= ' ORDER BY ' . $usersdef['name'] . ' ASC';

        // Run the query - finally :-)
        $result = $dbconn->Execute($query);
        if (!$result) {
            return;
        }

        $authors = [];
        while ($result->next()) {
            [$uid, $name] = $result->fields;
            $authors[$uid] = ['id' => $uid, 'name' => $name];
        }

        $result->Close();

        return $authors;
    }
}

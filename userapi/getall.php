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
 * publications userapi getall function
 * @extends MethodClass<UserApi>
 */
class GetallMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get overview of all publications
     * Note : the following parameters are all optional
     * @param array<mixed> $args
     * @var mixed $numitems number of publications to get
     * @var mixed $sort sort order ('create_date','title','hits','rating','author','id','summary','notes',...)
     * @var mixed $startnum starting article number
     * @var mixed $ids array of article ids to get
     * @var mixed $owner the ID of the author
     * @var mixed $ptid publication type ID (for news, sections, reviews, ...)
     * @var mixed $state array of requested status(es) for the publications
     * @var mixed $search search parameter(s)
     * @var mixed $searchfields array of fields to search in
     * @var mixed $searchtype start, end, like, eq, gt, ... (TODO)
     * @var mixed $cids array of category IDs for which to get publications (OR/AND)
     * (for all categories don?t set it)
     * @var mixed $andcids true means AND-ing categories listed in cids
     * @var mixed $create_date publications published in a certain year (YYYY), month (YYYY-MM) or day (YYYY-MM-DD)
     * @var mixed $startdate publications published at startdate or later
     * (unix timestamp format)
     * @var mixed $enddate publications published before enddate
     * (unix timestamp format)
     * @var mixed $fields array with all the fields to return per publication
     * Default list is : 'id','title','summary','owner',
     * 'create_date','pubtype_id','notes','state','body1'
     * Optional fields : 'cids','author','counter','rating','dynamicdata'
     * @var mixed $extra array with extra fields to return per article (in addition
     * to the default list). So you can EITHER specify *all* the
     * fields you want with 'fields', OR take all the default
     * ones and add some optional fields with 'extra'
     * @var mixed $where additional where clauses (e.g. myfield gt 1234)
     * @var mixed $locale language/locale (if not using multi-sites, categories etc.)
     * @return array|void Array of publications, or false on failure
     * @see UserApi::getall()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get arguments from argument array
        extract($args);

        // Optional argument
        if (!isset($startnum)) {
            $startnum = 1;
        }
        if (empty($cids)) {
            $cids = [];
        }
        if (!isset($andcids)) {
            $andcids = false;
        }
        if (empty($ptid)) {
            $ptid = null;
        }

        // Default fields in publications (for now)
        $columns = ['id','name','title','description','summary','body1','owner','pubtype_id',
            'notes','state','start_date', ];

        // Optional fields in publications (for now)
        // + 'cids' = list of categories an article belongs to
        // + 'author' = user name of owner
        // + 'counter' = number of times this article was displayed (hitcount)
        // + 'rating' = rating for this article (ratings)
        // + 'dynamicdata' = dynamic data fields for this article (dynamicdata)
        // + 'relevance' = relevance for this article (MySQL full-text search only)
        // $optional = array('cids','author','counter','rating','dynamicdata','relevance');
        if (!isset($fields)) {
            $fields = $columns;
        }

        if (isset($extra) && is_array($extra) && count($extra) > 0) {
            $fields = array_merge($fields, $extra);
        }

        if (empty($sort)) {
            if (!empty($search) && !empty($searchtype) && substr($searchtype, 0, 8) == 'fulltext') {
                if ($searchtype == 'fulltext boolean' && !in_array('relevance', $fields)) {
                    // add the relevance to the field list for sorting
                    $fields[] = 'relevance';
                }
                // let the database sort by relevance (= default for fulltext)
                $sortlist = [];
            } else {
                // default sort by create_date
                $sortlist = ['create_date'];
            }
        } elseif (is_array($sort)) {
            $sortlist = $sort;
        } else {
            $sortlist = explode(',', $sort);
        }

        $publications = [];

        // Security check
        if (!$this->sec()->checkAccess('ViewPublications')) {
            return;
        }

        // Fields requested by the calling function
        $required = [];
        foreach ($fields as $field) {
            $required[$field] = 1;
        }
        // mandatory fields for security
        $required['id'] = 1;
        $required['title'] = 1;

        $required['locale'] = 1;
        $required['parent_id'] = 1;
        $required['pubtype_id'] = 1;
        $required['create_date'] = 1;
        $required['owner'] = 1; // not to be confused with author (name) :-)
        // force cids as required when categories are given
        if (count($cids) > 0) {
            $required['cids'] = 1;
        }

        // TODO: put all this in dynamic data and retrieve everything via there (including hooked stuff)

        // Database information
        $dbconn = $this->db()->getConn();

        // Get the field names and LEFT JOIN ... ON ... parts from publications
        // By passing on the $args, we can let leftjoin() create the WHERE for
        // the publications-specific columns too now
        $publicationsdef = $userapi->leftjoin($args);

        // TODO : how to handle the case where name is empty, but uname isn't

        if (!empty($required['owner'])) {
            // Load API
            if (!$this->mod()->apiLoad('roles', 'user')) {
                return;
            }

            // Get the field names and LEFT JOIN ... ON ... parts from users
            $usersdef = $this->mod()->apiFunc('roles', 'user', 'leftjoin');
            if (empty($usersdef)) {
                return;
            }
        }

        $regid = $this->mod()->getRegID('publications');

        if (!empty($required['cids'])) {
            // Load API
            if (!$this->mod()->apiLoad('categories', 'user')) {
                return;
            }

            // Get the LEFT JOIN ... ON ...  and WHERE (!) parts from categories
            $categoriesdef = $this->mod()->apiFunc(
                'categories',
                'user',
                'leftjoin',
                ['cids' => $cids,
                    'andcids' => $andcids,
                    'itemtype' => $ptid ?? null,
                    'modid' => $regid, ]
            );
            if (empty($categoriesdef)) {
                return;
            }
        }

        if (!empty($required['counter']) && $this->mod()->isHooked('hitcount', 'publications', $ptid)) {
            // Load API
            if (!$this->mod()->apiLoad('hitcount', 'user')) {
                return;
            }

            // Get the LEFT JOIN ... ON ...  and WHERE (!) parts from hitcount
            $hitcountdef = $this->mod()->apiFunc(
                'hitcount',
                'user',
                'leftjoin',
                ['modid' => $regid,
                    'itemtype' => $ptid ?? null, ]
            );
        }

        if (!empty($required['rating']) && $this->mod()->isHooked('ratings', 'publications', $ptid)) {
            // Load API
            if (!$this->mod()->apiLoad('ratings', 'user')) {
                return;
            }

            // Get the LEFT JOIN ... ON ...  and WHERE (!) parts from ratings
            $ratingsdef = $this->mod()->apiFunc(
                'ratings',
                'user',
                'leftjoin',
                ['modid' => $regid,
                    'itemtype' => $ptid ?? null, ]
            );
        }

        // Create the SELECT part
        $select = [];
        foreach ($required as $field => $val) {
            // we'll handle this later
            if ($field == 'cids') {
                continue;
            } elseif ($field == 'dynamicdata') {
                continue;
            } elseif ($field == 'owner') {
                $select[] = $usersdef['name'];
            } elseif ($field == 'counter') {
                if (!empty($hitcountdef['hits'])) {
                    $select[] = $hitcountdef['hits'];
                }
            } elseif ($field == 'rating') {
                if (!empty($ratingsdef['rating'])) {
                    $select[] = $ratingsdef['rating'];
                }
            } else {
                $select[] = $publicationsdef[$field];
            }
        }

        // FIXME: <rabbitt> PostgreSQL requires that all fields in an 'Order By' be in the SELECT
        //        this has been added to remove the error that not having it creates
        // FIXME: <mikespub> Oracle doesn't allow having the same field in a query twice if you
        //        don't specify an alias (at least in sub-queries, which is what SelectLimit uses)
        //    if (!in_array($publicationsdef['create_date'], $select)) {
        //        $select[] = $publicationsdef['create_date'];
        //    }

        // we need distinct for multi-category OR selects where publications fit in more than 1 category
        if (count($cids) > 0) {
            $query = 'SELECT DISTINCT ' . join(', ', $select);
        } else {
            $query = 'SELECT ' . join(', ', $select);
        }

        // Create the FROM ... [LEFT JOIN ... ON ...] part
        $from = $publicationsdef['table'];
        $addme = 0;
        if (!empty($required['owner'])) {
            // Add the LEFT JOIN ... ON ... parts from users
            $from .= ' LEFT JOIN ' . $usersdef['table'];
            $from .= ' ON ' . $usersdef['field'] . ' = ' . $publicationsdef['owner'];
            $addme = 1;
        }

        if (!empty($required['counter']) && isset($hitcountdef)) {
            // add this for SQL compliance when there are multiple JOINs
            // bug 4429: sqlite doesnt like the parentheses
            if ($addme && ($dbconn->databaseType != 'sqlite')) {
                $from = '(' . $from . ')';
            }
            // Add the LEFT JOIN ... ON ... parts from hitcount
            $from .= ' LEFT JOIN ' . $hitcountdef['table'];
            $from .= ' ON ' . $hitcountdef['field'] . ' = ' . $publicationsdef['id'];
            $addme = 1;
        }
        if (!empty($required['rating']) && isset($ratingsdef)) {
            // add this for SQL compliance when there are multiple JOINs
            // bug 4429: sqlite doesnt like the parentheses
            if ($addme && ($dbconn->databaseType != 'sqlite')) {
                $from = '(' . $from . ')';
            }
            // Add the LEFT JOIN ... ON ... parts from ratings
            $from .= ' LEFT JOIN ' . $ratingsdef['table'];
            $from .= ' ON ' . $ratingsdef['field'] . ' = ' . $publicationsdef['id'];
            $addme = 1;
        }
        if (count($cids) > 0) {
            // add this for SQL compliance when there are multiple JOINs
            // bug 4429: sqlite doesnt like the parentheses
            if ($addme && ($dbconn->databaseType != 'sqlite')) {
                $from = '(' . $from . ')';
            }
            // Add the LEFT JOIN ... ON ... parts from categories
            $from .= ' LEFT JOIN ' . $categoriesdef['table'];
            $from .= ' ON ' . $categoriesdef['field'] . ' = ' . $publicationsdef['id'];
            if (!empty($categoriesdef['more']) && ($dbconn->databaseType != 'sqlite')) {
                $from = '(' . $from . ')';
                $from .= $categoriesdef['more'];
            }
        }
        $query .= ' FROM ' . $from;

        // TODO: check the order of the conditions for brain-dead databases ?
        // Create the WHERE part
        $where = [];
        // we rely on leftjoin() to create the necessary publications clauses now
        if (!empty($publicationsdef['where'])) {
            $where[] = $publicationsdef['where'];
        }
        if (!empty($required['counter']) && !empty($hitcountdef['where'])) {
            $where[] = $hitcountdef['where'];
        }
        if (!empty($required['rating']) && !empty($ratingsdef['where'])) {
            $where[] = $ratingsdef['where'];
        }
        if (count($cids) > 0) {
            // we rely on leftjoin() to create the necessary categories clauses
            $where[] = $categoriesdef['where'];
        }
        if (count($where) > 0) {
            $query .= ' WHERE ' . join(' AND ', $where);
        }

        // TODO: support other non-publications fields too someday ?
        // Create the ORDER BY part
        if (count($sortlist) > 0) {
            $sortparts = [];
            $seenid = 0;
            foreach ($sortlist as $criteria) {
                // ignore empty sort criteria
                if (empty($criteria)) {
                    continue;
                }
                // split off trailing ASC or DESC
                if (preg_match('/^(.+)\s+(ASC|DESC)\s*$/i', $criteria, $matches)) {
                    $criteria = trim($matches[1]);
                    $sortorder = strtoupper($matches[2]);
                } else {
                    $sortorder = '';
                }
                if ($criteria == 'title') {
                    $sortparts[] = $publicationsdef['title'] . ' ' . (!empty($sortorder) ? $sortorder : 'ASC');
                    //            } elseif ($criteria == 'create_date' || $criteria == 'date') {
                    //                $sortparts[] = $publicationsdef['create_date'] . ' ' . (!empty($sortorder) ? $sortorder : 'DESC');
                } elseif ($criteria == 'hits' && !empty($hitcountdef['hits'])) {
                    $sortparts[] = $hitcountdef['hits'] . ' ' . (!empty($sortorder) ? $sortorder : 'DESC');
                } elseif ($criteria == 'rating' && !empty($ratingsdef['rating'])) {
                    $sortparts[] = $ratingsdef['rating'] . ' ' . (!empty($sortorder) ? $sortorder : 'DESC');
                } elseif ($criteria == 'owner' && !empty($usersdef['name'])) {
                    $sortparts[] = $usersdef['name'] . ' ' . (!empty($sortorder) ? $sortorder : 'ASC');
                } elseif ($criteria == 'relevance' && !empty($publicationsdef['relevance'])) {
                    $sortparts[] = 'relevance' . ' ' . (!empty($sortorder) ? $sortorder : 'DESC');
                } elseif ($criteria == 'id') {
                    $sortparts[] = $publicationsdef['id'] . ' ' . (!empty($sortorder) ? $sortorder : 'ASC');
                    $seenid = 1;
                    // other publications fields, e.g. summary, notes, ...
                } elseif (!empty($publicationsdef[$criteria])) {
                    $sortparts[] = $publicationsdef[$criteria] . ' ' . (!empty($sortorder) ? $sortorder : 'ASC');
                } else {
                    // ignore unknown sort fields
                }
            }
            // add sorting by id for unique sort order
            if (count($sortparts) < 2 && empty($seenid)) {
                $sortparts[] = $publicationsdef['id'] . ' DESC';
            }
            $query .= ' ORDER BY ' . join(', ', $sortparts);
        } elseif (!empty($search) && !empty($searchtype) && substr($searchtype, 0, 8) == 'fulltext') {
            // For fulltext, let the database return the publications by relevance here (= default)

            // For fulltext in boolean mode, add MATCH () ... AS relevance ... ORDER BY relevance DESC (cfr. leftjoin)
            if (!empty($required['relevance']) && $searchtype == 'fulltext boolean') {
                $query .= ' ORDER BY relevance DESC, ' . $publicationsdef['create_date'] . ' DESC, ' . $publicationsdef['id'] . ' DESC';
            }
        } else { // default is 'create_date'
            $query .= ' ORDER BY ' . $publicationsdef['create_date'] . ' DESC, ' . $publicationsdef['id'] . ' DESC';
        }
        //echo $query;
        // Run the query - finally :-)
        if (isset($numitems) && is_numeric($numitems)) {
            $result = & $dbconn->SelectLimit($query, $numitems, $startnum - 1);
        } else {
            $result = $dbconn->Execute($query);
        }
        if (!$result) {
            return;
        }

        $itemids_per_type = [];
        // Put publications into result array
        while ($result->next()) {
            $data = $result->fields;
            $item = [];
            // loop over all required fields again
            foreach ($required as $field => $val) {
                if ($field == 'cids' || $field == 'dynamicdata' || $val != 1) {
                    continue;
                }
                $value = array_shift($data);
                if ($field == 'rating') {
                    $value = intval($value);
                }
                $item[$field] = $value;
            }
            // check security - don't generate an exception here
            if (empty($required['cids']) && !$this->sec()->check('ViewPublications', 0, 'Publication', "$item[pubtype_id]:All:$item[owner]:$item[id]")) {
                continue;
            }
            $publications[] = $item;
            if (!empty($required['dynamicdata'])) {
                $pubtype = $item['pubtype_id'];
                if (!isset($itemids_per_type[$pubtype])) {
                    $itemids_per_type[$pubtype] = [];
                }
                $itemids_per_type[$pubtype][] = $item['id'];
            }
        }
        $result->Close();

        if (!empty($required['cids']) && count($publications) > 0) {
            // Get all the categories at once
            $ids = [];
            foreach ($publications as $article) {
                $ids[] = $article['id'];
            }

            // Load API
            if (!$this->mod()->apiLoad('categories', 'user')) {
                return;
            }

            // Get the links for the Array of iids we have
            $cids = $this->mod()->apiFunc(
                'categories',
                'user',
                'getlinks',
                ['iids' => $ids,
                    'reverse' => 1,
                    // Note : we don't need to specify the item type here for publications, since we use unique ids anyway
                    'modid' => $regid, ]
            );

            // Inserting the corresponding Category ID in the Publication Description
            $delete = [];
            $cachesec = [];
            foreach ($publications as $key => $article) {
                if (isset($cids[$article['id']]) && count($cids[$article['id']]) > 0) {
                    $publications[$key]['cids'] = $cids[$article['id']];
                    foreach ($cids[$article['id']] as $cid) {
                        if (!$this->sec()->check('ViewPublications', 0, 'Publication', "$article[pubtype_id]:$cid:$article[owner]:$article[id]")) {
                            $delete[$key] = 1;
                            break;
                        }
                        if (!isset($cachesec[$cid])) {
                            // TODO: combine with ViewCategoryLink check when we can combine module-specific
                            // security checks with "parent" security checks transparently ?
                            $cachesec[$cid] = $this->sec()->check('ReadCategories', 0, 'Category', "All:$cid");
                        }
                        if (!$cachesec[$cid]) {
                            $delete[$key] = 1;
                            break;
                        }
                    }
                } else {
                    if (!$this->sec()->check('ViewPublications', 0, 'Publication', "$article[pubtype_id]:All:$article[owner]:$article[id]")) {
                        $delete[$key] = 1;
                        continue;
                    }
                }
            }
            if (count($delete) > 0) {
                foreach ($delete as $key => $val) {
                    unset($publications[$key]);
                }
            }
        }

        if (!empty($required['dynamicdata']) && count($publications) > 0) {
            foreach ($itemids_per_type as $pubtype => $itemids) {
                if (!$this->mod()->isHooked('dynamicdata', 'publications', $pubtype)) {
                    continue;
                }
                [$properties, $items] = $this->mod()->apiFunc(
                    'dynamicdata',
                    'user',
                    'getitemsforview',
                    ['module'   => 'publications',
                        'itemtype' => $pubtype,
                        'itemids'  => $itemids,
                        // ignore the display-only properties
                        'state'   => 1, ]
                );

                if (empty($properties) || count($properties) == 0) {
                    continue;
                }
                foreach ($publications as $key => $article) {
                    // otherwise publications (of different pub types) with dd properties having the same
                    // names reset previously set values to empty string for each iteration based on the pubtype
                    if ($article['pubtype_id'] != $pubtype) {
                        continue;
                    }

                    foreach (array_keys($properties) as $name) {
                        if (isset($items[$article['id']]) && isset($items[$article['id']][$name])) {
                            $value = $items[$article['id']][$name];
                        } else {
                            $value = $properties[$name]->default;
                        }

                        $publications[$key][$name] = $value;

                        // TODO: clean up this temporary fix
                        if (!empty($value)) {
                            $publications[$key][$name . '_output'] = $properties[$name]->showOutput(['value' => $value]);
                        }
                    }
                }
            }
        }

        return $publications;
    }
}

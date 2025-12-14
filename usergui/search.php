<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\UserGui;

use Xaraya\Modules\Publications\Defines;
use Xaraya\Modules\Publications\UserGui;
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;

/**
 * publications user search function
 * @extends MethodClass<UserGui>
 */
class SearchMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * search publications (called as hook from search module, or directly with pager)
     * @param array<mixed> $args
     * @var mixed $objectid could be the query ? (currently unused)
     * @var mixed $extrainfo all other parameters ? (currently unused)
     * @return array|string|void output
     * @see UserGui::search()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // pager stuff
        $this->var()->find('startnum', $startnum, 'int:0');

        // categories stuff
        $this->var()->find('cids', $cids, 'array');
        $this->var()->find('andcids', $andcids, 'str');
        $this->var()->find('catid', $catid, 'str');

        // single publication type when called via the pager
        $this->var()->find('ptid', $ptid, 'id');

        // multiple publication types when called via search hooks
        $this->var()->find('ptids', $ptids, 'array');

        // date stuff via forms
        $this->var()->find('publications_startdate', $startdate, 'str');
        $this->var()->find('publications_enddate', $enddate, 'str');

        // date stuff via URLs
        $this->var()->find('start', $start, 'int:0');
        $this->var()->find('end', $end, 'int:0');

        // search button was pressed
        $this->var()->find('search', $search, 'str');

        // select by article state (array or string)
        $this->var()->find('state', $state);

        // yes, this is the query
        $this->var()->find('q', $q, 'str');
        $this->var()->find('author', $author, 'str');

        // filter by category
        $this->var()->find('by', $by, 'str');

        // can't use list enum here, because we don't know which sorts might be used
        $this->var()->find('sort', $sort, 'regexp:/^[\w,]*$/');

        // boolean AND/OR for words (no longer used)
        //$this->var()->find('bool', $bool, 'str', NULL);

        // search in specific fields
        $this->var()->find('publications_fields', $fields);

        $this->var()->find('searchtype', $searchtype);

        if (isset($args['objectid'])) {
            $ishooked = 1;
        } else {
            $ishooked = 0;
            if (empty($fields)) {
                // search in specific fields via URLs
                $this->var()->find('fields', $fields);
            }
        }

        // TODO: could we need this someday ?
        if (isset($args['extrainfo'])) {
            extract($args['extrainfo']);
        }

        // TODO: clean up this copy & paste stuff :-)

        // Default parameters
        if (!isset($startnum)) {
            $startnum = 1;
        }
        if (!isset($numitems)) {
            $numitems = 20;
        }

        if (!$this->mod()->apiLoad('publications', 'user')) {
            return;
        }

        // Get publication types
        $pubtypes = $userapi->get_pubtypes();

        if ($this->sec()->checkAccess('AdminPublications', 0)) {
            $isadmin = true;
        } else {
            $isadmin = false;
        }

        // frontpage or approved state
        if (!$isadmin || !isset($state)) {
            $state = [Defines::STATE_FRONTPAGE,Defines::STATE_APPROVED];
        } elseif (is_string($state)) {
            if (strpos($state, ' ')) {
                $state = explode(' ', $state);
            } elseif (strpos($state, '+')) {
                $state = explode('+', $state);
            } else {
                $state = [$state];
            }
        }
        $seenstate = [];
        foreach ($state as $that) {
            if (empty($that) || !is_numeric($that)) {
                continue;
            }
            $seenstate[$that] = 1;
        }
        $state = array_keys($seenstate);
        if (count($state) != 2 || !in_array(Defines::STATE_APPROVED, $state) || !in_array(Defines::STATE_FRONTPAGE, $state)) {
            $stateline = implode('+', $state);
        } else {
            $stateline = null;
        }

        if (!isset($sort)) {
            $sort = null;
        }

        // default publication type(s) to search in
        if (!empty($ptid) && isset($pubtypes[$ptid])) {
            $ptids = [$ptid];
            $settings = unserialize($this->mod()->getVar('settings.' . $ptid));
            if (empty($settings['show_categories'])) {
                $show_categories = 0;
            } else {
                $show_categories = 1;
            }
        } elseif (!empty($ptids) && count($ptids) > 0) {
            foreach ($ptids as $curptid) {
                // default view doesn't apply here ?!
            }
            $show_categories = 1;
        } elseif (!isset($ptids)) {
            //    $ptids = array($this->mod()->getVar('defaultpubtype'));
            $ptids = [];
            foreach ($pubtypes as $pubid => $pubtype) {
                $ptids[] = $pubid;
            }
            $show_categories = 1;
        } else {
            // TODO: rethink this when we're dealing with multi-pubtype categories
            $show_categories = 0;
        }

        // turn $catid into $cids array (and set $andcids flag)
        if (!empty($catid)) {
            if (strpos($catid, ' ')) {
                $cids = explode(' ', $catid);
                $andcids = true;
            } elseif (strpos($catid, '+')) {
                $cids = explode('+', $catid);
                $andcids = true;
            } else {
                $cids = explode('-', $catid);
                $andcids = false;
            }
        }
        $seencid = [];
        $catid = null;
        if (isset($cids) && is_array($cids)) {
            foreach ($cids as $cid) {
                if (empty($cid) || !preg_match('/^_?[0-9]+$/', $cid)) {
                    continue;
                }
                $seencid[$cid] = 1;
            }
            $cids = array_keys($seencid);
            if ($andcids) {
                $catid = join('+', $cids);
            } else {
                $catid = join('-', $cids);
            }
        }
        $seenptid = [];
        if (isset($ptids) && is_array($ptids)) {
            foreach ($ptids as $curptid) {
                if (empty($curptid) || !isset($pubtypes[$curptid])) {
                    continue;
                }
                $seenptid[$curptid] = 1;
            }
            $ptids = array_keys($seenptid);
        }
        /* Ensure whitespace alone not passed to api -causes errors */
        if (isset($q) && trim($q) === '') {
            $q = null;
        }

        // Find the id of the author we're looking for
        if (!empty($author)) {
            // Load API
            if (!$this->mod()->apiLoad('roles', 'user')) {
                return;
            }
            $user = $this->mod()->apiFunc(
                'roles',
                'user',
                'get',
                ['name' => $author]
            );
            if (!empty($user['id'])) {
                $owner = $user['id'];
            } else {
                $owner = null;
                $author = null;
            }
        } else {
            $owner = null;
            $author = null;
        }

        if (isset($start) && is_numeric($start)) {
            $startdate = $this->mls()->formatDate("%Y-%m-%d %H:%M:%S", $start);
        }
        if (isset($end) && is_numeric($end)) {
            $enddate = $this->mls()->formatDate("%Y-%m-%d %H:%M:%S", $end);
        }

        if (empty($fields)) {
            $fieldlist = ['title', 'description', 'summary', 'body1'];
        } else {
            $fieldlist = array_keys($fields);
            // don't pass fields via URLs if we stick to the default list
            if (count($fields) == 3 && isset($fields['title']) && isset($fields['description']) && isset($fields['summary']) && isset($fields['body1'])) {
                $fields = null;
            }
        }

        // Set default searchtype to 'fulltext' if necessary
        $fulltext = $this->mod()->getVar('fulltextsearch');
        if (!isset($searchtype) && !empty($fulltext)) {
            $searchtype = 'fulltext';
        }
        // FIXME: fulltext only supports searching in all configured text fields !
        if (empty($fields) && !empty($fulltext) && !empty($searchtype) && $searchtype == 'fulltext') {
            $fieldlist = explode(',', $fulltext);
        }

        $data = [];
        $data['results'] = [];
        $data['state'] = '';
        $data['ishooked'] = $ishooked;
        // TODO: MichelV: $ishooked is never empty, but either 0 or 1
        if (empty($ishooked)) {
            $data['q'] = isset($q) ? $this->prep()->text($q) : null;
            $data['author'] = isset($author) ? $this->prep()->text($author) : null;
            $data['searchtype'] = $searchtype;
        }
        if ($isadmin) {
            $states = $userapi->getstates();
            $data['statelist'] = [];
            foreach ($states as $id => $name) {
                $data['statelist'][] = ['id' => $id, 'name' => $name, 'checked' => in_array($id, $state)];
            }
        }

        // TODO: show field labels when we're dealing with only 1 pubtype
        $data['fieldlist'] = [
            ['id' => 'title', 'name' => $this->ml('title'), 'checked' => in_array('title', $fieldlist)],
            ['id' => 'description', 'name' => $this->ml('description'), 'checked' => in_array('description', $fieldlist)],
            ['id' => 'summary', 'name' => $this->ml('summary'), 'checked' => in_array('summary', $fieldlist)],
            ['id' => 'body1', 'name' => $this->ml('body1'), 'checked' => in_array('body1', $fieldlist)],
            //                                     array('id' => 'notes', 'name' => $this->ml('notes'), 'checked' => in_array('notes',$fieldlist)),
        ];

        $data['publications'] = [];
        foreach ($pubtypes as $pubid => $pubtype) {
            if (!empty($seenptid[$pubid])) {
                $checked = ' checked="checked"';
            } else {
                $checked = '';
            }
            $data['publications'][] = ['id' => $pubid,
                'description' => $this->prep()->text($pubtype['description']),
                'checked' => $checked, ];
        }

        $data['categories'] = [];
        if (!empty($by) && $by == 'cat') {
            $catarray = [];
            foreach ($ptids as $curptid) {
                // get root categories for this publication type
                $catlinks = $this->mod()->apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications','itemtype' => $curptid]);
                foreach ($catlinks as $cat) {
                    $catarray[$cat['category_id']] = $cat['name'];
                }
            }

            foreach ($catarray as $cid => $title) {
                $select = $this->mod()->apiFunc(
                    'categories',
                    'visual',
                    'makeselect',
                    ['cid' => $cid,
                        'return_itself' => true,
                        'select_itself' => true,
                        'values' => &$seencid,
                        'multiple' => 1, ]
                );
                $data['categories'][] = ['cattitle' => $title,
                    'catselect' => $select, ];
            }
            $data['searchurl'] = $this->ctl()->getModuleURL('search', 'user', 'main');
        } else {
            $data['searchurl'] = $this->ctl()->getModuleURL(
                'search',
                'user',
                'main',
                ['by' => 'cat']
            );
        }

        $now = time();
        if (empty($startdate)) {
            $startdate = null;
            $data['startdate'] = 'N/A';
        } else {
            if (!preg_match('/[a-zA-Z]+/', $startdate)) {
                $startdate .= ' GMT';
            }
            $startdate = strtotime($startdate);
            // adjust for the user's timezone offset
            $startdate -= $this->mls()->userOffset() * 3600;
            if ($startdate > $now && !$isadmin) {
                $startdate = $now;
            }
            $data['startdate'] = $startdate;
        }
        if (empty($enddate)) {
            $enddate = $now;
            $data['enddate'] = 'N/A';
        } else {
            if (!preg_match('/[a-zA-Z]+/', $enddate)) {
                $enddate .= ' GMT';
            }
            $enddate = strtotime($enddate);
            // adjust for the user's timezone offset
            $enddate -= $this->mls()->userOffset() * 3600;
            if ($enddate > $now && !$isadmin) {
                $enddate = $now;
            }
            $data['enddate'] = $enddate;
        }

        if (!empty($q) || (!empty($author) && isset($owner)) || !empty($search) || !empty($ptid) || !empty($startdate) || $enddate != $now || !empty($catid)) {
            $getfields = ['id','title', 'start_date','pubtype_id','cids'];
            // Return the relevance when using MySQL full-text search
            //if (!empty($search) && !empty($searchtype) && substr($searchtype,0,8) == 'fulltext') {
            //    $getfields[] = 'relevance';
            //}
            $count = 0;
            // TODO: allow combination of searches ?
            foreach ($ptids as $curptid) {
                $publications = $userapi->getall(
                    ['startnum' => $startnum,
                        'cids' => $cids,
                        'andcids' => $andcids,
                        'ptid' => $curptid,
                        'owner' => $owner,
                        'sort' => $sort,
                        'numitems' => $numitems,
                        'state' => $state,
                        'start_date' => $startdate,
                        'end_date' => $enddate,
                        'searchfields' => $fieldlist,
                        'searchtype' => $searchtype,
                        'search' => $q,
                        'fields' => $getfields,
                    ]
                );
                // TODO: re-use article output code from elsewhere (view / archive / admin)
                if (!empty($publications) && count($publications) > 0) {
                    // retrieve the categories for each article
                    $catinfo = [];
                    if ($show_categories) {
                        $cidlist = [];
                        foreach ($publications as $article) {
                            if (!empty($article['cids']) && count($article['cids']) > 0) {
                                foreach ($article['cids'] as $cid) {
                                    $cidlist[$cid] = 1;
                                }
                            }
                        }
                        if (count($cidlist) > 0) {
                            $catinfo = $this->mod()->apiFunc(
                                'categories',
                                'user',
                                'getcatinfo',
                                ['cids' => array_keys($cidlist)]
                            );
                            // get root categories for this publication type
                            $catroots = $userapi->getrootcats(
                                ['ptid' => $curptid]
                            );
                            $catroots = $this->mod()->apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications','itemtype' => $curptid]);
                        }
                        foreach ($catinfo as $cid => $info) {
                            $catinfo[$cid]['name'] = $this->prep()->text($info['name']);
                            $catinfo[$cid]['link'] = $this->mod()->getURL(
                                'user',
                                'view',
                                ['ptid' => $curptid,
                                    'catid' => (($catid && $andcids) ? $catid . '+' . $cid : $cid), ]
                            );
                            // only needed when sorting by root category id
                            $catinfo[$cid]['root'] = 0; // means not found under a root category
                            // only needed when sorting by root category order
                            $catinfo[$cid]['order'] = 0; // means not found under a root category
                            $rootidx = 1;
                            foreach ($catroots as $rootcat) {
                                // see if we're a child category of this rootcat (cfr. Celko model)
                                if ($info['left'] >= $rootcat['left_id'] && $info['left'] < $rootcat['right_id']) {
                                    // only needed when sorting by root category id
                                    $catinfo[$cid]['root'] = $rootcat['category_id'];
                                    // only needed when sorting by root category order
                                    $catinfo[$cid]['order'] = $rootidx;
                                    break;
                                }
                                $rootidx++;
                            }
                        }
                    }

                    // needed for sort function below
                    $GLOBALS['artsearchcatinfo'] = $catinfo;

                    $items = [];
                    foreach ($publications as $article) {
                        $count++;
                        $curptid = $article['pubtype_id'];
                        $link = $this->mod()->getURL(
                            'user',
                            'display',
                            ['ptid' => $article['pubtype_id'],
                                'itemid' => $article['id'], ]
                        );
                        // publication date of article (if needed)
                        if (!empty($pubtypes[$curptid]['config']['startdate']['label'])
                            && !empty($article['startdate'])) {
                            $date = $this->mls()->formatDate('%a, %d %B %Y %H:%M:%S %Z', $article['startdate']);
                            $startdate = $article['startdate'];
                        } else {
                            $date = '';
                            $startdate = 0;
                        }
                        if (empty($article['title'])) {
                            $article['title'] = $this->ml('(none)');
                        }

                        // categories this article belongs to
                        $categories = [];
                        if ($show_categories && !empty($article['cids'])
                            && is_array($article['cids']) && count($article['cids']) > 0) {
                            $cidlist = $article['cids'];
                            // order cids by root category order
                            usort($cidlist, [$this, 'sortbyorder']);
                            // order cids by root category id
                            //usort($cidlist, [$this, 'sortbyroot']);
                            // order cids by position in Celko tree
                            //usort($cidlist, [$this, 'sortbyleft']);

                            $join = '';
                            foreach ($cidlist as $cid) {
                                $item = [];
                                if (!isset($catinfo[$cid])) {
                                    // oops
                                    continue;
                                }
                                $categories[] = ['cname' => $catinfo[$cid]['name'],
                                    'clink' => $catinfo[$cid]['link'],
                                    'cjoin' => $join, ];
                                if (empty($join)) {
                                    $join = ' | ';
                                }
                            }
                        }

                        $items[] = ['title' => $this->prep()->html($article['title']),
                            'locale' => $article['locale'],

                            'link' => $link,
                            'date' => $date,
                            'startdate' => $startdate,
                            'relevance' => $article['relevance'] ?? null,
                            'categories' => $categories, ];
                    }
                    unset($publications);

                    // Pager
                    // TODO: make count depend on locale in the future
                    $pager = $this->tpl()->getPager(
                        $startnum,
                        $userapi->countitems(
                            ['cids' => $cids,
                                'andcids' => $andcids,
                                'ptid' => $curptid,
                                'owner' => $owner,
                                'state' => $state,
                                'startdate' => $startdate,
                                'enddate' => $enddate,
                                'searchfields' => $fieldlist,
                                'searchtype' => $searchtype,
                                'search' => $q, ]
                        ),

                        /* trick : use *this* publications search instead of global search for pager :-)
                                                                $this->ctl()->getModuleURL('search', 'user', 'main',
                        */
                        $this->mod()->getURL(
                            'user',
                            'search',
                            ['ptid' => $curptid,
                                'catid' => $catid,
                                'q' => $q ?? null,
                                'author' => $author ?? null,
                                'start' => $startdate,
                                'end' => ($enddate != $now) ? $enddate : null,
                                'state' => $stateline,
                                'sort' => $sort,
                                'fields' => $fields,
                                'searchtype' => !empty($searchtype) ? $searchtype : null,
                                'startnum' => '%%', ]
                        ),
                        $numitems
                    );

                    if (strlen($pager) > 5) {
                        if (!isset($sort) || $sort == 'date') {
                            $othersort = 'title';
                        } else {
                            $othersort = 'date';
                        }
                        $sortlink = $this->mod()->getURL(
                            'user',
                            'search',
                            ['ptid' => $curptid,
                                'catid' => $catid,
                                'q' => $q ?? null,
                                'author' => $author ?? null,
                                'start' => $startdate,
                                'end' => ($enddate != $now) ? $enddate : null,
                                'state' => $stateline,
                                'fields' => $fields,
                                'searchtype' => !empty($searchtype) ? $searchtype : null,
                                'sort' => $othersort, ]
                        );

                        $pager .= '&#160;&#160;<a href="' . $sortlink . '">'
                                  . $this->ml('sort by') . ' ' . $this->ml($othersort) . '</a>';
                    }

                    $data['results'][] = ['description' => $this->prep()->text($pubtypes[$curptid]['description']),
                        'items' => $items,
                        'pager' => $pager, ];
                }
            }
            unset($catinfo);
            unset($items);
            unset($GLOBALS['artsearchcatinfo']);

            if ($count > 0) {
                // bail out, we have what we needed
                return $data;
            }

            $data['state'] = $this->ml('No pages found matching this search');
        }

        return $data;
    }

    /**
     * sorting function for article categories
     */
    protected function sortbyroot($a, $b)
    {
        if ($GLOBALS['artsearchcatinfo'][$a]['root'] == $GLOBALS['artsearchcatinfo'][$b]['root']) {
            return $this->sortbyleft($a, $b);
        }
        return ($GLOBALS['artsearchcatinfo'][$a]['root'] > $GLOBALS['artsearchcatinfo'][$b]['root']) ? 1 : -1;
    }

    protected function sortbyleft($a, $b)
    {
        if ($GLOBALS['artsearchcatinfo'][$a]['left'] == $GLOBALS['artsearchcatinfo'][$b]['left']) {
            return 0;
        }
        return ($GLOBALS['artsearchcatinfo'][$a]['left'] > $GLOBALS['artsearchcatinfo'][$b]['left']) ? 1 : -1;
    }

    protected function sortbyorder($a, $b)
    {
        if ($GLOBALS['artsearchcatinfo'][$a]['order'] == $GLOBALS['artsearchcatinfo'][$b]['order']) {
            return $this->sortbyleft($a, $b);
        }
        return ($GLOBALS['artsearchcatinfo'][$a]['order'] > $GLOBALS['artsearchcatinfo'][$b]['order']) ? 1 : -1;
    }
}

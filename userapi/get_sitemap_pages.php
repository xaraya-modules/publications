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
use Query;

/**
 * publications userapi get_sitemap_pages function
 * @extends MethodClass<UserApi>
 */
class GetSitemapPagesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get pages relative to a given page
     * Filters:
     * Add an arg of the type $args['filter_foo'] = bar
     * will add a condition to the SELECT as
     * WHERE foo = bar
     * @see UserApi::getSitemapPages()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (empty($args['itemid'])) {
            $args['itemid'] = 0;
        }
        if (empty($args['scope'])) {
            $args['scope'] = 'descendants';
        }
        if (($args['itemid'] == 0) && ($args['scope'] == 'descendants')) {
            $args['scope'] = 'all';
        }
        if (empty($args['sort'])) {
            $args['sort'] = 0;
        }

        // Make sure we have the base translation id
        if (!empty($args['itemid'])) {
            $args['itemid'] = $userapi->gettranslationid(['id' => $args['itemid'], 'locale' => $this->mod()->getVar('defaultlanguage')]);
        }

        // Identify any filters
        $filters = [];
        foreach ($args as $k => $v) {
            if (strpos($k, 'filter_') === 0) {
                $argname = substr($k, 7);
                $filters[$argname] = $v;
            }
        }

        $xartable = & $this->db()->getTables();
        $q = new Query();
        $q->addtable($xartable['publications'], 'p');

        switch ($args['scope']) {
            case 'all':
                $q->gt('p.leftpage_id', 0);
                break;
            case 'descendants':
                $q->addtable($xartable['publications'], 'root');
                $q->eq('root.id', $args['itemid']);
                $q->le('root.leftpage_id', 'expr:p.leftpage_id');
                $q->ge('root.rightpage_id', 'expr:p.rightpage_id');
                break;
            case 'children':
                $q->eq('p.parentpage_id', $args['itemid']);
                break;
            case 'siblings':
                $q->addtable($xartable['publications'], 'p1');
                $q->join('p.parentpage_id', 'p1.parentpage_id');
                $q->eq('p1.id', $args['itemid']);
                break;
        }
        if (!empty($args['itemtype'])) {
            $q->eq('p.pubtype_id', $args['itemtype']);
        }
        $q->eq('p.sitemap_flag', 1);
        $q->gt('p.state', 2);
        $q->addfield('p.id AS id');
        $q->addfield('p.name AS name');
        $q->addfield('p.title AS title');
        $q->addfield('p.description AS description');
        $q->addfield('p.sitemap_source_flag AS sitemap_source_flag');
        $q->addfield('p.sitemap_alias AS sitemap_alias');
        $q->addfield('p.pubtype_id AS pubtype_id');
        $q->addfield('p.rightpage_id AS rightpage_id');
        $q->addfield('p.redirect_flag AS redirect_flag');
        $q->addfield('p.state AS state');

        // Add any fiters we found
        foreach ($filters as $k => $v) {
            $q->eq('p.' . $k, $v);
        }

        // We can force alpha sorting, or else sort according to tree position
        if ($args['sort']) {
            $q->setorder('p.title');
        } else {
            $q->setorder('p.leftpage_id');
        }
        //    $q->qecho();
        $q->run();
        $pages = $q->output();

        $depthstack = [];
        foreach ($pages as $key => $page) {
            // Calculate the relative nesting level.
            // 'depth' is 0-based. Top level (root node) is zero.
            if (!empty($depthstack)) {
                while (!empty($depthstack) && end($depthstack) < $page['rightpage_id']) {
                    array_pop($depthstack);
                }
            }
            $depthstack[$page['id']] = $page['rightpage_id'];
            $pages[$key]['depth'] = (empty($depthstack) ? 0 : count($depthstack) - 1);
            // This item is the path for each page, based on page IDs.
            // It is effectively a list of ancestor IDs for a page.
            // FIXME: some paths seem to get a '0' root ID. They should only have real page IDs.
            $pages[$key]['idpath'] = array_keys($depthstack);

            $pathstack[$key] = $page['name'];
            // This item is the path for each page, based on names.
            // Imploding it can give a directory-style path, which is handy
            // in admin pages and reports.
            $pages[$key]['namepath'] = $pathstack;
        }

        // If we are looking for translations rather than base documents, then find what translations are available and substitute them
        // CHECKME: is there a better way?
        // If there is no translation the base document remains. Is this desired outcome?

        if (!empty($pages) && $this->mod()->getVar('defaultlanguage') != $this->user()->getLocale()) {
            $indexedpages = [];
            foreach ($pages as $v) {
                $indexedpages[$v['id']] = $v;
            }
            $ids = array_keys($indexedpages);

            $q = new Query();
            $q->addtable($xartable['publications']);
            $q->addfield('id');
            $q->addfield('parent_id');
            $q->addfield('name');
            $q->addfield('title');
            $q->addfield('description');
            $q->addfield('sitemap_source_flag');
            $q->addfield('sitemap_alias');
            $q->addfield('pubtype_id');
            $q->in('parent_id', $ids);
            $q->eq('locale', $this->user()->getLocale());

            // Add any fiters we found
            foreach ($filters as $k => $v) {
                $q->eq($k, $v);
            }

            $q->run();
            foreach ($q->output() as $row) {
                // Copy the name and id paths so we don't have to recalculate them
                $row['depth'] = $indexedpages[$row['parent_id']]['depth'];
                $row['idpath'] = $indexedpages[$row['parent_id']]['idpath'];
                $row['namepath'] = $indexedpages[$row['parent_id']]['namepath'];
                // Add the entire row to the result pages
                $indexedpages[$row['parent_id']] = $row;
            }
            $pages = & $indexedpages;
        }
        return $pages;
    }
}

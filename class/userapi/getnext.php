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

use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarModVars;
use xarMod;
use xarDB;
use Query;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getnext function
 */
class GetnextMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get next publication
     * Note : the following parameters are all optional (except id and ptid)
     * @param mixed $args ['id'] the publications ID we want to have the next publication of
     * @param mixed $args ['ptid'] publication type ID (for news, sections, reviews, ...)
     * @param mixed $args ['sort'] sort order ('date','title','hits','rating',...)
     * @param mixed $args ['owner'] the ID of the author
     * @param mixed $args ['state'] array of requested status(es) for the publications
     * @param mixed $args ['enddate'] publications published before enddate
     * (unix timestamp format)
     * @return array of publications fields, or false on failure
     */
    public function __invoke(array $args = [])
    {
        // Security check
        if (!xarSecurity::check('ViewPublications')) {
            return;
        }

        // Get arguments from argument array
        extract($args);

        // Optional argument
        if (empty($ptid)) {
            $ptid = xarModVars::get('publications', 'defaultpubtype');
        }
        if (empty($sort)) {
            $sort = 'date';
        }
        if (!isset($state)) {
            // frontpage or approved or placeholder
            xarMod::load('publications');
            $state = [PUBLICATIONS_STATE_ACTIVE,PUBLICATIONS_STATE_FRONTPAGE,PUBLICATIONS_STATE_PLACEHOLDER];
        }

        // Default fields in publications (for now)
        $fields = ['id','name','title'];

        // Create the query
        sys::import('xaraya.structures.query');
        $tables = & xarDB::getTables();
        $q = new Query('SELECT', $tables['publications']);
        $q->addfield('id');
        $q->addfield('name');
        $q->addfield('title');
        $q->addfield('pubtype_id');
        $q->in('state', $state);

        // Get the current article
        $current = xarMod::apiFunc('publications', 'user', 'get', ['id' => $id]);

        // Add the ordering
        switch ($sort) {
            case 'tree':
                $q->gt('leftpage_id', (int) $current['rightpage_id']);
                $q->setorder('leftpage_id', 'ASC');
                break;
            case 'id':
                $q->eq('pubtype_id', $ptid);
                $q->gt('id', (int) $current['id']);
                $q->setorder('id', 'ASC');
                break;
            case 'name':
                $q->eq('pubtype_id', $ptid);
                $q->gt('name', $current['name']);
                $q->setorder('name', 'ASC');
                break;
            case 'title':
                $q->eq('pubtype_id', $ptid);
                $q->gt('title', $current['title']);
                $q->setorder('title', 'ASC');
                break;
            case 'date':
            default:
                $q->eq('pubtype_id', $ptid);
                $q->gt('start_date', (int) $current['start_date']);
                $q->setorder('start_date', 'ASC');
        }

        // We only want a single row
        $q->setrowstodo(1);

        // Run the query
        $q->run();
        return $q->row();
    }
}

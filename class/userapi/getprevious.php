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

use Xaraya\Modules\Publications\Defines;
use Xaraya\Modules\Publications\UserApi;
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
 * publications userapi getprevious function
 * @extends MethodClass<UserApi>
 */
class GetpreviousMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get previous publication
     * Note : the following parameters are all optional (except id and ptid)
     * @param array<mixed> $args
     * @var mixed $id the publication ID we want to have the previous publication of
     * @var mixed $ptid publication type ID (for news, sections, reviews, ...)
     * @var mixed $sort sort order ('date','title','hits','rating',...)
     * @var mixed $owner the ID of the author
     * @var mixed $state array of requested status(es) for the publications
     * @var mixed $enddate publications published before enddate
     * (unix timestamp format)
     * @return array|void of publication fields, or false on failure
     */
    public function __invoke(array $args = [])
    {
        // Security check
        if (!$this->checkAccess('ViewPublications')) {
            return;
        }

        // Get arguments from argument array
        extract($args);

        // Optional argument
        if (empty($ptid)) {
            $ptid = $this->getModVar('defaultpubtype');
        }
        if (empty($sort)) {
            $sort = 'date';
        }
        if (!isset($state)) {
            // frontpage or approved or placeholder
            xarMod::load('publications');
            $state = [Defines::STATE_ACTIVE,Defines::STATE_FRONTPAGE,Defines::STATE_PLACEHOLDER];
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
                $q->lt('rightpage_id', (int) $current['leftpage_id']);
                $q->setorder('rightpage_id', 'DESC');
                break;
            case 'id':
                $q->eq('pubtype_id', $ptid);
                $q->lt('id', (int) $current['id']);
                $q->setorder('id', 'DESC');
                break;
            case 'name':
                $q->eq('pubtype_id', $ptid);
                $q->lt('name', $current['name']);
                $q->setorder('name', 'DESC');
                break;
            case 'title':
                $q->eq('pubtype_id', $ptid);
                $q->lt('title', $current['title']);
                $q->setorder('title', 'DESC');
                break;
            case 'date':
            default:
                $q->eq('pubtype_id', $ptid);
                $q->lt('start_date', (int) $current['start_date']);
                $q->setorder('start_date', 'DESC');
        }

        // We only want a single row
        $q->setrowstodo(1);

        // Run the query
        $q->run();
        return $q->row();

        // TODO: grab categories & check against them too

        // check security - don't generate an exception here
        if (!xarSecurity::check('ViewPublications', 0, 'Publication', "$item[pubtype_id]:All:$item[owner]:$item[id]")) {
            return [];
        }

        return $item;
    }
}

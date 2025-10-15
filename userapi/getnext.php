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
use Query;
use sys;

sys::import('xaraya.modules.method');

/**
 * publications userapi getnext function
 * @extends MethodClass<UserApi>
 */
class GetnextMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get next publication
     * Note : the following parameters are all optional (except id and ptid)
     * @param array<mixed> $args
     * @var mixed $id the publications ID we want to have the next publication of
     * @var mixed $ptid publication type ID (for news, sections, reviews, ...)
     * @var mixed $sort sort order ('date','title','hits','rating',...)
     * @var mixed $owner the ID of the author
     * @var mixed $state array of requested status(es) for the publications
     * @var mixed $enddate publications published before enddate
     * (unix timestamp format)
     * @return array|void of publications fields, or false on failure
     * @see UserApi::getnext()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security check
        if (!$this->sec()->checkAccess('ViewPublications')) {
            return;
        }

        // Get arguments from argument array
        extract($args);

        // Optional argument
        if (empty($ptid)) {
            $ptid = $this->mod()->getVar('defaultpubtype');
        }
        if (empty($sort)) {
            $sort = 'date';
        }
        if (!isset($state)) {
            // frontpage or approved or placeholder
            $this->mod()->load('publications');
            $state = [Defines::STATE_ACTIVE,Defines::STATE_FRONTPAGE,Defines::STATE_PLACEHOLDER];
        }

        // Default fields in publications (for now)
        $fields = ['id','name','title'];

        // Create the query
        sys::import('xaraya.structures.query');
        $tables = & $this->db()->getTables();
        $q = new Query('SELECT', $tables['publications']);
        $q->addfield('id');
        $q->addfield('name');
        $q->addfield('title');
        $q->addfield('pubtype_id');
        $q->in('state', $state);

        // Get the current article
        $current = $userapi->get(['id' => $id]);

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

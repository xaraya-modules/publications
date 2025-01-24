<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\AdminApi;


use Xaraya\Modules\Publications\AdminApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use xarDB;
use DataObjectFactory;
use Query;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications adminapi promote_alias function
 * @extends MethodClass<AdminApi>
 */
class PromoteAliasMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Make a translation page the base page
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (!isset($itemid)) {
            $msg = $this->ml(
                'Invalid #(1) for #(2) function #(3)() in module #(4)',
                'publication ID',
                'admin',
                'promote_alias',
                'Publications'
            );
            throw new BadParameterException(null, $msg);
        }

        $base_id = xarMod::apiFunc('publications', 'user', 'gettranslationid', ['itemid' => $itemid]);

        // If the alias was already the base ID, then we're done
        if ($base_id == $itemid) {
            return true;
        }

        $publication = $this->data()->getObject(['name' => 'publications']);

        // Get the alias, set its parent ID to 0 and save
        $publication->getItem(['itemid' => $itemid]);
        $publication->properties['parent_id']->value = 0;
        $publication->updateItem();

        // Get the base, set its parent ID to the alias and save
        $publication->getItem(['itemid' => $base_id]);
        $publication->properties['parent_id']->value = $itemid;
        $publication->updateItem();

        // Switch the linkages to categories
        sys::import('xaraya.structures.query');
        $tables = & $this->db()->getTables();

        // Remove the old base publication into the tree
        $q = new Query('UPDATE', $tables['publications_publications']);
        $q->eq('rightpage_id', $itemid);
        $q->addfield('rightpage_id', 0);
        $q->run();
        $q = new Query('UPDATE', $tables['publications_publications']);
        $q->eq('leftpage_id', $itemid);
        $q->addfield('leftpage_id', 0);
        $q->run();

        // Put the new base publication into the tree
        $q = new Query('UPDATE', $tables['publications_publications']);
        $q->eq('rightpage_id', $base_id);
        $q->addfield('rightpage_id', $itemid);
        $q->run();
        $q = new Query('UPDATE', $tables['publications_publications']);
        $q->eq('leftpage_id', $base_id);
        $q->addfield('leftpage_id', $itemid);
        $q->run();

        // Set the parentpage ID of the new base publication
        $q = new Query('SELECT', $tables['publications_publications']);
        $q->eq('id', $base_id);
        $q->addfield('parentpage_id');
        $q->run();
        $row = $q->getrow();
        $q = new Query('UPDATE', $tables['publications_publications']);
        $q->eq('id', $itemid);
        $q->addfield('parentpage_id', $row['parentpage']);
        $q->run();

        // Set the parentpage ID of the olö base publication to 0
        $q = new Query('UPDATE', $tables['publications_publications']);
        $q->eq('id', $base_id);
        $q->addfield('parentpage_id', 0);
        $q->run();

        return true;
    }
}

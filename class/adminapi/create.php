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
use xarModVars;
use xarUser;
use xarMLS;
use xarDB;
use xarModHooks;
use DataPropertyMaster;
use sys;
use BadParameterException;
use ForbiddenOperationException;

sys::import('xaraya.modules.method');

/**
 * publications adminapi create function
 * @extends MethodClass<AdminApi>
 */
class CreateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Create a new article
     * Usage : $id = xarMod::apiFunc('publications', 'admin', 'create', $article);
     * @param array<mixed> $args
     * @var string $title name of the item (this is the only mandatory argument)
     * @var string $summary summary for this item
     * @var string $body body text for this item
     * @var string $notes notes for the item
     * @var int $state state of the item
     * @var int $ptid publication type ID for the item
     * @var int $pubdate publication date in unix time format (or default now)
     * @var int $owner ID of the author (default is current user)
     * @var string $locale language of the item
     * @var array $cids category IDs this item belongs to
     * @return int|void publications item ID on success, false on failure
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array
        extract($args);

        // Argument check (all the rest is optional, and set to defaults below)
        if (empty($title)) {
            $msg = $this->ml(
                'Invalid #(1) for #(2) function #(3)() in module #(4)',
                'title',
                'admin',
                'create',
                'Publications'
            );
            throw new BadParameterException(null, $msg);
        }

        // Note : we use empty() here because we don't care whether it's set to ''
        //        or if it's not set at all - defaults will apply in either case !

        // Default publication type is defined in the admin interface
        if (empty($ptid) || !is_numeric($ptid)) {
            $ptid = $this->mod()->getVar('defaultpubtype');
            if (empty($ptid)) {
                $msg = $this->ml(
                    'Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'ptid',
                    'admin',
                    'create',
                    'Publications'
                );
                throw new BadParameterException(null, $msg);
            }
            // for security check below
            $args['ptid'] = $ptid;
        }

        // Default author ID is the current user, or Anonymous (1) otherwise
        if (empty($owner) || !is_numeric($owner)) {
            $owner = xarUser::getVar('id');
            if (empty($owner)) {
                $owner = _XAR_ID_UNREGISTERED;
            }
            // for security check below
            $args['owner'] = $owner;
        }

        // Default categories is none
        if (empty($cids) || !is_array($cids) ||
            // catch common mistake of using array('') instead of array()
            (count($cids) > 0 && empty($cids[0]))) {
            $cids = [];
            // for security check below
            $args['cids'] = $cids;
        }

        // Security check
        if (!xarMod::apiLoad('publications', 'user')) {
            return;
        }

        $args['mask'] = 'SubmitPublications';
        if (!xarMod::apiFunc('publications', 'user', 'checksecurity', $args)) {
            $msg = $this->ml(
                'Not authorized to add #(1) items',
                'Publication'
            );
            throw new ForbiddenOperationException(null, $msg);
        }

        // Default publication date is now
        if (empty($pubdate) || !is_numeric($pubdate)) {
            $pubdate = time();
        }

        // Default state is Submitted (0)
        if (empty($state) || !is_numeric($state)) {
            $state = 0;
        }

        // Default locale is current locale
        if (empty($locale)) {
            $locale = xarMLS::getCurrentLocale();
        }

        // Default summary is empty
        if (empty($summary)) {
            $summary = '';
        }

        // Default notes is empty
        if (empty($notes)) {
            $notes = '';
        }

        // Default body text is empty
        if (empty($body) || !is_string($body)) {
            $body = '';
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = & $this->db()->getTables();
        $publicationstable = $xartable['publications'];

        // Get next ID in table
        if (empty($id) || !is_numeric($id) || $id == 0) {
            $result = $dbconn->Execute("SELECT MAX(id) FROM $publicationstable");
            [$id] = $result->fields;
            $id++;
        }

        // Add item
        $query = "INSERT INTO $publicationstable (
                  id,
                  title,
                  summary,
                  body,
                  owner,
                  pubdate,
                  pubtype_id,
                  notes,
                  state,
                  locale)
                  VALUES (?,?,?,?,?,?,?,?,?,?)";
        $bindvars = [$id,
            (string) $title,
            (string) $summary,
            (string) $body,
            (int) $owner,
            (int) $pubdate,
            (int) $ptid,
            (string) $notes,
            (int) $state,
            (string) $locale, ];
        $result = $dbconn->Execute($query, $bindvars);
        if (!$result) {
            return;
        }

        // Get id to return
        if (empty($id) || !is_numeric($id) || $id == 0) {
            $id = $dbconn->PO_Insert_ID($publicationstable, 'id');
        }

        if (empty($cids)) {
            $cids = [];
        }

        /* ---------------------------- TODO: Remove once publications uses dd objects */
        sys::import('modules.dynamicdata.class.properties.master');
        $categories = DataPropertyMaster::getProperty(['name' => 'categories']);
        $categories->checkInput('categories', $id);
        $categories->createValue($id);
        /*------------------------------- */

        // Call create hooks for categories, hitcount etc.
        $args['id'] = $id;
        // Specify the module, itemtype and itemid so that the right hooks are called
        $args['module'] = 'publications';
        $args['itemtype'] = $ptid;
        $args['itemid'] = $id;
        // TODO: get rid of this
        $args['cids'] = $cids;
        xarModHooks::call('item', 'create', $id, $args);

        return $id;
    }
}

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
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarMod;
use xarDB;
use xarModHooks;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications adminapi deletepubtype function
 * @extends MethodClass<AdminApi>
 */
class DeletepubtypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Delete a publication type
     * @param array<mixed> $args
     * @var mixed $ptid ID of the publication type
     * @return bool|void true on success, false on failure
     * @see AdminApi::deletepubtype()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get arguments from argument array
        extract($args);

        // Argument check - make sure that all required arguments are present
        // and in the right format, if not then set an appropriate error
        // message and return
        if (!isset($ptid) || !is_numeric($ptid) || $ptid < 1) {
            $msg = $this->ml(
                'Invalid #(1) for #(2) function #(3)() in module #(4)',
                'publication type ID',
                'admin',
                'deletepubtype',
                'Publications'
            );
            throw new BadParameterException(null, $msg);
        }

        // Security check - we require ADMIN rights here
        if (!$this->sec()->check('AdminPublications', 1, 'Publication', "$ptid:All:All:All")) {
            return;
        }

        // Load user API to obtain item information function
        if (!$this->mod()->apiLoad('publications', 'user')) {
            return;
        }

        // Get current publication types
        $pubtypes = $userapi->get_pubtypes();
        if (!isset($pubtypes[$ptid])) {
            $msg = $this->ml(
                'Invalid #(1) for #(2) function #(3)() in module #(4)',
                'publication type ID',
                'admin',
                'deletepubtype',
                'Publications'
            );
            throw new BadParameterException(null, $msg);
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = & $this->db()->getTables();
        $pubtypestable = $xartable['publication_types'];

        // Delete the publication type
        $query = "DELETE FROM $pubtypestable
                WHERE pubtype_id = ?";
        $result = $dbconn->Execute($query, [$ptid]);
        if (!$result) {
            return;
        }

        $publicationstable = $xartable['publications'];

        // Delete all publications for this publication type
        $query = "DELETE FROM $publicationstable
                WHERE pubtype_id = ?";
        $result = $dbconn->Execute($query, [$ptid]);
        if (!$result) {
            return;
        }

        // TODO: call some kind of itemtype delete hooks here, once we have those
        //xarModHooks::call('itemtype', 'delete', $ptid,
        //                array('module' => 'publications',
        //                      'itemtype' =>'ptid'));

        return true;
    }
}

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
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications adminapi updatepubtype function
 * @extends MethodClass<AdminApi>
 */
class UpdatepubtypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update a publication type
     * @param array<mixed> $args
     * @var int $args['ptid'] ID of the publication type
     * @var string $args['name'] name of the publication type (not allowed here)
     * @var string $args['description'] description of the publication type
     * @var array $args['config'] configuration of the publication type
     * @return bool|void true on success, false on failure
     * @see AdminApi::updatepubtype()
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
        // Note : since we have several arguments we want to check here, we'll
        // report all those that are invalid at the same time...
        $invalid = [];
        if (!isset($ptid) || !is_numeric($ptid) || $ptid < 1) {
            $invalid[] = 'publication type ID';
        }
        /*
            if (!isset($name) || !is_string($name) || empty($name)) {
                $invalid[] = 'name';
            }
        */
        if (!isset($descr) || !is_string($descr) || empty($descr)) {
            $invalid[] = 'description';
        }
        if (!isset($config) || !is_array($config) || count($config) == 0) {
            $invalid[] = 'configuration';
        }
        if (count($invalid) > 0) {
            $msg = $this->ml(
                'Invalid #(1) for #(2) function #(3)() in module #(4)',
                join(', ', $invalid),
                'admin',
                'updatepubtype',
                'Publications'
            );
            throw new BadParameterException(null, $msg);
        }

        // Security check - we require ADMIN rights here
        if (!xarSecurity::check('AdminPublications', 1, 'Publication', "$ptid:All:All:All")) {
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
                'updatepubtype',
                'Publications'
            );
            throw new BadParameterException(null, $msg);
        }

        // Make sure we have all the configuration fields we need
        $pubfields = $userapi->getpubfields();
        foreach ($pubfields as $field => $value) {
            if (!isset($config[$field])) {
                $config[$field] = '';
            }
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = & $this->db()->getTables();
        $pubtypestable = $xartable['publication_types'];

        // Update the publication type (don't allow updates on name)
        $query = "UPDATE $pubtypestable
                SET pubtypedescr = ?,
                    pubtypeconfig = ?
                WHERE pubtype_id = ?";
        $bindvars = [$descr, serialize($config), $ptid];
        $result = $dbconn->Execute($query, $bindvars);
        if (!$result) {
            return;
        }

        return true;
    }
}

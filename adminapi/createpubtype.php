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
 * publications adminapi createpubtype function
 * @extends MethodClass<AdminApi>
 */
class CreatepubtypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Create a new publication type
     * @param array<mixed> $args
     * @var mixed $name name of the publication type
     * @var mixed $descr description of the publication type
     * @var mixed $config configuration of the publication type
     * @return int|void publication type ID on success, false on failure
     * @see AdminApi::createpubtype()
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
        if (!isset($name) || !is_string($name) || empty($name)) {
            $invalid[] = 'name';
        }
        if (!isset($config) || !is_array($config) || count($config) == 0) {
            $invalid[] = 'configuration';
        }
        if (count($invalid) > 0) {
            $msg = $this->ml(
                'Invalid #(1) for #(2) function #(3)() in module #(4)',
                join(', ', $invalid),
                'admin',
                'createpubtype',
                'Publications'
            );
            throw new BadParameterException(null, $msg);
        }

        if (empty($descr)) {
            $descr = $name;
        }

        // Publication type names *must* be lower-case for now
        $name = strtolower($name);

        // Security check - we require ADMIN rights here
        if (!$this->sec()->checkAccess('AdminPublications')) {
            return;
        }

        if (!$this->mod()->apiLoad('publications', 'user')) {
            return;
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

        // Get next ID in table
        $nextId = $dbconn->GenId($pubtypestable);

        // Insert the publication type
        $query = "INSERT INTO $pubtypestable (pubtype_id, pubtypename,
                pubtypedescr, pubtypeconfig)
                VALUES (?,?,?,?)";
        $bindvars = [$nextId, $name, $descr, serialize($config)];
        $result = $dbconn->Execute($query, $bindvars);
        if (!$result) {
            return;
        }

        // Get ptid to return
        $ptid = $dbconn->PO_Insert_ID($pubtypestable, 'pubtype_id');

        // Don't call creation hooks here...
        //xarModHooks::call('item', 'create', $ptid, 'ptid');

        return $ptid;
    }
}

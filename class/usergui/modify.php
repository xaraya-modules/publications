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

use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarController;
use xarMod;
use xarModVars;
use xarRoles;
use xarUser;
use xarResponse;
use xarTpl;
use xarCoreCache;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications user modify function
 */
class ModifyMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        // Xaraya security
        if (!xarSecurity::check('ModeratePublications')) {
            return;
        }

        extract($args);

        // Get parameters
        if (!xarVar::fetch('itemid', 'id', $data['itemid'], null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('id', 'id', $data['id'], null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('ptid', 'isset', $ptid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('returnurl', 'str:1', $data['returnurl'], 'view', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('name', 'str:1', $name, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('tab', 'str:1', $data['tab'], '', xarVar::NOT_REQUIRED)) {
            return;
        }

        if (empty($data['itemid']) && empty($data['id'])) {
            return xarController::notFound(null, $this->getContext());
        }
        // The itemid var takes precedence if it exiats
        if (!isset($data['itemid'])) {
            $data['itemid'] = $data['id'];
        }

        if (empty($name) && empty($ptid)) {
            $item = xarMod::apiFunc('publications', 'user', 'get', ['id' => $data['itemid']]);
            $ptid = $item['pubtype_id'];
        }

        if (!empty($ptid)) {
            $publication_type = DataObjectFactory::getObjectList(['name' => 'publications_types']);
            $where = 'id = ' . $ptid;
            $items = $publication_type->getItems(['where' => $where]);
            $item = current($items);
            $name = $item['name'];
        }
        if (empty($name)) {
            return xarController::notFound(null, $this->getContext());
        }

        // Get our object
        $data['object'] = DataObjectFactory::getObject(['name' => $name]);
        $data['object']->getItem(['itemid' => $data['itemid']]);

        # --------------------------------------------------------
        #
        # Are we allowed to modify this page?
        #
        $accessconstraints = xarMod::apiFunc('publications', 'admin', 'getpageaccessconstraints', ['property' => $data['object']->properties['access']]);
        $access = DataPropertyMaster::getProperty(['name' => 'access']);
        $allow = $access->check($accessconstraints['modify']);

        // If not allowed, check if admins or the designated site admin can modify even if not the owner
        if (!$allow) {
            $admin_override = xarModVars::get('publications', 'admin_override');
            switch ($admin_override) {
                case 0:
                    break;
                case 1:
                    $allow = xarRoles::isParent('Administrators', xarUser::getVar('uname'));
                    break;
                case 1:
                    $allow = xarModVars::get('roles', 'admin') == xarUser::getVar('id');
                    break;
            }
        }

        // If no access, then bail showing a forbidden or the "no permission" page or an empty page
        $nopermissionpage_id = xarModVars::get('publications', 'noprivspage');
        if (!$allow) {
            if ($accessconstraints['modify']['failure']) {
                return xarResponse::Forbidden();
            } elseif ($nopermissionpage_id) {
                xarController::redirect(xarController::URL(
                    'publications',
                    'user',
                    'display',
                    ['itemid' => $nopermissionpage_id]
                ), null, $this->getContext());
            } else {
                $data = ['context' => $this->getContext()];
                return xarTpl::module('publications', 'user', 'empty', $data);
            }
        }

        # --------------------------------------------------------
        #
        # Good to go. Continue
        #
        $data['ptid'] = $data['object']->properties['itemtype']->value;

        // Send the publication type and the object properties to the template
        $data['properties'] = $data['object']->getProperties();

        // Get the settings of the publication type we are using
        $data['settings'] = xarMod::apiFunc('publications', 'user', 'getsettings', ['ptid' => $data['ptid']]);

        // If creating a new translation get an empty copy
        if ($data['tab'] == 'newtranslation') {
            $data['object']->properties['id']->setValue(0);
            $data['object']->properties['parent']->setValue($data['itemid']);
            $data['items'][0] = $data['object']->getFieldValues([], 1);
            $data['tab'] = '';
        } else {
            $data['items'] = [];
        }

        // Get the base document. If this itemid is not the base doc,
        // then first find the correct itemid
        $data['object']->getItem(['itemid' => $data['itemid']]);
        $fieldvalues = $data['object']->getFieldValues([], 1);
        if (!empty($fieldvalues['parent'])) {
            $data['itemid'] = $fieldvalues['parent'];
            $data['object']->getItem(['itemid' => $data['itemid']]);
            $fieldvalues = $data['object']->getFieldValues([], 1);
        }
        $data['items'][$data['itemid']] = $fieldvalues;

        // Get any translations of the base document
        $data['objectlist'] = DataObjectFactory::getObjectList(['name' => $name]);
        $where = "parent = " . $data['itemid'];
        $items = $data['objectlist']->getItems(['where' => $where]);
        foreach ($items as $key => $value) {
            // Clear the previous values before starting the next round
            $data['object']->clearFieldValues();
            $data['object']->getItem(['itemid' => $key]);
            $data['items'][$key] = $data['object']->getFieldValues([], 1);
        }

        # --------------------------------------------------------
        #
        # Cache data
        #
        // Now we can cache all data away for blocks, subitems etc.
        xarCoreCache::setCached('Publications', 'itemid', $data['itemid']);

        return $data;
    }
}

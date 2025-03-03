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


use Xaraya\Modules\Publications\UserGui;
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\Publications\AdminApi;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarController;
use xarMod;
use xarModVars;
use xarRoles;
use xarUser;
use xarTpl;
use xarCoreCache;
use DataObjectFactory;
use DataPropertyMaster;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications user modify function
 * @extends MethodClass<UserGui>
 */
class ModifyMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see UserGui::modify()
     */

    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Xaraya security
        if (!$this->sec()->checkAccess('ModeratePublications')) {
            return;
        }

        extract($args);

        // Get parameters
        $this->var()->find('itemid', $data['itemid'], 'id');
        $this->var()->find('id', $data['id'], 'id');
        $this->var()->check('ptid', $ptid);
        $this->var()->find('returnurl', $data['returnurl'], 'str:1', 'view');
        $this->var()->find('name', $name, 'str:1', '');
        $this->var()->find('tab', $data['tab'], 'str:1', '');

        if (empty($data['itemid']) && empty($data['id'])) {
            return $this->ctl()->notFound();
        }
        // The itemid var takes precedence if it exiats
        if (!isset($data['itemid'])) {
            $data['itemid'] = $data['id'];
        }

        if (empty($name) && empty($ptid)) {
            $item = $userapi->get(['id' => $data['itemid']]);
            $ptid = $item['pubtype_id'];
        }

        if (!empty($ptid)) {
            $publication_type = $this->data()->getObjectList(['name' => 'publications_types']);
            $where = 'id = ' . $ptid;
            $items = $publication_type->getItems(['where' => $where]);
            $item = current($items);
            $name = $item['name'];
        }
        if (empty($name)) {
            return $this->ctl()->notFound();
        }

        // Get our object
        $data['object'] = $this->data()->getObject(['name' => $name]);
        $data['object']->getItem(['itemid' => $data['itemid']]);

        # --------------------------------------------------------
        #
        # Are we allowed to modify this page?
        #
        $accessconstraints = $adminapi->getpageaccessconstraints(['property' => $data['object']->properties['access']]);
        /** @var \AccessProperty $access */
        $access = $this->prop()->getProperty(['name' => 'access']);
        $allow = $access->check($accessconstraints['modify']);

        // If not allowed, check if admins or the designated site admin can modify even if not the owner
        if (!$allow) {
            $admin_override = $this->mod()->getVar('admin_override');
            switch ($admin_override) {
                case 0:
                    break;
                case 1:
                    $allow = xarRoles::isParent('Administrators', $this->user()->getUser());
                    break;
                case 2:
                    $allow = $this->user()->isSiteAdmin();
                    break;
            }
        }

        // If no access, then bail showing a forbidden or the "no permission" page or an empty page
        $nopermissionpage_id = $this->mod()->getVar('noprivspage');
        if (!$allow) {
            if ($accessconstraints['modify']['failure']) {
                return $this->ctl()->forbidden();
            } elseif ($nopermissionpage_id) {
                $this->ctl()->redirect($this->mod()->getURL(
                    'user',
                    'display',
                    ['itemid' => $nopermissionpage_id]
                ));
                return true;
            } else {
                $data = ['context' => $this->getContext()];
                return $this->mod()->template('empty', $data);
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
        $data['settings'] = $userapi->getsettings(['ptid' => $data['ptid']]);

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
        $data['objectlist'] = $this->data()->getObjectList(['name' => $name]);
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
        $this->var()->setCached('Publications', 'itemid', $data['itemid']);

        return $data;
    }
}

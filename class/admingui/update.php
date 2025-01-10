<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\AdminGui;

use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarSec;
use xarModVars;
use xarUser;
use xarConfigVars;
use xarMod;
use xarTpl;
use xarModHooks;
use xarModAlias;
use xarSession;
use xarHooks;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin update function
 */
class UpdateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('EditPublications')) {
            return;
        }

        // Get parameters
        if (!xarVar::fetch('itemid', 'isset', $data['itemid'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('items', 'str', $items, '', xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('ptid', 'isset', $data['ptid'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('modify_cids', 'isset', $cids, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('preview', 'isset', $data['preview'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('quit', 'isset', $data['quit'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('front', 'isset', $data['front'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('tab', 'str:1', $data['tab'], '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('returnurl', 'str:1', $data['returnurl'], 'view', xarVar::NOT_REQUIRED)) {
            return;
        }

        // Confirm authorisation code
        // This has been disabled for now
        //    if (!xarSec::confirmAuthKey()) return;

        $items = explode(',', $items);
        $pubtypeobject = DataObjectFactory::getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $data['ptid']]);
        $data['object'] = DataObjectFactory::getObject(['name' => $pubtypeobject->properties['name']->value]);

        // First we need to check all the data on the template
        // If checkInput fails, don't bail
        $itemsdata = [];
        $isvalid = true;
        foreach ($items as $prefix) {
            $data['object']->setFieldPrefix($prefix);

            // Disable the celkoposition property according if this is not the base document
            $fieldname = $prefix . '_dd_' . $data['object']->properties['parent']->id;
            $data['object']->properties['parent']->checkInput($fieldname);
            if (empty($data['object']->properties['parent']->value)) {
                $data['object']->properties['position']->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY);
            } else {
                $data['object']->properties['position']->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_DISABLED);
            }

            // Now get the input from the form
            $thisvalid = $data['object']->checkInput();
            $isvalid = $isvalid && $thisvalid;
            // Store each item for later processing
            $itemsdata[$prefix] = $data['object']->getFieldValues([], 1);
        }

        if ($data['preview'] || !$isvalid) {
            // Show debug info if called for
            if (!$isvalid &&
                xarModVars::get('publications', 'debugmode') &&
                in_array(xarUser::getVar('id'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                var_dump($data['object']->getInvalids());
            }
            // Preview or bad data: redisplay the form
            $data['properties'] = $data['object']->getProperties();
            if ($data['preview']) {
                $data['tab'] = 'preview';
            }
            $data['items'] = $itemsdata;
            // Get the settings of the publication type we are using
            $data['settings'] = xarMod::apiFunc('publications', 'user', 'getsettings', ['ptid' => $data['ptid']]);

            $data['context'] ??= $this->getContext();
            return xarTpl::module('publications', 'admin', 'modify', $data);
        }

        // call transform input hooks
        $article['transform'] = ['summary','body','notes'];
        //    $article = xarModHooks::call('item', 'transform-input', $data['itemid'], $article,
        //                               'publications', $data['ptid']);

        // Now talk to the database. Loop through all the translation pages
        foreach ($itemsdata as $id => $itemdata) {
            // Get the data for this item
            $data['object']->setFieldValues($itemdata, 1);

            // Save or create the item (depends whether this translation is new)
            if (empty($id)) {
                $item = $data['object']->createItem();
            } else {
                $item = $data['object']->updateItem();
            }

            // Check if we have an alias and set it as an alias of the publications module
            $alias_flag = $data['object']->properties['alias_flag']->value;
            if ($alias_flag == 1) {
                $alias = $data['object']->properties['alias']->value;
                if (!empty($alias)) {
                    xarModAlias::set($alias, 'publications');
                }
            } elseif ($alias_flag == 2) {
                $alias = $data['object']->properties['name']->value;
                if (!empty($alias)) {
                    xarModAlias::set($alias, 'publications');
                }
            }

            // Clear the itemid property in preparation for the next round
            unset($data['object']->itemid);
        }

        // Success
        xarSession::setVar('statusmsg', xarML('Publication Updated'));

        // Inform the world via hooks
        $item = ['module' => 'publications', 'itemid' => $data['itemid'], 'itemtype' => $data['object']->properties['itemtype']->value];
        xarHooks::notify('ItemUpdate', $item);

        // If quitting, go to admin view; otherwise redisplay the page
        if ($data['quit']) {
            // Redirect if we came from somewhere else
            $current_listview = xarSession::getVar('publications_current_listview');
            if (!empty($current_listview)) {
                xarController::redirect($current_listview, null, $this->getContext());
            }

            xarController::redirect(xarController::URL(
                'publications',
                'admin',
                'view',
                ['ptid' => $data['ptid']]
            ), null, $this->getContext());
        } elseif ($data['front']) {
            xarController::redirect(xarController::URL(
                'publications',
                'user',
                'display',
                ['name' => $pubtypeobject->properties['name']->value, 'itemid' => $data['itemid']]
            ), null, $this->getContext());
        } else {
            xarController::redirect(xarController::URL(
                'publications',
                'admin',
                'modify',
                ['name' => $pubtypeobject->properties['name']->value, 'itemid' => $data['itemid']]
            ), null, $this->getContext());
        }
        return true;
    }
}

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
use xarController;
use xarMod;
use xarSec;
use xarTpl;
use DataObjectFactory;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin modify_pubtype function
 */
class ModifyPubtypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('AdminPublications')) {
            return;
        }

        extract($args);

        // Get parameters
        if (!xarVar::fetch('itemid', 'isset', $data['itemid'], null, xarVar::DONT_SET)) {
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
        if (!xarVar::fetch('confirm', 'bool', $data['confirm'], false, xarVar::NOT_REQUIRED)) {
            return;
        }

        if (empty($name) && empty($itemid)) {
            return xarController::notFound(null, $this->getContext());
        }

        // Get our object
        $data['object'] = DataObjectFactory::getObject(['name' => 'publications_types']);
        if (!empty($data['itemid'])) {
            $data['object']->getItem(['itemid' => $data['itemid']]);
        } else {
            $type_list = DataObjectFactory::getObjectList(['name' => 'publications_types']);
            $where = 'name = ' . $name;
            $items = $type_list->getItems(['where' => $where]);
            $item = current($items);
            $data['object']->getItem(['itemid' => $item['id']]);
        }

        // Unpack the access data
        $data['access'] = unserialize($data['object']->properties['access']->value);
        if (empty($data['access'])) {
            $data['access'] = [
                'add' => [],
                'display' => [],
                'modify' => [],
                'delete' => [],
            ];
            $data['object']->properties['access']->value = serialize($data['access']);
        }
        // Get the settings of the publication type we are using
        $data['settings'] = xarMod::apiFunc('publications', 'user', 'getsettings', ['ptid' => $data['itemid']]);

        // Send the publication type and the object properties to the template
        $data['properties'] = $data['object']->getProperties();

        if ($data['confirm']) {
            // Check for a valid confirmation key
            if (!xarSec::confirmAuthKey()) {
                return;
            }

            // Get the data from the form
            $isvalid = $data['object']->checkInput();

            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                $data['context'] ??= $this->getContext();
                return xarTpl::module('publications', 'admin', 'modify_pubtype', $data);
            } else {
                // Good data: create the item
                $itemid = $data['object']->updateItem(['itemid' => $data['itemid']]);

                // Jump to the next page
                xarController::redirect(xarController::URL('publications', 'admin', 'view_pubtypes'), null, $this->getContext());
                return true;
            }
        }

        return $data;
    }
}

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
use xarSec;
use xarTpl;
use xarDB;
use xarSession;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications user clone function
 */
class CloneMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Publications Module
     * @package modules
     * @subpackage publications module
     * @category Third Party Xaraya Module
     * @version 2.0.0
     * @copyright (C) 2012 Netspan AG
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @author Marc Lutolf <mfl@netspan.ch>
     */
    public function __invoke(array $args = [])
    {
        // Xaraya security
        if (!xarSecurity::check('ModeratePublications')) {
            return;
        }

        if (!xarVar::fetch('name', 'isset', $objectname, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('ptid', 'isset', $ptid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('itemid', 'isset', $data['itemid'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('confirm', 'int', $confirm, 0, xarVar::DONT_SET)) {
            return;
        }

        if (empty($data['itemid'])) {
            return xarController::notFound(null, $this->getContext());
        }

        // If a pubtype ID was passed, get the name of the pub object
        if (isset($ptid)) {
            $pubtypeobject = DataObjectFactory::getObject(['name' => 'publications_types']);
            $pubtypeobject->getItem(['itemid' => $ptid]);
            $objectname = $pubtypeobject->properties['name']->value;
        }
        if (empty($objectname)) {
            return xarController::notFound(null, $this->getContext());
        }

        sys::import('modules.dynamicdata.class.objects.factory');
        $data['object'] = DataObjectFactory::getObject(['name' => $objectname]);
        if (empty($data['object'])) {
            return xarController::notFound(null, $this->getContext());
        }

        $data['object']->getItem(['itemid' => $data['itemid']]);

        $data['authid'] = xarSec::genAuthKey();
        $data['name'] = $data['object']->properties['name']->value;
        $data['label'] = $data['object']->label;
        xarTpl::setPageTitle(xarML('Clone Publication #(1) in #(2)', $data['itemid'], $data['label']));

        if ($confirm) {
            if (!xarSec::confirmAuthKey()) {
                return;
            }

            // Get the name for the clone
            if (!xarVar::fetch('newname', 'str', $newname, "", xarVar::NOT_REQUIRED)) {
                return;
            }
            if (empty($newname)) {
                $newname = $data['name'] . "_copy";
            }
            if ($newname == $data['name']) {
                $newname = $data['name'] . "_copy";
            }
            $newname = strtolower(str_ireplace(" ", "_", $newname));

            // Create the clone
            $data['object']->properties['name']->setValue($newname);
            $data['object']->properties['id']->setValue(0);
            $cloneid = $data['object']->createItem(['itemid' => 0]);

            // Create the clone's translations
            if (!xarVar::fetch('clone_translations', 'int', $clone_translations, 0, xarVar::NOT_REQUIRED)) {
                return;
            }
            if ($clone_translations) {
                // Get the info on all the objects to be cloned
                sys::import('xaraya.structures.query');
                $tables = & xarDB::getTables();
                $q = new Query();
                $q->addtable($tables['publications'], 'p');
                $q->addtable($tables['publications_types'], 'pt');
                $q->join('p.pubtype_id', 'pt.id');
                $q->eq('parent_id', $data['itemid']);
                $q->addfield('p.id AS id');
                $q->addfield('pt.name AS name');
                $q->run();

                // Clone each one
                foreach ($q->output() as $item) {
                    $object = DataObjectFactory::getObject(['name' => $item['name']]);
                    $object->getItem(['itemid' => $item['id']]);
                    $object->properties['parent']->value = $cloneid;
                    $object->properties['id']->value = 0;
                    $object->createItem(['itemid' => 0]);
                }
            }

            // Redirect if we came from somewhere else
            //$current_listview = xarSession::getVar('publications_current_listview');
            if (!empty($return_url)) {
                xarController::redirect($return_url, null, $this->getContext());
            } elseif (!empty($current_listview)) {
                xarController::redirect($current_listview, null, $this->getContext());
            } else {
                xarController::redirect(xarController::URL(
                    'publications',
                    'user',
                    'modify',
                    ['itemid' => $cloneid, 'name' => $objectname]
                ), null, $this->getContext());
            }
            return true;
        }
        return $data;
    }
}

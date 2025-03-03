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


use Xaraya\Modules\Publications\AdminGui;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarController;
use xarModVars;
use xarVar;
use xarSec;
use xarTpl;
use xarHooks;
use DataObjectFactory;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin delete function
 * @extends MethodClass<AdminGui>
 */
class DeleteMethod extends MethodClass
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
     * @see AdminGui::delete()
     */
    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('ManagePublications')) {
            return;
        }

        //$return = $this->mod()->getURL( 'admin','view',array('ptid' => $this->mod()->getVar('defaultpubtype')));
        $this->var()->find('confirmed', $confirmed, 'int');
        $this->var()->check('itemid', $itemid, 'int');
        $this->var()->find('idlist', $idlist, 'str');
        $this->var()->check('returnurl', $returnurl, 'str');

        if (!empty($itemid)) {
            $idlist = $itemid;
        }
        $ids = explode(',', trim($idlist, ','));

        if (empty($idlist)) {
            if (isset($returnurl)) {
                $this->ctl()->redirect($returnurl);
            } else {
                $this->ctl()->redirect($this->mod()->getURL('admin', 'view'));
            }
            return true;
        }

        $data['message'] = '';
        $data['itemid']  = $itemid;

        /*------------- Ask for Confirmation.  If yes, action ----------------------------*/

        sys::import('modules.dynamicdata.class.objects.factory');
        $publication = $this->data()->getObject(['name' => 'publications_publications']);
        if (!isset($confirmed)) {
            $data['idlist'] = $idlist;
            if (count($ids) > 1) {
                $data['title'] = $this->ml("Delete Publications");
            } else {
                $data['title'] = $this->ml("Delete Publication");
            }
            $data['authid'] = $this->sec()->genAuthKey();
            $items = [];
            foreach ($ids as $i) {
                $publication->getItem(['itemid' => $i]);
                $item = $publication->getFieldValues();
                $items[] = $item;
            }
            $data['items'] = $items;
            $data['yes_action'] = $this->mod()->getURL( 'admin', 'delete', ['idlist' => $idlist]);
            return $this->mod()->template('delete', $data);
        } else {
            if (!$this->sec()->confirmAuthKey()) {
                return;
            }
            foreach ($ids as $id) {
                $itemid = $publication->deleteItem(['itemid' => $id]);
                $data['message'] = "Publication deleted [ID $id]";

                // Inform the world via hooks
                $item = ['module' => 'publications', 'itemid' => $itemid, 'itemtype' => $publication->properties['itemtype']->value];
                xarHooks::notify('ItemDelete', $item);
            }
            if (isset($returnurl)) {
                $this->ctl()->redirect($returnurl);
            } else {
                $this->ctl()->redirect($this->mod()->getURL( 'admin', 'view', $data));
            }
            return true;
        }
    }
}

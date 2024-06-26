<?php
/**
 * Publications Module
 *
 * @package modules
 * @subpackage publications module
 * @category Third Party Xaraya Module
 * @version 2.0.0
 * @copyright (C) 2012 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */

function publications_admin_delete(array $args = [], $context = null)
{
    if (!xarSecurity::check('ManagePublications')) {
        return;
    }

    //$return = xarController::URL('publications', 'admin','view',array('ptid' => xarModVars::get('publications', 'defaultpubtype')));
    if (!xarVar::fetch('confirmed', 'int', $confirmed, null, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('itemid', 'int', $itemid, null, xarVar::DONT_SET)) {
        return;
    }
    if (!xarVar::fetch('idlist', 'str', $idlist, null, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('returnurl', 'str', $returnurl, null, xarVar::DONT_SET)) {
        return;
    }

    if (!empty($itemid)) {
        $idlist = $itemid;
    }
    $ids = explode(',', trim($idlist, ','));

    if (empty($idlist)) {
        if (isset($returnurl)) {
            xarController::redirect($returnurl, null, $context);
        } else {
            xarController::redirect(xarController::URL('publications', 'admin', 'view'), null, $context);
        }
    }

    $data['message'] = '';
    $data['itemid']  = $itemid;

    /*------------- Ask for Confirmation.  If yes, action ----------------------------*/

    sys::import('modules.dynamicdata.class.objects.factory');
    $publication = DataObjectFactory::getObject(['name' => 'publications_publications']);
    if (!isset($confirmed)) {
        $data['idlist'] = $idlist;
        if (count($ids) > 1) {
            $data['title'] = xarML("Delete Publications");
        } else {
            $data['title'] = xarML("Delete Publication");
        }
        $data['authid'] = xarSec::genAuthKey();
        $items = [];
        foreach ($ids as $i) {
            $publication->getItem(['itemid' => $i]);
            $item = $publication->getFieldValues();
            $items[] = $item;
        }
        $data['items'] = $items;
        $data['yes_action'] = xarController::URL('publications', 'admin', 'delete', ['idlist' => $idlist]);
        return xarTpl::module('publications', 'admin', 'delete', $data);
    } else {
        if (!xarSec::confirmAuthKey()) {
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
            xarController::redirect($returnurl, null, $context);
        } else {
            xarController::redirect(xarController::URL('publications', 'admin', 'view', $data), null, $context);
        }
        return true;
    }
}

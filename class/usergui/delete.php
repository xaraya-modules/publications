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
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarController;
use xarModVars;
use xarVar;
use xarSec;
use xarMod;
use xarRoles;
use xarUser;
use xarResponse;
use xarTpl;
use xarHooks;
use DataObjectFactory;
use DataPropertyMaster;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications user delete function
 * @extends MethodClass<UserGui>
 */
class DeleteMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * delete item
     */
    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('ModeratePublications')) {
            return;
        }

        $return = xarController::URL('publications', 'user', 'view', ['ptid' => xarModVars::get('publications', 'defaultpubtype')]);
        if (!xarVar::fetch('confirmed', 'int', $confirmed, null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('itemid', 'int', $itemid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('idlist', 'str', $idlist, null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('returnurl', 'str', $returnurl, $return, xarVar::NOT_REQUIRED)) {
            return;
        }

        if (!empty($itemid)) {
            $idlist = $itemid;
        }
        $ids = explode(',', trim($idlist, ','));

        if (empty($idlist)) {
            if (isset($returnurl)) {
                xarController::redirect($returnurl, null, $this->getContext());
            } else {
                xarController::redirect(xarController::URL('publications', 'user', 'view'), null, $this->getContext());
            }
        }

        $data['message'] = '';
        $data['itemid']  = $itemid;

        /*------------- Ask for Confirmation.  If yes, action ----------------------------*/

        sys::import('modules.dynamicdata.class.objects.factory');
        $publication = DataObjectFactory::getObject(['name' => 'publications_documents']);
        $access = DataPropertyMaster::getProperty(['name' => 'access']);
        $nopermissionpage_id = xarModVars::get('publications', 'noprivspage');

        if (!isset($confirmed)) {
            $data['idlist'] = $idlist;
            if (count($ids) > 1) {
                $data['title'] = xarML("Delete Publications");
            } else {
                $data['title'] = xarML("Delete Publication");
            }
            $data['authid'] = xarSec::genAuthKey();
            $items = [];
            foreach ($ids as $id) {
                $publication->getItem(['itemid' => $id]);
                $item = $publication->getFieldValues();

                # --------------------------------------------------------
                #
                # Are we allowed to delete the page(s)?
                #
                $accessconstraints = xarMod::apiFunc('publications', 'admin', 'getpageaccessconstraints', ['property' => $publication->properties['access']]);
                $allow = $access->check($accessconstraints['delete']);

                // If not allowed, check if admins or the designated site admin can modify even if not the owner
                if (!$allow) {
                    $admin_override = xarModVars::get('publications', 'admin_override');
                    switch ($admin_override) {
                        case 0:
                            break;
                        case 1:
                            $allow = xarRoles::isParent('Administrators', xarUser::getVar('uname'));
                            break;
                        case 2:
                            $allow = xarModVars::get('roles', 'admin') == xarUser::getVar('id');
                            break;
                    }
                }

                if (count($ids) == 1) {
                    // If no access, then bail showing a forbidden or the "no permission" page or an empty page
                    if (!$allow) {
                        if ($accessconstraints['delete']['failure']) {
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
                } else {
                    // More than one page to delete: just ignore the ones we can't
                    continue;
                }

                $items[] = $item;
            }
            $data['items'] = $items;
            $data['yes_action'] = xarController::URL('publications', 'user', 'delete', ['idlist' => $idlist]);
            $data['context'] ??= $this->getContext();
            return xarTpl::module('publications', 'user', 'delete', $data);
        } else {
            if (!xarSec::confirmAuthKey()) {
                return;
            }

            foreach ($ids as $id) {
                $publication->getItem(['itemid' => $id]);

                # --------------------------------------------------------
                #
                # Are we allowed to delete the page(s)?
                #
                $accessconstraints = xarMod::apiFunc('publications', 'admin', 'getpageaccessconstraints', ['property' => $publication->properties['access']]);
                $allow = $access->check($accessconstraints['delete']);

                // If not allowed, check if admins or the designated site admin can modify even if not the owner
                if (!$allow) {
                    $admin_override = xarModVars::get('publications', 'admin_override');
                    switch ($admin_override) {
                        case 0:
                            break;
                        case 1:
                            $allow = xarRoles::isParent('Administrators', xarUser::getVar('uname'));
                            break;
                        case 2:
                            $allow = xarModVars::get('roles', 'admin') == xarUser::getVar('id');
                            break;
                    }
                }

                if (count($ids) == 1) {
                    // If no access, then bail showing a forbidden or the "no permission" page or an empty page
                    if (!$allow) {
                        if ($accessconstraints['delete']['failure']) {
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
                } else {
                    // More than one page to delete: just ignore the ones we can't
                    continue;
                }

                // Delete the publication
                $itemid = $publication->deleteItem(['itemid' => $id]);
                $data['message'] = "Publication deleted [ID $id]";

                // Inform the world via hooks
                $item = ['module' => 'publications', 'itemid' => $itemid, 'itemtype' => $publication->properties['itemtype']->value];
                xarHooks::notify('ItemDelete', $item);
            }

            if (isset($returnurl)) {
                xarController::redirect($returnurl, null, $this->getContext());
            } else {
                xarController::redirect(xarController::URL('publications', 'user', 'view', $data), null, $this->getContext());
            }
            return true;
        }
    }
}

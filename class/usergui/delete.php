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
use Xaraya\Modules\Publications\AdminApi;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarController;
use xarModVars;
use xarVar;
use xarSec;
use xarMod;
use xarRoles;
use xarUser;
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
     * @see UserGui::delete()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        if (!$this->sec()->checkAccess('ModeratePublications')) {
            return;
        }

        $return = $this->mod()->getURL( 'user', 'view', ['ptid' => $this->mod()->getVar('defaultpubtype')]);
        if (!$this->var()->find('confirmed', $confirmed, 'int')) {
            return;
        }
        if (!$this->var()->check('itemid', $itemid, 'int')) {
            return;
        }
        if (!$this->var()->find('idlist', $idlist, 'str')) {
            return;
        }
        if (!$this->var()->find('returnurl', $returnurl, 'str', $return)) {
            return;
        }

        if (!empty($itemid)) {
            $idlist = $itemid;
        }
        $ids = explode(',', trim($idlist, ','));

        if (empty($idlist)) {
            if (isset($returnurl)) {
                $this->ctl()->redirect($returnurl);
            } else {
                $this->ctl()->redirect($this->mod()->getURL('user', 'view'));
            }
        }

        $data['message'] = '';
        $data['itemid']  = $itemid;

        /*------------- Ask for Confirmation.  If yes, action ----------------------------*/

        sys::import('modules.dynamicdata.class.objects.factory');
        $publication = $this->data()->getObject(['name' => 'publications_documents']);
        /** @var \AccessProperty $access */
        $access = $this->prop()->getProperty(['name' => 'access']);
        $nopermissionpage_id = $this->mod()->getVar('noprivspage');

        if (!isset($confirmed)) {
            $data['idlist'] = $idlist;
            if (count($ids) > 1) {
                $data['title'] = $this->ml("Delete Publications");
            } else {
                $data['title'] = $this->ml("Delete Publication");
            }
            $data['authid'] = $this->sec()->genAuthKey();
            $items = [];
            foreach ($ids as $id) {
                $publication->getItem(['itemid' => $id]);
                $item = $publication->getFieldValues();

                # --------------------------------------------------------
                #
                # Are we allowed to delete the page(s)?
                #
                $accessconstraints = $adminapi->getpageaccessconstraints(['property' => $publication->properties['access']]);
                $allow = $access->check($accessconstraints['delete']);

                // If not allowed, check if admins or the designated site admin can modify even if not the owner
                if (!$allow) {
                    $admin_override = $this->mod()->getVar('admin_override');
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
                            return $this->ctl()->forbidden();
                        } elseif ($nopermissionpage_id) {
                            $this->ctl()->redirect($this->mod()->getURL(
                                'user',
                                'display',
                                ['itemid' => $nopermissionpage_id]
                            ));
                        } else {
                            $data = ['context' => $this->getContext()];
                            return $this->mod()->template('empty', $data);
                        }
                    }
                } else {
                    // More than one page to delete: just ignore the ones we can't
                    continue;
                }

                $items[] = $item;
            }
            $data['items'] = $items;
            $data['yes_action'] = $this->mod()->getURL( 'user', 'delete', ['idlist' => $idlist]);
            $data['context'] ??= $this->getContext();
            return $this->mod()->template('delete', $data);
        } else {
            if (!$this->sec()->confirmAuthKey()) {
                return;
            }

            foreach ($ids as $id) {
                $publication->getItem(['itemid' => $id]);

                # --------------------------------------------------------
                #
                # Are we allowed to delete the page(s)?
                #
                $accessconstraints = $adminapi->getpageaccessconstraints(['property' => $publication->properties['access']]);
                $allow = $access->check($accessconstraints['delete']);

                // If not allowed, check if admins or the designated site admin can modify even if not the owner
                if (!$allow) {
                    $admin_override = $this->mod()->getVar('admin_override');
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
                            return $this->ctl()->forbidden();
                        } elseif ($nopermissionpage_id) {
                            $this->ctl()->redirect($this->mod()->getURL(
                                'user',
                                'display',
                                ['itemid' => $nopermissionpage_id]
                            ));
                        } else {
                            $data = ['context' => $this->getContext()];
                            return $this->mod()->template('empty', $data);
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
                $this->ctl()->redirect($returnurl);
            } else {
                $this->ctl()->redirect($this->mod()->getURL( 'user', 'view', $data));
            }
            return true;
        }
    }
}

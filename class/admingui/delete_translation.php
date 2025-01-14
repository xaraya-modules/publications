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
use xarVar;
use xarController;
use xarSec;
use xarTpl;
use DataObjectFactory;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin delete_translation function
 * @extends MethodClass<AdminGui>
 */
class DeleteTranslationMethod extends MethodClass
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
        if (!$this->checkAccess('ManagePublications')) {
            return;
        }

        if (!$this->fetch('confirmed', 'int', $confirmed, null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('itemid', 'str', $data['itemid'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('returnurl', 'str', $returnurl, null, xarVar::DONT_SET)) {
            return;
        }

        if (empty($data['itemid'])) {
            if (isset($returnurl)) {
                $this->redirect($returnurl);
            } else {
                $this->redirect($this->getUrl('admin', 'view'));
            }
        }

        $data['message'] = '';

        /*------------- Ask for Confirmation.  If yes, action ----------------------------*/

        sys::import('modules.dynamicdata.class.objects.factory');
        $publication = DataObjectFactory::getObject(['name' => 'publications_publications']);
        if (!isset($confirmed)) {
            $data['title'] = $this->translate("Delete Translation");
            $data['authid'] = $this->genAuthKey();
            $publication->getItem(['itemid' => $data['itemid']]);
            $data['item'] = $publication->getFieldValues();
            $data['yes_action'] = $this->getUrl( 'admin', 'delete', ['itemid' => $data['itemid']]);
            return xarTpl::module('publications', 'admin', 'delete_translation', $data);
        } else {
            if (!$this->confirmAuthKey()) {
                return;
            }
            $itemid = $publication->deleteItem(['itemid' => $data['itemid']]);
            $data['message'] = "Translation deleted [ID $itemid]";
            if (isset($returnurl)) {
                $this->redirect($returnurl);
            } else {
                $this->redirect($this->getUrl( 'admin', 'view', $data));
            }
            return true;
        }
    }
}

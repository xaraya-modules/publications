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
     * @see AdminGui::deleteTranslation()
     */
    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('ManagePublications')) {
            return;
        }

        $this->var()->find('confirmed', $confirmed, 'int');
        $this->var()->check('itemid', $data['itemid'], 'str');
        $this->var()->check('returnurl', $returnurl, 'str');

        if (empty($data['itemid'])) {
            if (isset($returnurl)) {
                $this->ctl()->redirect($returnurl);
            } else {
                $this->ctl()->redirect($this->mod()->getURL('admin', 'view'));
            }
        }

        $data['message'] = '';

        /*------------- Ask for Confirmation.  If yes, action ----------------------------*/

        sys::import('modules.dynamicdata.class.objects.factory');
        $publication = $this->data()->getObject(['name' => 'publications_publications']);
        if (!isset($confirmed)) {
            $data['title'] = $this->ml("Delete Translation");
            $data['authid'] = $this->sec()->genAuthKey();
            $publication->getItem(['itemid' => $data['itemid']]);
            $data['item'] = $publication->getFieldValues();
            $data['yes_action'] = $this->mod()->getURL( 'admin', 'delete', ['itemid' => $data['itemid']]);
            return $this->mod()->template('delete_translation', $data);
        } else {
            if (!$this->sec()->confirmAuthKey()) {
                return;
            }
            $itemid = $publication->deleteItem(['itemid' => $data['itemid']]);
            $data['message'] = "Translation deleted [ID $itemid]";
            if (isset($returnurl)) {
                $this->ctl()->redirect($returnurl);
            } else {
                $this->ctl()->redirect($this->mod()->getURL( 'admin', 'view', $data));
            }
            return true;
        }
    }
}

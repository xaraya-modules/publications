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
use xarSession;
use xarModVars;
use xarDB;
use xarMod;
use xarTpl;
use DataObjectFactory;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin new function
 * @extends MethodClass<AdminGui>
 */
class NewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        if (!$this->checkAccess('AddPublications')) {
            return;
        }

        extract($args);

        // Get parameters
        if (!$this->fetch('ptid', 'id', $data['ptid'], null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('catid', 'str', $catid, null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('itemtype', 'id', $itemtype, null, xarVar::NOT_REQUIRED)) {
            return;
        }

        if (null === $data['ptid']) {
            $data['ptid'] = xarSession::getVar('publications_current_pubtype');
            if (empty($data['ptid'])) {
                $data['ptid'] = $this->getModVar('defaultpubtype');
            }
        }
        xarSession::setVar('publications_current_pubtype', $data['ptid']);

        $pubtypeobject = DataObjectFactory::getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $data['ptid']]);
        $data['object'] = DataObjectFactory::getObject(['name' => $pubtypeobject->properties['name']->value]);

        //FIXME This should be configuration in the celko property itself
        $data['object']->properties['position']->initialization_celkoparent_id = 'parentpage_id';
        $data['object']->properties['position']->initialization_celkoright_id = 'rightpage_id';
        $data['object']->properties['position']->initialization_celkoleft_id  = 'leftpage_id';
        $xartable = & xarDB::getTables();
        $data['object']->properties['position']->initialization_itemstable = $xartable['publications'];

        $data['properties'] = $data['object']->getProperties();
        $data['items'] = [];

        if (!empty($data['ptid'])) {
            $template = $pubtypeobject->properties['template']->value;
        } else {
            // TODO: allow templates per category ?
            $template = null;
        }

        // Get the settings of the publication type we are using
        $data['settings'] = xarMod::apiFunc('publications', 'user', 'getsettings', ['ptid' => $data['ptid']]);

        $data['context'] ??= $this->getContext();
        return xarTpl::module('publications', 'admin', 'new', $data, $template);
    }
}

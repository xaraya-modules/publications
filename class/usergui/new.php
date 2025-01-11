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
use xarModVars;
use xarMod;
use xarResponse;
use xarController;
use xarTpl;
use DataObjectFactory;
use DataPropertyMaster;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications user new function
 */
class NewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        // Xaraya security
        if (!xarSecurity::check('ModeratePublications')) {
            return;
        }

        extract($args);

        // Get parameters
        if (!xarVar::fetch('ptid', 'int', $data['ptid'], xarModVars::get('publications', 'defaultpubtype'), xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('catid', 'str', $catid, null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('itemtype', 'id', $itemtype, null, xarVar::NOT_REQUIRED)) {
            return;
        }
        $data['items'] = [];

        $pubtypeobject = DataObjectFactory::getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $data['ptid']]);
        $data['object'] = DataObjectFactory::getObject(['name' => $pubtypeobject->properties['name']->value]);

        # --------------------------------------------------------
        #
        # Are we allowed to add a page?
        #
        $accessconstraints = xarMod::apiFunc('publications', 'admin', 'getpageaccessconstraints', ['property' => $data['object']->properties['access']]);
        $access = DataPropertyMaster::getProperty(['name' => 'access']);
        $allow = $access->check($accessconstraints['add']);

        // If no access, then bail showing a forbidden or the "no permission" page or an empty page
        $nopermissionpage_id = xarModVars::get('publications', 'noprivspage');
        if (!$allow) {
            if ($accessconstraints['add']['failure']) {
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

        # --------------------------------------------------------
        #
        # Good to go. Continue
        #
        $data['properties'] = $data['object']->getProperties();

        if (!empty($data['ptid'])) {
            $template = $pubtypeobject->properties['template']->value;
        } else {
            // TODO: allow templates per category ?
            $template = null;
        }

        // Get the settings of the publication type we are using
        $data['settings'] = xarMod::apiFunc('publications', 'user', 'getsettings', ['ptid' => $data['ptid']]);

        $data['context'] ??= $this->getContext();
        return xarTpl::module('publications', 'user', 'new', $data, $template);
    }
}

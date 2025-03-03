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
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarModVars;
use xarMod;
use xarController;
use xarTpl;
use DataObjectFactory;
use DataPropertyMaster;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications user new function
 * @extends MethodClass<UserGui>
 */
class NewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see UserGui::new()
     */

    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Xaraya security
        if (!$this->sec()->checkAccess('ModeratePublications')) {
            return;
        }

        extract($args);

        // Get parameters
        $this->var()->find('ptid', $data['ptid'], 'int', $this->mod()->getVar('defaultpubtype'));
        $this->var()->find('catid', $catid, 'str');
        $this->var()->find('itemtype', $itemtype, 'id');
        $data['items'] = [];

        $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $data['ptid']]);
        $data['object'] = $this->data()->getObject(['name' => $pubtypeobject->properties['name']->value]);

        # --------------------------------------------------------
        #
        # Are we allowed to add a page?
        #
        $accessconstraints = $adminapi->getpageaccessconstraints(['property' => $data['object']->properties['access']]);
        /** @var \AccessProperty $access */
        $access = $this->prop()->getProperty(['name' => 'access']);
        $allow = $access->check($accessconstraints['add']);

        // If no access, then bail showing a forbidden or the "no permission" page or an empty page
        $nopermissionpage_id = $this->mod()->getVar('noprivspage');
        if (!$allow) {
            if ($accessconstraints['add']['failure']) {
                return $this->ctl()->forbidden();
            } elseif ($nopermissionpage_id) {
                $this->ctl()->redirect($this->mod()->getURL(
                    'user',
                    'display',
                    ['itemid' => $nopermissionpage_id]
                ));
                return true;
            } else {
                $data = ['context' => $this->getContext()];
                return $this->mod()->template('empty', $data);
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
        $data['settings'] = $userapi->getsettings(['ptid' => $data['ptid']]);

        $data['context'] ??= $this->getContext();
        return $this->mod()->template('new', $data, $template);
    }
}

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
use xarVar;
use xarSec;
use xarMod;
use xarModVars;
use xarUser;
use xarConfigVars;
use xarTpl;
use xarHooks;
use xarController;
use xarSession;
use DataObjectFactory;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications user create function
 * @extends MethodClass<UserGui>
 */
class CreateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        // Xaraya security
        if (!$this->sec()->checkAccess('ModeratePublications')) {
            return;
        }

        if (!$this->var()->get('ptid', $data['ptid'], 'id')) {
            return;
        }
        if (!$this->var()->find('new_cids', $cids, 'array')) {
            return;
        }
        if (!$this->var()->find('preview', $data['preview'], 'str')) {
            return;
        }
        if (!$this->var()->find('save', $save, 'str')) {
            return;
        }

        // Confirm authorisation code
        // This has been disabled for now
        // if (!$this->sec()->confirmAuthKey()) return;

        $data['items'] = [];
        $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $data['ptid']]);
        $data['object'] = $this->data()->getObject(['name' => $pubtypeobject->properties['name']->value]);

        $isvalid = $data['object']->checkInput();

        $data['settings'] = xarMod::apiFunc('publications', 'user', 'getsettings', ['ptid' => $data['ptid']]);

        if ($data['preview'] || !$isvalid) {
            // Show debug info if called for
            if (!$isvalid &&
                $this->mod()->getVar('debugmode') &&
                in_array(xarUser::getVar('id'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                var_dump($data['object']->getInvalids());
            }
            // Preview or bad data: redisplay the form
            $data['properties'] = $data['object']->getProperties();
            if ($data['preview']) {
                $data['tab'] = 'preview';
            }
            $data['context'] ??= $this->getContext();
            return $this->mod()->template('new', $data);
        }

        // Create the object
        $itemid = $data['object']->createItem();

        // Inform the world via hooks
        $item = ['module' => 'publications', 'itemid' => $itemid, 'itemtype' => $data['object']->properties['itemtype']->value];
        xarHooks::notify('ItemCreate', $item);

        // Redirect if needed
        if (!$this->var()->find('return_url', $return_url, 'str', '')) {
            return;
        }
        if (!empty($return_url)) {
            // FIXME: this is a hack for short URLS
            $delimiter = (strpos($return_url, '&')) ? '&' : '?';
            $this->ctl()->redirect($return_url . $delimiter . 'itemid=' . $itemid);
        }

        // Redirect if we came from somewhere else
        $current_listview = xarSession::getVar('publications_current_listview');
        if (!empty($current_listview)) {
            $this->ctl()->redirect($current_listview);
        }

        $this->ctl()->redirect($this->mod()->getURL(
            'user',
            'view',
            ['ptid' => $data['ptid']]
        ));
        return true;
    }
}

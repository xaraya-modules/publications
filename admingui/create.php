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
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;

/**
 * publications admin create function
 * @extends MethodClass<AdminGui>
 */
class CreateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::create()
     */

    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->sec()->checkAccess('AddPublications')) {
            return;
        }

        $this->var()->get('ptid', $data['ptid'], 'id');
        $this->var()->find('new_cids', $cids, 'array');
        $this->var()->find('preview', $data['preview'], 'str');
        $this->var()->find('save', $save, 'str');

        // Confirm authorisation code
        // This has been disabled for now
        // if (!$this->sec()->confirmAuthKey()) return;

        $data['items'] = [];
        $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $data['ptid']]);
        $data['object'] = $this->data()->getObject(['name' => $pubtypeobject->properties['name']->value]);

        $isvalid = $data['object']->checkInput();

        $data['settings'] = $userapi->getsettings(['ptid' => $data['ptid']]);

        if ($data['preview'] || !$isvalid) {
            // Show debug info if called for
            if (!$isvalid
                && $this->mod()->getVar('debugmode') && $this->user()->isDebugAdmin()) {
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
        $this->mod()->notifyHooks('ItemCreate', $item);

        // Redirect if we came from somewhere else
        $current_listview = $this->session()->getVar('publications_current_listview');
        if (!empty($current_listview)) {
            $this->ctl()->redirect($current_listview);
            return true;
        }

        $this->ctl()->redirect($this->mod()->getURL(
            'admin',
            'view',
            ['ptid' => $data['ptid']]
        ));
        return true;
    }
}

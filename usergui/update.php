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
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;
use DataPropertyMaster;

/**
 * publications user update function
 * @extends MethodClass<UserGui>
 */
class UpdateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see UserGui::update()
     */

    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->sec()->checkAccess('ModeratePublications')) {
            return;
        }

        // Get parameters
        $this->var()->check('itemid', $data['itemid']);
        $this->var()->check('items', $items, 'str', '');
        $this->var()->check('ptid', $data['ptid']);
        $this->var()->check('modify_cids', $cids);
        $this->var()->check('preview', $data['preview']);
        $this->var()->find('returnurl', $data['returnurl'], 'str:1', '');
        $this->var()->check('quit', $data['quit']);
        $this->var()->check('front', $data['front']);
        $this->var()->find('tab', $data['tab'], 'str:1', '');

        // Confirm authorisation code
        // This has been disabled for now
        //    if (!$this->sec()->confirmAuthKey()) return;

        $items = explode(',', $items);
        $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $data['ptid']]);
        $data['object'] = $this->data()->getObject(['name' => $pubtypeobject->properties['name']->value]);

        // First we need to check all the data on the template
        // If checkInput fails, don't bail
        $itemsdata = [];
        $isvalid = true;
        foreach ($items as $prefix) {
            $data['object']->setFieldPrefix($prefix);

            // Disable the celkoposition property according if this is not the base document
            $fieldname = $prefix . '_dd_' . $data['object']->properties['parent']->id;
            $data['object']->properties['parent']->checkInput($fieldname);
            if (empty($data['object']->properties['parent']->value)) {
                $data['object']->properties['position']->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY);
            } else {
                $data['object']->properties['position']->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_DISABLED);
            }

            // Now get the input from the form
            $thisvalid = $data['object']->checkInput();
            $isvalid = $isvalid && $thisvalid;
            // Store each item for later processing
            $itemsdata[$prefix] = $data['object']->getFieldValues([], 1);
        }

        if ($data['preview'] || !$isvalid) {
            // Show debug info if called for
            if (!$isvalid
                && $this->mod()->getVar('debugmode') && $this->user()->isDebugAdmin()) {
                echo $this->ml('The following were invalid fields:');
                echo "<br/>";
                var_dump($data['object']->getInvalids());
            }
            // Preview or bad data: redisplay the form
            $data['properties'] = $data['object']->getProperties();
            if ($data['preview']) {
                $data['tab'] = 'preview';
            }
            $data['items'] = $itemsdata;
            // Get the settings of the publication type we are using
            $data['settings'] = $userapi->getsettings(['ptid' => $data['ptid']]);

            $data['context'] ??= $this->getContext();
            return $this->mod()->template('modify', $data);
        }

        // call transform input hooks
        $article['transform'] = ['summary','body','notes'];
        $article = $this->mod()->callHooks(
            'item',
            'transform-input',
            $data['itemid'],
            $article,
            'publications',
            $data['ptid']
        );

        // Now talk to the database. Loop through all the translation pages
        foreach ($itemsdata as $id => $itemdata) {
            // Get the data for this item
            $data['object']->setFieldValues($itemdata, 1);

            // Save or create the item (depends whether this translation is new)
            if (empty($id)) {
                $item = $data['object']->createItem();
            } else {
                $item = $data['object']->updateItem();
            }

            // Check if we have an alias and set it as an alias of the publications module
            $alias_flag = $data['object']->properties['alias_flag']->value;
            if ($alias_flag == 1) {
                $alias = $data['object']->properties['alias']->value;
                if (!empty($alias)) {
                    $this->mod()->defineAlias($alias, 'publications');
                }
            } elseif ($alias_flag == 2) {
                $alias = $data['object']->properties['name']->value;
                if (!empty($alias)) {
                    $this->mod()->defineAlias($alias, 'publications');
                }
            }

            // Clear the itemid property in preparation for the next round
            unset($data['object']->itemid);
        }

        // Success
        $this->session()->setVar('statusmsg', $this->ml('Publication Updated'));

        // Inform the world via hooks
        $item = ['module' => 'publications', 'itemid' => $data['itemid'], 'itemtype' => $data['object']->properties['itemtype']->value];
        $this->mod()->notifyHooks('ItemUpdate', $item);

        if ($data['quit']) {
            // Redirect if needed
            $this->var()->find('return_url', $return_url, 'str', '');
            if (!empty($return_url)) {
                // FIXME: this is a hack for short URLS
                $delimiter = (strpos($return_url, '&')) ? '&' : '?';
                $this->ctl()->redirect($return_url . $delimiter . 'itemid=' . $data['itemid']);
                return true;
            }

            // Redirect if we came from somewhere else
            $current_listview = $this->session()->getVar('publications_current_listview');
            if (!empty($current_listview)) {
                $this->ctl()->redirect($current_listview);
                return true;
            }
            $this->ctl()->redirect($this->mod()->getURL(
                'user',
                'view',
                ['ptid' => $data['ptid']]
            ));
            return true;
        } elseif ($data['front']) {
            $this->ctl()->redirect($this->mod()->getURL(
                'user',
                'display',
                ['name' => $pubtypeobject->properties['name']->value, 'itemid' => $data['itemid']]
            ));
        } else {
            if (!empty($data['returnurl'])) {
                $this->ctl()->redirect($data['returnurl']);
            } else {
                $this->ctl()->redirect($this->mod()->getURL(
                    'user',
                    'modify',
                    ['name' => $pubtypeobject->properties['name']->value, 'itemid' => $data['itemid']]
                ));
            }
            return true;
        }
    }
}

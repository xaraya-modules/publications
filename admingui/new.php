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
 * publications admin new function
 * @extends MethodClass<AdminGui>
 */
class NewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::new()
     */

    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->sec()->checkAccess('AddPublications')) {
            return;
        }

        extract($args);

        // Get parameters
        $this->var()->find('ptid', $data['ptid'], 'id');
        $this->var()->find('catid', $catid, 'str');
        $this->var()->find('itemtype', $itemtype, 'id');

        if (null === $data['ptid']) {
            $data['ptid'] = $this->session()->getVar('publications_current_pubtype');
            if (empty($data['ptid'])) {
                $data['ptid'] = $this->mod()->getVar('defaultpubtype');
            }
        }
        $this->session()->setVar('publications_current_pubtype', $data['ptid']);

        $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $data['ptid']]);
        $data['object'] = $this->data()->getObject(['name' => $pubtypeobject->properties['name']->value]);

        //FIXME This should be configuration in the celko property itself
        $data['object']->properties['position']->initialization_celkoparent_id = 'parentpage_id';
        $data['object']->properties['position']->initialization_celkoright_id = 'rightpage_id';
        $data['object']->properties['position']->initialization_celkoleft_id  = 'leftpage_id';
        $xartable = & $this->db()->getTables();
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
        $data['settings'] = $userapi->getsettings(['ptid' => $data['ptid']]);

        return $this->render('new', $data, $template);
    }
}

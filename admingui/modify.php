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
 * publications admin modify function
 * @extends MethodClass<AdminGui>
 */
class ModifyMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::modify()
     */

    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->sec()->checkAccess('EditPublications')) {
            return;
        }

        extract($args);

        // Get parameters
        $this->var()->check('itemid', $data['itemid']);
        $this->var()->check('ptid', $ptid);
        $this->var()->find('returnurl', $data['returnurl'], 'str:1', 'view');
        $this->var()->find('name', $name, 'str:1', '');
        $this->var()->find('tab', $data['tab'], 'str:1', '');

        if (empty($name) && empty($ptid)) {
            return $this->ctl()->notFound();
        }

        if (!empty($ptid)) {
            $publication_type = $this->data()->getObjectList(['name' => 'publications_types']);
            $where = 'id = ' . $ptid;
            $items = $publication_type->getItems(['where' => $where]);
            $item = current($items);
            $name = $item['name'];
        }

        # --------------------------------------------------------
        #
        # Get our object
        #
        $data['object'] = $this->data()->getObject(['name' => $name]);
        $data['object']->getItem(['itemid' => $data['itemid']]);
        $data['ptid'] = $data['object']->properties['itemtype']->value;

        // Send the publication type and the object properties to the template
        $data['properties'] = $data['object']->getProperties();

        // Get the settings of the publication type we are using
        $data['settings'] = $userapi->getsettings(['ptid' => $data['ptid']]);

        # --------------------------------------------------------
        #
        # If creating a new translation get an empty copy
        #
        if ($data['tab'] == 'newtranslation') {
            $data['object']->properties['id']->setValue(0);
            $data['object']->properties['parent']->setValue($data['itemid']);
            $data['items'][0] = $data['object']->getFieldValues([], 1);
            $data['tab'] = '';
        } else {
            $data['items'] = [];
        }

        # --------------------------------------------------------
        #
        # Get the base document. If this itemid is not the base doc,
        # then first find the correct itemid
        #
        $data['object']->getItem(['itemid' => $data['itemid']]);
        $fieldvalues = $data['object']->getFieldValues([], 1);
        if (!empty($fieldvalues['parent'])) {
            $data['itemid'] = $fieldvalues['parent'];
            $data['object']->getItem(['itemid' => $data['itemid']]);
            $fieldvalues = $data['object']->getFieldValues([], 1);
        }
        $data['items'][$data['itemid']] = $fieldvalues;

        # --------------------------------------------------------
        #
        # Get any translations of the base document
        #
        $data['objectlist'] = $this->data()->getObjectList(['name' => $name]);
        $where = "parent = " . $data['itemid'];
        $items = $data['objectlist']->getItems(['where' => $where]);
        foreach ($items as $key => $value) {
            // Clear the previous values before starting the next round
            $data['object']->clearFieldValues();
            $data['object']->getItem(['itemid' => $key]);
            $data['items'][$key] = $data['object']->getFieldValues([], 1);
        }

        # --------------------------------------------------------
        #
        # Get information on next and previous items
        #
        $data['prevpublication'] = $userapi->getprevious(
            ['id' => $data['itemid'],
                'ptid' => $ptid,
                'sort' => 'tree',]
        );
        $data['nextpublication'] = $userapi->getnext(
            ['id' => $data['itemid'],
                'ptid' => $ptid,
                'sort' => 'tree',]
        );
        return $data;
    }
}

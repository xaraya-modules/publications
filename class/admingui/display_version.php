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

use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin display_version function
 */
class DisplayVersionMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('ManagePublications')) {
            return;
        }

        if (!xarVar::fetch('itemid', 'id', $data['page_id'], 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('name', 'str', $data['objectname'], '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (empty($data['page_id'])) {
            return xarController::notFound(null, $this->getContext());
        }

        sys::import('modules.dynamicdata.class.objects.factory');
        $entries = DataObjectFactory::getObjectList(['name' => 'publications_versions']);
        $entries->dataquery->eq($entries->properties['page_id']->source, $data['page_id']);
        $data['versions'] = $entries->countItems();

        if ($data['versions'] < 1) {
            return $data;
        }

        if (!xarVar::fetch('confirm', 'int', $confirm, 1, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('version_1', 'int', $version_1, $data['versions'], xarVar::NOT_REQUIRED)) {
            return;
        }
        $data['version_1'] = $version_1;

        // Get the content data for the display
        $version = DataObjectFactory::getObjectList(['name' => 'publications_versions']);
        $version->dataquery->eq($version->properties['page_id']->source, $data['page_id']);
        $version->dataquery->eq($version->properties['version_number']->source, $version_1);
        $items = $version->getItems();
        if (count($items) > 1) {
            throw new Exception(xarML('More than one instance with the version number #(1)', $version_1));
        }
        $item = current($items);
        $content_array_1 = unserialize($item['content']);

        // Get an empty object for the page data
        $pubtype = DataObjectFactory::getObject(['name' => 'publications_types']);
        $pubtype->getItem(['itemid' => $content_array_1['itemtype']]);
        $page = DataObjectFactory::getObject(['name' => $pubtype->properties['name']->value]);
        $page->tplmodule = 'publications';
        $page->layout = 'publications_documents';

        // Load the data into its object
        $page->setFieldValues($content_array_1, 1);

        if ($confirm == 1) {
            // Now in turn get the actual display
            $data['content'] = $page->showDisplay();
            // Assemple options for the version dropdowns
            $data['options'] = [];
            for ($i = $data['versions'];$i >= 1;$i--) {
                $data['options'][] = ['id' => $i, 'name' => $i];
            }
        } elseif ($confirm == 2) {
            $page->properties['version']->value = $data['versions'] + 1;
            $page->updateItem();

            xarController::redirect(xarController::URL(
                'publications',
                'admin',
                'modify',
                ['name' => $pubtype->properties['name']->value, 'itemid' => $content_array_1['id']]
            ), null, $this->getContext());
            return true;
        }
        return $data;
    }
}

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
use Exception;

/**
 * publications admin display_version function
 * @extends MethodClass<AdminGui>
 */
class DisplayVersionMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::displayVersion()
     */

    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('ManagePublications')) {
            return;
        }

        $this->var()->find('itemid', $data['page_id'], 'id', 0);
        $this->var()->find('name', $data['objectname'], 'str', '');
        if (empty($data['page_id'])) {
            return $this->ctl()->notFound();
        }

        $entries = $this->data()->getObjectList(['name' => 'publications_versions']);
        $entries->dataquery->eq($entries->properties['page_id']->source, $data['page_id']);
        $data['versions'] = $entries->countItems();

        if ($data['versions'] < 1) {
            return $data;
        }

        $this->var()->find('confirm', $confirm, 'int', 1);
        $this->var()->find('version_1', $version_1, 'int', $data['versions']);
        $data['version_1'] = $version_1;

        // Get the content data for the display
        $version = $this->data()->getObjectList(['name' => 'publications_versions']);
        $version->dataquery->eq($version->properties['page_id']->source, $data['page_id']);
        $version->dataquery->eq($version->properties['version_number']->source, $version_1);
        $items = $version->getItems();
        if (count($items) > 1) {
            throw new Exception($this->ml('More than one instance with the version number #(1)', $version_1));
        }
        $item = current($items);
        $content_array_1 = unserialize($item['content']);

        // Get an empty object for the page data
        $pubtype = $this->data()->getObject(['name' => 'publications_types']);
        $pubtype->getItem(['itemid' => $content_array_1['itemtype']]);
        $page = $this->data()->getObject(['name' => $pubtype->properties['name']->value]);
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

            $this->ctl()->redirect($this->mod()->getURL(
                'admin',
                'modify',
                ['name' => $pubtype->properties['name']->value, 'itemid' => $content_array_1['id']]
            ));
            return true;
        }
        return $data;
    }
}

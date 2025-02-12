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
use xarController;
use DataObjectFactory;
use Diff;
use Diff_Renderer_Html_Inline;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * publications admin manage_versions function
 * @extends MethodClass<AdminGui>
 */
class ManageVersionsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::manageVersions()
     */

    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('ManagePublications')) {
            return;
        }

        if (!$this->var()->find('itemid', $data['page_id'], 'id', 0)) {
            return;
        }
        if (!$this->var()->find('name', $data['objectname'], 'str', '')) {
            return;
        }
        if (empty($data['page_id'])) {
            return $this->ctl()->notFound();
        }

        sys::import('modules.dynamicdata.class.objects.factory');
        $entries = $this->data()->getObjectList(['name' => 'publications_versions']);
        $entries->dataquery->eq($entries->properties['page_id']->source, $data['page_id']);
        $data['versions'] = $entries->countItems() + 1;

        if ($data['versions'] < 2) {
            return $data;
        }

        if (!$this->var()->find('version_1', $version_1, 'int', $data['versions'])) {
            return;
        }
        if (!$this->var()->find('version_2', $version_2, 'int', $data['versions'] - 1)) {
            return;
        }
        $data['version_1'] = $version_1;
        $data['version_2'] = $version_2;

        // Assemple options for the version dropdowns
        $data['options'] = [];
        for ($i = $data['versions'];$i >= 1;$i--) {
            $data['options'][] = ['id' => $i, 'name' => $i];
        }

        // Get an empty object for the page data
        $page = $this->data()->getObject(['name' => $data['objectname']]);

        $version = $this->data()->getObjectList(['name' => 'publications_versions']);

        if ($data['version_1'] == $data['versions']) {
            $page->getItem(['itemid' => $data['page_id']]);
            $content_array_1 = $page->getFieldValues([], 1);
        } else {
            $version->dataquery->eq($version->properties['page_id']->source, $data['page_id']);
            $version->dataquery->eq($version->properties['version_number']->source, $version_1);
            $items = $version->getItems();
            if (count($items) > 1) {
                throw new Exception($this->ml('More than one instance with the version number #(1)', $version_1));
            }
            $item = current($items);
            $content_array_1 = unserialize($item['content']);
        }

        if ($data['version_2'] == $data['versions']) {
            $page->getItem(['itemid' => $data['page_id']]);
            $content_array_2 = $page->getFieldValues([], 1);
        } else {
            $version->dataquery->clearconditions();
            $version->dataquery->eq($version->properties['page_id']->source, $data['page_id']);
            $version->dataquery->eq($version->properties['version_number']->source, $version_2);
            $items = $version->getItems();
            if (count($items) > 1) {
                throw new Exception($this->ml('More than one instance with the version number #(1)', $version_2));
            }
            $item = current($items);
            $content_array_2 = unserialize($item['content']);
        }

        $page->tplmodule = 'publications';
        $page->layout = 'publications_documents';

        // Now in turn get the actual display
        $page->setFieldValues($content_array_1, 1);
        $content_1 = $page->showDisplay();
        $page->setFieldValues($content_array_2, 1);
        $content_2 = $page->showDisplay();

        // Keep a copy to show if the two versions are identical
        $data['content'] = $content_2;
        /*
            sys::import('modules.publications.class.difflib');
            sys::import('modules.publications.class.showdiff');

            $diff = new \Xaraya\Modules\Publications\Diff( explode("\n",$orig_str), explode("\n",$final_str));
            $objshowdiff = new \Xaraya\Modules\Publications\showdiff();
            $data['result'] = $objshowdiff->checkdiff($orig_str,$final_str,$diff,'Line');

            $string_arr= explode("<br>",$data['result']);
        */
        sys::import('modules.publications.class.lib.Diff');
        sys::import('modules.publications.class.lib.Diff.Renderer.Html.Inline');

        // Explode the content by lines
        $content_1 = explode("\n", $content_1);
        $content_2 = explode("\n", $content_2);

        // Options for generating the diff
        $options = [
            //'ignoreWhitespace' => true,
            //'ignoreCase' => true,
        ];

        // Initialize the diff class
        $diff = new Diff($content_1, $content_2, $options);
        $renderer = new Diff_Renderer_Html_Inline();
        $data['diffresult'] = $diff->render($renderer);



        //	$data['content_1'] = nl2br($string_arr[0]);
        //	$data['content_2'] = nl2br($string_arr[1]);
        return $data;
    }
}

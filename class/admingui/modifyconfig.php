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
use xarModVars;
use xarMod;
use xarModAlias;
use DataObjectFactory;
use PropertyRegistration;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify configuration
     */
    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('AdminPublications')) {
            return;
        }

        // Get parameters
        if (!$this->var()->find('tab', $data['tab'], 'str:1:100', 'global')) {
            return;
        }
        if (!$this->var()->check('ptid', $data['ptid'], 'int', $this->mod()->getVar('defaultpubtype'))) {
            return;
        }

        if ($data['tab'] == 'pubtypes') {
            // Configuration specific to a publication type
            if (!xarSecurity::check('AdminPublications', 1, 'Publication', $data['ptid'] . ":All:All:All")) {
                return;
            }

            $viewoptions = [];
            $viewoptions[] = ['id' => 1, 'name' => $this->ml('Latest Items')];

            if (!isset($data['usetitleforurl'])) {
                $data['usetitleforurl'] = 0;
            }
            if (!isset($data['defaultsort'])) {
                $data['defaultsort'] = 'date';
            }

            // get root categories for this publication type
            if (!empty($id)) {
                $catlinks = xarMod::apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications','itemtype' => $data['ptid']]);
                // Note: if you want to use a *combination* of categories here, you'll
                //       need to use something like 'c15+32'
                foreach ($catlinks as $catlink) {
                    $viewoptions[] = ['id' => 'c' . $catlink['category_id'],
                        'name' => $this->ml('Browse in') . ' ' .
                                   $catlink['name'], ];
                }
            }
            $data['viewoptions'] = $viewoptions;

            // Get the publication type for this display
            $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
            $pubtypeobject->getItem(['itemid' => $data['ptid']]);

            // Get the settings for this publication type
            $settings = @unserialize((string) $pubtypeobject->properties['configuration']->getValue());
            $globalsettings = xarMod::apiFunc('publications', 'user', 'getglobalsettings');
            if (is_array($settings)) {
                $data['settings'] = $settings + $globalsettings;
            } else {
                $data['settings'] = $globalsettings;
            }
            $data['pubtypename'] = $pubtypeobject->properties['name']->getValue();
        } elseif ($data['tab'] == 'redirects') {
            // Redirect configuration
            // FIXME: remove this?
            $data['redirects'] = unserialize($this->mod()->getVar('redirects'));
        } else {
            // Global configuration
            if (!$this->sec()->checkAccess('AdminPublications')) {
                return;
            }

            //The usual bunch of vars
            $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'publications']);
            $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, user_menu_link, use_module_icons, frontend_page, backend_page');
            $data['module_settings']->getItem();

            $data['shorturls'] = $this->mod()->getVar('SupportShortURLs') ? true : false;

            $data['defaultpubtype'] = $this->mod()->getVar('defaultpubtype');
            if (empty($data['defaultpubtype'])) {
                $data['defaultpubtype'] = '';
            }
            $data['sortpubtypes'] = $this->mod()->getVar('sortpubtypes');
            if (empty($data['sortpubtypes'])) {
                $data['sortpubtypes'] = 'id';
                $this->mod()->setVar('sortpubtypes', 'id');
            }

            // Get the tree of all pages.
            //        $data['tree'] = xarMod::apiFunc('publications', 'user', 'getpagestree', array('dd_flag' => false));
            $data['tree'] = [];

            // Implode the names for each page into a path for display.
            $data['treeoptions'] = [];
            if (!empty($data['tree']['pages'])) {
                foreach ($data['tree']['pages'] as $key => $page) {
                    //        $data['tree']['pages'][$key]['slash_separated'] =  '/' . implode('/', $page['namepath']);
                    $data['treeoptions'][] = ['id' => $page['id'], 'name' => '/' . implode('/', $page['namepath'])];
                }
            }

            // Module alias for short URLs
            $pubtypes = xarMod::apiFunc('publications', 'user', 'get_pubtypes');
            if (!empty($id)) {
                $data['alias'] = $pubtypes[$id]['name'];
            } else {
                $data['alias'] = 'frontpage';
            }
            $modname = xarModAlias::resolve($data['alias']);
            if ($modname == 'publications') {
                $data['usealias'] = true;
            } else {
                $data['usealias'] = false;
            }

            // Whether the languages property is loaded
            sys::import('modules.dynamicdata.class.properties.registration');
            $types = PropertyRegistration::Retrieve();
            $data['languages'] = isset($types[30039]);
        }

        return $data;
    }
}

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
use xarSec;
use xarVar;
use xarModVars;
use xarDB;
use xarMod;
use xarTpl;
use xarModAlias;
use xarController;
use DataObjectFactory;
use DataPropertyMaster;
use PropertyRegistration;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin updateconfig function
 * @extends MethodClass<AdminGui>
 */
class UpdateconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        if (!$this->checkAccess('AdminPublications')) {
            return;
        }

        // Confirm authorisation code
        if (!$this->confirmAuthKey()) {
            return;
        }
        // Get parameters
        //A lot of these probably are bools, still might there be a need to change the template to return
        //'true' and 'false' to use those...
        if (!$this->fetch('settings', 'array', $settings, [], xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('usetitleforurl', 'int', $usetitleforurl, $this->getModVar('usetitleforurl'), xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('defaultstate', 'isset', $defaultstate, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('defaultsort', 'isset', $defaultsort, 'date', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('usealias', 'int', $usealias, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('ptid', 'isset', $ptid, $this->getModVar('defaultpubtype'), xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('multilanguage', 'int', $multilanguage, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('tab', 'str:1:10', $data['tab'], 'global', xarVar::NOT_REQUIRED)) {
            return;
        }

        if (!xarSecurity::check('AdminPublications', 1, 'Publication', "$ptid:All:All:All")) {
            return;
        }

        if ($data['tab'] == 'global') {
            if (!$this->fetch('defaultpubtype', 'isset', $defaultpubtype, 1, xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!$this->fetch('sortpubtypes', 'isset', $sortpubtypes, 'id', xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!$this->fetch('defaultlanguage', 'str:1:100', $defaultlanguage, $this->getModVar('defaultlanguage'), xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!$this->fetch('debugmode', 'checkbox', $debugmode, $this->getModVar('debugmode'), xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!$this->fetch('use_process_states', 'checkbox', $use_process_states, $this->getModVar('use_process_states'), xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!$this->fetch('use_versions', 'checkbox', $use_versions, $this->getModVar('use_versions'), xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!$this->fetch('hide_tree_display', 'checkbox', $hide_tree_display, $this->getModVar('hide_tree_display'), xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!$this->fetch('admin_override', 'int', $admin_override, $this->getModVar('admin_override'), xarVar::NOT_REQUIRED)) {
                return;
            }

            $this->setModVar('defaultpubtype', $defaultpubtype);
            $this->setModVar('sortpubtypes', $sortpubtypes);
            $this->setModVar('defaultlanguage', $defaultlanguage);
            $this->setModVar('debugmode', $debugmode);
            $this->setModVar('usealias', $usealias);
            $this->setModVar('usetitleforurl', $usetitleforurl);
            $this->setModVar('use_process_states', $use_process_states);
            $this->setModVar('use_versions', $use_versions);
            $this->setModVar('hide_tree_display', $hide_tree_display);
            $this->setModVar('admin_override', $admin_override);

            // Allow multilanguage only if the languages property is present
            sys::import('modules.dynamicdata.class.properties.registration');
            $types = PropertyRegistration::Retrieve();
            if (isset($types[30039])) {
                $this->setModVar('multilanguage', $multilanguage);
            } else {
                $this->setModVar('multilanguage', 0);
            }

            // Get the special pages.
            foreach (['defaultpage', 'errorpage', 'notfoundpage', 'noprivspage'] as $special_name) {
                unset($special_id);
                if (!$this->fetch($special_name, 'id', $special_id, 0, xarVar::NOT_REQUIRED)) {
                    return;
                }
                xarModVars::set('publications', $special_name, $special_id);
            }

            if (xarDB::getType() == 'mysql') {
                if (!$this->fetch('fulltext', 'isset', $fulltext, '', xarVar::NOT_REQUIRED)) {
                    return;
                }
                $oldval = $this->getModVar('fulltextsearch');
                $index = 'i_' . xarDB::getPrefix() . '_publications_fulltext';
                if (empty($fulltext) && !empty($oldval)) {
                    // Get database setup
                    $dbconn = xarDB::getConn();
                    $xartable = & xarDB::getTables();
                    $publicationstable = $xartable['publications'];
                    // Drop fulltext index on publications table
                    $query = "ALTER TABLE $publicationstable DROP INDEX $index";
                    $result = $dbconn->Execute($query);
                    if (!$result) {
                        return;
                    }
                    $this->setModVar('fulltextsearch', '');
                } elseif (!empty($fulltext) && empty($oldval)) {
                    $searchfields = ['title','description','summary','body1'];
                    //                $searchfields = explode(',',$fulltext);
                    // Get database setup
                    $dbconn = xarDB::getConn();
                    $xartable = & xarDB::getTables();
                    $publicationstable = $xartable['publications'];
                    // Add fulltext index on publications table
                    $query = "ALTER TABLE $publicationstable ADD FULLTEXT $index (" . join(', ', $searchfields) . ")";
                    $result = $dbconn->Execute($query);
                    if (!$result) {
                        return;
                    }
                    $this->setModVar('fulltextsearch', join(',', $searchfields));
                }
            }

            // Module settings
            $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'publications']);
            $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, user_menu_link, use_module_icons, frontend_page, backend_page');
            $isvalid = $data['module_settings']->checkInput();
            if (!$isvalid) {
                $data['context'] ??= $this->getContext();
                return xarTpl::module('base', 'admin', 'modifyconfig', $data);
            } else {
                $itemid = $data['module_settings']->updateItem();
            }

            // Pull the base category ids from the template and save them
            $picker = DataPropertyMaster::getProperty(['name' => 'categorypicker']);
            $picker->checkInput('basecid');
        } elseif ($data['tab'] == 'pubtypes') {
            // Get the publication type for this display and save the settings to it
            $pubtypeobject = DataObjectFactory::getObject(['name' => 'publications_types']);
            $pubtypeobject->getItem(['itemid' => $ptid]);
            $configsettings = $pubtypeobject->properties['configuration']->getValue();

            $checkbox = DataPropertyMaster::getProperty(['name' => 'checkbox']);
            $boxes = [
                'show_hitount',
                'show_ratings',
                'show_keywords',
                'show_comments',
                'show_prevnext',
                'show_archives',
                'show_publinks',
                'show_pubcount',
                'show_map',
                'dot_transform',
                'title_transform',
                'show_categories',
                'show_catcount',
            ];
            foreach ($boxes as $box) {
                $isvalid = $checkbox->checkInput($box);
                if ($isvalid) {
                    $settings[$box] = $checkbox->value;
                }
            }

            $isvalid = true;

            foreach ($_POST as $name => $field) {
                if (strpos($name, 'custom_') === 0) {
                    $settings[$name] = $field;
                }
            }

            $pubtypeobject->properties['configuration']->setValue(serialize($settings));
            $pubtypeobject->updateItem(['itemid' => $ptid]);

            $pubtypes = xarMod::apiFunc('publications', 'user', 'get_pubtypes');
            if ($usealias) {
                xarModAlias::set($pubtypes[$ptid]['name'], 'publications');
            } else {
                xarModAlias::delete($pubtypes[$ptid]['name'], 'publications');
            }
        } elseif ($data['tab'] == 'redirects') {
            $redirects = DataPropertyMaster::getProperty(['name' => 'array']);
            $redirects->display_column_definition['value'] = [["From","To"],[2,2],["",""],["",""]];
            $isvalid = $redirects->checkInput("redirects");
            $this->setModVar('redirects', $redirects->value);
        }
        $this->redirect($this->getUrl(
            'admin',
            'modifyconfig',
            ['ptid' => $ptid, 'tab' => $data['tab']]
        ));
        return true;
    }
}

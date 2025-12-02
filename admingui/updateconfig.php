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
use PropertyRegistration;

/**
 * publications admin updateconfig function
 * @extends MethodClass<AdminGui>
 */
class UpdateconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::updateconfig()
     */

    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->sec()->checkAccess('AdminPublications')) {
            return;
        }

        // Confirm authorisation code
        if (!$this->sec()->confirmAuthKey()) {
            return;
        }
        // Get parameters
        //A lot of these probably are bools, still might there be a need to change the template to return
        //'true' and 'false' to use those...
        $this->var()->find('settings', $settings, 'array', []);
        $this->var()->find('usetitleforurl', $usetitleforurl, 'int', $this->mod()->getVar('usetitleforurl'));
        $this->var()->find('defaultstate', $defaultstate, 'isset', 0);
        $this->var()->find('defaultsort', $defaultsort, 'isset', 'date');
        $this->var()->find('usealias', $usealias, 'int', 0);
        $this->var()->find('ptid', $ptid, 'isset', $this->mod()->getVar('defaultpubtype'));
        $this->var()->find('multilanguage', $multilanguage, 'int', 0);
        $this->var()->find('tab', $data['tab'], 'str:1:10', 'global');

        if (!$this->sec()->check('AdminPublications', 1, 'Publication', "$ptid:All:All:All")) {
            return;
        }

        if ($data['tab'] == 'global') {
            $this->var()->find('defaultpubtype', $defaultpubtype, 'isset', 1);
            $this->var()->find('sortpubtypes', $sortpubtypes, 'isset', 'id');
            $this->var()->find('defaultlanguage', $defaultlanguage, 'str:1:100', $this->mod()->getVar('defaultlanguage'));
            $this->var()->find('debugmode', $debugmode, 'checkbox', $this->mod()->getVar('debugmode'));
            $this->var()->find('use_process_states', $use_process_states, 'checkbox', $this->mod()->getVar('use_process_states'));
            $this->var()->find('use_versions', $use_versions, 'checkbox', $this->mod()->getVar('use_versions'));
            $this->var()->find('hide_tree_display', $hide_tree_display, 'checkbox', $this->mod()->getVar('hide_tree_display'));
            $this->var()->find('admin_override', $admin_override, 'int', $this->mod()->getVar('admin_override'));

            $this->mod()->setVar('defaultpubtype', $defaultpubtype);
            $this->mod()->setVar('sortpubtypes', $sortpubtypes);
            $this->mod()->setVar('defaultlanguage', $defaultlanguage);
            $this->mod()->setVar('debugmode', $debugmode);
            $this->mod()->setVar('usealias', $usealias);
            $this->mod()->setVar('usetitleforurl', $usetitleforurl);
            $this->mod()->setVar('use_process_states', $use_process_states);
            $this->mod()->setVar('use_versions', $use_versions);
            $this->mod()->setVar('hide_tree_display', $hide_tree_display);
            $this->mod()->setVar('admin_override', $admin_override);

            // Allow multilanguage only if the languages property is present
            $types = PropertyRegistration::Retrieve();
            if (isset($types[30039])) {
                $this->mod()->setVar('multilanguage', $multilanguage);
            } else {
                $this->mod()->setVar('multilanguage', 0);
            }

            // Get the special pages.
            foreach (['defaultpage', 'errorpage', 'notfoundpage', 'noprivspage'] as $special_name) {
                unset($special_id);
                $this->var()->find($special_name, $special_id, 'id', 0);
                $this->mod()->setVar($special_name, $special_id);
            }

            if ($this->db()->getType() == 'mysql') {
                $this->var()->find('fulltext', $fulltext);
                $oldval = $this->mod()->getVar('fulltextsearch');
                $index = 'i_' . $this->db()->getPrefix() . '_publications_fulltext';
                if (empty($fulltext) && !empty($oldval)) {
                    // Get database setup
                    $dbconn = $this->db()->getConn();
                    $xartable = & $this->db()->getTables();
                    $publicationstable = $xartable['publications'];
                    // Drop fulltext index on publications table
                    $query = "ALTER TABLE $publicationstable DROP INDEX $index";
                    $result = $dbconn->Execute($query);
                    if (!$result) {
                        return;
                    }
                    $this->mod()->setVar('fulltextsearch', '');
                } elseif (!empty($fulltext) && empty($oldval)) {
                    $searchfields = ['title','description','summary','body1'];
                    //                $searchfields = explode(',',$fulltext);
                    // Get database setup
                    $dbconn = $this->db()->getConn();
                    $xartable = & $this->db()->getTables();
                    $publicationstable = $xartable['publications'];
                    // Add fulltext index on publications table
                    $query = "ALTER TABLE $publicationstable ADD FULLTEXT $index (" . join(', ', $searchfields) . ")";
                    $result = $dbconn->Execute($query);
                    if (!$result) {
                        return;
                    }
                    $this->mod()->setVar('fulltextsearch', join(',', $searchfields));
                }
            }

            // Module settings
            $data['module_settings'] = $this->mod()->apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'publications']);
            $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, user_menu_link, use_module_icons, frontend_page, backend_page');
            $isvalid = $data['module_settings']->checkInput();
            if (!$isvalid) {
                $data['context'] ??= $this->getContext();
                return $this->mod()->template('modifyconfig', $data);
            } else {
                $itemid = $data['module_settings']->updateItem();
            }

            // Pull the base category ids from the template and save them
            $picker = $this->prop()->getProperty(['name' => 'categorypicker']);
            $picker->checkInput('basecid');
        } elseif ($data['tab'] == 'pubtypes') {
            // Get the publication type for this display and save the settings to it
            $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
            $pubtypeobject->getItem(['itemid' => $ptid]);
            $configsettings = $pubtypeobject->properties['configuration']->getValue();

            $checkbox = $this->prop()->getProperty(['name' => 'checkbox']);
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

            $pubtypes = $userapi->get_pubtypes();
            if ($usealias) {
                $this->mod()->defineAlias($pubtypes[$ptid]['name'], 'publications');
            } else {
                $this->mod()->removeAlias($pubtypes[$ptid]['name'], 'publications');
            }
        } elseif ($data['tab'] == 'redirects') {
            $redirects = $this->prop()->getProperty(['name' => 'array']);
            $redirects->display_column_definition['value'] = [["From","To"],[2,2],["",""],["",""]];
            $isvalid = $redirects->checkInput("redirects");
            $this->mod()->setVar('redirects', $redirects->value);
        }
        $this->ctl()->redirect($this->mod()->getURL(
            'admin',
            'modifyconfig',
            ['ptid' => $ptid, 'tab' => $data['tab']]
        ));
        return true;
    }
}

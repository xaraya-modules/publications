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
use Xaraya\Modules\Publications\TreeApi;
use Xaraya\Modules\MethodClass;

/**
 * publications admin view_pages function
 * @extends MethodClass<AdminGui>
 */
class ViewPagesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Publications Module
     * @package modules
     * @subpackage publications module
     * @category Third Party Xaraya Module
     * @version 2.0.0
     * @copyright (C) 2012 Netspan AG
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @author Marc Lutolf <mfl@netspan.ch>
     * @see AdminGui::viewPages()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // @todo convert TreeApi
        /** @var TreeApi $treeapi */
        $treeapi = $this->treeapi();
        if (!$this->sec()->checkAccess('ManagePublications')) {
            return;
        }

        extract($args);

        // Accept a parameter to allow selection of a single tree.
        $this->var()->find('root_id', $root_id, 'int');

        if (null === $root_id) {
            $root_id = $this->session()->getVar('publications_root_id');
            if (empty($root_id)) {
                $root_id = 0;
            }
        }
        $this->session()->setVar('publications_root_id', $root_id);

        $data = $userapi->getpagestree(
            ['key' => 'index', 'dd_flag' => false, 'tree_contains_id' => $root_id]
        );

        if (empty($data['pages'])) {
            // TODO: pass to template.
            return $data; //$this->ml('NO PAGES DEFINED');
        } else {
            $data['pages'] = $treeapi->array_maptree($data['pages']);
        }

        $data['root_id'] = $root_id;

        // Check modify and delete privileges on each page.
        // EditPage - allows basic changes, but no moving or renaming (good for sub-editors who manage content)
        // AddPage - new pages can be added (further checks may limit it to certain page types)
        // DeletePage - page can be renamed, moved and deleted
        if (!empty($data['pages'])) {
            // Bring in the access property for security checks
            /** @var \AccessProperty $accessproperty */
            $accessproperty = $this->prop()->getProperty(['name' => 'access']);
            $accessproperty->module = 'publications';
            $accessproperty->component = 'Page';
            foreach ($data['pages'] as $key => $page) {
                $thisinstance = $page['name'] . ':' . $page['pubtype_name'];

                // Do we have admin access?
                $args = [
                    'instance' => $thisinstance,
                    'level' => 800,
                ];
                $adminaccess = $accessproperty->check($args);

                // Decide whether this page can be modified by the current user
                /*try {
                    $args = array(
                        'instance' => $thisinstance,
                        'group' => $page['access']['modify_access']['group'],
                        'level' => $page['access']['modify_access']['level'],
                    );
                } catch (Exception $e) {
                    $args = array();
                }*/
                $data['pages'][$key]['edit_allowed'] = $adminaccess || $accessproperty->check($args);
                /*
                // Decide whether this page can be deleted by the current user
               try {
                    $args = array(
                        'instance' => $thisinstance,
                        'group' => $page['access']['delete_access']['group'],
                        'level' => $page['access']['delete_access']['level'],
                    );
                } catch (Exception $e) {
                    $args = array();
                }*/
                $data['pages'][$key]['delete_allowed'] = $adminaccess ||  $accessproperty->check($args);
            }
        }

        // Flag this as the current list view
        $this->session()->setVar('publications_current_listview', $this->ctl()->getCurrentURL());

        return $data;
    }
}

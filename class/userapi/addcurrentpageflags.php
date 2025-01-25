<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\UserApi;


use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * publications userapi addcurrentpageflags function
 * @extends MethodClass<UserApi>
 */
class AddcurrentpageflagsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see UserApi::addcurrentpageflags()
     */

    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($pagedata) || empty($id)) {
            return [];
        }

        $targetpagekey = false;
        foreach ($pagedata['pages'] as $key => $page) {
            if ($page['id'] == $id) {
                $targetpagekey = true;
                break;
            }
        }
        if (!$targetpagekey) {
            return [];
        }

        if (empty($root_ids) || !is_array($root_ids)) {
            $root_ids = [];
        }

        // Set up a bunch of flags against pages to allow hierarchical menus
        // to be generated. We do not want to make any assumptions here as to
        // how the menus will look and function (i.e. what will be displayed,
        // what will be suppressed, hidden etc) but rather just provide flags
        // that allow a template to build a menu of its choice.
        //
        // The basic flags are:
        // 'depth' - 0 for root, counting up for each subsequent level
        // 'is_ancestor' - flag indicates an ancestor of the current page
        // 'is_child' - flag indicates a child of the current page
        // 'is_sibling' - flag indicates a sibling of the current page
        // 'is_ancestor_sibling' - flag indicates a sibling of an ancestor of the current page
        // 'is_current' - flag indicates the current page
        // 'is_root' - flag indicates the page is a root page of the hierarchy - good
        //      starting point for menus
        // 'has_children' - flag indicates a page has children [done in getpagestree]
        //
        // Any page will have a depth flag, and may have one or more of the
        // remaining flags.
        // NOTE: with the exception of the following, all the above flags are
        // set in previous loops.

        // Point the current page at the page in the tree.
        $pagedata['current_page'] = & $pagedata['pages'][$key];

        // Create an ancestors array.
        // Shift the pages onto the start of the array, so the resultant array
        // is in order furthest ancestor towards the current page.
        // The ancestors array includes the current page.
        // TODO: stop at a non-ACTIVE page. Non-ACTIVE pages act as blockers
        // in the hierarchy.
        // Ancestors will include self - filter out in the template if required.
        $pagedata['ancestors'] = [];
        $this_id = $key;

        // TODO: allow a 'virtual root' to stop before we reach the real root page. Used
        // when we are filtering lower sections of a tree. Physically remove pages that
        // do not fall into this range.
        // This *could* happen if a root page is set to INACTIVE and a child page is
        // set as a module alias.
        $ancestor_ids = [];
        while (true) {
            // Set flag for menus.
            $pagedata['pages'][$this_id]['is_ancestor'] = true;

            // Record the id, so we don't accidently include this page again.
            array_unshift($ancestor_ids, $this_id);

            // Reference the page. Note we are working back down the tree
            // towards the root page, so will unshift each page to the front
            // of the ancestors array.
            array_unshift($pagedata['ancestors'], null);
            $pagedata['ancestors'][0] = & $pagedata['pages'][$this_id];

            // Get the parent page.
            try {
                $id_ancestor = $pagedata['pages'][$this_id]['parentpage_id'];
            } catch (Exception $e) {
                $id_ancestor = 0;
            }

            // If there is no parent, then stop.
            // Likewise if this is a page we have already seen (infinite loop protection).
            if ($id_ancestor == 0 || in_array($id_ancestor, $ancestor_ids) || in_array($this_id, $root_ids)) {
                // Make a note of the final root page.
                $root_id = $this_id;

                // Since we have reached the 'root' page for the purposes
                // of this ancestry, make sure this root page has no parents
                // by resetting any parent links.
                $pagedata['pages'][$this_id]['parentpage_id'] = 0;

                // Reference the root page in the main structure.
                $pagedata['root_page'] = & $pagedata['pages'][$root_id];

                // Finished the loop.
                break;
            }

            // Move to the parent page and loop.
            $this_id = $id_ancestor;
        }

        // Create a 'children' array for children of the current page.
        $pagedata['children'] = [];
        if (!empty($pagedata['current_page']['child_keys'])) {
            foreach ($pagedata['current_page']['child_keys'] as $key => $child) {
                // Set flag for menus. The flag 'is_child' means the page is a
                // child of the 'current' page.
                $pagedata['pages'][$key]['is_child'] = true;
                // Reference the child page from the children array.
                $pagedata['children'][$key] = & $pagedata['pages'][$child];
            }
        }

        // TODO: create a 'siblings' array.
        // Siblings are the children of the current page parent.
        // The root page will have no siblings, as we want to keep this in
        // a single tree.
        // Siblings will include self - filter out in the template if necessary.
        $pagedata['siblings'] = [];
        if (!empty($pagedata['current_page']['parentpage_id']) && isset($pagedata['pages'][$pagedata['current_page']['parentpage_id']]['child_keys'])) {
            // Loop though all children of the parent.
            foreach ($pagedata['pages'][$pagedata['current_page']['parentpage_id']]['child_keys'] as $key => $child) {
                // Set flag for menus.
                $pagedata['pages'][$key]['is_sibling'] = true;
                // Reference the page.
                $pagedata['siblings'][$key] = & $pagedata['pages'][$child];
            }
        }

        // Go through each ancestor and flag up the siblings of those ancestors.
        // They will be all pages that are children of the ancestors, assuming the
        // root ancestor does not have any siblings.
        foreach ($pagedata['ancestors'] as $key => $value) {
            if (isset($value['child_keys']) && is_array($value['child_keys'])) {
                foreach ($value['child_keys'] as $value2) {
                    $pagedata['pages'][$value2]['is_ancestor_sibling'] = true;
                }
            }
        }

        $pagedata['id'] = $this_id;
        $pagedata['pages'][$this_id]['is_current'] = true;

        return $pagedata;
    }
}

<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\UserGui;


use Xaraya\Modules\Publications\UserGui;
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;
use xarVar;
use xarController;
use xarCoreCache;
use xarModVars;
use xarTpl;
use xarMod;
use DataObjectFactory;
use DataPropertyMaster;
use XarayaCompiler;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * publications user preview function
 * @extends MethodClass<UserGui>
 */
class PreviewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see UserGui::preview()
     */

    public function __invoke(array $data = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->var()->find('layout', $layout, 'str:1', 'detail')) {
            return;
        }

        // Override xarVar::fetch
        extract($data);

        if (empty($data['object'])) {
            return $this->ctl()->notFound();
        }

        # --------------------------------------------------------
        #
        # We have an object, now get the page
        #
        // Here we get the publication type first, and then from that the page
        // Perhaps more efficient to get the page directly?
        $ptid = $data['object']->properties['itemtype']->value;

        // An empty publication type means the page does not exist
        if (empty($ptid)) {
            return $this->ctl()->notFound();
        }

        $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $ptid]);
        // Save this as the current pubtype
        $this->var()->setCached('Publications', 'current_pubtype_object', $pubtypeobject);

        # --------------------------------------------------------
        #
        # Are we allowed to see this page?
        #
        $accessconstraints = unserialize($data['object']->properties['access']->value);
        /** @var \AccessProperty $access */
        $access = $this->prop()->getProperty(['name' => 'access']);
        $allow = $access->check($accessconstraints['display']);
        $nopublish = (time() < $data['object']->properties['start_date']->value) || ((time() > $data['object']->properties['end_date']->value) && !$data['object']->properties['no_end']->value);

        // If no access, then bail showing a forbidden or the "no permission" page or an empty page
        $nopermissionpage_id = $this->mod()->getVar('noprivspage');
        if (!$allow || $nopublish) {
            if ($accessconstraints['display']['failure']) {
                return $this->ctl()->forbidden();
            } elseif ($nopermissionpage_id) {
                $this->ctl()->redirect($this->mod()->getURL(
                    'user',
                    'display',
                    ['itemid' => $nopermissionpage_id]
                ));
            } else {
                $data = ['context' => $this->getContext()];
                return $this->mod()->template('empty', $data);
            }
        }

        // If we use process states, then also check that
        if ($this->mod()->getVar('use_process_states')) {
            if ($data['object']->properties['process_state']->value < 3) {
                if ($accessconstraints['display']['failure']) {
                    return $this->ctl()->forbidden();
                } elseif ($nopermissionpage_id) {
                    $this->ctl()->redirect($this->mod()->getURL(
                        'user',
                        'display',
                        ['itemid' => $nopermissionpage_id]
                    ));
                } else {
                    $data = ['context' => $this->getContext()];
                    return $this->mod()->template('empty', $data);
                }
            }
        }

        # --------------------------------------------------------
        #
        # If this is a blocklayout page, then process it
        #
        if ($data['object']->properties['pagetype']->value == 2) {
            // Get a copy of the compiler
            sys::import('xaraya.templating.compiler');
            $blCompiler = XarayaCompiler::instance();

            // Get the data fields
            $fields = [];
            $sourcefields = ['title','description','summary','body1','body2','body3','body4','body5','notes'];
            $prefix = strlen('publications.') - 1;
            foreach ($data['object']->properties as $prop) {
                if (in_array(substr($prop->source, $prefix), $sourcefields)) {
                    $fields[] = $prop->name;
                }
            }

            // Run each template field through the compiler
            foreach ($fields as $field) {
                try {
                    $tplString  = '<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">';
                    $tplString .= $userapi->prepareforbl(['string' => $data['object']->properties[$field]->value]);

                    $tplString .= '</xar:template>';

                    $tplString = $blCompiler->compilestring($tplString);
                    // We don't allow passing $data to the template for now
                    $tpldata = [];
                    $tplString = xarTpl::string($tplString, $tpldata);
                } catch (Exception $e) {
                    var_dump($tplString);
                }
                $data['object']->properties[$field]->value = $tplString;
            }
        }

        # --------------------------------------------------------
        #
        # Get the complete tree for this section of pages. We need this for blocks etc.
        #
        /*
            $tree = $userapi->getpagestree(array(
                    'tree_contains_pid' => $id,
                    'key' => 'id',
                    'status' => 'ACTIVE,FRONTPAGE,PLACEHOLDER'
                )
            );

            // If this page is of type PLACEHOLDER, then look in its descendents
            if ($data['object']->properties['state']->value == 5) {

                // Scan for a descendent that is ACTIVE or FRONTPAGE
                if (!empty($tree['pages'][$id]['child_keys'])) {
                    foreach($tree['pages'][$id]['child_keys'] as $scan_key) {
                        // If the page is displayable, then treat it as the new page.
                        if ($tree['pages'][$scan_key]['status'] == 3 || $tree['pages'][$scan_key]['status'] == 4) {
                            $id = $tree['pages'][$scan_key]['id'];
                            $id = $userapi->gettranslationid(array('id' => $id));
                            $itemid = $data['object']->getItem(array('itemid' => $id));
                            break;
                        }
                    }
                }
            }
        */
        # --------------------------------------------------------
        #
        # Additional data
        #
        // Pass the layout to the template
        $data['layout'] = $layout;

        // Get the settings for this publication type
        $data['settings'] = $userapi->getsettings(['ptid' => $ptid]);

        // The name of this object
        $data['objectname'] = $data['object']->name;

        # --------------------------------------------------------
        #
        # Set the theme if needed
        #
        if (!empty($data['object']->properties['theme']->value)) {
            xarTpl::setThemeName($data['object']->properties['theme']->value);
        }

        # --------------------------------------------------------
        #
        # Set the page template from the pubtype if needed
        #
        if (!empty($pubtypeobject->properties['page_template']->value)) {
            $pagename = $pubtypeobject->properties['page_template']->value;
            $position = strpos($pagename, '.');
            if ($position === false) {
                $pagetemplate = $pagename;
            } else {
                $pagetemplate = substr($pagename, 0, $position);
            }
            $this->tpl()->setPageTemplateName($pagetemplate);
        }
        // It can be overridden by the page itself
        if (!empty($data['object']->properties['page_template']->value)) {
            $pagename = $data['object']->properties['page_template']->value;
            $position = strpos($pagename, '.');
            if ($position === false) {
                $pagetemplate = $pagename;
            } else {
                $pagetemplate = substr($pagename, 0, $position);
            }
            $this->tpl()->setPageTemplateName($pagetemplate);
        }

        # --------------------------------------------------------
        #
        # Do the same for page title, page description and keywords
        # The values (if any) are then passed to the meta tag in the template
        #
        // Page title
        if (!empty($pubtypeobject->properties['page_title']->value)) {
            $data['page_title'] = $pubtypeobject->properties['page_title']->value;
        }
        // It can be overridden by the page itself
        if (!empty($data['object']->properties['page_template']->value)) {
            $data['page_title'] = $data['object']->properties['page_title']->value;
        }

        // Page description
        if (!empty($pubtypeobject->properties['page_description']->value)) {
            $data['page_description'] = $pubtypeobject->properties['page_description']->value;
        }
        // It can be overridden by the page itself
        if (!empty($data['object']->properties['page_description']->value)) {
            $data['page_description'] = $data['object']->properties['page_description']->value;
        }

        // Page keywords
        if (!empty($pubtypeobject->properties['keywords']->value)) {
            $data['keywords'] = $pubtypeobject->properties['keywords']->value;
        }
        // It can be overridden by the page itself
        if (!empty($data['object']->properties['keywords']->value)) {
            $data['keywords'] = $data['object']->properties['keywords']->value;
        }
        # --------------------------------------------------------
        #
        # Cache data for blocks
        #
        // Now we can cache all this data away for the blocks.
        // The blocks should have access to most of the same data as the page.
        //    $this->var()->setCached('Blocks.publications', 'pagedata', $tree);

        // The 'serialize' hack ensures we have a proper copy of the
        // paga data, which is a self-referencing array. If we don't
        // do this, then any changes we make will affect the stored version.
        $data = unserialize(serialize($data));

        // Save some values. These are used by blocks in 'automatic' mode.
        //    $this->var()->setCached('Blocks.publications', 'current_id', $id);
        $this->var()->setCached('Blocks.publications', 'ptid', $ptid);
        $this->var()->setCached('Blocks.publications', 'author', $data['object']->properties['author']->value);

        # --------------------------------------------------------
        #
        # Make the properties available to the template
        #
        $data['properties'] = & $data['object']->properties;
        # --------------------------------------------------------
        #
        # Tell the template(s) that this is a preview
        #
        $data['preview'] = 1;

        $data['context'] ??= $this->getContext();
        return $this->mod()->template('display', $data);
    }
}

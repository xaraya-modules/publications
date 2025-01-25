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

use Xaraya\Modules\Publications\Defines;
use Xaraya\Modules\Publications\UserGui;
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\Publications\AdminApi;
use Xaraya\Modules\MethodClass;
use xarVar;
use xarModVars;
use xarCoreCache;
use xarMod;
use xarController;
use xarTpl;
use xarServer;
use xarRequest;
use xarRouter;
use xarDispatcher;
use DataObjectFactory;
use DataPropertyMaster;
use XarayaCompiler;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * publications user display function
 * @extends MethodClass<UserGui>
 */
class DisplayMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see UserGui::display()
     */

    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Get parameters from user
        // this is used to determine whether we come from a pubtype-based view or a
        // categories-based navigation
        // Note we support both id and itemid
        if (!$this->var()->find('name', $name, 'str', '')) {
            return;
        }
        if (!$this->var()->check('ptid', $ptid, 'id')) {
            return;
        }
        if (!$this->var()->find('itemid', $itemid, 'id')) {
            return;
        }
        if (!$this->var()->find('id', $id, 'id')) {
            return;
        }
        if (!$this->var()->find('page', $page, 'int:1')) {
            return;
        }
        if (!$this->var()->find('translate', $translate, 'int:1', 1)) {
            return;
        }
        if (!$this->var()->find('layout', $layout, 'str:1', 'detail')) {
            return;
        }

        // Override xarVar::fetch
        extract($args);

        // The itemid var takes precedence if it exiata
        if (isset($itemid)) {
            $id = $itemid;
        }

        # --------------------------------------------------------
        #
        # If no ID supplied, try getting the id of the default page.
        #
        if (empty($id)) {
            $id = $this->mod()->getVar('defaultpage');
        }

        # --------------------------------------------------------
        #
        # Get the ID of the translation if required
        #
        // First save the "untranslated" id for blocks etc.
        $this->var()->setCached('Blocks.publications', 'current_base_id', $id);

        if ($translate) {
            $id = $userapi->gettranslationid(['id' => $id]);
            /*
            $newid = $userapi->gettranslationid(array('id' => $id));
            if ($newid != $id) {
                // We do a full redirect rather than just continuing with the new id so that
                // anything working off the itemid of the page to be displayed will automatically
                // use the new one
                $this->ctl()->redirect($this->mod()->getURL( 'user', 'display',
                    array('itemid' => $newid, 'translate' => 0)));
            }
            */
        }

        # --------------------------------------------------------
        #
        # If still no ID, check if we are trying to display a pubtype
        #
        if (empty($name) && empty($ptid) && empty($id)) {
            // Nothing to be done
            $id = $this->mod()->getVar('notfoundpage');
        } elseif (empty($id)) {
            // We're missing an id but can get a pubtype: jump to the pubtype view
            $this->ctl()->redirect($this->mod()->getURL('user', 'view'));
        }

        # --------------------------------------------------------
        #
        # If still no ID, we have come to the end of the line
        #
        if (empty($id)) {
            return $this->ctl()->notFound();
        }

        # --------------------------------------------------------
        #
        # We have an ID, now first get the page
        #
        // Here we get the publication type first, and then from that the page
        // Perhaps more efficient to get the page directly?
        $ptid = $userapi->getitempubtype(['itemid' => $id]);

        // An empty publication type means the page does not exist
        if (empty($ptid)) {
            return $this->ctl()->notFound();
        }

        $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $ptid]);

        // A non-active publication type means the page does not exist
        if ($pubtypeobject->properties['state']->value < Defines::STATE_ACTIVE) {
            return $this->ctl()->notFound();
        }

        // Save this as the current pubtype
        $this->var()->setCached('Publications', 'current_pubtype_object', $pubtypeobject);

        $data['object'] = $this->data()->getObject(['name' => $pubtypeobject->properties['name']->value]);
        //    $id = $userapi->gettranslationid(array('id' => $id));
        $itemid = $data['object']->getItem(['itemid' => $id]);

        # --------------------------------------------------------
        #
        # Are we allowed to see this page?
        #
        $accessconstraints = $adminapi->getpageaccessconstraints(['property' => $data['object']->properties['access']]);
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
        # If this is a redirect page, then send it on its way now
        #
        $redirect_type = $data['object']->properties['redirect_flag']->value;
        if ($redirect_type == 1) {
            // This is a simple redirect to another page
            $url = $data['object']->properties['redirect_url']->value;
            if (empty($url)) {
                return $this->ctl()->notFound();
            }
            try {
                // Check if this is a Xaraya function
                $pos = strpos($url, 'xar');
                if ($pos === 0) {
                    eval('$url = ' . $url . ';');
                }
                $this->ctl()->redirect($url, 301);
            } catch (Exception $e) {
                return $this->ctl()->notFound();
            }
        } elseif ($redirect_type == 2) {
            // This displays a page of a different module
            // If this is from a link of a redirect child page, use the child param as new URL
            if (!$this->var()->find('child', $child, 'str')) {
                return;
            }
            if (!empty($child)) {
                // This page was submitted
                // Turn entities into amps
                $url = urldecode($child);
            } else {
                // Get the proxy URL to redirect to
                $url = $data['object']->properties['proxy_url']->value;
            }

            // Bail if the URL is bad
            if (empty($url)) {
                return $this->ctl()->notFound();
            }
            try {
                // Check if this is a Xaraya function
                $pos = strpos($url, 'xar');
                if ($pos === 0) {
                    eval('$url = ' . $url . ';');
                }

                // Parse the URL to get host and port
                // we can use a simple parse_url() in this case
                $params = parse_url($url);
            } catch (Exception $e) {
                return $this->ctl()->notFound();
            }

            // If this is an external link, show it without further processing
            if (!empty($params['host']) && $params['host'] != xarServer::getHost() && $params['host'] . ":" . $params['port'] != xarServer::getHost()) {
                $this->ctl()->redirect($url, 301);
            } elseif (strpos($this->ctl()->getCurrentURL(), $url) === 0) {
                // CHECKME: is this robust enough?
                // Redirect to avoid recursion if $url is already our present URL
                $this->ctl()->redirect($url, 301);
            } else {
                // This is a local URL. We need to parse it, but parse_url is no longer good enough here
                $request = new xarRequest($url);
                $router = new xarRouter();
                $router->route($request);
                $request->setRoute($router->getRoute());
                $dispatcher = new xarDispatcher();
                /** @var \iController $controller */
                $controller = $dispatcher->findController($request);
                $controller->actionstring = $request->getActionString();
                $args = $controller->decode() + $request->getFunctionArgs();
                $controller->chargeRequest($request, $args);

                try {
                    $page = xarMod::guiFunc($request->getModule(), 'user', $request->getFunction(), $request->getFunctionArgs());
                } catch (Exception $e) {
                    return $this->ctl()->notFound();
                }

                // Debug
                // echo $this->ctl()->getModuleURL($info['module'],'user',$info['func'],$other_params);
                # --------------------------------------------------------
                #
                # For proxy pages: the transform of the subordinate function's template
                #
                // Find the URLs in submits
                $pattern = '/(action)="([^"\r\n]*)"/';
                preg_match_all($pattern, $page, $matches);
                $pattern = [];
                $replace = [];
                foreach ($matches[2] as $match) {
                    $pattern[] = '%</form%';
                    $replace[] = '<input type="hidden" name="return_url" id="return_url" value="' . urlencode($this->ctl()->getCurrentURL()) . '"/><input type="hidden" name="child" value="' . urlencode($match) . '"/></form';
                }
                $page = preg_replace($pattern, $replace, $page);

                // We don't really want to replace the submits, do we?
                /*
                $pattern='/(action)="([^"\r\n]*)"/';
                $page = preg_replace_callback($pattern,
                    function($matches) {
                        return $matches[1] = 'action="' . $this->ctl()->getCurrentURL() . '"';
                    },
                    $page
                );
                */

                // Find the URLs in links
                $pattern = '/(href)="([^"\r\n]*)"/';
                $page = preg_replace_callback(
                    $pattern,
                    function ($matches) {
                        return $matches[1] = $this->ctl()->getCurrentURL(["child" => urlencode($matches[2])]);
                    },
                    $page
                );

                return $page;
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

        // Pass the access rules of the publication type to the template
        $data['pubtype_access'] = $pubtypeobject->properties['access']->getValue();
        $this->var()->setCached('Publications', 'pubtype_access', $data['pubtype_access']);

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
        $pagename = $pubtypeobject->properties['page_template']->value;
        if (!empty($pagename)  && ($pagename != 'admin.xt')) {
            $position = strpos($pagename, '.');
            if ($position === false) {
                $pagetemplate = $pagename;
            } else {
                $pagetemplate = substr($pagename, 0, $position);
            }
            $this->tpl()->setPageTemplateName($pagetemplate);
        }
        // It can be overridden by the page itself
        $pagename = $data['object']->properties['page_template']->value;
        if (!empty($pagename)  && ($pagename != 'admin.xt')) {
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
        if (!empty($data['object']->properties['page_title']->value)) {
            $data['page_title'] = $data['object']->properties['page_title']->value;
        }
        // If nothing then the setting from the themes module will be used, so we pass this page's title
        if (empty($data['page_title'])) {
            $data['page_title'] = $data['object']->properties['title']->value;
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
        $this->var()->setCached('Blocks.publications', 'current_id', $id);
        $this->var()->setCached('Blocks.publications', 'ptid', $ptid);
        $this->var()->setCached('Blocks.publications', 'author', $data['object']->properties['author']->value);

        # --------------------------------------------------------
        #
        # Make the properties available to the template
        #
        $data['properties'] = & $data['object']->properties;

        # --------------------------------------------------------
        #
        # Get information on next and previous items
        #
        if ($data['settings']['show_prevnext']) {
            $prevpublication = $userapi->getprevious(['id' => $itemid,
                    'ptid' => $data['object']->properties['itemtype']->value,
                    'sort' => 'title',]
            );
            $nextpublication = $userapi->getnext(['id' => $itemid,
                    'ptid' => $data['object']->properties['itemtype']->value,
                    'sort' => 'title',]
            );
        } else {
            $prevpublication = '';
            $nextpublication = '';
        }
        $this->var()->setCached('Publications', 'prevpublication', $prevpublication);
        $this->var()->setCached('Publications', 'nextpublication', $nextpublication);

        return $data;
    }
}

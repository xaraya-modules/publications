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

use Xaraya\Modules\Publications\Defines;
use Xaraya\Modules\Publications\AdminGui;
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;
use xarTplPager;
use XarayaCompiler;
use Exception;

/**
 * publications admin display function
 * @extends MethodClass<AdminGui>
 */
class DisplayMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Summary of __invoke
     * @param array<string, mixed> $args
     * @see AdminGui::display()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->sec()->checkAccess('EditPublications')) {
            return;
        }

        // Get parameters from user
        // this is used to determine whether we come from a pubtype-based view or a
        // categories-based navigation
        // Note we support both id and itemid
        $this->var()->find('name', $name, 'str', '');
        $this->var()->check('ptid', $ptid, 'id');
        $this->var()->find('itemid', $itemid, 'id');
        $this->var()->find('id', $id, 'id');
        $this->var()->find('page', $page, 'int:1');
        $this->var()->find('translate', $translate, 'int:1', 1);
        $this->var()->find('layout', $layout, 'str:1', 'detail');

        // Override xar::var()->fetch
        extract($args);

        /** @var AdminGui $admingui */
        $admingui = $this->admingui();

        //The itemid var takes precedence if it exiata
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
        // First save the "untranslated" id
        $this->mem()->set('Blocks.publications', 'current_base_id', $id);

        if ($translate) {
            $id = $userapi->gettranslationid(['id' => $id]);
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
            return true;
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

        /*    if (empty($name) && empty($ptid)) return $this->ctl()->notFound();

            if(empty($ptid)) {
                $publication_type = $this->data()->getObjectList(array('name' => 'publications_types'));
                $where = 'name = ' . $name;
                $items = $publication_type->getItems(array('where' => $where));
                $item = current($items);
                $ptid = $item['id'];
            }
        */
        $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $ptid]);
        $data['object'] = $this->data()->getObject(['name' => $pubtypeobject->properties['name']->value]);
        //    $id = $userapi->gettranslationid(array('id' => $id));
        $itemid = $data['object']->getItem(['itemid' => $id]);

        # --------------------------------------------------------
        #
        # Are we allowed to see this page?
        #
        $accessconstraints = unserialize($data['object']->properties['access']->value);
        /** @var \AccessProperty $access */
        $access = $this->prop()->getProperty(['name' => 'access']);
        $data['allow'] = $access->check($accessconstraints['display']);
        $nopublish = (time() < $data['object']->properties['start_date']->value) || ((time() > $data['object']->properties['end_date']->value) && !$data['object']->properties['no_end']->value);

        // If no access, then bail showing a forbidden or an empty page
        if (!$data['allow'] || $nopublish) {
            if ($accessconstraints['display']['failure']) {
                return $this->ctl()->forbidden();
            } else {
                return $data;
            }
        }

        # --------------------------------------------------------
        #
        # If this is a redirect page, then send it on its way now
        #
        $redirect_type = $data['object']->properties['redirect_flag']->value;
        if ($redirect_type == 1) {
            // This is a simple redirect to another page
            try {
                $url = $data['object']->properties['redirect_url']->value;

                // Check if this is a Xaraya function
                $pos = strpos($url, 'xar');
                if ($pos === 0) {
                    eval('$url = ' . $url . ';');
                }

                $this->ctl()->redirect($url, 301);
                return true;
            } catch (Exception $e) {
                return $this->ctl()->notFound();
            }
        } elseif ($redirect_type == 2) {
            // This displays a page of a different module
            // If this is from a link of a redirect child page, use the child param as new URL
            $this->var()->find('child', $child, 'str');
            if (!empty($child)) {
                // Turn entities into amps
                $url = urldecode($child);
            } else {
                $url = $data['object']->properties['proxy_url']->value;
            }

            // Bail if the URL is bad
            try {
                // Check if this is a Xaraya function
                $pos = strpos($url, 'xar');
                if ($pos === 0) {
                    eval('$url = ' . $url . ';');
                }

                $params = parse_url($url);
                $params['query'] = preg_replace('/&amp;/', '&', $params['query']);
            } catch (Exception $e) {
                return $this->ctl()->notFound();
            }

            $baseurl = $this->ctl()->getBaseURL();
            $parsed = parse_url($baseurl);
            // If this is an external link, show it without further processing
            if (!empty($params['host']) && $params['host'] != $parsed['host'] && $params['port'] != $parsed['port']) {
                $this->ctl()->redirect($url, 301);
                return true;
            } else {
                parse_str($params['query'], $info);
                $other_params = $info;
                unset($other_params['module']);
                unset($other_params['type']);
                unset($other_params['func']);
                unset($other_params['child']);
                try {
                    $page = $this->mod()->guiFunc($info['module'], 'user', $info['func'], $other_params);
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

                $pattern = '/(action)="([^"\r\n]*)"/';
                $page = preg_replace_callback(
                    $pattern,
                    function ($matches) {
                        return $matches[1] = "\"" . $this->ctl()->getCurrentURL() . "\"";
                    },
                    $page
                );

                // Find the URLs in links
                $pattern = '/(href)="([^"\r\n]*)"/';
                $page = preg_replace_callback(
                    $pattern,
                    function ($matches) {
                        return $matches[1] = "\"" . $this->ctl()->getCurrentURL(["child" => urlencode($matches[2])]) . "\"";
                    },
                    $page
                );

                return $page;
            }
        }

        # --------------------------------------------------------
        #
        # If this is a bloccklayout page, then process it
        #

        if ($data['object']->properties['pagetype']->value == 2) {
            // Get a copy of the compiler
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
                    $tplString = $this->tpl()->string($tplString, $tpldata);
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

        $tree = $userapi->getpagestree(
            [
                'tree_contains_pid' => $id,
                'key' => 'id',
                'status' => 'ACTIVE,FRONTPAGE,PLACEHOLDER',
            ]
        );

        // If this page is of type PLACEHOLDER, then look in its descendents
        if ($data['object']->properties['state']->value == 5) {
            // Scan for a descendent that is ACTIVE or FRONTPAGE
            if (!empty($tree['pages'][$id]['child_keys'])) {
                foreach ($tree['pages'][$id]['child_keys'] as $scan_key) {
                    // If the page is displayable, then treat it as the new page.
                    if ($tree['pages'][$scan_key]['status'] == 3 || $tree['pages'][$scan_key]['status'] == 4) {
                        $id = $tree['pages'][$scan_key]['id'];
                        $id = $userapi->gettranslationid(['id' => $id]);
                        $itemid = $data['object']->getItem(['itemid' => $id]);
                        break;
                    }
                }
            }
        }

        # --------------------------------------------------------
        #
        # Additional data
        #
        // Pass the layout to the template
        $data['layout'] = $layout;

        // Get the settings for this publication type;
        $data['settings'] = $userapi->getsettings(['ptid' => $ptid]);

        // The name of this object
        $data['objectname'] = $data['object']->name;

        # --------------------------------------------------------
        #
        # Set the theme if needed
        #
        if (!empty($data['object']->properties['theme']->value)) {
            $this->tpl()->setThemeName($data['object']->properties['theme']->value);
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
        # Cache data for blocks
        #
        // Now we can cache all this data away for the blocks.
        // The blocks should have access to most of the same data as the page.
        $this->mem()->set('Blocks.publications', 'pagedata', $tree);

        // The 'serialize' hack ensures we have a proper copy of the
        // paga data, which is a self-referencing array. If we don't
        // do this, then any changes we make will affect the stored version.
        $data = unserialize(serialize($data));

        // Save some values. These are used by blocks in 'automatic' mode.
        $this->mem()->set('Blocks.publications', 'current_id', $id);
        $this->mem()->set('Blocks.publications', 'ptid', $ptid);
        $this->mem()->set('Blocks.publications', 'author', $data['object']->properties['author']->value);

        # --------------------------------------------------------
        #
        # Make the properties available to the template
        #
        $data['properties'] = & $data['object']->properties;

        # --------------------------------------------------------
        #
        # Get information on next and previous items
        #
        $data['prevpublication'] = $userapi->getnext(
            ['id' => $id,
                'ptid' => $ptid,
                'sort' => 'tree',]
        );
        $data['nextpublication'] = $userapi->getnext(
            ['id' => $id,
                'ptid' => $ptid,
                'sort' => 'tree',]
        );
        return $data;
    }

    protected function highlights(array $args = [])
    {
        /*
            // TEST - highlight search terms
            $this->var()->find('q', $q, 'str', NULL);
        */

        // Override if needed from argument array (e.g. preview)
        extract($args);

        // Defaults
        if (!isset($page)) {
            $page = 1;
        }

        // via arguments only
        if (!isset($preview)) {
            $preview = 0;
        }

        /*
            if ($preview) {
                if (!isset($publication)) {
                    return $this->ml('Invalid publication');
                }
                $id = $publication->properties['id']->value;
            } elseif (!isset($id) || !is_numeric($id) || $id < 1) {
                return $this->ml('Invalid publication ID');
            }
        */

        /*    // Get publication
            if (!$preview) {
                $publication = $userapi->get(array('id' => $id,
                                              'withcids' => true));
            }

            if (!is_array($publication)) {
                $msg = $this->ml('Failed to retrieve publication in #(3)_#(1)_#(2).php', 'userapi', 'get', 'publications');
                throw new DataNotFoundException(null, $msg);
            }
            // Get publication types
            $pubtypes = $userapi->get_pubtypes();

            // Check that the publication type is valid, otherwise use the publication's pubtype
            if (!empty($ptid) && !isset($pubtypes[$ptid])) {
                $ptid = $publication['pubtype_id'];
            }

        */
        // keep original ptid (if any)
        //    $ptid = $publication['pubtype_id'];
        //    $pubtype_id = $publication->properties['itemtype']->value;
        //    $owner = $publication->properties['author']->value;
        /*    if (!isset($publication['cids'])) {
                $publication['cids'] = array();
            }
            $cids = $publication['cids'];
        */
        // Get the publication settings for this publication type
        if (empty($ptid)) {
            $settings = unserialize($this->mod()->getVar('settings'));
        } else {
            $settings = unserialize($this->mod()->getVar('settings.' . $ptid));
        }

        // show the number of publications for each publication type
        if (!isset($show_pubcount)) {
            if (!isset($settings['show_pubcount']) || !empty($settings['show_pubcount'])) {
                $show_pubcount = 1; // default yes
            } else {
                $show_pubcount = 0;
            }
        }
        // show the number of publications for each category
        if (!isset($show_catcount)) {
            if (empty($settings['show_catcount'])) {
                $show_catcount = 0; // default no
            } else {
                $show_catcount = 1;
            }
        }

        // Initialize the data array
        $data = $publication->getFieldValues();
        $data['ptid'] = $ptid; // navigation pubtype
        $data['pubtype_id'] = $pubtype_id; // publication pubtype

        // TODO: improve the case where we have several icons :)
        $data['topic_icons'] = '';
        $data['topic_images'] = [];
        $data['topic_urls'] = [];
        $data['topic_names'] = [];
        /*
        if (count($cids) > 0) {
            if (!$this->mod()->apiLoad('categories', 'user')) return;
            $catlist = $this->mod()->apiFunc('categories',
                                    'user',
                                    'getcatinfo',
                                    array('cids' => $cids));
            foreach ($catlist as $cat) {
                $link = $this->mod()->getURL('user','view',
                                 array(//'state' => array(Defines::STATE_FRONTPAGE,Defines::STATE_APPROVED).
                                       'ptid' => $ptid,
                                       'catid' => $cat['cid']));
                $name = $this->prep()->text($cat['name']);

                $data['topic_urls'][] = $link;
                $data['topic_names'][] = $name;

                if (!empty($cat['image'])) {
                    $image = $this->tpl()->getImage($cat['image'], 'module', 'categories');
                    $data['topic_icons'] .= '<a href="'. $link .'">'.
                                            '<img src="'. $image .
                                            '" alt="'. $name .'" />'.
                                            '</a>';
                    $data['topic_images'][] = $image;

                    break;
                }
            }
        }
    */
        // multi-page output for 'body' field (mostly for sections at the moment)
        $themeName = $this->mem()->get('Themes.name', 'CurrentTheme');
        if ($themeName != 'print') {
            if (strstr($publication->properties['body']->value, '<!--pagebreak-->')) {
                if ($preview) {
                    $publication['body'] = preg_replace(
                        '/<!--pagebreak-->/',
                        '<hr/><div style="text-align: center;">' . $this->ml('Page Break') . '</div><hr/>',
                        $publication->properties['body']->value
                    );
                    $data['previous'] = '';
                    $data['next'] = '';
                } else {
                    $pages = explode('<!--pagebreak-->', $publication->properties['body']->value);

                    // For documents with many pages, the pages can be
                    // arranged in blocks.
                    $pageBlockSize = 10;

                    // Get pager information: one item per page.
                    $pagerinfo = xarTplPager::getInfo((empty($page) ? 1 : $page), count($pages), 1, $pageBlockSize);

                    // Retrieve current page and total pages from the pager info.
                    // These will have been normalised to ensure they are in range.
                    $page = $pagerinfo['currentpage'];
                    $numpages = $pagerinfo['totalpages'];

                    // Discard everything but the current page.
                    $publication['body'] = $pages[$page - 1];
                    unset($pages);

                    if ($page > 1) {
                        // Don't count page hits after the first page.
                        $this->mem()->set('Hooks.hitcount', 'nocount', 1);
                    }

                    // Pass in the pager info so a complete custom pager
                    // can be created in the template if required.
                    $data['pagerinfo'] = $pagerinfo;

                    // Get the rendered pager.
                    // The pager template (last parameter) could be an
                    // option for the publication type.
                    $urlmask = $this->mod()->getURL(
                        'user',
                        'display',
                        ['ptid' => $ptid, 'id' => $id, 'page' => '%%']
                    );
                    $data['pager'] = $this->tpl()->getPager(
                        $page,
                        $numpages,
                        $urlmask,
                        1,
                        $pageBlockSize,
                        'multipage'
                    );

                    // Next two assignments for legacy templates.
                    // TODO: deprecate them?
                    $data['next'] = $this->tpl()->getPager(
                        $page,
                        $numpages,
                        $urlmask,
                        1,
                        $pageBlockSize,
                        'multipagenext'
                    );
                    $data['previous'] = $this->tpl()->getPager(
                        $page,
                        $numpages,
                        $urlmask,
                        1,
                        $pageBlockSize,
                        'multipageprev'
                    );
                }
            } else {
                $data['previous'] = '';
                $data['next'] = '';
            }
        } else {
            $publication['body'] = preg_replace(
                '/<!--pagebreak-->/',
                '',
                $publication['body']
            );
        }

        // Display publication
        unset($publication);

        // temp. fix to include dynamic data fields without changing templates
        if ($this->mod()->isHooked('dynamicdata', 'publications', $pubtype_id)) {
            [$properties] = $this->mod()->apiFunc(
                'dynamicdata',
                'user',
                'getitemfordisplay',
                ['module'   => 'publications',
                    'itemtype' => $pubtype_id,
                    'itemid'   => $id,
                    'preview'  => $preview, ]
            );
            if (!empty($properties) && count($properties) > 0) {
                foreach (array_keys($properties) as $field) {
                    $data[$field] = $properties[$field]->getValue();
                    // POOR mans flagging for transform hooks
                    try {
                        $configuration = $properties[$field]->configuration;
                        if (substr($configuration, 0, 10) == 'transform:') {
                            $data['transform'][] = $field;
                        }
                    } catch (Exception $e) {
                    }
                    // TODO: clean up this temporary fix
                    $data[$field . '_output'] = $properties[$field]->showOutput();
                }
            }
        }

        // Let any transformation hooks know that we want to transform some text.
        // You'll need to specify the item id, and an array containing all the
        // pieces of text that you want to transform (e.g. for autolinks, wiki,
        // smilies, bbcode, ...).
        $data['itemtype'] = $pubtype_id;
        // TODO: what about transforming DDfields ?
        // <mrb> see above for a hack, needs to be a lot better.

        // Summary is always included, is that handled somewhere else? (publication config says i can ex/include it)
        // <mikespub> publications config allows you to call transforms for the publications summaries in the view function
        if (!isset($title_transform)) {
            if (empty($settings['title_transform'])) {
                $data['transform'][] = 'summary';
                $data['transform'][] = 'body';
                $data['transform'][] = 'notes';
            } else {
                $data['transform'][] = 'title';
                $data['transform'][] = 'summary';
                $data['transform'][] = 'body';
                $data['transform'][] = 'notes';
            }
        }
        $data = $this->mod()->callHooks('item', 'transform', $id, $data, 'publications');

        return $this->tpl()->module('publications', 'user', 'display', $data);


        if (!empty($data['title'])) {
            // CHECKME: <rabbit> Strip tags out of the title - the <title> tag shouldn't have any other tags in it.
            $title = strip_tags($data['title']);
            $this->tpl()->setPageTitle($this->prep()->text($title), $this->prep()->text($pubtypes[$data['itemtype']]['description']));

            // Save some variables to (temporary) cache for use in blocks etc.
            $this->mem()->set('Comments.title', 'title', $data['title']);
        }

        /*
            if (!empty($q)) {
            // TODO: split $q into search terms + add style (cfr. handlesearch in search module)
                foreach ($data['transform'] as $field) {
                    $data[$field] = preg_replace("/$q/","<span class=\"xar-search-match\">$q</span>",$data[$field]);
                }
            }
        */

        // Navigation links
        $data['publabel'] = $this->ml('Publication');
        $data['publinks'] = []; //$userapi->getpublinks(//    array('state' => array(Defines::STATE_FRONTPAGE,Defines::STATE_APPROVED),
        //          'count' => $show_pubcount));
        if (isset($show_map)) {
            $settings['show_map'] = $show_map;
        }
        if (!empty($settings['show_map'])) {
            $data['maplabel'] = $this->ml('View Publication Map');
            $data['maplink'] = $this->mod()->getURL(
                'user',
                'viewmap',
                ['ptid' => $ptid]
            );
        }
        if (isset($show_archives)) {
            $settings['show_archives'] = $show_archives;
        }
        if (!empty($settings['show_archives'])) {
            $data['archivelabel'] = $this->ml('View Archives');
            $data['archivelink'] = $this->mod()->getURL(
                'user',
                'archive',
                ['ptid' => $ptid]
            );
        }
        if (isset($show_publinks)) {
            $settings['show_publinks'] = $show_publinks;
        }
        if (!empty($settings['show_publinks'])) {
            $data['show_publinks'] = 1;
        } else {
            $data['show_publinks'] = 0;
        }
        $data['show_catcount'] = $show_catcount;

        // Tell the hitcount hook not to display the hitcount, but to save it
        // in the variable cache.
        if ($this->mod()->isHooked('hitcount', 'publications', $pubtype_id)) {
            $this->mem()->set('Hooks.hitcount', 'save', 1);
            $data['dohitcount'] = 1;
        } else {
            $data['dohitcount'] = 0;
        }

        // Tell the ratings hook to save the rating in the variable cache.
        if ($this->mod()->isHooked('ratings', 'publications', $pubtype_id)) {
            $this->mem()->set('Hooks.ratings', 'save', 1);
            $data['doratings'] = 1;
        } else {
            $data['doratings'] = 0;
        }


        // Retrieve the current hitcount from the variable cache
        if ($data['dohitcount'] && $this->mem()->has('Hooks.hitcount', 'value')) {
            $data['counter'] = $this->mem()->get('Hooks.hitcount', 'value');
        } else {
            $data['counter'] = '';
        }

        // Retrieve the current rating from the variable cache
        if ($data['doratings'] && $this->mem()->has('Hooks.ratings', 'value')) {
            $data['rating'] = intval($this->mem()->get('Hooks.ratings', 'value'));
        } else {
            $data['rating'] = '';
        }

        // Save some variables to (temporary) cache for use in blocks etc.
        $this->mem()->set('Blocks.publications', 'title', $data['title']);

        // Generating keywords from the API now instead of setting the entire
        // body into the cache.
        $keywords = $userapi->generatekeywords(
            ['incomingkey' => $data['body']]
        );

        $this->mem()->set('Blocks.publications', 'body', $keywords);
        $this->mem()->set('Blocks.publications', 'summary', $data['summary']);
        $this->mem()->set('Blocks.publications', 'id', $id);
        $this->mem()->set('Blocks.publications', 'ptid', $ptid);
        $this->mem()->set('Blocks.publications', 'cids', $cids);
        $this->mem()->set('Blocks.publications', 'owner', $owner);
        if (isset($data['author'])) {
            $this->mem()->set('Blocks.publications', 'author', $data['author']);
        }
        // TODO: add this to publications configuration ?
        //if ($shownavigation) {
        $data['id'] = $id;
        $data['cids'] = $cids;
        $this->mem()->set('Blocks.categories', 'module', 'publications');
        $this->mem()->set('Blocks.categories', 'itemtype', $ptid);
        $this->mem()->set('Blocks.categories', 'itemid', $id);
        $this->mem()->set('Blocks.categories', 'cids', $cids);

        if (!empty($ptid) && !empty($pubtypes[$ptid]['description'])) {
            $this->mem()->set('Blocks.categories', 'title', $pubtypes[$ptid]['description']);
        }

        // optional category count
        if ($show_catcount && !empty($ptid)) {
            $pubcatcount = $userapi->getpubcatcount(// frontpage or approved
                ['state' => [Defines::STATE_FRONTPAGE,Defines::STATE_APPROVED],
                    'ptid' => $ptid, ]
            );
            if (!empty($pubcatcount[$ptid])) {
                $this->mem()->set('Blocks.categories', 'catcount', $pubcatcount[$ptid]);
            }
        } else {
            //    $this->mem()->set('Blocks.categories','catcount',array());
        }
        //}

        // Module template depending on publication type
        $template = $pubtypes[$pubtype_id]['name'];

        // Page template depending on publication type (optional)
        // Note : this cannot be overridden in templates
        if (empty($preview) && !empty($settings['page_template'])) {
            $this->tpl()->setPageTemplateName($settings['page_template']);
        }

        // Specific layout within a template (optional)
        if (isset($layout)) {
            $data['layout'] = $layout;
        }

        $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $ptid]);
        $data['object'] = $this->data()->getObject(['name' => $pubtypeobject->properties['name']->value]);
        $id = $userapi->getranslationid(['id' => $id]);
        $data['object']->getItem(['itemid' => $id]);

        return $this->tpl()->module('publications', 'user', 'display', $data, $template);
    }
}

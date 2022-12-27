<?php
/**
 * Publications Module
 *
 * @package modules
 * @subpackage publications module
 * @category Third Party Xaraya Module
 * @version 2.0.0
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author mikespub
 */
/**
 * Original Author of file: Jim McDonald
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class Publications_NavigationBlock extends BasicBlock implements iBlock
{
    // File Information, supplied by developer, never changes during a versions lifetime, required
    protected $type             = 'navigation';
    protected $module           = 'publications'; // module block type belongs to, if any
    protected $text_type        = 'Navigation';  // Block type display name
    protected $text_type_long   = 'Show navigation for immediate child items'; // Block type description
    // Additional info, supplied by developer, optional
    protected $type_category    = 'block'; // options [(block)|group]
    protected $author           = '';
    protected $contact          = '';
    protected $credits          = '';
    protected $license          = '';

    // blocks subsystem flags
    protected $show_preview = true;  // let the subsystem know if it's ok to show a preview
    // @todo: drop the show_help flag, and go back to checking if help method is declared
    protected $show_help    = false; // let the subsystem know if this block type has a help() method

    public $layout = 1;
    public $show_catcount = 0;
    public $showchildren = 0;
    public $showempty = false;
    public $startmodule = '';
    public $dynamictitle = false;

    public function display()
    {
        $vars = $this->getContent();

        if (!empty($vars) && is_array($vars)) {
            extract($vars);
        }

        // Get requested layout
        if (empty($layout)) {
            $layout = 1; // default tree here
        }

        if (!empty($startmodule)) {
            // static behaviour
            [$module, $itemtype, $rootcid] = explode('.', $startmodule);
            if (empty($rootcid)) {
                $rootcids = null;
            } elseif (strpos($rootcid, ' ')) {
                $rootcids = explode(' ', $rootcid);
            } elseif (strpos($rootcid, '+')) {
                $rootcids = explode('+', $rootcid);
            } else {
                $rootcids = explode('-', $rootcid);
            }
        }

        // TODO: for multi-module pages, we'll need some other reference point(s)
//       (e.g. cross-module categories defined in categories admin ?)
        // Get current module
        if (empty($module)) {
            if (xarVar::isCached('Blocks.categories', 'module')) {
                $modname = xarCoreCache::getCached('Blocks.categories', 'module');
            }
            if (empty($modname)) {
                $modname = xarMod::getName();
            }
        } else {
            $modname = $module;
        }
        $modid = xarMod::getRegID($modname);
        if (empty($modid)) {
            return;
        }

        // Get current item type (if any)
        if (!isset($itemtype)) {
            if (xarVar::isCached('Blocks.categories', 'itemtype')) {
                $itemtype = xarCoreCache::getCached('Blocks.categories', 'itemtype');
            } else {
                // try to get itemtype from input
                xarVar::fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET);
                if (empty($itemtype)) {
                    xarVar::fetch('ptid', 'isset', $itemtype, null, xarVar::DONT_SET);
                }// if
            }
        }
        if (empty($itemtype)) {
            $itemtype = null;
        }

        // Get current item id (if any)
        if (!isset($itemid)) {
            if (xarVar::isCached('Blocks.categories', 'itemid')) {
                $itemid = xarCoreCache::getCached('Blocks.categories', 'itemid');
            } else {
                // try to get itemid from input
                xarVar::fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET);
                if (empty($itemid)) {
                    xarVar::fetch('id', 'isset', $itemid, null, xarVar::DONT_SET);
                }// if
            }
        }
        if (empty($itemid)) {
            $itemid = null;
        }

        if (isset($rootcids)) {
            $mastercids = $rootcids;
        } else {
            // Get number of categories for this module + item type

            $numcats = xarMod::apiFunc(
                'categories',
                'user',
                'countcatbases',
                [
                    'module'=>$modname,
                    'itemtype'=>(empty($itemtype) ? null : $itemtype),
                ]
            );

            if (empty($numcats)) {
                // no categories to show here -> return empty output
                return;
            }

            // Get master cids for this module + item type
            $mastercids = xarMod::apiFunc(
                'categories',
                'user',
                'getallcatbases',
                [
                    'module' => $modname,
                    'format' => 'cids',
                    'order' => 'cid',
                    'itemtype' => (empty($itemtype) ? null : $itemtype),
                ]
            );

            if (empty($mastercids)) {
                // no categories to show here -> return empty output
                return;
            }

            $mastercids = array_unique($mastercids);

            if (!empty($startmodule)) {
                $rootcids = $mastercids;
            }
        }

        // See if we need to show a count per category
        if (!isset($show_catcount)) {
            $show_catcount = 0;
        }

        // See if we need to show the children of current categories
        if (!isset($showchildren)) {
            $showchildren = 1;
        }

        // Get current category counts (optional array of cid => count)
        if (empty($show_catcount)) {
            $catcount = [];
        }
        if (empty($showempty) || !empty($show_catcount)) {
            // A 'deep count' sums the totals at each node with the totals of all descendants.
            if (xarVar::isCached('Blocks.categories', 'deepcount')) {
                $deepcount = xarCoreCache::getCached('Blocks.categories', 'deepcount');
            } else {
                $deepcount = xarMod::apiFunc(
                    'categories',
                    'user',
                    'deepcount',
                    ['modid' => $modid, 'itemtype' => $itemtype]
                );
                xarCoreCache::setCached('Blocks.categories', 'deepcount', $deepcount);
            }
        }

        if (!empty($show_catcount)) {
            if (xarVar::isCached('Blocks.categories', 'catcount')) {
                $catcount = xarCoreCache::getCached('Blocks.categories', 'catcount');
            } else {
                // Get number of items per category (for this module).
                // If show_catcount == 2 then add in all descendants too.

                if ($show_catcount == 1) {
                    // We want to display only children category counts.
                    $catcount = xarMod::apiFunc(
                        'categories',
                        'user',
                        'groupcount',
                        ['modid' => $modid, 'itemtype' => $itemtype]
                    );
                } else {
                    // We want to display the deep counts.
                    $catcount =& $deepcount;
                }

                xarCoreCache::setCached('Blocks.categories', 'catcount', $catcount);
            }
        }

        // Specify type=... & func = ... arguments for xarController::URL()
        if (empty($type)) {
            if (xarVar::isCached('Blocks.categories', 'type')) {
                $type = xarCoreCache::getCached('Blocks.categories', 'type');
            }
            if (empty($type)) {
                $type = 'user';
            }
        }
        if (empty($func)) {
            if (xarVar::isCached('Blocks.categories', 'func')) {
                $func = xarCoreCache::getCached('Blocks.categories', 'func');
            }
            if (empty($func)) {
                $func = 'view';
            }
        }

        // Get current categories
        if (xarVar::isCached('Blocks.categories', 'catid')) {
            $catid = xarCoreCache::getCached('Blocks.categories', 'catid');
        }
        if (empty($catid)) {
            // try to get catid from input
            xarVar::fetch('catid', 'isset', $catid, null, xarVar::DONT_SET);
        }
        // turn $catid into $cids array (and set $andcids flag)
        $istree = 0;
        if (!empty($catid)) {
            // if we're viewing all items below a certain category, i.e. catid = _NN
            if (strstr($catid, '_')) {
                $catid = preg_replace('/_/', '', $catid);
                $istree = 1;
            }
            if (strpos($catid, ' ')) {
                $cids = explode(' ', $catid);
                $andcids = true;
            } elseif (strpos($catid, '+')) {
                $cids = explode('+', $catid);
                $andcids = true;
            } else {
                $cids = explode('-', $catid);
                $andcids = false;
            }
        } elseif (empty($cids)) {
            if (xarVar::isCached('Blocks.categories', 'cids')) {
                $cids = xarCoreCache::getCached('Blocks.categories', 'cids');
            }
            if (xarVar::isCached('Blocks.categories', 'andcids')) {
                $andcids = xarCoreCache::getCached('Blocks.categories', 'andcids');
            }
            if (empty($cids)) {
                // try to get cids from input
                xarVar::fetch('cids', 'isset', $cids, null, xarVar::DONT_SET);
                xarVar::fetch('andcids', 'isset', $andcids, false, xarVar::NOT_REQUIRED);

                if (empty($cids)) {
                    $cids = [];
                    if ((empty($module) || $module == $modname) && !empty($itemid)) {
                        $links = xarMod::apiFunc(
                            'categories',
                            'user',
                            'getlinks',
                            ['modid' => $modid,
                                                    'itemtype' => $itemtype,
                                                    'iids' => [$itemid], ]
                        );
                        if (!empty($links) && count($links) > 0) {
                            $cids = array_keys($links);
                        }
                    }
                }
            }
        }
        if (count($cids) > 0) {
            $seencid = [];
            foreach ($cids as $cid) {
                if (empty($cid) || ! is_numeric($cid)) {
                    continue;
                }
                $seencid[$cid] = 1;
            }
            $cids = array_keys($seencid);
        }

        $data = [];
        $data['cids'] = $cids;
        // pass information about current module, item type and item id (if any) to template
        $data['module'] = $modname;
        $data['itemtype'] = $itemtype;
        $data['itemid'] = $itemid;
        // pass information about current function to template
        $data['type'] = $type;
        $data['func'] = $func;

        $blockinfo['content'] = '';

        // Generate output
        switch ($layout) {
            case 3: // menu category
                $template = 'menu';
                break;

            case 2: // crumbtrails
                $template = 'trails';
                $data['cattrees'] = [];

                if (empty($cids) || sizeof($cids) <= 0) {
                    return;
                }// if

                $cid = current($cids);
                $data['cid'] = $cid;

                # Get category parents
                $parents = xarMod::apiFunc(
                    'categories',
                    'user',
                    'getparents',
                    ['cid' => $cid]
                );
                if (empty($parents)) {
                    return;
                }// if

                $root = '';
                $parentid = 0;
                foreach ($parents as $id => $info) {
                    $publications = xarMod::apiFunc(
                        'publications',
                        'user',
                        'getall',
                        ['cid'=>$info['cid'],
                                                  'ptid'=>$itemtype,
                                                  'fields'=>['id','title'],
                                                  ]
                    );
                    foreach ($publications as $k=>$article) {
                        $publications[$article['title']] = $article['id'];
                        unset($publications[$k]);
                    }// foreach

                    $label = xarVar::prepForDisplay($info['name']);

                    if (isset($publications[$label])) {
                        $link = xarController::URL(
                            $modname,
                            $type,
                            'display',
                            ['ptid' => $itemtype,
                                            'catid' => $info['cid'],
                                            'id'=>$publications[$label], ]
                        );
                    } else {
                        $link = xarController::URL(
                            $modname,
                            $type,
                            $func,
                            ['itemtype' => $itemtype,
                                            'catid' => $info['cid'], ]
                        );
                    }// if

                    if (empty($root)) {
                        $link = xarController::URL('', '', '');
                        $root = $label;
                    }// if

                    if (!empty($catcount[$info['cid']])) {
                        $count = $catcount[$info['cid']];
                    } else {
                        $count = 0;
                    }// if
                    $catparents[] = ['catlabel' => $label,
                                        'catid' => $info['cid'],
                                        'catlink' => $link,
                                        'catcount' => $count, ];
                }// foreach

                $data['cattrees'][] = ['catparents' => $catparents];
                $data['crumbSeparator'] = '&#160;>&#160;';
                break;



            case 1: // tree
            default:

                $template = 'tree';
                $data['cattrees'] = [];

                if (empty($cids) || sizeof($cids) <= 0) {
                    return;
                }// if

                $cid = current($cids);

                $cat = xarMod::apiFunc(
                    'categories',
                    'user',
                    'getcatinfo',
                    ['cid' => $cid]
                );

                $blockinfo['title'] = xarVar::prepForDisplay($cat['name']);
                if (isset($cat['blockimage'])) {
                    $data['catimage'] = $cat['blockimage'];
                }// if


                # Get child categories
                $childrenCategories = xarMod::apiFunc(
                    'categories',
                    'user',
                    'getchildren',
                    ['cid' => $cid]
                );


                # get all the pubtypes so we can digest the ids
                $pubtypes = xarMod::apiFunc('publications', 'user', 'get_pubtypes', []);

                # get immediate items in current category
                $items = xarMod::apiFunc(
                    'publications',
                    'user',
                    'getall',
                    ['cids'=>[$cid],
                                                'fields'=>['id', 'pubtype_id', 'title'],
                                                ]
                );
                $tmpPublications = [];
                foreach ($items as $k=>$item) {
                    if (strtolower($item['title']) == strtolower($cat['name'])) {
                        unset($items[$k]);
                    } else {
                        $label = xarVar::prepForDisplay($item['title']);
                        $class = ($item['id'] == $itemid) ? 'xar-menu-item-current' : 'xar-menu-item';
                        $link = xarController::URL(
                            $modname,
                            $type,
                            'display',
                            ['id'       => $item['id'],
                                            'itemtype'  => $item['pubtype_id'],
                                            'catid'     => $cid, ]
                        );

                        $count = 0;
                        $items[$k] = ['label' => $label,
                                       'id' => $item['id'],
                                       'class'=>$class,
                                       'link' => $link, ];

                        $tmpPublications[$pubtypes[$item['pubtype_id']]['description']][] = $items[$k];
                    }// if
                }// foreach
                $items = $tmpPublications;
                unset($tmpPublications);


                if (empty($itemid) && empty($andcids)) {
                    $link = '';
                }

                $catitems = [];
                if (!empty($childrenCategories) && count($childrenCategories) > 0) {
                    foreach ($childrenCategories as $child) {
                        $publications = xarMod::apiFunc(
                            'publications',
                            'user',
                            'getall',
                            ['cid'=>$child['cid'],
                                                      'ptid'=>$itemtype,
                                                      'fields'=>['id','title'],
                                                      ]
                        );
                        foreach ($publications as $k=>$article) {
                            $publications[$article['title']] = $article['id'];
                            unset($publications[$k]);
                        }// foreach

                        $clabel = xarVar::prepForDisplay($child['name']);

                        if (isset($publications[$clabel])) {
                            $clink = xarController::URL(
                                $modname,
                                $type,
                                'display',
                                ['ptid' => $itemtype,
                                                'catid' => $child['cid'],
                                                'id'=>$publications[$clabel], ]
                            );
                        } else {
                            $clink = xarController::URL(
                                $modname,
                                $type,
                                $func,
                                ['itemtype' => $itemtype,
                                                'catid' => $child['cid'], ]
                            );
                        }// if

                        if (!empty($catcount[$child['cid']])) {
                            $ccount = $catcount[$child['cid']];
                        } else {
                            $ccount = 0;
                        }
                        $catitems[] = ['catlabel' => $clabel,
                                             'catid' => $child['cid'],
                                             'catlink' => $clink,
                                             'catcount' => $ccount,
                                             'catchildren'=>[], ];
                    }// foreach
                }// if

                if (sizeof($catitems) > 0 || sizeof($items) > 0) {
                    $data['cattrees'][] = ['catitems' => $catitems,
                                              'items' => $items, ];
                } else {
                    return;
                }// if

                break;
        }
        $data['blockid'] = $this->block_id;
        // The template base is set by this block if not already provided.
        // The base is 'nav-tree', 'nav-trails' or 'nav-prevnext', but allow
        // the admin to override this completely.
        $this->setTemplateBase('nav-' . $template);

        return $data;
    }
}

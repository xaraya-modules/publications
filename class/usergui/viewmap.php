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
use Xaraya\Modules\MethodClass;
use xarVar;
use xarModVars;
use xarController;
use xarMod;
use xarCoreCache;
use xarTpl;
use DataObjectFactory;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications user viewmap function
 * @extends MethodClass<UserGui>
 */
class ViewmapMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * view article map
     * @see UserGui::viewmap()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get parameters
        if (!$this->var()->find('ptid', $ptid, 'id', $this->mod()->getVar('defaultpubtype'))) {
            return;
        }
        if (!$this->var()->find('by', $by, 'enum:pub:cat:grid')) {
            return;
        }
        if (!$this->var()->find('go', $go, 'str')) {
            return;
        }
        if (!$this->var()->find('catid', $catid, 'str')) {
            return;
        }
        if (!$this->var()->find('cids', $cids, 'array')) {
            return;
        }

        // Override if needed from argument array
        extract($args);

        $default = $this->mod()->getVar('defaultpubtype');
        if (empty($by)) {
            if (empty($default) && empty($ptid)) {
                $by = 'cat';
            } else {
                $by = 'pub';
            }
        }

        // turn $catid into $cids array (and set $andcids flag)
        if (!empty($catid)) {
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
        }
        $seencid = [];
        if (isset($cids) && is_array($cids)) {
            foreach ($cids as $cid) {
                // make sure cids are numeric
                if (!empty($cid) && is_numeric($cid)) {
                    $seencid[$cid] = 1;
                }
            }
            $cids = array_keys($seencid);
            sort($cids, SORT_NUMERIC);
        }

        // Get publication types
        sys::import('modules.dynamicdata.class.objects.factory');
        $object = $this->data()->getObjectList(['name' => 'publications_types']);
        $data['pubtypes'] = $object->getItems();

        // redirect to filtered view
        if (!empty($go) && (!empty($ptid) || $by == 'cat')) {
            if (is_array($cids) && count($cids) > 0) {
                $catid = join('+', $cids);
            } else {
                $catid = null;
            }
            $url = $this->mod()->getURL( 'user', 'view', ['ptid' => $ptid, 'catid' => $catid]);
            $this->ctl()->redirect($url);
            return;
        }

        $data['catfilter'] = [];
        $data['cattree'] = [];
        $data['catgrid'] = [];

        $dump = '';

        $publinks = [];

        if ($by == 'cat') {
            $data['maplink'] = $this->mod()->getURL( 'user', 'viewmap', ['by' => 'cat']);

            // TODO: re-evaluate this after user feedback...
            // *trick* Use the 'default' categories here, instead of all rootcats
            $basecats = $this->mod()->apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications']);

            $catlist = [];
            foreach ($basecats as $basecat) {
                $catlist[$basecat['category_id']] = 1;
            }
            $data['basecids'] = array_keys($catlist);

            // create the category tree for each root category
            // TODO: make sure permissions are taken into account here !
            foreach ($catlist as $cid => $val) {
                if (empty($val)) {
                    continue;
                }
                $data['cattree'][$cid] = $userapi->getchildcats(// frontpage or approved
                    ['state' => [Defines::STATE_APPROVED,Defines::STATE_FRONTPAGE],
                        'cid' => $cid,
                        'ptid' => null,
                        // keep a link to the parent cid
                        'showcid' => true, ]
                );
            }
        } elseif ($by == 'grid') {
            $data['catgrid'][0] = [];
            $data['catgrid'][0][0] = '';

            // Get the base categories
            if (!empty($ptid)) {
                $rootcats = $this->mod()->apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications','itemtype' => $ptid]);
            } else {
                $rootcats = $this->mod()->apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications','itemtype' => 0]);
                $ptid = null;
            }

            if (count($rootcats) != 2) {
                $data['catgrid'][0][0] = $this->ml('You need 2 base categories in order to use this grid view');
            } else {
                $catlist = [];
                if (!empty($rootcats) && is_array($rootcats)) {
                    foreach ($rootcats as $cid) {
                        $catlist[$catid['category_id']] = 1;
                    }
                }

                $cattree = [];
                // Get the category tree for each base category
                foreach ($catlist as $cid => $val) {
                    if (empty($val)) {
                        continue;
                    }
                    $cattree[$cid] = $userapi->getchildcats(// frontpage or approved
                        ['state' => [Defines::STATE_FRONTPAGE,Defines::STATE_APPROVED],
                            'cid' => $cid,
                            'ptid' => $ptid,
                            // keep a link to the parent cid
                            'showcid' => true, ]
                    );
                }

                // Find out which category tree is the shortest
                if (count($cattree[$rootcats[0]]) > count($cattree[$rootcats[1]])) {
                    $rowcat = $rootcats[0];
                    $colcat = $rootcats[1];
                } else {
                    $rowcat = $rootcats[1];
                    $colcat = $rootcats[0];
                }

                // Fill in the column headers
                $row = 0;
                $col = 1;
                $colcid = [];
                foreach ($cattree[$colcat] as $info) {
                    $data['catgrid'][$row][$col] = '<a href="' . $info['link'] . '">' . $info['name'] . '</a>';
                    $colcid[$info['id']] = $col;
                    $col++;
                }
                $maxcol = $col;

                // Fill in the row headers
                $row = 1;
                $col = 0;
                $data['catgrid'][$row] = [];
                $rowcid = [];
                foreach ($cattree[$rowcat] as $info) {
                    $data['catgrid'][$row][$col] = '<a href="' . $info['link'] . '">' . $info['name'] . '</a>';
                    $rowcid[$info['id']] = $row;
                    $row++;
                }
                $maxrow = $row;

                // Initialise the rest of the array
                for ($row = 1; $row < $maxrow; $row++) {
                    if (!isset($data['catgrid'][$row])) {
                        $data['catgrid'][$row] = [];
                    }
                    for ($col = 1; $col < $maxcol; $col++) {
                        $data['catgrid'][$row][$col] = '';
                    }
                }

                // Get the counts for all groups of (N) categories
                $pubcatcount = $userapi->getpubcatcount(// frontpage or approved
                    ['state' => [Defines::STATE_FRONTPAGE,Defines::STATE_APPROVED],
                        'ptid' => $ptid,
                        'groupcids' => 2,
                        'reverse' => 1, ]
                );

                if (!empty($ptid)) {
                    $what = $ptid;
                } else {
                    $what = 'total';
                }
                // Fill in the count values
                foreach ($pubcatcount as $cids => $counts) {
                    [$ca, $cb] = explode('+', $cids);
                    if (isset($rowcid[$ca]) && isset($colcid[$cb])) {
                        $link = $this->mod()->getURL(
                            'user',
                            'view',
                            ['ptid' => $ptid,
                                'catid' => $ca . '+' . $cb, ]
                        );
                        $data['catgrid'][$rowcid[$ca]][$colcid[$cb]] = '<a href="' . $link . '"> ' . $counts[$what] . ' </a>';
                    }
                    if (isset($rowcid[$cb]) && isset($colcid[$ca])) {
                        $link = $this->mod()->getURL(
                            'user',
                            'view',
                            ['ptid' => $ptid,
                                'catid' => $cb . '+' . $ca, ]
                        );
                        $data['catgrid'][$rowcid[$cb]][$colcid[$ca]] = '<a href="' . $link . '"> ' . $counts[$what] . ' </a>';
                    }
                }
            }

            if (!empty($ptid)) {
                $descr = $data['pubtypes'][$ptid]['description'];
            }
        } else {
            $data['maplink'] = $this->mod()->getURL( 'user', 'viewmap', ['by' => 'pub']);

            // get the links and counts for all publication types
            $publinks = $userapi->getpublinks(['state' => [Defines::STATE_FRONTPAGE,Defines::STATE_APPROVED],
                    'all' => 1, ]
            );

            // build the list of root categories for all publication types
            // and save results in publinks as well
            $catlist = [];
            for ($i = 0;$i < count($publinks);$i++) {
                $pubid = $publinks[$i]['pubid'];
                $cidstring = $this->mod()->getVar('mastercids.' . $pubid);
                if (!empty($cidstring)) {
                    $rootcats = explode(';', $cidstring);
                    foreach ($rootcats as $cid) {
                        $catlist[$cid] = 1;
                    }
                    $publinks[$i]['rootcats'] = $rootcats;
                } else {
                    $publinks[$i]['rootcats'] = [];
                }
            }

            // for all publication types
            for ($i = 0;$i < count($publinks);$i++) {
                $publinks[$i]['cats'] = [];
                $pubid = $publinks[$i]['pubid'];
                // for each root category of this publication type
                foreach ($publinks[$i]['rootcats'] as $cid) {
                    // add the category tree to the list of categories to show
                    $childcats =  $userapi->getchildcats(// frontpage or approved
                        ['state' => [Defines::STATE_FRONTPAGE,Defines::STATE_APPROVED],
                            'cid' => $cid,
                            'ptid' => $pubid,
                            // keep a link to the parent cid
                            'showcid' => true, ]
                    );
                    $publinks[$i]['cats'][] = $childcats;
                }
            }

            $array = [];
            if (empty($ptid)) {
                $ptid = $default;
            }
            if (!empty($ptid)) {
                for ($i = 0; $i < count($publinks); $i++) {
                    if ($ptid == $publinks[$i]['pubid']) {
                        $array = $publinks[$i]['rootcats'];
                    }
                }
            }

            foreach ($publinks as $pub) {
                if ($pub['pubid'] == $ptid) {
                    $descr = $pub['pubtitle'];
                }
            }
        }

        if (empty($descr)) {
            $descr = $this->ml('Publications');
            $data['descr'] = '';
        } else {
            $data['descr'] = $descr;
        }

        // Save some variables to (temporary) cache for use in blocks etc.
        $this->var()->setCached('Blocks.publications', 'ptid', $ptid);
        //if ($shownavigation) {
        $this->var()->setCached('Blocks.categories', 'module', 'publications');
        $this->var()->setCached('Blocks.categories', 'itemtype', $ptid);
        if (!empty($descr)) {
            $this->var()->setCached('Blocks.categories', 'title', $descr);
            $this->tpl()->setPageTitle($this->ml('Map'), $this->var()->prep($descr));
        }
        //}

        if (empty($ptid)) {
            $ptid = null;
        }
        $data['publinks'] = $publinks;
        $data['ptid'] = $ptid;
        $data['viewlabel'] = $this->ml('Back to') . ' ' . $descr;
        $data['viewlink'] = $this->mod()->getURL(
            'user',
            'view',
            ['ptid' => $ptid]
        );
        $data['archivelabel'] = $this->ml('View Archives');
        $data['archivelink'] = $this->mod()->getURL(
            'user',
            'archive',
            ['ptid' => $ptid]
        );
        $data['dump'] = $dump;
        if (count($data['catfilter']) == 2) {
        }

        if (!empty($ptid)) {
            $object = $this->data()->getObject(['name' => 'publications_types']);
            $object->getItem(['itemid' => $ptid]);
            $template = $object->properties['template']->value;
        } else {
            // TODO: allow templates per category ?
            $template = null;
        }

        // Pass the type of map to the template, so we can decide what links to show
        $data['by'] = $by;

        $data['context'] ??= $this->getContext();
        return $this->mod()->template('viewmap', $data, $template);
    }
}

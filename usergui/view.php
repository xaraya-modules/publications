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

/**
 * publications user view function
 * @extends MethodClass<UserGui>
 */
class ViewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see UserGui::view()
     */

    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get parameters
        $this->var()->find('ptid', $ptid, 'id', $this->mod()->getVar('defaultpubtype'));
        $this->var()->find('startnum', $startnum, 'int:0', 1);
        $this->var()->find('cids', $cids, 'array');
        $this->var()->find('andcids', $andcids, 'str');
        $this->var()->find('catid', $catid, 'str');
        $this->var()->find('itemtype', $itemtype, 'id');
        // TODO: put the query string through a proper parser, so searches on multiple words can be done.
        $this->var()->find('q', $q, 'pre:trim:passthru:str:1:200');
        // can't use list enum here, because we don't know which sorts might be used
        // True - but we can provide some form of validation and normalisation.
        // The original 'regexp:/^[\w,]*$/' lets through *any* non-space character.
        // This validation will accept a list of comma-separated words, and will lower-case, trim
        // and strip out non-alphanumeric characters from each word.
        $this->var()->find('sort', $sort, 'strlist:,:pre:trim:lower:alnum');
        $this->var()->find('numcols', $numcols, 'int:0');
        $this->var()->find('owner', $owner, 'id');
        $this->var()->find('pubdate', $pubdate, 'str:1');
        // This may not be set via user input, only e.g. via template tags, API calls, blocks etc.
        //    $this->var()->find('startdate', $startdate, 'int:0', NULL);
        //    $this->var()->find('enddate', $enddate, 'int:0', NULL);
        //    $this->var()->find('where', $where, 'str', NULL);

        // Added to implement an Alpha Pager
        $this->var()->find('letter', $letter, 'pre:lower:passthru:str:1:20');

        // Override if needed from argument array (e.g. ptid, numitems etc.)
        extract($args);

        $pubtypes = $userapi->get_pubtypes();

        // We need a valid pubtype number here
        if (!is_numeric($ptid) || !isset($pubtypes[$ptid])) {
            return $this->ctl()->notFound();
        }

        // Constants used throughout.
        //
        // publications module ID
        $c_modid = $this->mod()->getID('publications');
        // state: front page or approved
        $c_posted = [Defines::STATE_FRONTPAGE,Defines::STATE_APPROVED];

        // Default parameters
        if (!isset($startnum)) {
            $startnum = 1;
        }

        // Check if we want the default 'front page'
        if (!isset($catid) && !isset($cids) && empty($ptid) && !isset($owner)) {
            $ishome = true;
            // default publication type
            $ptid = $this->mod()->getVar('defaultpubtype');
            // frontpage state
            $state = [Defines::STATE_FRONTPAGE];
        } else {
            $ishome = false;
            // frontpage or approved state
            $state = $c_posted;
        }

        // Get the publication type for this display
        $data['pubtypeobject'] = $this->data()->getObject(['name' => 'publications_types']);
        $data['pubtypeobject']->getItem(['itemid' => $ptid]);

        // Pass the access rules of the publication type to the template
        $this->mem()->set('Publications', 'pubtype_access', $data['pubtypeobject']->properties['access']->getValue());

        // A non-active publication type means the page does not exist
        if ($data['pubtypeobject']->properties['state']->value < Defines::STATE_ACTIVE) {
            return $this->ctl()->notFound();
        }

        // Get the settings of this publication type
        $data['settings'] = $userapi->getsettings(['ptid' => $ptid]);

        // Get the template for this publication type
        if ($ishome) {
            $data['template'] = 'frontpage';
        } else {
            $data['template'] = $data['pubtypeobject']->properties['template']->getValue();
        }

        $isdefault = 0;
        // check default view for this type of publications
        if (empty($catid) && empty($cids) && empty($owner) && empty($sort)) {
            if (substr($data['settings']['defaultview'], 0, 1) == 'c') {
                $catid = substr($data['settings']['defaultview'], 1);
            }
        }

        // Do not transform titles if we are not transforming output at all.
        if (empty($data['settings']['do_transform'])) {
            $data['settings']['dotitletransform'] = 0;
        }

        if (empty($data['settings']['defaultsort'])) {
            $defaultsort = 'date';
        } else {
            $defaultsort = $data['settings']['defaultsort'];
        }
        if (empty($sort)) {
            $sort = $defaultsort;
        }

        // TODO: show this *after* category list when we start from categories :)
        // Navigation links
        $data['publabel'] = $this->ml('Publication');
        $data['publinks'] = $userapi->getpublinks(
            [
                'ptid' => $ishome ? '' : $ptid,
                'state' => $c_posted,
                'count' => $data['settings']['show_pubcount'],
            ]
        );
        //    $data['pager'] = '';

        // Add Sort to data passed to template so that we can automatically turn on alpha pager, if needed
        $data['sort'] = $sort;

        // Add current display letter, so that we can highlight the current filter in the alpha pager
        $data['letter'] = $letter;

        // Get the users requested number of stories per page.
        // If user doesn't care, use the site default
        if ($this->user()->isLoggedIn()) {
            // TODO: figure how to let users specify their settings
            // COMMENT: if the settings were split into separate module variables,
            // then they could all be individually over-ridden by each user.
            //$numitems = xar::mod()->getUserVar('items_per_page');
        }
        if (empty($numitems)) {
            if (!empty($settings['items_per_page'])) {
                $numitems = (int) $settings['items_per_page'];
            } else {
                $numitems = (int) $this->mod()->getVar('items_per_page');
            }
        }

        // turn $catid into $cids array and set $andcids flag
        if (!empty($catid)) {
            if (strpos($catid, ' ')) {
                $cids = explode(' ', $catid);
                $andcids = true;
            } elseif (strpos($catid, '+')) {
                $cids = explode('+', $catid);
                $andcids = true;
            } elseif (strpos($catid, '-')) {
                $cids = explode('-', $catid);
                $andcids = false;
            } else {
                $cids = [$catid];
                if (strstr($catid, '_')) {
                    $andcids = false; // don't combine with current category
                } else {
                    $andcids = true;
                }
            }
        } else {
            if (empty($cids)) {
                $cids = [];
            }
            if (!isset($andcids)) {
                $andcids = true;
            }
        }
        // rebuild $catid in standard format again
        $catid = null;
        if (count($cids) > 0) {
            $seencid = [];
            foreach ($cids as $cid) {
                // make sure cids are numeric
                if (!empty($cid) && preg_match('/^_?[0-9]+$/', $cid)) {
                    $seencid[$cid] = 1;
                }
            }
            $cids = array_keys($seencid);
            sort($cids, SORT_NUMERIC);
            if ($andcids) {
                $catid = join('+', $cids);
            } else {
                $catid = join('-', $cids);
            }
        }

        // every field you always wanted to know about but were afraid to ask for :)
        $extra = [];
        //    $extra[] = 'author';

        // Note: we always include cids for security checks now (= performance impact if show_categories was 0)
        $extra[] = 'cids';
        if ($data['settings']['show_hitcount']) {
            $extra[] = 'counter';
        }
        if ($data['settings']['show_ratings']) {
            $extra[] = 'rating';
        }

        $now = time();

        if (empty($startdate) || !is_numeric($startdate) || $startdate > $now) {
            $startdate = null;
        }
        if (empty($enddate) || !is_numeric($enddate) || $enddate > $now) {
            $enddate = $now;
        }
        if (empty($pubdate) || !preg_match('/^\d{4}(-\d+(-\d+|)|)$/', $pubdate)) {
            $pubdate = null;
        }
        if (empty($where)) {
            $where = null;
        }

        // Modify the where clause if an Alpha filter has been specified.
        if (!empty($letter)) {
            // We will allow up to three initial letters, anything more than that is assumed to be 'Other'.
            // Need to also be very wary of SQL injection, since we are not using bind variables here.
            // TODO: take into account international characters.
            if (preg_match('/^[a-z]{1,3}$/i', $letter)) {
                $extrawhere = "title LIKE '$letter%'";
            } else {
                // Loop through the alphabet for the 'not in' part.
                $letterwhere = [];
                for ($i = ord('a'); $i <= ord('z'); $i++) {
                    $letterwhere[] = "title NOT LIKE '" . chr($i) . "%'";
                }
                $extrawhere = implode(' and ', $letterwhere);
            }
            if ($where == null) {
                $where = $extrawhere;
            } else {
                $where .= $extrawhere;
            }
        }
        /*
            // Get publications
            $publications = $userapi->getall(array(
                    'startnum' => $startnum,
                    'cids' => $cids,
                    'andcids' => $andcids,
                    'ptid' => (isset($ptid) ? $ptid : null),
                    'owner' => $owner,
                    'state' => $state,
                    'sort' => $sort,
                    'extra' => $extra,
                    'where' => $where,
                    'search' => $q,
                    'numitems' => $numitems,
                    'pubdate' => $pubdate,
                    'startdate' => $startdate,
                    'enddate' => $enddate
                )
            );

            if (!is_array($publications)) {
                throw new Exception('Failed to retrieve publications');
            }
        */
        // TODO : support different 'index' templates for different types of publications
        //        (e.g. News, Sections, ...), depending on what "view" the user
        //        selected (per category, per publication type, a combination, ...) ?

        if (!empty($owner)) {
            $data['author'] = $this->user($owner)->getName();
            if (empty($data['author'])) {
                $data['author'] = $this->ml('Unknown');
            }
        }
        if (!empty($pubdate)) {
            $data['pubdate'] = $pubdate;
        }

        // Save some variables to (temporary) cache for use in blocks etc.
        $this->mem()->set('Blocks.publications', 'ptid', $ptid);
        $this->mem()->set('Blocks.publications', 'cids', $cids);
        $this->mem()->set('Blocks.publications', 'owner', $owner);
        if (isset($data['author'])) {
            $this->mem()->set('Blocks.publications', 'author', $data['author']);
        }
        if (isset($data['pubdate'])) {
            $this->mem()->set('Blocks.publications', 'pubdate', $data['pubdate']);
        }

        // TODO: add this to publications configuration ?
        if ($ishome) {
            $data['ptid'] = null;
            if ($this->sec()->checkAccess('SubmitPublications', 0)) {
                $data['submitlink'] = $this->mod()->getURL('admin', 'new');
            }
        } else {
            $data['ptid'] = $ptid;
            if (!empty($ptid)) {
                $curptid = $ptid;
            } else {
                $curptid = 'All';
            }
            if (count($cids) > 0) {
                foreach ($cids as $cid) {
                    if ($this->sec()->check('SubmitPublications', 0, 'Publication', "$curptid:$cid:All:All")) {
                        $data['submitlink'] = $this->mod()->getURL('admin', 'new', ['ptid' => $ptid, 'catid' => $catid]);
                        break;
                    }
                }
            } elseif ($this->sec()->check('SubmitPublications', 0, 'Publication', "$curptid:All:All:All")) {
                $data['submitlink'] = $this->mod()->getURL('admin', 'new', ['ptid' => $ptid]);
            }
        }
        $data['cids'] = $cids;
        $data['catid'] = $catid;
        $this->mem()->set('Blocks.categories', 'module', 'publications');
        $this->mem()->set('Blocks.categories', 'itemtype', $ptid);
        $this->mem()->set('Blocks.categories', 'cids', $cids);
        if (!empty($ptid) && !empty($pubtypes[$ptid]['description'])) {
            $this->mem()->set('Blocks.categories', 'title', $pubtypes[$ptid]['description']);
            // Note : this gets overriden by the categories navigation if necessary
            $this->tpl()->setPageTitle($this->prep()->text($pubtypes[$ptid]['description']));
        }

        // optional category count
        if ($data['settings']['show_catcount']) {
            if (!empty($ptid)) {
                $pubcatcount = $userapi->getpubcatcount(// frontpage or approved
                    ['state' => $c_posted, 'ptid' => $ptid]
                );
                if (isset($pubcatcount[$ptid])) {
                    $this->mem()->set('Blocks.categories', 'catcount', $pubcatcount[$ptid]);
                }
                unset($pubcatcount);
            } else {
                $pubcatcount = $userapi->getpubcatcount(// frontpage or approved
                    ['state' => $c_posted, 'reverse' => 1]
                );

                if (isset($pubcatcount) && count($pubcatcount) > 0) {
                    $catcount = [];
                    foreach ($pubcatcount as $cat => $count) {
                        $catcount[$cat] = $count['total'];
                    }
                    $this->mem()->set('Blocks.categories', 'catcount', $catcount);
                }
                unset($pubcatcount);
            }
        } else {
            // $this->mem()->set('Blocks.categories','catcount',array());
        }

        // retrieve the number of comments for each article
        if ($this->mod()->isAvailable('comments')) {
            if ($data['settings']['show_comments']) {
                $idlist = [];
                foreach ($publications as $article) {
                    $idlist[] = $article['id'];
                }
                $numcomments = $this->mod()->apiFunc(
                    'comments',
                    'user',
                    'get_countlist',
                    ['modid' => $c_modid, 'objectids' => $idlist]
                );
            }
        }

        // retrieve the keywords for each article
        if ($this->mod()->isAvailable('coments')) {
            if ($data['settings']['show_keywords']) {
                $idlist = [];
                foreach ($publications as $article) {
                    $idlist[] = $article['id'];
                }

                $keywords = $this->mod()->apiFunc(
                    'keywords',
                    'user',
                    'getmultiplewords',
                    [
                        'modid' => $c_modid,
                        'objectids' =>  $idlist,
                        'itemtype'  => $ptid,
                    ]
                );
            }
        }
        /* ------------------------------------------------------------
            // retrieve the categories for each article
            $catinfo = array();
            if ($show_categories) {
                $cidlist = array();
                foreach ($publications as $article) {
                    if (!empty($article['cids']) && count($article['cids']) > 0) {
                         foreach ($article['cids'] as $cid) {
                             $cidlist[$cid] = 1;
                         }
                    }
                }
                if (count($cidlist) > 0) {
                    $catinfo = $this->mod()->apiFunc('categories','user','getcatinfo', array('cids' => array_keys($cidlist)));
                    // get root categories for this publication type
                    // get base categories for all if needed
                    $catroots = $userapi->getrootcats(array('ptid' => $ptid, 'all' => true)
                    );
                }
                foreach ($catinfo as $cid => $info) {
                    $catinfo[$cid]['name'] = $this->prep()->text($info['name']);
                    $catinfo[$cid]['link'] = $this->mod()->getURL( 'user', 'view',
                        array('ptid' => $ptid, 'catid' => (($catid && $andcids) ? $catid . '+' . $cid : $cid) )
                    );

                    // only needed when sorting by root category id
                    $catinfo[$cid]['root'] = 0; // means not found under a root category
                    // only needed when sorting by root category order
                    $catinfo[$cid]['order'] = 0; // means not found under a root category
                    $rootidx = 1;
                    foreach ($catroots as $rootcat) {
                        // see if we're a child category of this rootcat (cfr. Celko model)
                        if ($info['left'] >= $rootcat['catleft'] && $info['left'] < $rootcat['catright']) {
                            // only needed when sorting by root category id
                            $catinfo[$cid]['root'] = $rootcat['catid'];
                            // only needed when sorting by root category order
                            $catinfo[$cid]['order'] = $rootidx;
                            break;
                        }
                        $rootidx++;
                    }
                }
                // needed for sort function below
                $GLOBALS['artviewcatinfo'] = $catinfo;
            }

            $number = 0;
            foreach ($publications as $article)
            {
                // TODO: don't include ptid and catid if we don't use short URLs
                // link to article
                $article['link'] = $this->mod()->getURL( 'user', 'display',
                    // don't include pubtype id if we're navigating by category
                    array(
                        'ptid' => empty($ptid) ? null : $article['pubtype_id'],
                        'catid' => $catid,
                        'id' => $article['id']
                    )
                );

                // N words/bytes more in article
                if (!empty($article['body'])) {
                    // note : this is only an approximate number
                    $wordcount = count(preg_split("/\s+/", strip_tags($article['body']), -1, PREG_SPLIT_NO_EMPTY));
                    $article['words'] = $wordcount;

                    // byte-count is less CPU-intensive -> make configurable ?
                    $article['bytes'] = strlen($article['body']);
                } else {
                    $article['words'] = 0;
                    $article['bytes'] = 0;
                }

                // current publication type
                $curptid = $article['pubtype_id'];

                // TODO: make configurable?
                $article['redirect'] = $this->mod()->getURL( 'user', 'redirect',
                    array('ptid' => $curptid, 'id' => $article['id'])
                );


                // multi-column display (default from left to right, then from top to bottom)
                $article['number'] = $number;
                if (!empty($settings['number_of_columns'])) {
                    $col = $number % $settings['number_of_columns'];
                } else {
                    $col = 0;
                }

                // RSS Processing
                $current_theme = $this->mem()->get('Themes.name', 'CurrentTheme');
                if (($current_theme == 'rss') or ($current_theme == 'atom')){
                    $article['rsstitle'] = htmlspecialchars($article['title']);
                    //$article['rssdate'] = strtotime($article['date']);
                    $article['rsssummary'] = preg_replace('<br />', "\n", $article['summary']);
                    $article['rsssummary'] = $this->prep()->text(strip_tags($article['rsssummary']));
                    $article['rsscomment'] = $this->ctl()->getModuleURL('comments', 'user', 'display', array('modid' => $c_modid, 'objectid' => $article['id']));
                    // $article['rsscname'] = htmlspecialchars($item['cname']);
                    // <category>#$rsscname#</category>
                }

                // TODO: clean up depending on field format
                if ($do_transform) {
                    $article['itemtype'] = $article['pubtype_id'];
                    // TODO: what about transforming DD fields?
                    if ($title_transform) {
                        $article['transform'] = array('title', 'summary', 'body', 'notes');
                    } else {
                        $article['transform'] = array('summary', 'body', 'notes');
                    }
                    $article = $this->mod()->callHooks('item', 'transform', $article['id'], $article, 'publications');
                }

                $data['titles'][$article['id']] = $article['title'];

                // fill in the summary template for this article
                $summary_template = $pubtypes[$article['pubtype_id']]['name'];
                $number++;echo $number;
            }
        ------------------------------------------------------------ */

        // TODO: verify for other URLs as well
        if ($ishome) {
            if (!empty($numcols) && $numcols > 1) {
                // if we're currently showing more than 1 column
                $data['showcols'] = 1;
            } else {
                $defaultcols = $data['settings']['number_of_columns'];
                if ($defaultcols > 1) {
                    // if the default number of columns is more than 1
                    $data['showcols'] = $defaultcols;
                }
            }
        }

        // Specific layout within a template (optional)
        if (isset($layout)) {
            $data['layout'] = $layout;
        }

        // Get the publications we want to view
        $data['object'] = $this->data()->getObject(['name' => $data['pubtypeobject']->properties['name']->value]);
        $data['objectname'] = $data['pubtypeobject']->properties['name']->value;
        $data['ptid'] = (int) $ptid;

        //    $object = $this->data()->getObjectList(array('name' => $data['pubtypeobject']->properties['name']->value));
        //    $data['items'] = $object->getItems();
        $data['object'] = $this->data()->getObjectList(['name' => $data['pubtypeobject']->properties['name']->value]);
        // Set the itemtype to static for filtering
        $data['object']->modifyProperty('itemtype', ['type' => 1]);

        $q = $data['object']->dataquery;

        // Cater to default settings
        if ($data['sort'] == 'date ASC') {
            $q->setorder('modify_date', 'ASC');
        } elseif ($data['sort'] == 'modify_date' || $data['sort'] == 'date') {
            $q->setorder('modify_date', 'DESC');
        } else {
            // Sort by whatever was passed, if the property exists
            if (isset($data['object']->properties[$data['sort']])) {
                $q->setorder($data['object']->properties[$data['sort']]->source, 'ASC');
            }
        }

        // Settings for the pager
        $data['numitems'] = (int) $numitems;
        $data['startnum'] = (int) $startnum;
        $q->setrowstodo($numitems);

        // Set the page template if needed
        // Page template for frontpage or depending on publication type (optional)
        // Note : this cannot be overridden in templates
        if (!empty($data['pubtypeobject']->properties['page_template']->value)) {
            $pagename = $data['pubtypeobject']->properties['page_template']->value;
            $position = strpos($pagename, '.');
            if ($position === false) {
                $pagetemplate = $pagename;
            } else {
                $pagetemplate = substr($pagename, 0, $position);
            }
            $this->tpl()->setPageTemplateName($pagetemplate);
        }

        // Throw all the settings we are using into the cache
        $this->mem()->set('publications', 'settings_' . $data['ptid'], $data['settings']);

        // Flag this as the current list view
        $this->session()->setVar('publications_current_listview', $this->ctl()->getCurrentURL(['ptid' => $data['ptid']]));

        $data['context'] ??= $this->getContext();
        return $this->mod()->template('view', $data, $data['template']);
    }
}

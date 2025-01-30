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
use xarSecurity;
use xarVar;
use xarMod;
use xarPrivileges;
use xarController;
use DataPropertyMaster;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin privileges function
 * @extends MethodClass<AdminGui>
 */
class PrivilegesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Manage definition of instances for privileges (unfinished)
     * @return array<mixed>|void for template
     * @see AdminGui::privileges()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->sec()->checkAccess('EditPublications')) {
            return;
        }

        extract($args);

        // fixed params
        if (!$this->var()->check('ptid', $ptid)) {
            return;
        }
        if (!$this->var()->check('cid', $cid)) {
            return;
        }
        if (!$this->var()->check('uid', $uid)) {
            return;
        }
        if (!$this->var()->check('author', $author)) {
            return;
        }
        if (!$this->var()->check('id', $id)) {
            return;
        }
        if (!$this->var()->check('apply', $apply)) {
            return;
        }
        if (!$this->var()->check('extpid', $extpid)) {
            return;
        }
        if (!$this->var()->check('extname', $extname)) {
            return;
        }
        if (!$this->var()->check('extrealm', $extrealm)) {
            return;
        }
        if (!$this->var()->check('extmodule', $extmodule)) {
            return;
        }
        if (!$this->var()->check('extcomponent', $extcomponent)) {
            return;
        }
        if (!$this->var()->check('extinstance', $extinstance)) {
            return;
        }
        if (!$this->var()->check('extlevel', $extlevel)) {
            return;
        }

        sys::import('modules.dynamicdata.class.properties.master');
        /** @var \CategoriesProperty $categories */
        $categories = $this->prop()->getProperty(['name' => 'categories']);
        $cids = $categories->returnInput('privcategories');

        if (!empty($extinstance)) {
            $parts = explode(':', $extinstance);
            if (count($parts) > 0 && !empty($parts[0])) {
                $ptid = $parts[0];
            }
            if (count($parts) > 1 && !empty($parts[1])) {
                $cid = $parts[1];
            }
            if (count($parts) > 2 && !empty($parts[2])) {
                $uid = $parts[2];
            }
            if (count($parts) > 3 && !empty($parts[3])) {
                $id = $parts[3];
            }
        }

        if (empty($ptid) || $ptid == 'All' || !is_numeric($ptid)) {
            $ptid = 0;
            if (!$this->sec()->checkAccess('AdminPublications')) {
                return;
            }
        } else {
            if (!xarSecurity::check('AdminPublications', 1, 'Publication', "$ptid:All:All:All")) {
                return;
            }
        }

        // TODO: do something with cid for security check

        // TODO: figure out how to handle more than 1 category in instances
        if (empty($cid) || $cid == 'All' || !is_numeric($cid)) {
            $cid = 0;
        }
        if (empty($cid) && isset($cids) && is_array($cids)) {
            foreach ($cids as $catid) {
                if (!empty($catid)) {
                    $cid = $catid;
                    // bail out for now
                    break;
                }
            }
        }

        if (empty($id) || $id == 'All' || !is_numeric($id)) {
            $id = 0;
        }
        $title = '';
        if (!empty($id)) {
            $article = $userapi->get(['id'      => $id,
                    'withcids' => true, ]
            );
            if (empty($article)) {
                $id = 0;
            } else {
                // override whatever other params we might have here
                $ptid = $article['pubtype_id'];
                // TODO: review when we can handle multiple categories and/or subtrees in privilege instances
                if (!empty($article['cids']) && count($article['cids']) == 1) {
                    // if we don't have a category, or if we have one but this article doesn't belong to it
                    if (empty($cid) || !in_array($cid, $article['cids'])) {
                        // we'll take that category
                        $cid = $article['cids'][0];
                    }
                } else {
                    // we'll take no categories
                    $cid = 0;
                }
                $uid = $article['owner'];
                $title = $article['title'];
            }
        }

        // TODO: figure out how to handle groups of users and/or the current user (later)
        if (strtolower($uid) == 'myself') {
            $uid = 'Myself';
            $author = 'Myself';
        } elseif (empty($uid) || $uid == 'All' || (!is_numeric($uid) && (strtolower($uid) != 'myself'))) {
            $uid = 0;
            if (!empty($author)) {
                $user = $this->mod()->apiFunc(
                    'roles',
                    'user',
                    'get',
                    ['name' => $author]
                );
                if (!empty($user) && !empty($user['id'])) {
                    if (strtolower($author) == 'myself') {
                        $uid = 'Myself';
                    } else {
                        $uid = $user['id'];
                    }
                } else {
                    $author = '';
                }
            }
        } else {
            $author = '';
            /*
                    $user = $this->mod()->apiFunc('roles', 'user', 'get',
                                          array('id' => $uid));
                    if (!empty($user) && !empty($user['name'])) {
                        $author = $user['name'];
                    }
            */
        }

        // define the new instance
        $newinstance = [];
        $newinstance[] = empty($ptid) ? 'All' : $ptid;
        $newinstance[] = empty($cid) ? 'All' : $cid;
        $newinstance[] = empty($uid) ? 'All' : $uid;
        $newinstance[] = empty($id) ? 'All' : $id;

        if (!empty($apply)) {
            // create/update the privilege
            $id = xarPrivileges::external($extpid, $extname, $extrealm, $extmodule, $extcomponent, $newinstance, $extlevel);
            if (empty($id)) {
                return;
            } // throw back

            // redirect to the privilege
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                'privileges',
                'admin',
                'modifyprivilege',
                ['id' => $id]
            ));
            return;
        }

        // get the list of current authors
        $authorlist =  $userapi->getauthors(['ptid' => $ptid,
                'cids' => empty($cid) ? [] : [$cid], ]
        );
        if (!empty($author) && isset($authorlist[$uid])) {
            $author = '';
        }

        if (empty($id)) {
            $numitems = $userapi->countitems(['ptid' => $ptid,
                    'cids' => empty($cid) ? [] : [$cid],
                    'owner' => $uid, ]
            );
        } else {
            $numitems = 1;
        }
        $data = [
            'ptid'         => $ptid,
            'cid'          => $cid,
            'uid'          => $uid,
            'author'       => $this->var()->prep($author),
            'authorlist'   => $authorlist,
            'id'          => $id,
            'title'        => $this->var()->prep($title),
            'numitems'     => $numitems,
            'extpid'       => $extpid,
            'extname'      => $extname,
            'extrealm'     => $extrealm,
            'extmodule'    => $extmodule,
            'extcomponent' => $extcomponent,
            'extlevel'     => $extlevel,
            'extinstance'  => $this->var()->prep(join(':', $newinstance)),
        ];

        // Get publication types
        $data['pubtypes'] = $userapi->get_pubtypes();

        $catlist = [];
        if (!empty($ptid)) {
            $basecats = $this->mod()->apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications', 'itemtype' => $ptid]);
            foreach ($basecats as $catid) {
                $catlist[$catid['id']] = 1;
            }
            if (empty($data['pubtypes'][$ptid]['config']['owner']['label'])) {
                $data['showauthor'] = 0;
            } else {
                $data['showauthor'] = 1;
            }
        } else {
            foreach (array_keys($data['pubtypes']) as $pubid) {
                $basecats = $this->mod()->apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications', 'itemtype' => $pubid]);
                foreach ($basecats as $catid) {
                    $catlist[$catid['id']] = 1;
                }
            }
            $data['showauthor'] = 1;
        }

        $seencid = [];
        if (!empty($cid)) {
            $seencid[$cid] = 1;
        }

        $data['cids'] = $cids;
        $data['cats'] = $catlist;
        $data['refreshlabel'] = $this->ml('Refresh');
        $data['applylabel'] = $this->ml('Finish and Apply to Privilege');
        return $data;
    }
}

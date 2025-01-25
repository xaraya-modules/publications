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
use xarMod;
use xarDB;
use xarSecurity;
use xarModHooks;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi get function
 * @extends MethodClass<UserApi>
 */
class GetMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get a specific article by id, or by a combination of other fields
     * @param array<string,mixed> $args
     * @var int $args['id'] id of article to get, or
     * @var int $args['pubtype_id'] pubtype id of article to get, and optional
     * @var string $args['title'] title of article to get, and optional
     * @var string $args['summary'] summary of article to get, and optional
     * @var string $args['body'] body of article to get, and optional
     * @var int $args['owner'] id of the author of article to get, and optional
     *     $args['pubdate'] pubdate of article to get, and optional
     * @var string $args['notes'] notes of article to get, and optional
     * @var int $args['state'] status of article to get, and optional
     * @var string $args['locale'] language of article to get
     * @var bool $args['withcids'] (optional) if we want the cids too (default false)
     * @var array $args['fields'] array with all the fields to return per article
     *                        Default list is : 'id','title','summary','owner',
     *                        'pubdate','pubtype_id','notes','state','body'
     *                        Optional fields : 'cids','author','counter','rating','dynamicdata'
     * @var array $args['extra'] array with extra fields to return per article (in addition
     *                       to the default list). So you can EITHER specify *all* the
     *                       fields you want with 'fields', OR take all the default
     *                       ones and add some optional fields with 'extra'
     * @var int $args['ptid'] same as 'pubtype_id'
     * @return array|false|void article array, or false on failure
     * @see UserApi::get()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (isset($id) && (!is_numeric($id) || $id < 1)) {
            $msg = $this->ml(
                'Invalid #(1) for #(2) function #(3)() in module #(4)',
                'article ID',
                'user',
                'get',
                'Publications'
            );
            throw new BadParameterException(null, $msg);
        }

        // allow ptid instead of pubtype_id, like getall and other api's (if both specified, ptid wins)
        if (!empty($ptid)) {
            $pubtype_id = $ptid;
        }

        // bypass this function, call getall instead?
        if (isset($fields) || isset($extra)) {
            if (!empty($id)) {
                $args['ids'] = [$id];
            }
            if (!empty($pubtype_id)) {
                $args['ptid'] = $pubtype_id;
            }
            $wheres = [];
            if (!empty($title)) {
                $wheres[] = "title eq '$title'";
            }
            if (!empty($summary)) {
                $wheres[] = "summary eq '$summary'";
            }
            if (!empty($body)) {
                $wheres[] = "body eq '$body'";
            }
            if (!empty($notes)) {
                $wheres[] = "notes eq '$notes'";
            }
            if (!empty($withcids)) {
                $fields[] = "cids";
            }
            foreach ($wheres as $w) {
                if (isset($where)) {
                    $where .= " || $w";
                } else {
                    $where = $w;
                }
            }
            if (isset($where)) {
                $args['where'] = $where;
            }
            $arts = $userapi->getall($args);
            if (!empty($arts)) {
                return current($arts);
            } else {
                return false;
            }
        }

        // TODO: put all this in dynamic data and retrieve everything via there (including hooked stuff)

        $bindvars = [];
        if (!empty($id)) {
            $where = "WHERE id = ?";
            $bindvars[] = $id;
        } else {
            $wherelist = [];
            $fieldlist = ['title','summary','owner','start_date','pubtype_id',
                'notes','state','body1','locale', ];
            foreach ($fieldlist as $field) {
                if (isset($$field)) {
                    $wherelist[] = "$field = ?";
                    $bindvars[] = $$field;
                }
            }
            if (count($wherelist) > 0) {
                $where = "WHERE " . join(' AND ', $wherelist);
            } else {
                $where = '';
            }
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = & $this->db()->getTables();
        $publicationstable = $xartable['publications'];

        // Get item
        $query = "SELECT id,
                       title,
                       summary,
                       body1,
                       owner,
                       start_date,
                       pubtype_id,
                       leftpage_id,
                       rightpage_id,
                       notes,
                       state,
                       locale
                FROM $publicationstable
                $where";
        if (!empty($id)) {
            $result = $dbconn->Execute($query, $bindvars);
        } else {
            $result = $dbconn->SelectLimit($query, 1, 0, $bindvars);
        }
        if (!$result) {
            return;
        }

        if ($result->EOF) {
            return false;
        }

        [$id, $title, $summary, $body, $owner, $start_date, $pubtype_id, $leftpage_id, $rightpage_id, $notes,
            $state, $locale] = $result->fields;

        $article = ['id' => $id,
            'title' => $title,
            'summary' => $summary,
            'body' => $body,
            'owner' => $owner,
            'start_date' => $start_date,
            'pubtype_id' => $pubtype_id,
            'leftpage_id' => $leftpage_id,
            'rightpage_id' => $rightpage_id,
            'notes' => $notes,
            'state' => $state,
            'locale' => $locale, ];

        if (!empty($withcids)) {
            $article['cids'] = [];
            if (!xarMod::apiLoad('categories', 'user')) {
                return;
            }

            $info = xarMod::getBaseInfo('publications');
            $regid = $info['systemid'];
            $articlecids = xarMod::apiFunc(
                'categories',
                'user',
                'getlinks',
                ['iids' => [$id],
                    'itemtype' => $pubtype_id,
                    'modid' => $regid,
                    'reverse' => 0,
                ]
            );
            if (is_array($articlecids) && count($articlecids) > 0) {
                $article['cids'] = array_keys($articlecids);
            }
        }

        // Security check
        if (isset($article['cids']) && count($article['cids']) > 0) {
            // TODO: do we want all-or-nothing access here, or is one access enough ?
            foreach ($article['cids'] as $cid) {
                if (!xarSecurity::check('ReadPublications', 0, 'Publication', "$pubtype_id:$cid:$owner:$id")) {
                    return;
                }
                // TODO: combine with ViewCategoryLink check when we can combine module-specific
                // security checks with "parent" security checks transparently ?
                if (!xarSecurity::check('ReadCategories', 0, 'Category', "All:$cid")) {
                    return;
                }
            }
        } else {
            if (!xarSecurity::check('ReadPublications', 0, 'Publication', "$pubtype_id:All:$owner:$id")) {
                return;
            }
        }

        /*
            if (xarModHooks::isHooked('dynamicdata','publications')) {
                $values = xarMod::apiFunc('dynamicdata','user','getitem',
                                         array('module'   => 'publications',
                                               'itemtype' => $pubtype_id,
                                               'itemid'   => $id));
                if (!empty($values) && count($values) > 0) {
                // TODO: compare with looping over $name => $value pairs
                    $article = array_merge($article,$values);
                }
            }
        */
        return $article;
    }
}

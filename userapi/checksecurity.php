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
use xarSecurity;

/**
 * publications userapi checksecurity function
 * @extends MethodClass<UserApi>
 */
class ChecksecurityMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * check security for a particular article
     * @param array<mixed> $args
     * @var mixed $mask the requested security mask
     * @var mixed $article the article array (if already retrieved)
     * @var mixed $id the article ID (if known, and article array not
     * already retrieved)
     * @var mixed $owner the user ID of the author (if not already included)
     * @var mixed $ptid the publication type ID (if not already included)
     * @var mixed $cids array of additional required category checks
     * @return bool|void true if OK, false if not OK
     * @see UserApi::checksecurity()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get arguments from argument array
        extract($args);

        // Compatibility mode with old API params - remove later
        if (isset($access) && !isset($mask)) {
            switch ($access) {
                case xarSecurity::ACCESS_OVERVIEW:
                    $mask = 'ViewPublications';
                    break;
                case xarSecurity::ACCESS_READ:
                    $mask = 'ReadPublications';
                    break;
                case xarSecurity::ACCESS_COMMENT:
                    $mask = 'SubmitPublications';
                    break;
                case xarSecurity::ACCESS_EDIT:
                    $mask = 'EditPublications';
                    break;
                case xarSecurity::ACCESS_DELETE:
                    $mask = 'ManagePublications';
                    break;
                case xarSecurity::ACCESS_ADMIN:
                    $mask = 'AdminPublications';
                    break;
                default:
                    $mask = '';
            }
        }

        if (empty($mask)) {
            return false;
        }
        // Get article information
        if (!isset($publication) && !empty($id) && $mask != 'SubmitPublications') {
            $publication = $userapi->get(
                ['id' => $id,
                    'withcids' => true, ]
            );
            if ($publication == false) {
                return false;
            }
        }
        if (empty($id) && isset($publication['id'])) {
            $id = $publication['id'];
        }
        if (!isset($id)) {
            $id = '';
        }

        // Get author ID
        if (isset($publication['owner']) && empty($owner)) {
            $owner = $publication['owner'];
        }

        // Get state
        if (isset($publication['state']) && !isset($state)) {
            $state = $publication['state'];
        }
        if (empty($state)) {
            $state = 0;
        }
        // reject reading access to unapproved publications
        if ($state < 2 && ($mask == 'ViewPublications' || $mask == 'ReadPublications')) {
            return false;
        }

        // Get publication type ID
        if (isset($publication['pubtype_id'])) {
            if (!isset($ptid)) {
                $ptid = $publication['pubtype_id'];
            } elseif ($ptid != $publication['pubtype_id'] && $mask != 'EditPublications') {
                return false;
            }
        }

        // Get root categories for this publication type
        if (!empty($ptid)) {
            $rootcats = $this->mod()->apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications', 'itemtype' => $ptid]);
        } else {
            $ptid = null;
        }
        if (!isset($rootcids)) {
            // TODO: handle cross-pubtype views better
            $rootcats = $this->mod()->apiFunc('categories', 'user', 'getallcatbases', ['module' => 'publications']);
        }

        // Get category information for this article
        if (!isset($publication['cids']) && !empty($id)) {
            if (!$this->mod()->apiLoad('categories', 'user')) {
                return;
            }
            $info = xarMod::getBaseInfo('publications');
            $sysid = $info['systemid'];
            $publicationcids = $this->mod()->apiFunc(
                'categories',
                'user',
                'getlinks',
                ['iids' => [$id],
                    'itemtype' => $ptid,
                    'modid' => $sysid,
                    'reverse' => 0,
                ]
            );
            if (is_array($publicationcids) && count($publicationcids) > 0) {
                $publication['cids'] = array_keys($publicationcids);
            }
        }
        if (!isset($publication['cids'])) {
            $publication['cids'] = [];
        }

        if (!isset($cids)) {
            $cids = [];
        }

        $jointcids = [];
        /* TODO: forget about parent/root cids for now
            foreach ($rootcids as $cid) {
                $jointcids[$cid] = 1;
            }
        */
        foreach ($publication['cids'] as $cid) {
            $jointcids[$cid] = 1;
        }
        // FIXME: the line within the foreach is known to give an illegal offset error, not sure how to properly
        // fix it. Only seen on using xmlrpc and bloggerapi.
        foreach ($cids as $cid) {
            if (empty($cid) || !is_numeric($cid)) {
                continue;
            }
            $jointcids[$cid] = 1;
        }

        // TODO 1: find a way to combine checking over several categories
        // TODO 2: find a way to check parent categories for privileges too

        // TODO 3: find a way to specify current user in privileges too
        // TODO 4: find a way to check parent groups of authors for privileges too ??

        if (empty($ptid)) {
            $ptid = 'All';
        }
        if (count($jointcids) == 0) {
            $jointcids['All'] = 1;
        }
        // TODO: check for anonymous publications
        if (!isset($owner)) {
            $owner = 'All';
        }
        if (empty($id)) {
            $id = 'All';
        }

        // Loop over all categories and check the different combinations
        $result = false;
        foreach (array_keys($jointcids) as $cid) {
            // TODO: do we want all-or-nothing access here, or is one access enough ?
            if ($this->sec()->check($mask, 0, 'Publication', "$ptid:$cid:$owner:$id")) {
                $result = true;
            }
        }
        return $result;
    }
}

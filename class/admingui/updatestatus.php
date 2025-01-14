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
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarSec;
use xarMod;
use xarCoreCache;
use xarSession;
use xarController;
use sys;
use BadParameterException;
use DataNotFoundException;
use ForbiddenOperationException;

sys::import('xaraya.modules.method');

/**
 * publications admin updatestatus function
 * @extends MethodClass<AdminGui>
 */
class UpdatestatusMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * update item from publications_admin_modify
     */
    public function updatestate(array $args = [])
    {
        if (!$this->checkAccess('EditPublications')) {
            return;
        }

        // Get parameters
        if (!$this->fetch('ids', 'isset', $ids, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('state', 'isset', $state, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('catid', 'isset', $catid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('ptid', 'isset', $ptid, null, xarVar::DONT_SET)) {
            return;
        }


        // Confirm authorisation code
        if (!$this->confirmAuthKey()) {
            return;
        }

        if (!isset($ids) || count($ids) == 0) {
            $msg = $this->translate('No publications selected');
            throw new DataNotFoundException(null, $msg);
        }
        $states = xarMod::apiFunc('publications', 'user', 'getstates');
        if (!isset($state) || !is_numeric($state) || $state < -1 || ($state != -1 && !isset($states[$state]))) {
            $msg = $this->translate('Invalid state');
            throw new BadParameterException(null, $msg);
        }

        $pubtypes = xarMod::apiFunc('publications', 'user', 'get_pubtypes');
        if (!empty($ptid)) {
            $descr = $pubtypes[$ptid]['description'];
        } else {
            $descr = $this->translate('Publications');
            $ptid = null;
        }

        // We need to tell some hooks that we are coming from the update state screen
        // and not the update the actual article screen.  Right now, the keywords vanish
        // into thin air.  Bug 1960 and 3161
        xarCoreCache::setCached('Hooks.all', 'noupdate', 1);

        foreach ($ids as $id => $val) {
            if ($val != 1) {
                continue;
            }
            // Get original article information
            $article = xarMod::apiFunc(
                'publications',
                'user',
                'get',
                ['id' => $id,
                    'withcids' => 1, ]
            );
            if (!isset($article) || !is_array($article)) {
                $msg = $this->translate(
                    'Unable to find #(1) item #(2)',
                    $descr,
                    xarVar::prepForDisplay($id)
                );
                throw new BadParameterException(null, $msg);
            }
            $article['ptid'] = $article['pubtype_id'];
            // Security check
            $input = [];
            $input['article'] = $article;
            if ($state < 0) {
                $input['mask'] = 'ManagePublications';
            } else {
                $input['mask'] = 'EditPublications';
            }
            if (!xarMod::apiFunc('publications', 'user', 'checksecurity', $input)) {
                $msg = $this->translate(
                    'You have no permission to modify #(1) item #(2)',
                    $descr,
                    xarVar::prepForDisplay($id)
                );
                throw new ForbiddenOperationException(null, $msg);
            }

            if ($state < 0) {
                // Pass to API
                if (!xarMod::apiFunc('publications', 'admin', 'delete', $article)) {
                    return; // throw back
                }
            } else {
                // Update the state now
                $article['state'] = $state;

                // Pass to API
                if (!xarMod::apiFunc('publications', 'admin', 'update', $article)) {
                    return; // throw back
                }
            }
        }
        unset($article);

        // Return to the original admin view
        $lastview = xarSession::getVar('Publications.LastView');
        if (isset($lastview)) {
            $lastviewarray = unserialize($lastview);
            if (!empty($lastviewarray['ptid']) && $lastviewarray['ptid'] == $ptid) {
                extract($lastviewarray);
                $this->redirect($this->getUrl(
                    'admin',
                    'view',
                    ['ptid' => $ptid,
                        'catid' => $catid,
                        'state' => $state,
                        'startnum' => $startnum, ]
                ));
                return true;
            }
        }

        if (empty($catid)) {
            $catid = null;
        }
        $this->redirect($this->getUrl(
            'admin',
            'view',
            ['ptid' => $ptid, 'catid' => $catid]
        ));

        return true;
    }
}

<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Articles Module
 * @link http://xaraya.com/index.php/release/151.html
 * @author mikespub
 */
/**
 * update item from articles_admin_modify
 */
function articles_admin_updatestatus()
{
    // Get parameters
    if(!xarVarFetch('ids',   'isset', $ids,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('status', 'isset', $status,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('catid',  'isset', $catid,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptid',   'isset', $ptid,    NULL, XARVAR_DONT_SET)) {return;}


    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;

    if (!isset($ids) || count($ids) == 0) {
        $msg = xarML('No articles selected');
        throw new DataNotFoundException(null, $msg);
    }
    $states = xarModAPIFunc('articles','user','getstates');
    if (!isset($status) || !is_numeric($status) || $status < -1 || ($status != -1 && !isset($states[$status]))) {
        $msg = xarML('Invalid status');
        throw new BadParameterException(null,$msg);
    }

    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
    if (!empty($ptid)) {
        $descr = $pubtypes[$ptid]['descr'];
    } else {
        $descr = xarML('Articles');
        $ptid = null;
    }

    // We need to tell some hooks that we are coming from the update status screen
    // and not the update the actual article screen.  Right now, the keywords vanish
    // into thin air.  Bug 1960 and 3161
    xarVarSetCached('Hooks.all','noupdate',1);

    foreach ($ids as $id => $val) {
        if ($val != 1) {
            continue;
        }
        // Get original article information
        $article = xarModAPIFunc('articles',
                                 'user',
                                 'get',
                                 array('id' => $id,
                                       'withcids' => 1));
        if (!isset($article) || !is_array($article)) {
            $msg = xarML('Unable to find #(1) item #(2)',
                         $descr, xarVarPrepForDisplay($id));
            throw new BadParameterException(null,$msg);
        }
        $article['ptid'] = $article['pubtypeid'];
        // Security check
        $input = array();
        $input['article'] = $article;
        if ($status < 0) {
            $input['mask'] = 'DeleteArticles';
        } else {
            $input['mask'] = 'EditArticles';
        }
        if (!xarModAPIFunc('articles','user','checksecurity',$input)) {
            $msg = xarML('You have no permission to modify #(1) item #(2)',
                         $descr, xarVarPrepForDisplay($id));
            throw new ForbiddenOperationException(null, $msg);
        }

        if ($status < 0) {
            // Pass to API
            if (!xarModAPIFunc('articles', 'admin', 'delete', $article)) {
                return; // throw back
            }
        } else {
            // Update the status now
            $article['status'] = $status;

            // Pass to API
            if (!xarModAPIFunc('articles', 'admin', 'update', $article)) {
                return; // throw back
            }
        }
    }
    unset($article);

    // Return to the original admin view
    $lastview = xarSession::getVar('Articles.LastView');
    if (isset($lastview)) {
        $lastviewarray = unserialize($lastview);
        if (!empty($lastviewarray['ptid']) && $lastviewarray['ptid'] == $ptid) {
            extract($lastviewarray);
            xarResponseRedirect(xarModURL('articles', 'admin', 'view',
                                          array('ptid' => $ptid,
                                                'catid' => $catid,
                                                'status' => $status,
                                                'startnum' => $startnum)));
            return true;
        }
    }

    if (empty($catid)) {
        $catid = null;
    }
    xarResponseRedirect(xarModURL('articles', 'admin', 'view',
                                  array('ptid' => $ptid, 'catid' => $catid)));

    return true;
}

?>

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
use sys;

sys::import('xaraya.modules.method');

/**
 * publications admin main function
 * @extends MethodClass<AdminGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * the main administration function
     * It currently redirects to the admin-view function
     * @return bool|void true on success
     * @see AdminGui::main()
     */
    public function __invoke(array $args = [])
    {
        // Security Check
        if (!$this->sec()->checkAccess('EditPublications')) {
            return;
        }

        $redirect = $this->mod()->getVar('backend_page');
        if (!empty($redirect)) {
            $truecurrenturl = $this->ctl()->getCurrentURL([], false);
            $urldata = $this->mod()->apiFunc('roles', 'user', 'parseuserhome', ['url' => $redirect,'truecurrenturl' => $truecurrenturl]);
            $this->ctl()->redirect($urldata['redirecturl']);
            return true;
        } else {
            $this->ctl()->redirect($this->mod()->getURL('admin', 'view'));
        }
        return true;
    }
}

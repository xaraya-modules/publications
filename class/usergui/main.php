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


use Xaraya\Modules\Publications\UserGui;
use Xaraya\Modules\MethodClass;
use xarModVars;
use xarVar;
use xarController;
use xarServer;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications user main function
 * @extends MethodClass<UserGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * the main user function
     * @see UserGui::main()
     */
    public function __invoke(array $args = [])
    {
        # --------------------------------------------------------
        #
        # Try getting the id of the default page.
        #
        $id = $this->mod()->getVar('defaultpage');

        if (!empty($id)) {
            # --------------------------------------------------------
            #
            # Get the ID of the translation if required
            #
            if (!$this->var()->find('translate', $translate, 'int:1', 1)) {
                return;
            }
            return $this->ctl()->redirect($this->mod()->getURL(
                'user',
                'display',
                ['itemid' => $id,'translate' => $translate]
            ));
        } else {
            # --------------------------------------------------------
            #
            # No default page, check for a redirect or just show the view page
            #
            $redirect = $this->mod()->getVar('frontend_page');
            if (!empty($redirect)) {
                $truecurrenturl = $this->ctl()->getCurrentURL([], false);
                $urldata = xarMod::apiFunc('roles', 'user', 'parseuserhome', ['url' => $redirect,'truecurrenturl' => $truecurrenturl]);
                $this->ctl()->redirect($urldata['redirecturl']);
                return true;
            } else {
                $this->ctl()->redirect($this->mod()->getURL('user', 'view'));
            }
            return true;
        }
    }
}

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
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;
use xarModHooks;
use sys;
use DataNotFoundException;

sys::import('xaraya.modules.method');

/**
 * publications user redirect function
 * @extends MethodClass<UserGui>
 */
class RedirectMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * redirect to a site based on some URL field of the item
     * @see UserGui::redirect()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get parameters from user
        $this->var()->find('id', $id, 'id');

        // Override if needed from argument array
        extract($args);

        if (!isset($id) || !is_numeric($id) || $id < 1) {
            return $this->ml('Invalid publication ID');
        }

        // Load API
        if (!$this->mod()->apiLoad('publications', 'user')) {
            return;
        }

        // Get publication
        $publication = $userapi->get(['id' => $id]
        );

        if (!is_array($publication)) {
            $msg = $this->ml('Failed to retrieve publication in #(3)_#(1)_#(2).php', 'user', 'get', 'publications');
            throw new DataNotFoundException(null, $msg);
        }

        $ptid = $publication['pubtype_id'];

        // Get publication types
        $pubtypes = $userapi->get_pubtypes();

        // TODO: improve this e.g. when multiple URL fields are present
        // Find an URL field based on the pubtype configuration
        foreach ($pubtypes[$ptid]['config'] as $field => $value) {
            if (empty($value['label'])) {
                continue;
            }
            if ($value['format'] == 'url' && !empty($publication[$field]) && $publication[$field] != 'http://') {
                // TODO: add some verifications here !
                $hooks = xarModHooks::call(
                    'item',
                    'display',
                    $id,
                    ['module'    => 'publications',
                        'itemtype'  => $ptid,
                    ],
                    'publications'
                );
                $this->ctl()->redirect($article[$field]);
                return true;
            } elseif ($value['format'] == 'urltitle' && !empty($publication[$field]) && substr($publication[$field], 0, 2) == 'a:') {
                $array = unserialize($publication[$field]);
                if (!empty($array['link']) && $array['link'] != 'http://') {
                    $hooks = xarModHooks::call(
                        'item',
                        'display',
                        $id,
                        ['module'    => 'publications',
                            'itemtype'  => $ptid,
                        ],
                        'publications'
                    );
                    $this->ctl()->redirect($array['link']);
                    return true;
                }
            }
        }

        return $this->ml('Unable to find valid redirect field');
    }
}

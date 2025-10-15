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
use sys;

sys::import('xaraya.modules.method');

/**
 * publications userapi getpubfields function
 * @extends MethodClass<UserApi>
 */
class GetpubfieldsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get array of configurable fields for publication types
     * @TODO : add dynamic fields here for .81+
     * @return array array('title'   => array('label'  => $this->ml('...'),
     * 'format' => '...',
     * 'input'  => 1),
     * 'summary' => array('label'  => $this->ml('...'),
     * 'format' => '...',
     * 'input'  => 1),
     * ...);
     * @see UserApi::getpubfields()
     */
    public function __invoke(array $args = [])
    {
        return [
            'title'    => ['label'  => $this->ml('Title'),
                'format' => 'textbox',
                'input'  => 1, ],
            'summary'  => ['label'  => $this->ml('Summary'),
                'format' => 'textarea_medium',
                'input'  => 1, ],
            'body' => ['label'  => $this->ml('Body'),
                'format' => 'textarea_large',
                'input'  => 1, ],
            'notes'    => ['label'  => $this->ml('Notes'),
                'format' => 'textarea',
                'input'  => 0, ],
            'owner' => ['label'  => $this->ml('Author'),
                'format' => 'username',
                'input'  => 0, ],
            'pubdate'  => ['label'  => $this->ml('Publication Date'),
                'format' => 'calendar',
                'input'  => 0, ],
            'state'   => ['label'  => $this->ml('Status'),
                'format' => 'state',
                'input'  => 0, ],
        ];
    }
}

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
use BadParameterException;

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
     * @return array array('title'   => array('label'  => $this->translate('...'),
     * 'format' => '...',
     * 'input'  => 1),
     * 'summary' => array('label'  => $this->translate('...'),
     * 'format' => '...',
     * 'input'  => 1),
     * ...);
     */
    public function __invoke(array $args = [])
    {
        return [
            'title'    => ['label'  => $this->translate('Title'),
                'format' => 'textbox',
                'input'  => 1, ],
            'summary'  => ['label'  => $this->translate('Summary'),
                'format' => 'textarea_medium',
                'input'  => 1, ],
            'body' => ['label'  => $this->translate('Body'),
                'format' => 'textarea_large',
                'input'  => 1, ],
            'notes'    => ['label'  => $this->translate('Notes'),
                'format' => 'textarea',
                'input'  => 0, ],
            'owner' => ['label'  => $this->translate('Author'),
                'format' => 'username',
                'input'  => 0, ],
            'pubdate'  => ['label'  => $this->translate('Publication Date'),
                'format' => 'calendar',
                'input'  => 0, ],
            'state'   => ['label'  => $this->translate('Status'),
                'format' => 'state',
                'input'  => 0, ],
        ];
    }
}

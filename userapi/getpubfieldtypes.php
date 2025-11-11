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

/**
 * publications userapi getpubfieldtypes function
 * @extends MethodClass<UserApi>
 */
class GetpubfieldtypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get array of (fixed) field types for publication types
     * @TODO : add dynamic fields here for .81+
     * @return array array('title'   => 'string',
     * 'summary' => 'text',
     * ...);
     * @see UserApi::getpubfieldtypes()
     */
    public function __invoke(array $args = [])
    {
        return [
            'title'    => 'string',
            'summary'  => 'text',
            'notes'    => 'text',
            'body'     => 'text',
            'owner' => 'integer',
            'pubdate'  => 'integer',
            'state'   => 'integer',
        ];
    }
}

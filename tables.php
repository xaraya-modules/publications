<?php

/**
 * Publications Module
 *
 * @package modules
 * @subpackage publications module
 * @category Third Party Xaraya Module
 * @version 2.0.0
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author mikespub
 */

namespace Xaraya\Modules\Publications;

use xarDB;

class Tables
{
    /**
     * Manage the tables in publications
     *
     * @return array with the tables used in publications
     */
    public function __invoke(?string $prefix = null)
    {
        $prefix ??= xarDB::getPrefix();
        $xartable['publications'] = $prefix . '_publications';
        $xartable['publications_types'] = $prefix . '_publications_types';

        // Return table information
        return $xartable;
    }
}

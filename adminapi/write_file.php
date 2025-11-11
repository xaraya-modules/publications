<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\AdminApi;

use Xaraya\Modules\Publications\AdminApi;
use Xaraya\Modules\MethodClass;
use Exception;

/**
 * publications adminapi write_file function
 * @extends MethodClass<AdminApi>
 */
class WriteFileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Publications Module
     * @package modules
     * @subpackage publications module
     * @category Third Party Xaraya Module
     * @version 2.0.0
     * @copyright (C) 2012 Netspan AG
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @author Marc Lutolf <mfl@netspan.ch>
     * @see AdminApi::writeFile()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['file'])) {
            return false;
        }
        try {
            $dir = dirname($args['file']);
            if (!file_exists($dir)) {
                mkdir($dir, 0o777, true);
            }
            $fp = fopen($args['file'], "wb");

            fwrite($fp, $args['data']);
            fclose($fp);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

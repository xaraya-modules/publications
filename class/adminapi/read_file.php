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

use Xaraya\Modules\MethodClass;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications adminapi read_file function
 */
class ReadFileMethod extends MethodClass
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
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['file'])) {
            return false;
        }
        try {
            $data = "";
            if (file_exists($args['file'])) {
                $fp = fopen($args['file'], "rb");
                while (!feof($fp)) {
                    $filestring = fread($fp, 4096);
                    $data .=  $filestring;
                }
                fclose($fp);
            }
            return $data ;
        } catch (Exception $e) {
            return '';
        }
    }
}

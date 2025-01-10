<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Publications;

use Xaraya\Modules\AdminGuiClass;
use sys;

sys::import('xaraya.modules.admingui');
sys::import('modules.publications.class.adminapi');

/**
 * Handle the publications admin GUI
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...

    public function is_writeable_dir($path)
    {
        $patharray = explode("/", $path);
        array_shift($patharray);
        $path = "themes";
        foreach ($patharray as $child) {
            if (!file_exists($path . "/" . $child)) {
                break;
            }
            $path = $path . "/" . $child;
        }
        return $this->check_dir($path);
    }

    /**
     * Check whether directory permissions allow to write and read files inside it
     *
     * @access private
     * @param string dirname directory name
     * @return boolean true if directory is writable, readable and executable
     */
    public function check_dir($dirname)
    {
        if (@touch($dirname . '/.check_dir')) {
            $fd = @fopen($dirname . '/.check_dir', 'r');
            if ($fd) {
                fclose($fd);
                unlink($dirname . '/.check_dir');
            } else {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }
}

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
sys::import('modules.publications.adminapi');

/**
 * Handle the publications admin GUI
 *
 * @method mixed clone(array $args)
 * @method mixed create(array $args)
 * @method mixed delete(array $args)
 * @method mixed deletePubtype(array $args)
 * @method mixed deleteTranslation(array $args)
 * @method mixed display(array $args)
 * @method mixed displayVersion(array $args)
 * @method mixed importpages(array $args)
 * @method mixed importpictures(array $args)
 * @method mixed importpubtype(array $args)
 * @method mixed importwebpage(array $args)
 * @method mixed main(array $args)
 * @method mixed manageVersions(array $args)
 * @method mixed modify(array $args)
 * @method mixed modifyPubtype(array $args)
 * @method mixed modifyconfig(array $args)
 * @method mixed multiops(array $args)
 * @method mixed new(array $args)
 * @method mixed overview(array $args)
 * @method mixed privileges(array $args)
 * @method mixed stats(array $args)
 * @method mixed stylesheetType(array $args)
 * @method mixed templatesPage(array $args)
 * @method mixed templatesType(array $args)
 * @method mixed update(array $args)
 * @method mixed updateconfig(array $args)
 * @method mixed updatestatus(array $args)
 * @method mixed view(array $args)
 * @method mixed viewPages(array $args)
 * @method mixed viewPubtypes(array $args)
 * @method mixed waitingcontent(array $args)
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

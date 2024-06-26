<?php
/**
 * Publications Module
 *
 * @package modules
 * @subpackage publications module
 * @category Third Party Xaraya Module
 * @version 2.0.0
 * @copyright (C) 2012 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('modules.dynamicdata.class.objects.factory');

function publications_admin_templates_type(array $args = [], $context = null)
{
    if (!xarSecurity::check('AdminPublications')) {
        return;
    }

    extract($args);

    if (!xarVar::fetch('confirm', 'int', $confirm, 0, xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('ptid', 'id', $data['ptid'], xarModVars::get('publications', 'defaultpubtype'), xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('file', 'str', $data['file'], 'summary', xarVar::NOT_REQUIRED)) {
        return;
    }
    if (!xarVar::fetch('source_data', 'str', $data['source_data'], '', xarVar::NOT_REQUIRED)) {
        return;
    }

    $pubtypeobject = DataObjectFactory::getObject(['name' => 'publications_types']);
    $pubtypeobject->getItem(['itemid' => $data['ptid']]);
    $pubtype = explode('_', $pubtypeobject->properties['name']->value);
    $pubtype = $pubtype[1] ?? $pubtype[0];

    $data['object'] = DataObjectFactory::getObject(['name' => $pubtypeobject->properties['name']->value]);

    $basepath = sys::code() . "modules/publications/xartemplates/objects/" . $pubtype;
    $sourcefile = $basepath . "/" . $data['file'] . ".xt";
    $overridepath = "themes/" . xarModVars::get('themes', 'default_theme') . "/modules/publications/objects/" . $pubtype;
    $overridefile = $overridepath . "/" . $data['file'] . ".xt";

    // If we are saving, write the file now
    if ($confirm && !empty($data['source_data'])) {
        xarMod::apiFunc('publications', 'admin', 'write_file', ['file' => $overridefile, 'data' => $data['source_data']]);
    }

    // Let the template know what kind of file this is
    if (file_exists($overridefile)) {
        $data['filetype'] = 'theme';
        $filepath = $overridefile;
        $data['writable'] = is_writable($overridefile);
    } else {
        $data['filetype'] = 'module';
        $filepath = $sourcefile;
        $data['writable'] = is_writeable_dir($overridepath);
    }

    $data['source_data'] = trim(xarMod::apiFunc('publications', 'admin', 'read_file', ['file' => $filepath]));
    $data['filepath'] = $filepath;

    // Initialize the template
    if (empty($data['source_data'])) {
        $source_dist = $basepath . "/" . $data['file'] . "_dist.xt";
        $data['source_data'] = xarMod::apiFunc('publications', 'admin', 'read_file', ['file' => $source_dist]);
        xarMod::apiFunc('publications', 'admin', 'write_file', ['file' => $sourcefile, 'data' => $data['source_data']]);
    }

    $data['files'] = [
        ['id' => 'summary', 'name' => 'summary display'],
        ['id' => 'detail',  'name' => 'detail display'],
        ['id' => 'input',   'name' => 'input form'],
    ];
    return $data;
}

function is_writeable_dir($path)
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
    return check_dir($path);
}

/**
 * Check whether directory permissions allow to write and read files inside it
 *
 * @access private
 * @param string dirname directory name
 * @return boolean true if directory is writable, readable and executable
 */
function check_dir($dirname)
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

<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\AdminGui;

use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarController;
use xarModVars;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin templates_page function
 */
class TemplatesPageMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('ManagePublications')) {
            return;
        }

        extract($args);

        if (!xarVar::fetch('confirm', 'int', $confirm, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('ptid', 'id', $data['ptid'], 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('itemid', 'id', $data['itemid'], 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('file', 'str', $data['file'], 'summary', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('source_data', 'str', $data['source_data'], '', xarVar::NOT_REQUIRED)) {
            return;
        }

        if (empty($data['itemid']) || empty($data['ptid'])) {
            return xarController::notFound(null, $this->getContext());
        }

        $pubtypeobject = DataObjectFactory::getObject(['name' => 'publications_types']);
        $pubtypeobject->getItem(['itemid' => $data['ptid']]);
        $pubtype = explode('_', $pubtypeobject->properties['name']->value);
        $pubtype = $pubtype[1] ?? $pubtype[0];

        $data['object'] = DataObjectFactory::getObject(['name' => $pubtypeobject->properties['name']->value]);

        $basepath = sys::code() . "modules/publications/xartemplates/objects/" . $pubtype;
        $sourcefile = $basepath . "/" . $data['file'] . "_" . $data['itemid'] . ".xt";
        $overridepath = "themes/" . xarModVars::get('themes', 'default_theme') . "/modules/publications/objects/" . $pubtype;
        $overridefile = $overridepath . "/" . $data['file'] . "-" . $data['itemid'] . ".xt";

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
            $data['writable'] = $this->getParent()->is_writeable_dir($overridepath);
        }

        $data['source_data'] = trim(xarMod::apiFunc('publications', 'admin', 'read_file', ['file' => $filepath]));

        // Initialize the template
        if (empty($data['source_data'])) {
            $data['source_data'] = '<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">';
            $data['source_data'] .= "\n";
            $data['source_data'] .= "\n" . '</xar:template>';
        }

        $data['files'] = [
            ['id' => 'summary', 'name' => 'summary display'],
            ['id' => 'detail',  'name' => 'detail display'],
        ];
        return $data;
    }
}

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


use Xaraya\Modules\Publications\AdminGui;
use Xaraya\Modules\Publications\AdminApi;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarMod;
use xarSec;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin importpubtype function
 * @extends MethodClass<AdminGui>
 */
class ImportpubtypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Import an object definition or an object item from XML
     * @see AdminGui::importpubtype()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        if (!$this->sec()->checkAccess('AdminPublications')) {
            return;
        }

        if (!$this->var()->check('import', $import)) {
            return;
        }
        if (!$this->var()->check('xml', $xml)) {
            return;
        }

        extract($args);

        $data = [];
        $data['menutitle'] = $this->ml('Dynamic Data Utilities');

        $data['warning'] = '';
        $data['options'] = [];

        $basedir = 'modules/publications';
        $filetype = 'xml';
        $files = xarMod::apiFunc(
            'dynamicdata',
            'admin',
            'browse',
            ['basedir' => $basedir,
                'filetype' => $filetype, ]
        );
        if (!isset($files) || count($files) < 1) {
            $files = [];
            $data['warning'] = $this->ml('There are currently no XML files available for import in "#(1)"', $basedir);
        }

        if (!empty($import) || !empty($xml)) {
            if (!$this->sec()->confirmAuthKey()) {
                return;
            }

            if (!empty($import)) {
                $found = '';
                foreach ($files as $file) {
                    if ($file == $import) {
                        $found = $file;
                        break;
                    }
                }
                if (empty($found) || !file_exists($basedir . '/' . $file)) {
                    $msg = $this->ml('File not found');
                    throw new BadParameterException(null, $msg);
                }
                $ptid = $adminapi->importpubtype(['file' => $basedir . '/' . $file]
                );
            } else {
                $ptid = $adminapi->importpubtype(['xml' => $xml]
                );
            }
            if (empty($ptid)) {
                return;
            }

            $data['warning'] = $this->ml('Publication type #(1) was successfully imported', $ptid);
        }

        natsort($files);
        array_unshift($files, '');
        foreach ($files as $file) {
            $data['options'][] = ['id' => $file,
                'name' => $file, ];
        }

        $data['authid'] = $this->sec()->genAuthKey();
        return $data;
    }
}

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
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarMod;
use xarSec;
use xarUser;
use DataObjectFactory;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications admin importpictures function
 * @extends MethodClass<AdminGui>
 */
class ImportpicturesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * import pictures into publications
     */
    public function __invoke(array $args = [])
    {
        if (!$this->checkAccess('AdminPublications')) {
            return;
        }

        // Get parameters
        if (!$this->fetch('basedir', 'isset', $basedir, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('baseurl', 'isset', $baseurl, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('thumbnail', 'isset', $thumbnail, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('filelist', 'isset', $filelist, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('refresh', 'isset', $refresh, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('ptid', 'int', $data['ptid'], 5, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('title', 'isset', $title, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('summary', 'isset', $summary, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('content', 'isset', $content, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('usefilemtime', 'isset', $usefilemtime, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('cids', 'isset', $cids, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('test', 'isset', $test, null, xarVar::DONT_SET)) {
            return;
        }
        if (!$this->fetch('import', 'isset', $import, null, xarVar::DONT_SET)) {
            return;
        }

        # --------------------------------------------------------
        #
        # Get the base directory where the html files to be imported are located
        #
        if (!isset($baseurl)) {
            $data['baseurl'] = sys::code() . 'modules/publications/xarimages/';
        } else {
            $data['baseurl'] = $baseurl;
        }
        if (!isset($basedir)) {
            $data['basedir'] = realpath($data['baseurl']);
        } else {
            $data['basedir'] = realpath($basedir);
        }

        if (!isset($thumbnail)) {
            $data['thumbnail'] = 'tn_';
        } else {
            $data['thumbnail'] = $thumbnail;
        }

        $data['filelist'] = xarMod::apiFunc(
            'publications',
            'admin',
            'browse',
            ['basedir' => $data['basedir'],
                'filetype' => '(gif|jpg|jpeg|png)', ]
        );

        // try to match the thumbnails with the pictures
        $data['thumblist'] = [];
        if (!empty($data['thumbnail'])) {
            foreach ($data['filelist'] as $file) {
                // for subdir/myfile.jpg
                $fileparts = pathinfo($file);
                // jpg
                $extension = $fileparts['extension'];
                // subdir
                $dirname = $fileparts['dirname'];
                // myfile
                $basename = $fileparts['basename'];
                $basename = preg_replace("/\.$extension/", '', $basename);
                if (!empty($dirname) && $dirname != '.') {
                    $thumb = $dirname . '/' . $data['thumbnail'] . $basename;
                } else {
                    $thumb = $data['thumbnail'] . $basename;
                }
                // subdir/tn_file.jpg
                if (in_array($thumb . '.' . $extension, $data['filelist'])) {
                    $data['thumblist'][$file] = $thumb . '.' . $extension;

                    // subdir/tn_file_jpg.jpg
                } elseif (in_array($thumb . '_' . $extension . '.' . $extension, $data['filelist'])) {
                    $data['thumblist'][$file] = $thumb . '_' . $extension . '.' . $extension;

                    // subdir/tn_file.jpg.jpg
                } elseif (in_array($thumb . '.' . $extension . '.' . $extension, $data['filelist'])) {
                    $data['thumblist'][$file] = $thumb . '.' . $extension . '.' . $extension;
                }
            }
            if (count($data['thumblist']) > 0) {
                $deletelist = array_values($data['thumblist']);
                $data['filelist'] = array_diff($data['filelist'], $deletelist);
            }
        }

        if (isset($refresh) || isset($test) || isset($import)) {
            // Confirm authorisation code
            if (!$this->confirmAuthKey()) {
                return;
            }
        }

        $data['authid'] = $this->genAuthKey();

        // Get current publication types
        $pubtypes = xarMod::apiFunc('publications', 'user', 'get_pubtypes');

        $data['pubtypes'] = [];
        foreach ($pubtypes as $pubtype) {
            $data['pubtypes'][] = ['id' => $pubtype['id'], 'name' => $pubtype['description']];
        }

        // Set default pubtype to Pictures (if it exists)
        $data['titlefield'] = 'title';
        $data['summaryfield'] = 'summary';
        $data['contentfield'] = 'body';

        $data['fields'] = [];
        $data['cats'] = [];
        if (!empty($data['ptid'])) {
            # --------------------------------------------------------
            #
            # Get the fields of hte chosen pubtype
            #
            sys::import('modules.dynamicdata.class.objects.factory');
            $pubtypeobject = DataObjectFactory::getObject(['name' => 'publications_types']);
            $pubtypeobject->getItem(['itemid' => $data['ptid']]);
            $objectname = $pubtypeobject->properties['name']->value;
            $pageobject = DataObjectFactory::getObject(['name' => $objectname]);

            foreach ($pageobject->properties as $name => $property) {
                if ($property->basetype == 'string') {
                    $data['fields'][] = ['id' => $name, 'name' => $property->label];
                }
            }

            /*
                    $catlist = array();
                    $rootcats = xarMod::apiFunc('categories','user','getallcatbases',array('module' => 'publications','itemtype' => $data['ptid']));
                    foreach ($rootcats as $catid) {
                        $catlist[$catid['category_id']] = 1;
                    }
                    $seencid = array();
                    if (isset($cids) && is_array($cids)) {
                        foreach ($cids as $catid) {
                            if (!empty($catid)) {
                                $seencid[$catid] = 1;
                            }
                        }
                    }
                    $cids = array_keys($seencid);
                    foreach (array_keys($catlist) as $catid) {
                        $data['cats'][] = xarMod::apiFunc('categories',
                                                        'visual',
                                                        'makeselect',
                                                        Array('cid' => $catid,
                                                              'return_itself' => true,
                                                              'select_itself' => true,
                                                              'values' => &$seencid,
                                                              'multiple' => 1));
                    }*/
        }

        $data['selected'] = [];
        if (!isset($refresh) && isset($filelist) && is_array($filelist) && count($filelist) > 0) {
            foreach ($filelist as $file) {
                if (!empty($file) && in_array($file, $data['filelist'])) {
                    $data['selected'][$file] = 1;
                }
            }
        }

        if (isset($title) && isset($data['fields'][$titlefield])) {
            $data['title'] = $title;
        }
        if (isset($summary) && isset($data['fields'][$summaryfield])) {
            $data['summary'] = $summary;
        }
        if (isset($content) && isset($data['fields'][$contentfield])) {
            $data['content'] = $content;
        }

        if (empty($usefilemtime)) {
            $data['usefilemtime'] = 0;
        } else {
            $data['usefilemtime'] = 1;
        }

        if (isset($data['ptid']) && isset($data['content']) && count($data['selected']) > 0
            && (isset($test) || isset($import))) {
            // TODO: allow changing the order of import + editing the titles etc. before creating the publications

            $data['logfile'] = '';
            foreach (array_keys($data['selected']) as $file) {
                $curfile = realpath($basedir . '/' . $file);
                if (!file_exists($curfile) || !is_file($curfile)) {
                    continue;
                }

                $filename = $file;
                if (empty($baseurl)) {
                    $imageurl = $file;
                } elseif (substr($baseurl, -1) == '/') {
                    $imageurl = $baseurl . $file;
                } else {
                    $imageurl = $baseurl . '/' . $file;
                }
                if (!empty($data['thumblist'][$file])) {
                    if (empty($baseurl)) {
                        $thumburl = $data['thumblist'][$file];
                    } elseif (substr($baseurl, -1) == '/') {
                        $thumburl = $baseurl . $data['thumblist'][$file];
                    } else {
                        $thumburl = $baseurl . '/' . $data['thumblist'][$file];
                    }
                } else {
                    $thumburl = '';
                }

                $article = ['title' => ' ',
                    'summary' => '',
                    'body' => '',
                    'notes' => '',
                    'pubdate' => (empty($usefilemtime) ? time() : filemtime($curfile)),
                    'state' => 2,
                    'ptid' => $data['ptid'],
                    'cids' => $cids,
                    // for preview
                    'pubtype_id' => $data['ptid'],
                    'owner' => xarUser::getVar('id'),
                    'id' => 0, ];
                if (!empty($data['title']) && !empty($filename)) {
                    $article[$data['title']] = $filename;
                }
                if (!empty($data['summary']) && !empty($thumburl)) {
                    $article[$data['summary']] = $thumburl;
                }
                if (!empty($data['content']) && !empty($imageurl)) {
                    $article[$data['content']] = $imageurl;
                }
                if (isset($test)) {
                    // preview the first file as a test
                    $data['preview'] = xarMod::guiFunc(
                        'publications',
                        'user',
                        'display',
                        ['article' => $article, 'preview' => true]
                    );
                    break;
                } else {
                    $id = xarMod::apiFunc('publications', 'admin', 'create', $article);
                    if (empty($id)) {
                        return; // throw back
                    } else {
                        $data['logfile'] .= $this->translate('File #(1) was imported as #(2) #(3)', $curfile, $pubtypes[$data['ptid']]['description'], $id);
                        $data['logfile'] .= '<br />';
                    }
                }
            }
        }

        // Return the template variables defined in this function
        return $data;
    }
}

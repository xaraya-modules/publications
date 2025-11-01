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
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\Publications\UserGui;
use Xaraya\Modules\MethodClass;
use sys;

sys::import('xaraya.modules.method');

/**
 * publications admin importwebpage function
 * @extends MethodClass<AdminGui>
 */
class ImportwebpageMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * manage publication types (all-in-one function for now)
     * @see AdminGui::importwebpage()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var UserGui $usergui */
        $usergui = $this->usergui();
        if (!$this->sec()->checkAccess('AdminPublications')) {
            return;
        }

        // Get parameters
        $this->var()->check('url', $data['url'], 'str', '');
        $this->var()->check('refresh', $refresh);
        $this->var()->find('ptid', $data['ptid'], 'int', 0);
        $this->var()->check('contentfield', $data['contentfield'], 'str', '');
        $this->var()->check('titlefield', $data['titlefield'], 'str', '');
        $this->var()->check('cids', $cids);
        $this->var()->check('filterhead', $filterhead);
        $this->var()->check('filtertail', $filtertail);
        $this->var()->check('findtitle', $findtitle);
        $this->var()->check('numrules', $numrules);
        $this->var()->check('search', $search);
        $this->var()->check('replace', $replace);
        $this->var()->check('test', $test);
        $this->var()->check('import', $import);

        # --------------------------------------------------------
        #
        # Get the URL of the web page to import
        #
        if (isset($refresh) || isset($test) || isset($import)) {
            // Confirm authorisation code
            if (!$this->sec()->confirmAuthKey()) {
                return;
            }
        }

        $data['authid'] = $this->sec()->genAuthKey();

        # --------------------------------------------------------
        #
        # Get the current publication types
        #
        $pubtypes = $userapi->get_pubtypes();

        $data['pubtypes'] = [];
        foreach ($pubtypes as $pubtype) {
            $data['pubtypes'][] = ['id' => $pubtype['id'], 'name' => $pubtype['description']];
        }
        $data['fields'] = [];
        $data['cats'] = [];
        if (!empty($data['ptid'])) {
            # --------------------------------------------------------
            #
            # Get the fields of hte chosen pubtype
            #
            sys::import('modules.dynamicdata.class.objects.factory');
            $pubtypeobject = $this->data()->getObject(['name' => 'publications_types']);
            $pubtypeobject->getItem(['itemid' => $data['ptid']]);
            $objectname = $pubtypeobject->properties['name']->value;
            $pageobject = $this->data()->getObject(['name' => $objectname]);

            foreach ($pageobject->properties as $name => $property) {
                if ($property->basetype == 'string') {
                    $data['fields'][] = ['id' => $name, 'name' => $property->label];
                }
            }
            /*
                    $catlist = array();
                    $rootcats = $this->mod()->apiFunc('categories','user','getallcatbases',array('module' => 'publications','itemtype' => $data['ptid']));
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
                        $data['cats'][] = $this->mod()->apiFunc('categories',
                                                        'visual',
                                                        'makeselect',
                                                        Array('cid' => $catid,
                                                              'return_itself' => true,
                                                              'select_itself' => true,
                                                              'values' => &$seencid,
                                                              'multiple' => 1));
                    }
                    */
        }

        # --------------------------------------------------------
        #
        # Get the data from the form
        #
        if (!isset($filterhead)) {
            $data['filterhead'] = '#^.*<body[^>]*>#is';
        } else {
            $data['filterhead'] = $filterhead;
        }
        if (!isset($filtertail)) {
            $data['filtertail'] = '#</body.*$#is';
        } else {
            $data['filtertail'] = $filtertail;
        }
        if (!isset($findtitle)) {
            $data['findtitle'] = '#<title>(.*?)</title>#is';
        } else {
            $data['findtitle'] = $findtitle;
        }

        if (!isset($numrules)) {
            $numrules = 3;
        }
        $data['search'] = [];
        $data['replace'] = [];
        for ($i = 0; $i < $numrules; $i++) {
            if (isset($search[$i])) {
                $data['search'][$i] = $search[$i];
                if (isset($replace[$i])) {
                    $data['replace'][$i] = $replace[$i];
                } else {
                    $data['replace'][$i] = '';
                }
            } else {
                $data['search'][$i] = '';
                $data['replace'][$i] = '';
            }
        }

        # --------------------------------------------------------
        #
        # Perform the import
        #
        if (!empty($data['ptid']) && isset($data['contentfield'])
            && (isset($test) || isset($import))) {
            $mysearch = [];
            $myreplace = [];
            for ($i = 0; $i < $numrules; $i++) {
                if (!empty($data['search'][$i])) {
                    $mysearch[] = $data['search'][$i];
                    if (!empty($data['replace'][$i])) {
                        $myreplace[] = $data['replace'][$i];
                    } else {
                        $myreplace[] = '';
                    }
                }
            }

            $data['logfile'] = '';

            # --------------------------------------------------------
            #
            # Get the page
            #
            $crl = curl_init();
            $timeout = 5;
            curl_setopt($crl, CURLOPT_URL, $data['url']);
            curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
            $page = curl_exec($crl);
            curl_close($crl);
            if (empty($page)) {
                return $data;
            }
            # --------------------------------------------------------
            #
            # Manipulate the contents
            #
            if (!empty($data['findtitle']) && preg_match($data['findtitle'], $page, $matches)) {
                $title = $matches[1];
            } else {
                $title = '';
            }
            if (!empty($data['filterhead'])) {
                $page = preg_replace($filterhead, '', $page);
            }
            if (!empty($data['filtertail'])) {
                $page = preg_replace($filtertail, '', $page);
            }
            if (count($mysearch) > 0) {
                $page = preg_replace($mysearch, $myreplace, $page);
            }

            $args[$data['contentfield']] = $page;
            if (!empty($data['titlefield'])) {
                $args[$data['titlefield']] = $title;
                $args['name'] = str_replace(' ', '_', trim(strtolower($title)));
            }
            $pageobject = $this->data()->getObject(['name' => $objectname]);
            $pageobject->setFieldValues($args, 1);

            # --------------------------------------------------------
            #
            # Show or save the contents
            #
            if (isset($test)) {
                // preview the first file as a test
                $data['preview'] = $usergui->preview(['object' => $pageobject]
                );
            } else {
                $id = $pageobject->createItem();
                if (empty($id)) {
                    return; // throw back
                } else {
                    $data['logfile'] .= $this->ml('URL #(1) was imported as #(2) with ID #(3)', $data['url'], $pubtypes[$data['ptid']]['description'], $id);
                    $data['logfile'] .= '<br />';
                }
            }
        }

        $data['filterhead'] = $this->prep()->text($data['filterhead']);
        $data['filtertail'] = $this->prep()->text($data['filtertail']);
        $data['findtitle'] = $this->prep()->text($data['findtitle']);
        for ($i = 0; $i < $numrules; $i++) {
            if (!empty($data['search'][$i])) {
                $data['search'][$i] = $this->prep()->text($data['search'][$i]);
            }
            if (!empty($data['replace'][$i])) {
                $data['replace'][$i] = $this->prep()->text($data['replace'][$i]);
            }
        }
        return $data;
    }
}

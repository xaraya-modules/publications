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
use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;
use sys;
use BadParameterException;
use DuplicateException;

sys::import('xaraya.modules.method');

/**
 * publications adminapi importpubtype function
 * @extends MethodClass<AdminApi>
 */
class ImportpubtypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Import an object definition or an object item from XML
     * @see AdminApi::importpubtype()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security check - we require ADMIN rights here
        if (!$this->sec()->checkAccess('AdminPublications')) {
            return;
        }

        extract($args);

        if (empty($xml) && empty($file)) {
            $msg = $this->ml('Missing import file or XML content');
            throw new BadParameterException(null, $msg);
        } elseif (!empty($file) && (!file_exists($file) || !preg_match('/\.xml$/', $file))) {
            $msg = $this->ml('Invalid import file');
            throw new BadParameterException(null, $msg);
        }

        $pubtypes = $userapi->get_pubtypes();

        $proptypes = $this->prop()->getPropertyTypes();
        $name2id = [];
        foreach ($proptypes as $propid => $proptype) {
            $name2id[$proptype['name']] = $propid;
        }

        $prefix = $this->db()->getPrefix();
        $prefix .= '_';

        if (!empty($file)) {
            $fp = @fopen($file, 'r');
            if (!$fp) {
                $msg = $this->ml('Unable to open import file');
                throw new BadParameterException(null, $msg);
            }
        } else {
            $lines = preg_split("/\r?\n/", $xml);
            $maxcount = count($lines);
        }

        $what = '';
        $count = 0;
        $ptid = 0;
        $objectname2objectid = [];
        $objectcache = [];
        $objectmaxid = [];
        while ((!empty($file) && !feof($fp)) || (!empty($xml) && $count < $maxcount)) {
            if (!empty($file)) {
                $line = fgets($fp, 4096);
            } else {
                $line = $lines[$count];
            }
            $count++;
            if (empty($what)) {
                if (preg_match('#<object name="(\w+)">#', $line, $matches)) { // in case we import the object definition
                    $object = [];
                    $object['name'] = $matches[1];
                    $what = 'object';
                } elseif (preg_match('#<items>#', $line)) { // in case we only import data
                    $what = 'item';
                }
            } elseif ($what == 'object') {
                if (preg_match('#<([^>]+)>(.*)</\1>#', $line, $matches)) {
                    $key = $matches[1];
                    $value = $matches[2];
                    if (isset($object[$key])) {
                        if (!empty($file)) {
                            fclose($fp);
                        }
                        $msg = $this->ml('Duplicate definition for #(1) key #(2) on line #(3)', 'object', $this->prep()->text($key), $count);
                        throw new DuplicateException(null, $msg);
                    }
                    $object[$key] = $value;
                } elseif (preg_match('#<config>#', $line)) {
                    if (isset($object['config'])) {
                        if (!empty($file)) {
                            fclose($fp);
                        }
                        $msg = $this->ml('Duplicate definition for #(1) key #(2) on line #(3)', 'object', 'config', $count);
                        throw new DuplicateException(null, $msg);
                    }
                    $config = [];
                    $what = 'config';
                } elseif (preg_match('#<properties>#', $line)) {
                    if (empty($object['name']) || empty($object['moduleid'])) {
                        if (!empty($file)) {
                            fclose($fp);
                        }
                        $msg = $this->ml('Missing keys in object definition');
                        throw new BadParameterException(null, $msg);
                    }
                    // make sure we drop the object id, because it might already exist here
                    unset($object['objectid']);

                    $properties = [];
                    $what = 'property';
                } elseif (preg_match('#<items>#', $line)) {
                    $what = 'item';
                } elseif (preg_match('#</object>#', $line)) {
                    $what = '';
                } else {
                    // multi-line entries not relevant here
                }
            } elseif ($what == 'config') {
                if (preg_match('#<([^>]+)>(.*)</\1>#', $line, $matches)) {
                    $key = $matches[1];
                    $value = $matches[2];
                    if (isset($config[$key])) {
                        if (!empty($file)) {
                            fclose($fp);
                        }
                        $msg = $this->ml('Duplicate definition for #(1) key #(2) on line #(3)', 'config', $this->prep()->text($key), $count);
                        throw new DuplicateException(null, $msg);
                    }
                    $config[$key] = $value;
                } elseif (preg_match('#</config>#', $line)) {
                    // override default view if necessary
                    $config['defaultview'] = 1;

                    $object['config'] = serialize($config);
                    $config = [];
                    $what = 'object';
                } else {
                    // multi-line entries not relevant here
                }
            } elseif ($what == 'property') {
                if (preg_match('#<property name="(\w+)">#', $line, $matches)) {
                    $property = [];
                    $property['name'] = $matches[1];
                } elseif (preg_match('#</property>#', $line)) {
                    if (empty($property['name']) || empty($property['type'])) {
                        if (!empty($file)) {
                            fclose($fp);
                        }
                        $msg = $this->ml('Missing keys in property definition');
                        throw new BadParameterException(null, $msg);
                    }
                    // make sure we drop the property id, because it might already exist here
                    unset($property['id']);

                    // TODO: watch out for multi-sites
                    // replace default xar_* table prefix with local one
                    $property['source'] = preg_replace("/^xar_/", $prefix, $property['source']);

                    // add this property to the list
                    $properties[] = $property;
                } elseif (preg_match('#<([^>]+)>(.*)</\1>#', $line, $matches)) {
                    $key = $matches[1];
                    $value = $matches[2];
                    if (isset($property[$key])) {
                        if (!empty($file)) {
                            fclose($fp);
                        }
                        $msg = $this->ml('Duplicate definition for #(1) key #(2) on line #(3)', 'property', $this->prep()->text($key), $count);
                        throw new DuplicateException(null, $msg);
                    }
                    $property[$key] = $value;
                } elseif (preg_match('#</properties>#', $line)) {
                    // 1. make sure we have a unique pubtype name
                    foreach ($pubtypes as $pubid => $pubtype) {
                        if ($object['name'] == $pubtype['name']) {
                            $object['name'] .= '_' . time();
                            break;
                        }
                    }

                    // 2. fill in the pubtype field config
                    $fields = [];
                    $extra = [];
                    foreach ($properties as $property) {
                        $field = $property['name'];
                        switch ($field) {
                            case 'id':
                            case 'pubtype_id':
                                // skip these
                                break;

                            case 'title':
                            case 'summary':
                            case 'body':
                            case 'notes':
                            case 'owner':
                            case 'pubdate':
                            case 'state':
                                // convert property type to string if necessary
                                if (is_numeric($property['type'])) {
                                    if (isset($proptypes[$property['type']])) {
                                        $property['type'] = $proptypes[$property['type']]['name'];
                                    } else {
                                        $property['type'] = 'static';
                                    }
                                }
                                // reset disabled field labels to empty
                                if (empty($property['state'])) {
                                    $property['label'] = '';
                                }
                                if (!isset($property['validation'])) {
                                    $property['validation'] = '';
                                }
                                $fields[$field] = ['label' => $property['label'],
                                    'format' => $property['type'],
                                    'input' => $property['input'],
                                    'validation' => $property['validation'],
                                ];
                                break;

                            default:
                                // convert property type to numeric if necessary
                                if (!is_numeric($property['type'])) {
                                    if (isset($name2id[$property['type']])) {
                                        $property['type'] = $name2id[$property['type']];
                                    } else {
                                        $property['type'] = 1;
                                    }
                                }
                                $extra[] = $property;
                                break;
                        }
                    }

                    // 3. create the pubtype
                    $ptid = $adminapi->createpubtype(['name' => $object['name'],
                            'descr' => $object['label'],
                            'config' => $fields, ]
                    );
                    if (empty($ptid)) {
                        return;
                    }

                    // 4. set the module variables
                    $this->mod()->setVar('settings.' . $ptid, $object['config']);
                    $this->mod()->setVar('number_of_categories.' . $ptid, 0);
                    $this->mod()->setVar('mastercids.' . $ptid, '');

                    // 5. create a dynamic object if necessary
                    if (count($extra) > 0) {
                        $object['itemtype'] = $ptid;
                        $object['config'] = '';
                        $object['isalias'] = 0;
                        $objectid = $this->mod()->apiFunc(
                            'dynamicdata',
                            'admin',
                            'createobject',
                            $object
                        );
                        if (!isset($objectid)) {
                            if (!empty($file)) {
                                fclose($fp);
                            }
                            return;
                        }

                        // 6. create the dynamic properties
                        foreach ($extra as $property) {
                            $property['objectid'] = $objectid;
                            $property['moduleid'] = $object['moduleid'];
                            $property['itemtype'] = $object['itemtype'];

                            $prop_id = $this->mod()->apiFunc(
                                'dynamicdata',
                                'admin',
                                'createproperty',
                                $property
                            );
                            if (!isset($prop_id)) {
                                if (!empty($file)) {
                                    fclose($fp);
                                }
                                return;
                            }
                        }

                        // 7. check if we need to enable DD hooks for this pubtype
                        if (!$this->mod()->isHooked('dynamicdata', 'publications')) {
                            $this->mod()->apiFunc(
                                'modules',
                                'admin',
                                'enablehooks',
                                ['callerModName' => 'publications',
                                    'callerItemType' => $ptid,
                                    'hookModName' => 'dynamicdata', ]
                            );
                        }
                    }

                    $properties = [];
                    $what = 'object';
                } elseif (preg_match('#<items>#', $line)) {
                    $what = 'item';
                } elseif (preg_match('#</object>#', $line)) {
                    $what = '';
                } else {
                    // multi-line entries not relevant here
                }
            } elseif ($what == 'item') {
                /* skip this for publications
                            if (preg_match('#<([^> ]+) itemid="(\d+)">#',$line,$matches)) {
                                // find out what kind of item we're dealing with
                                $objectname = $matches[1];
                                $itemid = $matches[2];
                                if (empty($objectname2objectid[$objectname])) {
                                    $objectinfo = $this->data()->getObjectInfo(array('name' => $objectname));
                                    if (isset($objectinfo) && !empty($objectinfo['objectid'])) {
                                        $objectname2objectid[$objectname] = $objectinfo['objectid'];
                                    } else {
                                        if (!empty($file)) fclose($fp);
                                        $msg = $this->ml('Unknown #(1) "#(2)" on line #(3)','object',$this->prep()->text($objectname),$count);
                                        throw new BadParameterException(null, $msg);
                                    }
                                }
                                $objectid = $objectname2objectid[$objectname];
                                $item = array();
                                // don't save the item id for now...
                            // TODO: keep the item id if we set some flag
                                //$item['itemid'] = $itemid;
                                $closeitem = $objectname;
                                $closetag = 'N/A';
                            } elseif (preg_match("#</$closeitem>#",$line)) {
                                // let's create the item now...
                                if (!isset($objectcache[$objectid])) {
                                    $objectcache[$objectid] = new DataObject(array('objectid' => $objectid));
                                }
                                // set the item id to 0
                            // TODO: keep the item id if we set some flag
                                $item['itemid'] = 0;
                                // create the item
                                $itemid = $objectcache[$objectid]->createItem($item);
                                if (empty($itemid)) {
                                    if (!empty($file)) fclose($fp);
                                    return;
                                }
                                // keep track of the highest item id
                                if (empty($objectmaxid[$objectid]) || $objectmaxid[$objectid] < $itemid) {
                                    $objectmaxid[$objectid] = $itemid;
                                }
                                $closeitem = 'N/A';
                                $closetag = 'N/A';
                            } elseif (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                                $key = $matches[1];
                                $value = $matches[2];
                                if (isset($item[$key])) {
                                    if (!empty($file)) fclose($fp);
                                    $msg = $this->ml('Duplicate definition for #(1) key #(2) on line #(3)','item',$this->prep()->text($key),$count);
                                    throw new DuplicateException(null, $msg);
                                }
                                $item[$key] = $value;
                                $closetag = 'N/A';
                            } elseif (preg_match('#<([^/>]+)>(.*)#',$line,$matches)) {
                                // multi-line entries *are* relevant here
                                $key = $matches[1];
                                $value = $matches[2];
                                if (isset($item[$key])) {
                                    if (!empty($file)) fclose($fp);
                                    $msg = $this->ml('Duplicate definition for #(1) key #(2)','item',$this->prep()->text($key));
                                    throw new DuplicateException(null, $msg);
                                }
                                $item[$key] = $value;
                                $closetag = $key;
                            } elseif (preg_match("#(.*)</$closetag>#",$line,$matches)) {
                                // multi-line entries *are* relevant here
                                $value = $matches[1];
                                if (!isset($item[$closetag])) {
                                    if (!empty($file)) fclose($fp);
                                    $msg = $this->ml('Undefined #(1) key #(2)','item',$this->prep()->text($closetag));
                                    throw new BadParameterException(null, $msg);
                                }
                                $item[$closetag] .= $value;
                                $closetag = 'N/A';
                            } elseif ($closetag != 'N/A') {
                                // multi-line entries *are* relevant here
                                if (!isset($item[$closetag])) {
                                    if (!empty($file)) fclose($fp);
                                    $msg = $this->ml('Undefined #(1) key #(2)','item',$this->prep()->text($closetag));
                                    throw new BadParameterException(null, $msg);
                                }
                                $item[$closetag] .= $line;
                            } elseif (preg_match('#</items>#',$line)) {
                skip this for publications */
                if (preg_match('#</items>#', $line)) {
                    $what = 'object';
                } elseif (preg_match('#</object>#', $line)) {
                    $what = '';
                } else {
                }
            } else {
            }
        }
        if (!empty($file)) {
            fclose($fp);
        }
        return $ptid;
    }
}

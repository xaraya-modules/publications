<?php

/**
 * @package modules\publications
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Publications\UserApi;


use Xaraya\Modules\Publications\UserApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi getrandom function
 * @extends MethodClass<UserApi>
 */
class GetrandomMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get Random Publication(s)
     * Note : the following parameters are all optional
     * @author Michel Dalle <mikespub@xaraya.com>
     * @param array<mixed> $args
     * @var int $numitems number of publications to get
     * @var int $ptid publication type ID (for news, sections, reviews, ...)
     * @var array $state array of requested status(es) for the publications
     * @var array $cids array of category IDs for which to get publications (OR/AND)
     * (for all categories don?t set it)
     * @var bool $andcids true means AND-ing categories listed in cids
     * @var array $fields array with all the fields to return per article
     * Default list is : 'id','title','summary','owner',
     * 'pubdate','pubtype_id','notes','state','body'
     * Optional fields : 'cids','author','counter','rating','dynamicdata'
     * @var string $locale language/locale (if not using multi-sites, categories etc.)
     * @var bool $unique return unique results
     * @return array|void of publications, or false on failure
     */
    public function __invoke(array $args = [])
    {
        // 1. count the number of items that apply
        $count = xarMod::apiFunc('publications', 'user', 'countitems', $args);
        if (empty($count)) {
            return [];
        }

        // 2. retrieve numitems random publications
        if (empty($args['numitems'])) {
            $numitems = 1;
        } else {
            $numitems = $args['numitems'];
        }

        $idlist = [];
        if (empty($args['unique'])) {
            $args['unique'] = false;
        } else {
            $args['unique'] = true;
        }

        $publications = [];
        mt_srand((float) microtime() * 1000000);

        if ($count <= $numitems) {
            unset($args['numitems']);
            // retrieve all publications and randomize the order
            $items = xarMod::apiFunc('publications', 'user', 'getall', $args);
            $randomkeys = array_rand($items, $count);
            if (!is_array($randomkeys)) {
                $randomkeys = [$randomkeys];
            }
            foreach ($randomkeys as $key) {
                array_push($publications, $items[$key]);
            }
        } else {
            // retrieve numitems x 1 random article
            $args['numitems'] = 1;

            for ($i = 0; $i < $numitems; $i++) {
                $args['startnum'] = mt_rand(1, $count);

                if ($args['unique'] && in_array($args['startnum'], $idlist)) {
                    $i--;
                } else {
                    $idlist[] = $args['startnum'];
                    $items = xarMod::apiFunc('publications', 'user', 'getall', $args);
                    if (empty($items)) {
                        break;
                    }
                    array_push($publications, array_pop($items));
                }
            }
        }

        return $publications;
    }
}

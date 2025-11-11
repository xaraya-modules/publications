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
use Query;

/**
 * publications userapi get_nextname function
 * @extends MethodClass<UserApi>
 */
class GetNextnameMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get a default name for the current document
     * @param array<mixed> $args
     * @var mixed $ptid int publication type ID (optional) OR
     * @var mixed $name string publication type name (optional)
     * @return array|string|false array(id => array('name' => name, 'description' => description)), or false on
     * failure
     * @see UserApi::getNextname()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (empty($args['ptid'])) {
            return $this->ml('new_publication');
        }

        // Get the namestring for this pubtype
        switch ($args['ptid']) {
            case 1: $namestring = 'new';
                break;    // news
            case 2: $namestring = 'doc';
                break;    // document
            case 3: $namestring = 'rev';
                break;    // review
            case 4: $namestring = 'faq';
                break;    // FAQ
            case 5: $namestring = 'pic';
                break;    // picture
            case 6: $namestring = 'web';
                break;    // web page
            case 7: $namestring = 'quo';
                break;    // quote
            case 8: $namestring = 'dow';
                break;    // download
            case 9: $namestring = 'tra';
                break;    // translation
            case 10: $namestring = 'gen';
                break;    // generic
            case 11: $namestring = 'blo';
                break;    // blog
            case 12: $namestring = 'cat';
                break;    // catalogue
            case 13: $namestring = 'eve';
                break;    // event
            default:
                $namestring = $userapi->getsetting(['ptid' => $args['ptid'], 'setting' => 'namestring']);
                break;
        }

        // Get the number of publications of this pubtype and increment by 1
        $tables = & $this->db()->getTables();
        $q = new Query('SELECT', $tables['publications']);
        $q->eq('pubtype_id', $args['ptid']);
        $q->addfield('COUNT(*)');
        $q->run();
        $count = $q->row();
        $count = (int) reset($count);
        $count++;

        // Put them together
        if (!empty($namestring)) {
            $namestring .= "_";
        }
        $namestring .= $count;
        return $namestring;
    }
}

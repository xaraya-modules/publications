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

/**
 * publications userapi generatekeywords function
 * @extends MethodClass<UserApi>
 */
class GeneratekeywordsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * create a keyword list from a given article
     * @param mixed $args array containing text from an article
     * @return string
     * @see UserApi::generatekeywords()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // Strip -all- html
        $htmlless = strip_tags($incomingkey);

        // Strip anything that isn't alphanumeric or _ -
        $symbolLess = trim(mb_ereg_replace('([^a-zA-Z0-9_-])+', ' ', $htmlless));

        // Remove duplicate words
        $keywords = explode(" ", strtolower($symbolLess));
        $keywords = array_unique($keywords);

        $list = [];
        // Remove words that are < four characters in length
        foreach ($keywords as $word) {
            if (strlen($word) >= 4 && !empty($word)) {
                $list[] = $word;
            }
        }
        $keywords = $list;

        // Sort the list of words in Ascending order Alphabetically
        sort($keywords, SORT_STRING);

        // Merge the list of words into a single, comma delimited string of keywords
        $keywords = implode(",", $keywords);

        return $keywords;
    }
}

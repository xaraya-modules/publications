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
use xarModVars;
use xarUser;
use xarDB;
use Query;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * publications userapi gettranslationid function
 * @extends MethodClass<UserApi>
 */
class GettranslationidMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Parameters
     * $id: the id of the page to be displayed
     * $partiallocale: 1 if locales are of the form de_DE, else 0
     * $locale: a partial or full locale that we want the page ID of
     *
     * N.B. set $locale to $this->mod()->getVar('defaultlanguage') to force returning the base translation ID
     * @see UserApi::gettranslationid()
     */
    public function __invoke(array $args = [])
    {
        if (!isset($args['id'])) {
            throw new BadParameterException('id');
        }
        if (empty($args['id'])) {
            return 0;
        }

        // We can check on a full locale or just a partial one (excluding charset)
        if (empty($args['partiallocale'])) {
            $args['partiallocale'] = 0;
        }
        // We can look for a specific translation
        if (empty($args['locale'])) {
            $locale = xarUser::getNavigationLocale();
        } else {
            $locale = $args['locale'];
        }

        sys::import('xaraya.structures.query');

        if ($args['partiallocale']) {
            $parts = explode('.', $locale);
            $locale = $parts[0];
        }

        $xartable = & $this->db()->getTables();

        if (empty($args['locale'])) {
            // Return the id of the translation if it exists, or else the base document
            // Strategy: don't filter on locale in the SQL, so that we are assured of a non-empty result.
            $q = new Query('SELECT');
            $q->setdistinct('id');
            $q->addtable($xartable['publications'], 't1');
            $q->addtable($xartable['publications'], 't2');
            $q->join('t1.parent_id', 't2.parent_id');
            $q->addfield('t2.id AS id');
            $q->addfield('t2.parent_id AS parent_id');
            $q->addfield('t2.locale AS locale');
            $d[] = $q->peq('t1.parent_id', (int) $args['id']);
            $c[] = $q->peq('t1.id', (int) $args['id']);
            $c[] = $q->pne('t1.parent_id', 0);
            $d[] = $q->qand($c);
            $q->qor($d);
            // The query fails: return the input value
            if (!$q->run()) {
                return (int) $args['id'];
            }
            $result = $q->output();
            // The result is empty (no children): return the input value
            if (empty($result)) {
                return (int) $args['id'];
            }
            // Go through the results for the (first) one with the correct locale
            foreach ($result as $row) {
                if ($locale == $row['locale']) {
                    return $row['id'];
                }
            }
            // If nothing was returned it means either the base document has the correct locale,
            // or no document in this group has it. Either way we need to return the base document.
            return (int) $row['parent_id'];
        } elseif ($args['locale'] == xarUser::getNavigationLocale()) {
            // No need to look further
            return $args['id'];
        } elseif ($args['locale'] == $this->mod()->getVar('defaultlanguage')) {
            // Force getting the base document
            $q = new Query('SELECT', $xartable['publications']);
            $q->addfield('parent_id');
            $q->eq('id', (int) $args['id']);
            if (!$q->run()) {
                return $args['id'];
            }
            $result = $q->row();
            if (empty($result)) {
                return $args['id'];
            }
            // If this was already the base document, return its ID
            if (empty($result['parent_id'])) {
                return (int) $args['id'];
            }
            // Else return the parent ID
            return $result['parent_id'];
        } else {
            // Force getting another translation
            $q = new Query('SELECT');
            $q->addtable($xartable['publications'], 'p1');
            $q->addtable($xartable['publications'], 'p2');
            $q->join('p2.parent_id', 'p1.parent_id');
            $q->addfield('p2.id');
            $q->eq('p2.locale', $locale);
            $q->eq('p1.id', (int) $args['id']);
            if (!$q->run()) {
                return $args['id'];
            }
            $result = $q->row();
            if (empty($result)) {
                return (int) $args['id'];
            }
            return $result['id'];
        }
    }
}

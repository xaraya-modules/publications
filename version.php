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

namespace Xaraya\Modules\Publications;

class Version
{
    /**
     * Get module version information
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'name' => 'publications',
            'id' => '30065',
            'version' => '2.0.0',
            'displayname' => 'Publications',
            'description' => 'Manage publications on a Xaraya site',
            'credits' => '',
            'help' => '',
            'changelog' => '',
            'license' => '',
            'official' => 1,
            'author' => 'M. Lutolf',
            'contact' => 'http://www.netspan.ch/',
            'admin' => true,
            'user' => true,
            'class' => 'Complete',
            'category' => 'Content',
            'namespace' => 'Xaraya\\Modules\\Publications',
            'twigtemplates' => true,
            'dependencyinfo'
             => [
                 0
                  => [
                      'name' => 'Xaraya Core',
                      'version_ge' => '2.4.1',
                  ],
             ],
            'propertyinfo'
             => [
                 30039
                  => [
                      'name' => 'language',
                  ],
                 30059
                  => [
                      'name' => 'datetime',
                  ],
                 30099
                  => [
                      'name' => 'pager',
                  ],
                 30100
                  => [
                      'name' => 'listing',
                  ],
                 30101
                  => [
                      'name' => 'codemirror',
                  ],
                 30122
                  => [
                      'name' => 'iconcheckbox',
                  ],
                 30123
                  => [
                      'name' => 'icondropown',
                  ],
             ],
        ];
    }
}

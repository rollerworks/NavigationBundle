<?php

/**
 * This file is part of the RollerworksNavigationBundle package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\NavigationBundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\AbstractConfigurationTestCase;
use Rollerworks\Bundle\NavigationBundle\DependencyInjection\Configuration;

class ConfigurationTest extends AbstractConfigurationTestCase
{
    public function testDefaultValuesAreValid()
    {
        $this->assertProcessedConfigurationEquals(
            array(
                array() // no values
            ),
            array('menus' => array(), 'breadcrumbs' => array())
        );
    }

    public function testBreadcrumbsWithDefaulted()
    {
        $this->assertProcessedConfigurationEquals(
            array(
                array(
                    'breadcrumbs' => array(
                        'customers' => array(),
                    )
                ),
            ),
            array(
                'menus' => array(),
                'breadcrumbs' => array(
                    'customers' => array(
                        'parent' => null,
                        'label' => null,
                        'translator_domain' => 'Breadcrumbs',
                        'expression' => null,
                    ),
                ),
            )
        );
    }

    public function testBreadcrumbAcceptsService()
    {
        $this->assertProcessedConfigurationEquals(
            array(
                array(
                    'breadcrumbs' => array(
                        'customer' => array(
                            'service' => array(
                                'id' => 'acme_customer.navigation',
                                'method' => 'getBreadcrumb',
                            )
                        ),
                        'webhosting' => array(
                            'service' => array(
                                'id' => 'acme_webhosting.navigation',
                                'method' => 'getBreadcrumb',
                                'parameters' => array(
                                    'id' => '%webhosting.account%',
                                    'foo' => array('bar', 'bla')
                                ),
                            )
                        )
                    )
                )
            ),
            array(
                'menus' => array(),
                'breadcrumbs' => array(
                    'customer' => array(
                        'parent' => null,
                        'label' => null,
                        'translator_domain' => 'Breadcrumbs',
                        'service' => array(
                            'id' => 'acme_customer.navigation',
                            'method' => 'getBreadcrumb',
                            'parameters' => array(),
                        ),
                        'expression' => null,
                    ),
                    'webhosting' => array(
                        'parent' => null,
                        'label' => null,
                        'translator_domain' => 'Breadcrumbs',
                        'service' => array(
                            'id' => 'acme_webhosting.navigation',
                            'method' => 'getBreadcrumb',
                            'parameters' => array(
                                'id' => '%webhosting.account%',
                                'foo' => array('bar', 'bla')
                            ),
                        ),
                        'expression' => null,
                    ),
                ),
            )
        );
    }

    public function testMenusWithDefaulted()
    {
        $this->assertProcessedConfigurationEquals(
            array(
                array(
                    'menus' => array(
                        'customers' => array(),
                    )
                ),
            ),
            array(
                'menus' => array(
                    'customers' => array(
                        'template' => null,
                        'items' => array(),
                    ),
                ),
                'breadcrumbs' => array()
            )
        );
    }

    protected function getConfiguration()
    {
        return new Configuration('rollerworks_navigation');
    }
}

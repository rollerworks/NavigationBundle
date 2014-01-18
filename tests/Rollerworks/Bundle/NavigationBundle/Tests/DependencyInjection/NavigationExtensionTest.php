<?php

/*
 * This file is part of the RollerworksNavigationBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\NavigationBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Rollerworks\Bundle\NavigationBundle\DependencyInjection\NavigationExtension;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\ExpressionLanguage\Expression;

class NavigationExtensionTest extends AbstractExtensionTestCase
{
    public function testBreadcrumbsAreRegistered()
    {
        $this->load(array(
            'breadcrumbs' => array(
                'customers' => array(
                    'parent' => null,
                    'label' => 'Customers',
                    'translator_domain' => 'Breadcrumbs',
                ),
                'webhosting' => array(
                    'parent' => null,
                    'label' => 'Webhosting',
                    'translator_domain' => 'Breadcrumbs',
                ),
            )
        ));

        $def = new Definition('Knp\Menu\MenuFactory');
        $def->setTags(array('knp_menu.menu' => array(array('alias' => 'customers'))));
        $def->setFactoryService('knp_menu.factory');
        $def->setFactoryMethod('createItem');
        $def->setArguments(array('root', array()));
        $def->addMethodCall('addChild', array('customers', array('label' => 'Customers', 'translator_domain' => 'Breadcrumbs')));

        $this->assertEquals($def, $this->container->findDefinition('rollerworks_navigation.breadcrumbs.customers'));

        $def = new Definition('Knp\Menu\MenuFactory');
        $def->setTags(array('knp_menu.menu' => array(array('alias' => 'webhosting'))));
        $def->setFactoryService('knp_menu.factory');
        $def->setFactoryMethod('createItem');
        $def->setArguments(array('root', array()));
        $def->addMethodCall('addChild', array('webhosting', array('label' => 'Webhosting', 'translator_domain' => 'Breadcrumbs')));
        $this->assertEquals($def, $this->container->findDefinition('rollerworks_navigation.breadcrumbs.webhosting'));

        $this->compile();
    }

    public function testBreadcrumbParentsAreResolved()
    {
        $this->load(array(
            'breadcrumbs' => array(
                'dashboard' => array(
                    'parent' => null,
                    'label' => 'Dashboard',
                    'route' => array('name' => 'site_default'),
                    'translator_domain' => 'Breadcrumbs',
                ),
                'webhosting' => array(
                    'parent' => 'dashboard',
                    'label' => 'Webhosting',
                    'route' => array('name' => 'webhosting_home'),
                    'translator_domain' => 'Breadcrumbs',
                ),
                // note normally one would use a service for this
                'webhosting_account' => array(
                    'parent' => 'webhosting',
                    'label' => 'Example.com',
                    'route' => array('name' => 'webhosting_account', 'parameters' => array('id' => 5)),
                    'translator_domain' => null,
                ),
            )
        ));

        $def = new Definition('Knp\Menu\MenuFactory');
        $def->setTags(array('knp_menu.menu' => array(array('alias' => 'webhosting'))));
        $def->setFactoryService('knp_menu.factory');
        $def->setFactoryMethod('createItem');
        $def->setArguments(array('root', array()));
        $def->addMethodCall('addChild', array('dashboard', array('label' => 'Dashboard', 'translator_domain' => 'Breadcrumbs', 'route' => array('name' => 'site_default', 'parameters' => array()))));
        $def->addMethodCall('addChild', array('webhosting', array('label' => 'Webhosting', 'translator_domain' => 'Breadcrumbs', 'route' => array('name' => 'webhosting_home', 'parameters' => array()))));
        $this->assertEquals($def, $this->container->findDefinition('rollerworks_navigation.breadcrumbs.webhosting'));

        $def = new Definition('Knp\Menu\MenuFactory');
        $def->setTags(array('knp_menu.menu' => array(array('alias' => 'webhosting_account'))));
        $def->setFactoryService('knp_menu.factory');
        $def->setFactoryMethod('createItem');
        $def->setArguments(array('root', array()));
        $def->addMethodCall('addChild', array('dashboard', array('label' => 'Dashboard', 'translator_domain' => 'Breadcrumbs', 'route' => array('name' => 'site_default', 'parameters' => array()))));
        $def->addMethodCall('addChild', array('webhosting', array('label' => 'Webhosting', 'translator_domain' => 'Breadcrumbs', 'route' => array('name' => 'webhosting_home', 'parameters' => array()))));
        $def->addMethodCall('addChild', array('webhosting_account', array('label' => 'Example.com', 'translator_domain' => null, 'route' => array('name' => 'webhosting_account', 'parameters' => array('id' => 5)))));
        $this->assertEquals($def, $this->container->findDefinition('rollerworks_navigation.breadcrumbs.webhosting_account'));

        $this->compile();
    }

    public function testBreadcrumbsAreRegisteredWithExpression()
    {
        if (!class_exists('Symfony\Component\DependencyInjection\ExpressionLanguage')) {
            $this->markTestSkipped('Requires at least version 2.4 of the DependencyInjection component.');
        }

        $this->load(array(
            'breadcrumbs' => array(
                'webhosting' => array(
                    'parent' => null,
                    'label' => 'Webhosting',
                    'translator_domain' => 'Breadcrumbs',
                    'route' => array(
                        'name' => 'test',
                        'parameters' => array('foo' => '@@bar', 'name' => "@service('security_context').getToken().getName()")
                    )
                ),
                'news' => array('expression' => "service('acme_customer.navigation').getBreadcrumb()"),
            )
        ));

        $def = new Definition('Knp\Menu\MenuFactory');
        $def->setTags(array('knp_menu.menu' => array(array('alias' => 'webhosting'))));
        $def->setFactoryService('knp_menu.factory');
        $def->setFactoryMethod('createItem');
        $def->setArguments(array('root', array()));
        $def->addMethodCall('addChild', array(
            'webhosting', array(
                'label' => 'Webhosting',
                'translator_domain' => 'Breadcrumbs',
                'route' => array(
                    'name' => 'test',
                    'parameters' => array(
                        'foo' => '@bar',
                        'name' => new Expression("service('security_context').getToken().getName()"),
                    )
                ),
            ),
        ));

        $this->assertEquals($def, $this->container->findDefinition('rollerworks_navigation.breadcrumbs.webhosting'));

        $def = new Definition('Knp\Menu\MenuFactory');
        $def->setTags(array('knp_menu.menu' => array(array('alias' => 'news'))));
        $def->setFactoryService('knp_menu.factory');
        $def->setFactoryMethod('createItem');
        $def->setArguments(array('root', array()));
        $def->addMethodCall('addChild', array(new Expression("service('acme_customer.navigation').getBreadcrumb()")));

        $this->assertEquals($def, $this->container->findDefinition('rollerworks_navigation.breadcrumbs.news'));

        $this->compile();
    }

    public function testDynamicBreadcrumbsAreRegistered()
    {
        $this->registerService('acme_customer.navigation', 'stdClass');

        $this->load(array(
            'breadcrumbs' => array(
                'customers' => array(
                    'parent' => null,
                    'service' => array(
                        'id' => 'acme_customer.navigation',
                        'method' => 'getBreadcrumb',
                        'parameters' => array('foo', 'bar')
                    ),
                ),
            )
        ));

        $definition = new Definition('stdClass');
        $definition->setFactoryService('acme_customer.navigation');
        $definition->setFactoryMethod('getBreadcrumb');
        $definition->setArguments(array('foo', 'bar'));

        $def = new Definition('Knp\Menu\MenuFactory');
        $def->setTags(array('knp_menu.menu' => array(array('alias' => 'customers'))));
        $def->setFactoryService('knp_menu.factory');
        $def->setFactoryMethod('createItem');
        $def->setArguments(array('root', array()));
        $def->addMethodCall('addChild', array($definition));

        $this->assertEquals($def, $this->container->findDefinition('rollerworks_navigation.breadcrumbs.customers'));

        $this->compile();
    }

    public function testMenusAreRegistered()
    {
        $this->load(array(
            'menus' => array(
                'main' => array(
                    'items' => array(
                        'customer' => array(
                            'label' => 'Customers',
                            'route' => array('name' => 'webhosting_home'),
                        ),
                        'administration' => array(
                            'label' => 'Administration',
                            'route' => array('name' => 'administration_home'),
                        ),
                    )

                ),
                'control_panel' => array(
                    'items' => array(
                        'administration' => array(
                            'label' => 'Administration',
                            'route' => array('name' => 'administration_home'),
                            'items' => array(
                                'customer' => array(
                                    'label' => 'Customers',
                                    'route' => array('name' => 'administration_customers'),
                                ),
                                'invoices' => array(
                                    'label' => 'Invoices',
                                    'route' => array('name' => 'administration_invoices'),
                                    'items' => array(
                                        'invoices_paid' => array(
                                            'label' => 'Paid',
                                            'route' => array('name' => 'administration_invoices', 'parameters' => array('filter' => 'paid')),
                                        ),
                                    )
                                ),
                            )
                        ),
                    )
                ),
            )
        ));

        $def = new Definition('Knp\Menu\MenuFactory');
        $def->setTags(array('knp_menu.menu' => array(array('alias' => 'main'))));
        $def->setFactoryService('knp_menu.factory');
        $def->setFactoryMethod('createItem');
        $def->setArguments(array('root', array('template' => null)));
        $def->addMethodCall('addChild', array('customer', array('label' => 'Customers', 'translator_domain' => 'Menus', 'route' => array('name' => 'webhosting_home', 'parameters' => array()))));
        $def->addMethodCall('addChild', array('administration', array('label' => 'Administration', 'translator_domain' => 'Menus', 'route' => array('name' => 'administration_home', 'parameters' => array()))));

        $this->assertEquals($def, $this->container->findDefinition('rollerworks_navigation.menu.main'));

        $def = new Definition('Knp\Menu\MenuFactory');
        $def->setTags(array('knp_menu.menu' => array(array('alias' => 'control_panel'))));
        $def->setFactoryService('knp_menu.factory');
        $def->setFactoryMethod('createItem');
        $def->setArguments(array('root', array('template' => null)));

        // ---------
        $rootDef = new Definition('Knp\Menu\MenuFactory');
        $rootDef->setFactoryService('knp_menu.factory');
        $rootDef->setFactoryMethod('createItem');
        $rootDef->setArguments(array('administration', array('label' => 'Administration', 'route' => array('name' => 'administration_home', 'parameters' => array()), 'translator_domain' => 'Menus')));
        $rootDef->addMethodCall('addChild', array('customer', array('label' => 'Customers', 'route' => array('name' => 'administration_customers', 'parameters' => array()), 'translator_domain' => 'Menus')));

        $invoiceDef = new Definition('Knp\Menu\MenuFactory');
        $invoiceDef->setFactoryService('knp_menu.factory');
        $invoiceDef->setFactoryMethod('createItem');
        $invoiceDef->setArguments(array('invoices', array('label' => 'Invoices', 'route' => array('name' => 'administration_invoices', 'parameters' => array()) , 'translator_domain' => 'Menus')));
        $invoiceDef->addMethodCall('addChild', array('invoices_paid', array('label' => 'Paid', 'route' => array('name' => 'administration_invoices', 'parameters' => array('filter' => 'paid')), 'translator_domain' => 'Menus')));
        $rootDef->addMethodCall('addChild', array($invoiceDef));

        $def->addMethodCall('addChild', array($rootDef));

        $this->assertEquals((array) $def, (array) $this->container->findDefinition('rollerworks_navigation.menu.control_panel'));

        $this->compile();
    }

    public function testDynamicMenusAreRegistered()
    {
        $this->registerService('acme_customer.navigation', 'stdClass');

        $this->load(array(
            'menus' => array(
                'main' => array(
                    'items' => array(
                        'customer' => array(
                            'label' => 'Customers',
                            'route' => array('name' => 'webhosting_home'),
                        ),
                        'administration' => array(
                            'service' => array(
                                'id' => 'acme_customer.navigation',
                                'method' => 'getAdminMenu',
                                'parameters' => array('foo', 'bar')
                            )
                        ),
                    )
                ),
            )
        ));

        $def = new Definition('Knp\Menu\MenuFactory');
        $def->setTags(array('knp_menu.menu' => array(array('alias' => 'main'))));
        $def->setFactoryService('knp_menu.factory');
        $def->setFactoryMethod('createItem');
        $def->setArguments(array('root', array('template' => null)));
        $def->addMethodCall('addChild', array('customer', array('label' => 'Customers', 'translator_domain' => 'Menus', 'route' => array('name' => 'webhosting_home', 'parameters' => array()))));

        $childDef = new Definition('stdClass');
        $childDef->setFactoryService('acme_customer.navigation');
        $childDef->setFactoryMethod('getAdminMenu');
        $childDef->setArguments(array('foo', 'bar'));

        $def->addMethodCall('addChild', array($childDef));

        $this->assertEquals($def, $this->container->findDefinition('rollerworks_navigation.menu.main'));

        $this->compile();
    }

    public function testMenusAreRegisteredWithExpression()
    {
        if (!class_exists('Symfony\Component\DependencyInjection\ExpressionLanguage')) {
            $this->markTestSkipped('Requires at least version 2.4 of the DependencyInjection component.');
        }

        $this->load(array(
            'menus' => array(
                'main' => array(
                    'items' => array(
                        'customer' => array(
                            'label' => 'Customers',
                            'route' => array('name' => 'webhosting_home'),
                        ),
                        'administration' => array(
                            'label' => 'Administration',
                            'route' => array(
                                'name' => 'administration_home',
                                'parameters' => array('foo' => '@@bar', 'name' => "@service('security_context').getToken().getName()")
                            ),
                        ),
                        'servers' => array(
                            'service' => array(
                                'id' => 'acme_servers.navigation',
                                'method' => 'getMenu',
                                'parameters' => array('foo' => '@@bar', 'name' => "@service('security_context').getToken().getName()")
                            )
                        ),
                        'news' => array('expression' => "service('acme_servers.navigation').getMenu()")
                    )
                ),
            )
        ));

        $def = new Definition('Knp\Menu\MenuFactory');
        $def->setTags(array('knp_menu.menu' => array(array('alias' => 'main'))));
        $def->setFactoryService('knp_menu.factory');
        $def->setFactoryMethod('createItem');
        $def->setArguments(array('root', array('template' => null)));
        $def->addMethodCall('addChild', array('customer', array('label' => 'Customers', 'translator_domain' => 'Menus', 'route' => array('name' => 'webhosting_home', 'parameters' => array()))));
        $def->addMethodCall('addChild', array('administration', array('label' => 'Administration', 'translator_domain' => 'Menus', 'route' => array('name' => 'administration_home', 'parameters' => array(
                'foo' => '@bar',
                'name' => new Expression("service('security_context').getToken().getName()"),
            )
        ))));

        $childDef = new Definition('stdClass');
        $childDef->setFactoryService('acme_servers.navigation');
        $childDef->setFactoryMethod('getMenu');
        $childDef->setArguments(array('foo' => '@bar', 'name' => new Expression("service('security_context').getToken().getName()")));
        $def->addMethodCall('addChild', array($childDef));
        $def->addMethodCall('addChild', array(new Expression("service('acme_servers.navigation').getMenu()")));

        $this->assertEquals($def, $this->container->findDefinition('rollerworks_navigation.menu.main'));

        $this->compile();
    }

    protected function setUp()
    {
        parent::setUp();

        $this->registerService('knp_menu.factory', 'Knp\Menu\MenuFactory');
    }

    protected function getContainerExtensions()
    {
        return array(
            new NavigationExtension()
        );
    }
}

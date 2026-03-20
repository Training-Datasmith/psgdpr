<?php

declare (strict_types=1);
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
namespace Presta_Shop\Module\Psgdpr\Service;

use Configuration;
use Context;
use Hook;
use Presta_Shop\Module\Psgdpr\Exception\Customer\Delete_Exception;
use Presta_Shop\Module\Psgdpr\Repository\Cart_Repository;
use Presta_Shop\Module\Psgdpr\Repository\Cart_Rule_Repository;
use Presta_Shop\Module\Psgdpr\Repository\Customer_Repository;
use Presta_Shop\Presta_Shop\Core\Command_Bus\Command_Bus_Interface;
use Presta_Shop\Presta_Shop\Core\Crypto\Hashing;
use Presta_Shop\Presta_Shop\Core\Domain\Address\Command\Add_Customer_Address_Command;
use Presta_Shop\Presta_Shop\Core\Domain\Address\Value_Object\Address_Id;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Command\Add_Customer_Command;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Command\Delete_Customer_Command;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Query\Get_Customer_For_Viewing;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Query_Result\Address_Information;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Query_Result\Viewable_Customer;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Value_Object\Customer_Delete_Method;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Value_Object\Customer_Id;
use Presta_Shop\Presta_Shop\Core\Group\Provider\Default_Groups_Provider_Interface;
use Presta_Shop_Exception;
use Psgdpr;
use Tools;
class Customer_Service
{
    /**
     * @var Psgdpr
     */
    private $module;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var CartRepository
     */
    private $cart_repository;
    /**
     * @var CartRuleRepository
     */
    private $cart_rule_repository;
    /**
     * @var CustomerRepository
     */
    private $customer_repository;
    /**
     * @var CommandBusInterface
     */
    private $command_bus;
    /**
     * @var CommandBusInterface
     */
    private $query_bus;
    /**
     * @var DefaultGroupsProviderInterface
     */
    private $default_group_provider;
    /**
     * @var Hashing
     */
    private $hashing;
    /**
     * CustomerService constructor.
     *
     *
     */
    public function __construct(Psgdpr $module, Context $context, Cart_Repository $cart_repository, Cart_Rule_Repository $cart_rule_repository, Customer_Repository $customer_repository, Command_Bus_Interface $command_bus, Command_Bus_Interface $query_bus, Default_Groups_Provider_Interface $default_group_provider, Hashing $hashing)
    {
        $this->module = $module;
        $this->context = $context;
        $this->cart_repository = $cart_repository;
        $this->cart_rule_repository = $cart_rule_repository;
        $this->customer_repository = $customer_repository;
        $this->command_bus = $command_bus;
        $this->query_bus = $query_bus;
        $this->default_group_provider = $default_group_provider;
        $this->hashing = $hashing;
    }
    /**
     * Delete customer data from Prestashop
     *
     *
     * @throws DeleteException
     */
    public function delete_customer_data_from_prestashop(Customer_Id $customer_id): void
    {
        $anonymous_customer_infos = $this->create_anonymous_customer();
        try {
            $this->cart_repository->anonymize_customer_cart_by_customer_id($customer_id, $anonymous_customer_infos['customerId'], $anonymous_customer_infos['addressId']);
            $this->cart_rule_repository->delete_cart_rules_by_customer_id($customer_id);
            $this->command_bus->handle(new Delete_Customer_Command($customer_id->get_value(), Customer_Delete_Method::ALLOW_CUSTOMER_REGISTRATION));
        } catch (\Exception $e) {
            throw new Delete_Exception($e->get_message());
        }
    }
    /**
     * Delete customer data from modules
     *
     * @param string|string[] $data
     *
     * @throws DeleteException
     */
    public function delete_customer_data_from_modules($data): void
    {
        $modules_list = Hook::get_hook_module_exec_list('actionDeleteGDPRCustomer');
        if ($modules_list == false) {
            return;
        }
        foreach ($modules_list as $module) {
            if ($module['id_module'] != $this->module->id) {
                Hook::exec('actionDeleteGDPRCustomer', [$data], $module['id_module']);
            }
        }
    }
    /**
     * Find or create an anonymous customer
     */
    private function create_anonymous_customer(): array
    {
        $default_groups = $this->default_group_provider->get_groups();
        if (null === $this->context) {
            throw new Presta_Shop_Exception('Context is not defined');
        }
        $shop = $this->context->shop;
        if (null === $shop) {
            throw new Presta_Shop_Exception('Shop is not defined');
        }
        /** @var int|bool $anonymousCustomerId */
        $anonymous_customer_id = $this->customer_repository->find_customer_id_by_email('anonymous@psgdpr.com');
        if (false === $anonymous_customer_id) {
            $anonymous_customer = $this->command_bus->handle(new Add_Customer_Command('Anonymous', 'Anonymous', 'anonymous@psgdpr.com', $this->hashing->hash((string) Tools::passwd_gen(64)), $default_groups->get_customers_group()->get_id(), [$default_groups->get_customers_group()->get_id()], $shop->get_shop_id()));
            $anonymous_address = $this->command_bus->handle(new Add_Customer_Address_Command($anonymous_customer->get_value(), 'Anonymous', 'Anonymous', 'Anonymous', 'Anonymous', 'Anonymous', (int) Configuration::get('PS_COUNTRY_DEFAULT'), '00000'));
            return ['customerId' => $anonymous_customer, 'addressId' => $anonymous_address];
        }
        /** @var ViewableCustomer $anonymousCustomer */
        $anonymous_customer = $this->query_bus->handle(new Get_Customer_For_Viewing($anonymous_customer_id));
        /** @var AddressInformation $anonymousAddress */
        $anonymous_address = $anonymous_customer->get_addresses_information()[0];
        return ['customerId' => $anonymous_customer->get_customer_id(), 'addressId' => new Address_Id($anonymous_address->get_address_id())];
    }
}
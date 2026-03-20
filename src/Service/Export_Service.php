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

use Cart;
use Cart_Rule;
use Context;
use Currency;
use Customer;
use DateTime;
use Error;
use Exception;
use Gender;
use Group;
use Hook;
use Language;
use Module;
use Order;
use Presta_Shop\Module\Psgdpr\Service\Export\Export_Interface;
use Presta_Shop\Presta_Shop\Adapter\Entity\Customer_Thread;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Value_Object\Customer_Id;
use Presta_Shop_Bundle\Translation\Translator_Interface;
use Presta_Shop_Exception;
use Tools;
class Export_Service
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * ExportService constructor.
     *
     *
     */
    public function __construct(Context $context, Translator_Interface $translator)
    {
        $this->context = $context;
        $this->translator = $translator;
    }
    /**
     * Collects all GDPR data for a customer (PrestaShop core + third-party module data)
     * and serialises it using the given export strategy.
     *
     * @param Customer_Id       $customer_id      ID of the customer to export
     * @param Export_Interface  $export_strategy  Strategy that formats the data (CSV, PDF, etc.)
     *
     * @return string Serialised customer data in the format produced by the export strategy
     */
    public function export_customer_data(Customer_Id $customer_id, Export_Interface $export_strategy): string
    {
        $customer = new Customer($customer_id->get_value());
        $export_data = $this->get_prestashop_informations($customer);
        $export_data['modules'] = $this->get_third_party_modules_informations($customer);
        return $export_strategy->get_data($export_data);
    }
    /**
     * Returns all PrestaShop core GDPR data for a customer, including personal info,
     * addresses, orders, carts, messages, connections, discounts, sent emails, and groups.
     *
     * @param Customer $customer The customer to collect data for
     *
     * @return array<string, mixed> Map of data category name to data array
     */
    public function get_prestashop_informations(Customer $customer): array
    {
        return ['personalinformations' => $this->get_personal_informations($customer), 'addresses' => $this->get_addresses_informations($customer), 'orders' => $this->get_orders_informations($customer), 'productsOrdered' => $this->get_products_ordered_informations($customer), 'carts' => $this->get_carts_informations($customer), 'productsInCart' => $this->get_products_in_cart_information($customer), 'messages' => $this->get_messages_informations($customer), 'lastConnections' => $this->get_last_connections_informations($customer), 'discounts' => $this->get_discounts_informations($customer), 'lastSentEmails' => $this->get_last_sent_emails_informations($customer), 'groups' => $this->get_groups_informations($customer)];
    }
    /**
     * @param mixed $customer
     *
     *
     * @throws PrestaShopException
     */
    public function get_third_party_modules_informations($customer): array
    {
        $third_party_modules_list = Hook::get_hook_module_exec_list('actionExportGDPRData');
        $third_party_module_data = [];
        foreach ($third_party_modules_list as $module) {
            $module_infos = Module::get_instance_by_id($module['id_module']);
            $entry_name = "MODULE : {$module_infos->display_name}";
            try {
                $data_from_module = Hook::exec('actionExportGDPRData', (array) $customer, $module['id_module']);
            } catch (Exception|Error $e) {
                $error_message = $this->translator->trans('An error occurred while retrieving data, please contact the module author.', [], 'Modules.Psgdpr.Admin');
                $third_party_module_data[$module_infos->name]['name'] = $entry_name;
                $third_party_module_data[$module_infos->name]['headers'][] = $this->translator->trans('Error', [], 'Modules.Psgdpr.Admin');
                $third_party_module_data[$module_infos->name]['data'][] = [$error_message];
                continue;
            }
            /** @var array $moduleData */
            $module_data = json_decode($data_from_module);
            if (empty($module_data)) {
                $module_data = $this->translator->trans('No data available', [], 'Modules.Psgdpr.Admin');
            }
            if (!is_array($module_data)) {
                $third_party_module_data[$module_infos->name]['name'] = $entry_name;
                $third_party_module_data[$module_infos->name]['headers'][] = $this->translator->trans('Information', [], 'Modules.Psgdpr.Admin');
                $third_party_module_data[$module_infos->name]['data'][] = [$module_data];
                continue;
            }
            foreach ($module_data as $data) {
                $data_to_array = json_decode(json_encode($data), true);
                $third_party_module_data[$module_infos->name]['name'] = $entry_name;
                $third_party_module_data[$module_infos->name]['headers'] = array_keys($data_to_array);
                $third_party_module_data[$module_infos->name]['data'][] = array_values($data_to_array);
            }
        }
        return $third_party_module_data;
    }
    /**
     * Get customer personal informations
     *
     *
     */
    private function get_personal_informations(Customer $customer): array
    {
        $customer_gender = new Gender($customer->id_gender, $this->context->language->id);
        $customer_language = Language::get_language($customer->id_lang);
        $customer_stats = $customer->get_stats();
        $gender_name = $customer_gender->name;
        $today = new Datetime(date('m.d.y'));
        $age = $today->diff(new DateTime($customer->birthday));
        return ['name' => 'personal informations', 'headers' => [$this->translator->trans('Id', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Social title', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('First name', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Last name', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Birthday', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Age', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Email', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Language', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Registration date', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Last visit date', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Is guest', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Company', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Is newsletter subscribed', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Is partner offers subscribed', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Siret', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Ape', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Website', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Personal note', [], 'Modules.Psgdpr.Admin')], 'data' => [['id' => $customer->id, 'gender' => $gender_name, 'firstname' => $customer->firstname, 'lastname' => $customer->lastname, 'birthday' => $customer->birthday, 'age' => $age->y, 'email' => $customer->email, 'language' => $customer_language['name'], 'dateAdd' => $customer->date_add, 'lastVisit' => $customer_stats['last_visit'], 'isGuest' => json_encode($customer->is_guest), 'company' => $customer->company, 'isNewsletterSubscribed' => json_encode($customer->newsletter), 'isPartnerOffersSubscribed' => json_encode($customer->optin), 'siret' => $customer->siret, 'ape' => $customer->ape, 'website' => $customer->website, 'note' => $customer->note]]];
    }
    /**
     * Get customer addresses informations
     *
     *
     */
    private function get_addresses_informations(Customer $customer): array
    {
        $customer_addresses = $customer->get_addresses($this->context->language->id);
        return ['name' => 'addresses', 'headers' => [$this->translator->trans('Alias', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Company', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Full name', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Full address', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Phone', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Phone mobile', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Country name', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Date add', [], 'Modules.Psgdpr.Admin')], 'data' => array_map(function (array $address): array {
            $full_name = "{$address['firstname']} {$address['lastname']}";
            $full_address = "{$address['address1']} {$address['address2']} {$address['postcode']} {$address['city']}";
            return ['alias' => $address['alias'], 'company' => $address['company'], 'fullName' => $full_name, 'fullAddress' => $full_address, 'country' => $address['country'], 'phone' => $address['phone'], 'mobilePhone' => $address['phone_mobile'], 'dateAdd' => $address['date_add']];
        }, $customer_addresses)];
    }
    /**
     * Get customer orders informations
     *
     *
     */
    private function get_orders_informations(Customer $customer): array
    {
        $order_list = Order::get_customer_orders($customer->id);
        return ['name' => 'orders', 'headers' => [$this->translator->trans('Reference', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Payment', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('status', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Total paid with taxes', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Date of order', [], 'Modules.Psgdpr.Admin')], 'data' => array_map(function (array $order): array {
            $currency = Currency::get_currency($order['id_currency']);
            $total_paid = number_format($order['total_paid_tax_incl'], 2) . ' ' . $currency['iso_code'];
            return ['reference' => $order['reference'], 'payment' => $order['payment'], 'state' => $order['order_state'], 'totalPaid' => $total_paid, 'date' => $order['date_add']];
        }, $order_list)];
    }
    /**
     * Get customer discounts informations
     *
     *
     */
    private function get_products_ordered_informations(Customer $customer): array
    {
        $order_list = Order::get_customer_orders($customer->id);
        $products_ordered = [];
        foreach ($order_list as $order) {
            $current_order = new Order($order['id_order']);
            $products_in_order = $current_order->get_products();
            $products_ordered += array_map(function (array $product) use ($current_order): array {
                return ['orderReference' => $current_order->reference, 'reference' => $product['product_reference'], 'name' => $product['product_name'], 'quantity' => $product['product_quantity']];
            }, $products_in_order);
        }
        return ['name' => 'products ordered', 'headers' => [$this->translator->trans('Order reference', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Reference', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Name', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Quantity', [], 'Modules.Psgdpr.Admin')], 'data' => $products_ordered];
    }
    /**
     * Get customer carts informations
     *
     *
     */
    private function get_carts_informations(Customer $customer): array
    {
        $cart_list = Cart::get_customer_carts($customer->id, false);
        return ['name' => 'carts', 'headers' => [$this->translator->trans('Id', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Total', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Creation date', [], 'Modules.Psgdpr.Admin')], 'data' => array_map(function (array $cart): array {
            $current_cart = new Cart($cart['id_cart']);
            $products_cart = $current_cart->get_products();
            return ['cartId' => $cart['id_cart'], 'totalProducts' => count($products_cart), 'creationDate' => $cart['date_add']];
        }, $cart_list)];
    }
    /**
     * Get customer products in cart informations
     *
     *
     */
    private function get_products_in_cart_information(Customer $customer): array
    {
        $cart_list = Cart::get_customer_carts($customer->id, false);
        $products_in_cart = [];
        foreach ($cart_list as $cart) {
            $current_cart = new Cart($cart['id_cart']);
            $products_list = $current_cart->get_products();
            $products_in_cart += array_map(function (array $product) use ($current_cart): array {
                return ['cartId' => $current_cart->id, 'reference' => $product['reference'], 'name' => $product['name'], 'quantity' => $product['quantity']];
            }, $products_list);
        }
        return ['name' => 'products in cart', 'headers' => [$this->translator->trans('Cart id', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Reference', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Name', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Quantity', [], 'Modules.Psgdpr.Admin')], 'data' => $products_in_cart];
    }
    /**
     * Get customer messages informations
     *
     *
     */
    private function get_messages_informations(Customer $customer): array
    {
        $customer_messages = Customer_Thread::get_customer_messages($customer->id);
        return ['name' => 'messages', 'headers' => [$this->translator->trans('Ip address', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Message', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Creation date', [], 'Modules.Psgdpr.Admin')], 'data' => array_map(function (array $message): array {
            $ip_address = $message['ip_address'];
            if ((int) $message['ip_address'] == $message['ip_address']) {
                $ip_address = long2ip((int) $message['ip_address']);
            }
            return ['ipAddress' => $ip_address, 'message' => $message['message'], 'creationDate' => $message['date_add']];
        }, $customer_messages)];
    }
    /**
     * Get customer last connections informations
     *
     *
     */
    private function get_last_connections_informations(Customer $customer): array
    {
        $last_connections = $customer->get_last_connections();
        return ['name' => 'last connections', 'headers' => [$this->translator->trans('id', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Http referer', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Pages viewed', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Total time', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Ip address', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Date', [], 'Modules.Psgdpr.Admin')], 'data' => array_map(function (array $connection): array {
            $ip_address = $connection['ipaddress'];
            if ((int) $connection['ipaddress'] == $connection['ipaddress']) {
                $ip_address = long2ip((int) $connection['ipaddress']);
            }
            return ['connectionId' => $connection['id_connections'], 'httpReferer' => $connection['http_referer'], 'pagesViewed' => $connection['pages'], 'totalTime' => $connection['time'], 'ipAddress' => $ip_address, 'date' => $connection['date_add']];
        }, $last_connections)];
    }
    /**
     * Get customer discounts informations
     *
     *
     */
    private function get_discounts_informations(Customer $customer): array
    {
        $discounts_list = Cart_Rule::get_all_customer_cart_rules($customer->id);
        return ['name' => 'discounts', 'headers' => [$this->translator->trans('Id', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Code', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Name', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Description', [], 'Modules.Psgdpr.Admin')], 'data' => array_map(function (array $discount): array {
            return ['discountId' => $discount['id_cart_rule'], 'code' => $discount['code'], 'name' => $discount['name'], 'description' => $discount['description']];
        }, $discounts_list)];
    }
    /**
     * Get customer sent emails informations
     *
     *
     */
    private function get_last_sent_emails_informations(Customer $customer): array
    {
        $emails = $customer->get_last_emails();
        return ['name' => 'last sent emails', 'headers' => [$this->translator->trans('Date', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Language', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Subject', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Template', [], 'Modules.Psgdpr.Admin')], 'data' => array_map(function (array $email): array {
            return ['creationDate' => Tools::display_date($email['date_add'], true), 'language' => $email['language'], 'subject' => $email['subject'], 'template' => $email['template']];
        }, $emails)];
    }
    /**
     * Get customer groups informations
     *
     *
     */
    private function get_groups_informations(Customer $customer): array
    {
        $groupsid_list = $customer->get_groups();
        return ['name' => 'groups', 'headers' => [$this->translator->trans('Id', [], 'Modules.Psgdpr.Admin'), $this->translator->trans('Name', [], 'Modules.Psgdpr.Admin')], 'data' => array_map(function ($group_id): array {
            $current_group = new Group($group_id);
            $language_id = $this->context->language->id;
            return ['groupId' => $current_group->id, 'name' => $current_group->name[$language_id]];
        }, $groupsid_list)];
    }
}
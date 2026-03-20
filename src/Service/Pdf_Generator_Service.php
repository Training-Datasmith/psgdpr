<?php

declare (strict_types=1);
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
// require _PS_MODULE_DIR_.'psgdpr/psgdpr.php';
namespace Presta_Shop\Module\Psgdpr\Service;

use Configuration;
use Context;
use Html_Template;
use Shop;
use Smarty;
use Tools;
class Pdf_Generator_Service extends Html_Template
{
    /**
     * @var array
     */
    public $customer_data;
    /**
     * @var bool
     */
    public $available_in_your_account = false;
    /**
     * @var Context
     */
    public $context;
    /**
     * @param array $customerData
     */
    public function __construct($customer_data, Smarty $smarty)
    {
        $this->customer_data = $customer_data;
        $this->smarty = $smarty;
        $this->context = Context::get_context();
        $firstname = $this->customer_data['personalinformations']['data'][0]['firstname'];
        $lastname = $this->customer_data['personalinformations']['data'][0]['lastname'];
        $this->title = "{$firstname} {$lastname}";
        $this->date = Tools::display_date(date('Y-m-d H:i:s'));
        $this->shop = new Shop((int) Context::get_context()->shop->id);
    }
    /**
     * Returns the template's HTML footer
     *
     * @return string HTML footer
     */
    public function get_footer()
    {
        $shop_address = $this->get_shop_address();
        $this->smarty->assign(['available_in_your_account' => $this->available_in_your_account, 'shop_address' => $shop_address, 'shop_fax' => Configuration::get('PS_SHOP_FAX'), 'shop_phone' => Configuration::get('PS_SHOP_PHONE'), 'shop_details' => Configuration::get('PS_SHOP_DETAILS'), 'free_text' => '']);
        return $this->smarty->fetch($this->get_template('footer'));
    }
    /**
     * Returns the template's HTML content
     *
     * @return string HTML content
     */
    public function get_content()
    {
        $orders_list = $this->customer_data['orders'];
        $products_ordered_list = $this->customer_data['productsOrdered'];
        $carts_list = $this->customer_data['carts'];
        $products_cart_list = $this->customer_data['productsInCart'];
        foreach ($orders_list['data'] as $index => $order) {
            $orders_list['data'][$index]['products'] = [];
            foreach ($products_ordered_list['data'] as $product) {
                if ($product['orderReference'] == $order['reference']) {
                    $orders_list['data'][$index]['products'][] = $product;
                }
            }
        }
        foreach ($carts_list['data'] as $index => $cart) {
            $carts_list['data'][$index]['products'] = [];
            foreach ($products_cart_list['data'] as $product) {
                if ($product['cartId'] == $cart['cartId']) {
                    $carts_list['data'][$index]['products'][] = $product;
                }
            }
        }
        $this->smarty->assign(['customerInfo' => ['headers' => $this->customer_data['personalinformations']['headers'], 'data' => array_map(function ($infos): array {
            return array_values($infos);
        }, $this->customer_data['personalinformations']['data'])], 'addresses' => $this->customer_data['addresses'], 'orders' => $orders_list, 'carts' => $carts_list, 'messages' => $this->customer_data['messages'], 'lastConnections' => $this->customer_data['lastConnections'], 'discounts' => $this->customer_data['discounts'], 'lastSentEmails' => $this->customer_data['lastSentEmails'], 'groups' => $this->customer_data['groups'], 'modules' => $this->customer_data['modules']]);
        // Generate templates after, to be able to reuse data above
        $this->smarty->assign(['style' => $this->smarty->fetch($this->get_gdpr_template('style')), 'general_informations_section' => $this->smarty->fetch($this->get_gdpr_template('sections/general_informations')), 'addresses_section' => $this->smarty->fetch($this->get_gdpr_template('sections/addresses')), 'orders_section' => $this->smarty->fetch($this->get_gdpr_template('sections/orders')), 'carts_section' => $this->smarty->fetch($this->get_gdpr_template('sections/carts')), 'messages_section' => $this->smarty->fetch($this->get_gdpr_template('sections/messages')), 'last_connections_section' => $this->smarty->fetch($this->get_gdpr_template('sections/last_connections')), 'discounts_section' => $this->smarty->fetch($this->get_gdpr_template('sections/discounts')), 'last_sent_emails_section' => $this->smarty->fetch($this->get_gdpr_template('sections/last_sent_emails')), 'groups_section' => $this->smarty->fetch($this->get_gdpr_template('sections/groups')), 'modules_section' => $this->smarty->fetch($this->get_gdpr_template('sections/modules'))]);
        return $this->smarty->fetch($this->get_gdpr_template('personal_data'));
    }
    /**
     * Returns the template filename
     *
     * @return string filename
     */
    public function get_filename()
    {
        return 'personal-data-' . date('Y-m-d') . '.pdf';
    }
    /**
     * Returns the template filename
     *
     * @return string filename
     */
    public function get_bulk_filename()
    {
        return 'personal-data-' . date('Y-m-d') . '.pdf';
    }
    /**
     * If the template is not present in the theme directory, it will return the default template
     * in _PS_PDF_DIR_ directory
     *
     * @param string $template_name
     *
     * @return string
     */
    protected function get_gdpr_template($template_name)
    {
        return _PS_MODULE_DIR_ . 'psgdpr/views/templates/front/pdf/' . $template_name . '.tpl';
    }
}
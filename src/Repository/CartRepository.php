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
namespace Presta_Shop\Module\Psgdpr\Repository;

use Doctrine\DBAL\Connection;
use Presta_Shop\Presta_Shop\Core\Domain\Address\Value_Object\Address_Id;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Value_Object\Customer_Id;
class Cart_Repository
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * CartRepository constructor.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    /**
     * Find customer carts by customer id
     *
     *
     */
    public function find_carts_by_customer_id(Customer_Id $customer_id): array
    {
        $qb = $this->connection->create_query_builder();
        $query = $qb->select('cart.id_cart', 'cart.date_add', 'carrier.name as carrier_name', 'cart.id_currency', 'currency.iso_code as currency_iso_code')->from(_DB_PREFIX_ . 'cart', 'cart')->left_join('cart', _DB_PREFIX_ . 'carrier', 'carrier', 'carrier.id_carrier = cart.id_carrier')->left_join('cart', _DB_PREFIX_ . 'currency', 'currency', 'currency.id_currency = cart.id_currency')->where('cart.id_customer = :id_customer')->order_by('cart.date_add', 'DESC')->set_parameter('id_customer', $customer_id->get_value());
        $result = $query->execute();
        return $result->fetch_associative();
    }
    /**
     * Anonymize customer cart by customer id
     *
     *
     */
    public function anonymize_customer_cart_by_customer_id(Customer_Id $customer_id_to_anonymize, Customer_Id $anonymous_customer_id, Address_Id $anonymous_address_id): bool
    {
        $qb = $this->connection->create_query_builder();
        $qb->update(_DB_PREFIX_ . 'cart', 'c')->set('c.id_customer', strval($anonymous_customer_id->get_value()))->set('c.id_address_delivery', strval($anonymous_address_id->get_value()))->set('c.id_address_invoice', strval($anonymous_address_id->get_value()))->where('c.id_customer = :customerId')->set_parameter('customerId', $customer_id_to_anonymize->get_value());
        $qb->execute();
        return true;
    }
}
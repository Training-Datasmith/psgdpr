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
use Exception;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Value_Object\Customer_Id;
class Order_Repository
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * OrderRepository constructor.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    /**
     * Find customer cart products by customer id
     *
     *
     */
    public function find_products_carts_not_ordered_by_customer_id(Customer_Id $customer_id): array
    {
        try {
            $qb = $this->connection->create_query_builder();
            $ordered_product_query = $qb->select('1')->from(_DB_PREFIX_ . 'orders', 'order')->left_join('order', _DB_PREFIX_ . 'order_detail', 'detail', 'order.id_order = detail.id_order')->where('product_id = cart_product.id_product')->and_where('order.valid = 1')->and_where('order.id_customer = :id_customer')->get_sql();
            $query = $qb->select('cart_product.id_product', 'cart.id_cart', 'cart.id_shop', 'cart_product.id_shop AS cart_product_id_shop')->from(_DB_PREFIX_ . 'cart_product', 'cart_product')->left_join('cart_product', _DB_PREFIX_ . 'cart', 'cart', 'cart.id_cart = cart_product.id_cart')->left_join('cart_product', _DB_PREFIX_ . 'product', 'product', 'cart_product.id_product = product.id_product')->where('cart.id_customer = :id_customer')->and_where('NOT EXISTS (' . $ordered_product_query . ')')->set_parameter('id_customer', $customer_id->get_value());
            $result = $query->execute();
            return $result->fetch_all_associative();
        } catch (Exception $e) {
            return [];
        }
    }
}
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
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Value_Object\Customer_Id;
class Order_Invoice_Repository
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
    public function find_if_invoices_exist_by_customer_id(Customer_Id $customer_id): bool
    {
        $qb = $this->connection->create_query_builder();
        $query = $qb->select('count(*)')->from(_DB_PREFIX_ . 'order_invoice', 'oi')->left_join('oi', _DB_PREFIX_ . 'orders', 'o', 'oi.id_order = o.id_order')->where('o.id_customer = :customerId')->set_parameter('customerId', $customer_id->get_value());
        $result = $query->execute();
        if ($result->fetch_one() == 0) {
            return false;
        }
        return true;
    }
    /**
     * Find customer cart products by customer id
     *
     *
     */
    public function find_all_invoices_by_customer_id(Customer_Id $customer_id): array
    {
        $qb = $this->connection->create_query_builder();
        $query = $qb->select('oi.*')->from(_DB_PREFIX_ . 'order_invoice', 'oi')->left_join('oi', _DB_PREFIX_ . 'orders', 'o', 'oi.id_order = o.id_order')->where('o.id_customer = :customerId')->set_parameter('customerId', $customer_id->get_value());
        $result = $query->execute();
        return $result->fetch_all_associative();
    }
}
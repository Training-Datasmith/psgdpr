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
use Doctrine\ORM\Query\Expr;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Value_Object\Customer_Id;
class Customer_Repository
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * CustomerRepository constructor.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    /**
     * Find customer name by customer id
     *
     *
     */
    public function find_customer_name_by_customer_id(Customer_Id $customer_id): string
    {
        $qb = $this->connection->create_query_builder();
        $expression = new Expr();
        $concat = $expression->concat('firstname', '" "', 'lastname');
        $query = $qb->select($concat . ' as name')->from(_DB_PREFIX_ . 'customer', 'customer')->where('customer.id_customer = :id_customer')->set_parameter('id_customer', $customer_id->get_value());
        $result = $query->execute();
        return $result->fetch_one();
    }
    /**
     * Find customer id by email
     *
     *
     * @return int|bool
     */
    public function find_customer_id_by_email(string $email)
    {
        $qb = $this->connection->create_query_builder();
        $query = $qb->add_select('c.id_customer')->from(_DB_PREFIX_ . 'customer', 'c')->where('c.email = :email')->set_parameter('email', $email);
        $result = $query->execute();
        $data = $result->fetch_one();
        if ($data) {
            return (int) $data;
        }
        return false;
    }
}
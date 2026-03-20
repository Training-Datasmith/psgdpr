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
class Cart_Rule_Repository
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * CartRuleRepository constructor.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    /**
     * Delete cart rules by customer id
     *
     *
     */
    public function delete_cart_rules_by_customer_id(Customer_Id $customer_id): bool
    {
        $qb = $this->connection->create_query_builder();
        $qb->delete(_DB_PREFIX_ . 'cart_rule')->where('id_customer = :customerId')->set_parameter('customerId', $customer_id->get_value());
        $qb->execute();
        return true;
    }
}
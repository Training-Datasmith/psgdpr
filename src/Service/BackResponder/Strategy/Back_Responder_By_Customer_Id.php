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
namespace Presta_Shop\Module\Psgdpr\Service\Back_Responder\Strategy;

use Presta_Shop\Module\Psgdpr\Service\Back_Responder\Back_Responder_Context;
use Presta_Shop\Module\Psgdpr\Service\Back_Responder\Back_Responder_Interface;
use Presta_Shop\Module\Psgdpr\Service\Export\Strategy\Export_To_Json;
use Presta_Shop\Module\Psgdpr\Service\Logger_Service;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Value_Object\Customer_Id;
use Symfony\Component\Http_Foundation\Json_Response;
use Symfony\Component\Http_Foundation\Response;
class Back_Responder_By_Customer_Id extends Back_Responder_Context implements Back_Responder_Interface
{
    public const TYPE = 'customer';
    /**
     * export customer data
     *
     *
     */
    public function export(string $data): Response
    {
        $customer_id = new Customer_Id((int) $data);
        $export_strategy = $this->export_factory->get_strategy_by_type(Export_To_Json::TYPE);
        $result = $this->export_service->export_customer_data($customer_id, $export_strategy);
        return new Json_Response(json_decode($result));
    }
    /**
     * delete customer data
     *
     *
     */
    public function delete(string $data): Response
    {
        $customer_id = new Customer_Id(intval($data));
        $customer_data = $this->customer_repository->find_customer_name_by_customer_id($customer_id);
        $this->customer_service->delete_customer_data_from_prestashop($customer_id);
        $this->customer_service->delete_customer_data_from_modules(strval($customer_id->get_value()));
        $this->logger_service->create_log($customer_id->get_value(), Logger_Service::REQUEST_TYPE_DELETE, 0, 0, $customer_data);
        return new Json_Response(['message' => 'delete completed']);
    }
    public function supports(string $type): bool
    {
        return $type === self::TYPE;
    }
}
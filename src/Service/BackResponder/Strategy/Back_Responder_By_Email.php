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
use Presta_Shop\Module\Psgdpr\Service\Logger_Service;
use Symfony\Component\Http_Foundation\Json_Response;
use Symfony\Component\Http_Foundation\Response;
class Back_Responder_By_Email extends Back_Responder_Context implements Back_Responder_Interface
{
    public const TYPE = 'email';
    /**
     * export customer data
     *
     *
     */
    public function export(string $data): Response
    {
        $result = $this->export_service->get_third_party_modules_informations(['email' => $data]);
        return new Json_Response($result);
    }
    /**
     * delete customer data
     *
     *
     */
    public function delete(string $data): Response
    {
        $this->customer_service->delete_customer_data_from_modules(['email' => $data]);
        $this->logger_service->create_log(0, Logger_Service::REQUEST_TYPE_DELETE, 0, 0, $data);
        return new Json_Response(['message' => 'delete completed']);
    }
    public function supports(string $type): bool
    {
        return $type === self::TYPE;
    }
}
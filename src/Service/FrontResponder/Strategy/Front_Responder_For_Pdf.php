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
namespace Presta_Shop\Module\Psgdpr\Service\Front_Responder\Strategy;

use Exception;
use Presta_Shop\Module\Psgdpr\Exception\Customer\Export_Exception;
use Presta_Shop\Module\Psgdpr\Service\Export\Strategy\Export_To_Pdf;
use Presta_Shop\Module\Psgdpr\Service\Front_Responder\Front_Responder_Context;
use Presta_Shop\Module\Psgdpr\Service\Front_Responder\Front_Responder_Interface;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Value_Object\Customer_Id;
class Front_Responder_For_Pdf extends Front_Responder_Context implements Front_Responder_Interface
{
    public const TYPE = 'pdf';
    /**
     * export customer data to csv
     *
     *
     */
    public function export(Customer_Id $customerid): void
    {
        try {
            $export_strategy = $this->export_factory->get_strategy_by_type(Export_To_Pdf::TYPE);
            $this->export_service->export_customer_data($customerid, $export_strategy);
            exit;
        } catch (Exception $e) {
            throw new Export_Exception('A problem occurred while exporting customer to pdf. please try again');
        }
    }
    public function supports(string $type): bool
    {
        return $type === self::TYPE;
    }
}
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
namespace Presta_Shop\Module\Psgdpr\Service\Export\Strategy;

use Presta_Shop\Module\Psgdpr\Service\Export\Export_Context;
use Presta_Shop\Module\Psgdpr\Service\Export\Export_Interface;
use Presta_Shop\Module\Psgdpr\Service\Logger_Service;
class Export_To_Csv extends Export_Context implements Export_Interface
{
    public const TYPE = 'csv';
    /**
     * Generate CSV file from customer data
     */
    public function get_data(array $customer_data): string
    {
        $buffer = fopen('php://output', 'w');
        ob_start();
        foreach ($customer_data as $key => $value) {
            if ($key === 'modules') {
                foreach ($value as $third_party_value) {
                    $this->insert_data_in_csv($buffer, $third_party_value);
                }
                continue;
            }
            $this->insert_data_in_csv($buffer, $value);
        }
        $csv_file = ob_get_clean();
        fclose($buffer);
        if (empty($csv_file)) {
            return '';
        }
        $customer_full_name = $customer_data['personalinformations']['data'][0]['firstname'] . ' ' . $customer_data['personalinformations']['data'][0]['lastname'];
        $this->logger_service->create_log($customer_data['personalinformations']['data'][0]['id'], Logger_Service::REQUEST_TYPE_EXPORT_CSV, 0, 0, $customer_full_name);
        return $csv_file;
    }
    /**
     * Insert data in CSV file
     *
     * @param mixed $buffer
     * @param mixed $value
     */
    private function insert_data_in_csv($buffer, array $value): void
    {
        fputcsv($buffer, [strtoupper($value['name'])]);
        fputcsv($buffer, $value['headers']);
        foreach ($value['data'] as $data) {
            fputcsv($buffer, $data);
        }
        fputcsv($buffer, []);
    }
    public function supports(string $type): bool
    {
        return $type === self::TYPE;
    }
}
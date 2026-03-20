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

use Pdf_Generator;
use Presta_Shop\Module\Psgdpr\Service\Export\Export_Context;
use Presta_Shop\Module\Psgdpr\Service\Export\Export_Interface;
use Presta_Shop\Module\Psgdpr\Service\Logger_Service;
use Presta_Shop\Module\Psgdpr\Service\Pdf_Generator_Service;
class Export_To_Pdf extends Export_Context implements Export_Interface
{
    public const TYPE = 'pdf';
    /**
     * Generate PDF file from customer data
     */
    public function get_data(array $customer_data): string
    {
        $this->context->smarty->escape_html = false;
        $pdf_generator = new Pdf_Generator(false, 'P');
        $template = new Pdf_Generator_Service($customer_data, $this->context->smarty);
        $pdf_generator->set_font_for_lang($this->context->language->iso_code);
        $pdf_generator->start_page_group();
        $pdf_generator->create_header($template->get_header());
        $pdf_generator->create_pagination($template->get_pagination());
        $pdf_generator->create_content($template->get_content());
        $pdf_generator->create_footer($template->get_footer());
        $pdf_generator->write_page();
        $pdf_file = $pdf_generator->render($template->get_filename(), 'D');
        $customer_full_name = $customer_data['personalinformations']['data'][0]['firstname'] . ' ' . $customer_data['personalinformations']['data'][0]['lastname'];
        $this->logger_service->create_log($customer_data['personalinformations']['data'][0]['id'], Logger_Service::REQUEST_TYPE_EXPORT_PDF, 0, 0, $customer_full_name);
        return $pdf_file;
    }
    public function supports(string $type): bool
    {
        return $type === self::TYPE;
    }
}
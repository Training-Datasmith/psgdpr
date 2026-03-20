<?php

declare (strict_types=1);
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
namespace Presta_Shop\Module\Psgdpr\Controller\Admin;

use Context;
use Exception;
use Object_Model;
use PDF;
use Presta_Shop\Module\Psgdpr\Exception\Customer_Has_Not_Invoices_Exception;
use Presta_Shop\Module\Psgdpr\Exception\Download_Invoices_Failed_Exception;
use Presta_Shop\Module\Psgdpr\Repository\Order_Invoice_Repository;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Value_Object\Customer_Id;
use Presta_Shop_Bundle\Controller\Admin\Framework_Bundle_Admin_Controller;
use Symfony\Component\Http_Foundation\Request;
class Download_Customer_Invoices_Controller extends Framework_Bundle_Admin_Controller
{
    /**
     * @var OrderInvoiceRepository
     */
    private $order_invoice_repository;
    public function __construct(Order_Invoice_Repository $order_invoice_repository)
    {
        $this->order_invoice_repository = $order_invoice_repository;
    }
    /**
     * Endpoint to retrieve all pdf invoices from a specific customer
     *
     *
     * @throws DownloadInvoicesFailedException
     */
    public function download_invoices_by_customer_id(Request $request, int $customer_id): void
    {
        $customer_id = new Customer_Id($customer_id);
        $this->assert_that_customer_has_invoices_before_download($customer_id);
        try {
            $order_invoice_list = $this->order_invoice_repository->find_all_invoices_by_customer_id($customer_id);
            $order_invoice_collection = Object_Model::hydrate_collection('OrderInvoice', $order_invoice_list);
            $pdf = new PDF($order_invoice_collection, PDF::TEMPLATE_INVOICE, Context::get_context()->smarty);
            $pdf->render();
        } catch (Exception $e) {
            throw new Download_Invoices_Failed_Exception('An error occured while trying to download the invoices');
        }
    }
    /**
     * @throws CustomerHasNotInvoicesException
     */
    private function assert_that_customer_has_invoices_before_download(Customer_Id $customer_id): void
    {
        $customer_has_invoices = $this->order_invoice_repository->find_if_invoices_exist_by_customer_id($customer_id);
        if (!$customer_has_invoices) {
            throw new Customer_Has_Not_Invoices_Exception('The given customer has not any invoices associated to his account');
        }
    }
}
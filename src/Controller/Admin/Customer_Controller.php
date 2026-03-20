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

use Exception;
use Order;
use Presta_Shop\Module\Psgdpr\Exception\Customer\Delete_Exception;
use Presta_Shop\Module\Psgdpr\Repository\Order_Invoice_Repository;
use Presta_Shop\Module\Psgdpr\Service\Back_Responder\Back_Responder_Factory;
use Presta_Shop\Presta_Shop\Core\Command_Bus\Command_Bus_Interface;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Query\Search_Customers;
use Presta_Shop\Presta_Shop\Core\Domain\Customer\Value_Object\Customer_Id;
use Presta_Shop_Bundle\Controller\Admin\Framework_Bundle_Admin_Controller;
use Symfony\Component\Http_Foundation\Request;
use Symfony\Component\Http_Foundation\Response;
class Customer_Controller extends Framework_Bundle_Admin_Controller
{
    /**
     * @var CommandBusInterface
     */
    private $query_bus;
    /**
     * @var OrderInvoiceRepository
     */
    private $order_invoice_repository;
    /**
     * @var BackResponderFactory
     */
    private $back_responder_factory;
    public function __construct(Command_Bus_Interface $query_bus, Order_Invoice_Repository $order_invoice_repository, Back_Responder_Factory $back_responder_factory)
    {
        $this->query_bus = $query_bus;
        $this->order_invoice_repository = $order_invoice_repository;
        $this->back_responder_factory = $back_responder_factory;
    }
    /**
     * Search customer by email
     *
     *
     */
    public function search_customers(Request $request): Response
    {
        $request_body_content = json_decode($request->get_content(), true);
        $phrase = $request_body_content['phrase'];
        if (empty($phrase)) {
            return $this->json(['message' => 'Property phrase is missing or empty.'], Response::HTTP_BAD_REQUEST);
        }
        /** @var array $customerList */
        $customer_list = $this->query_bus->handle(new Search_Customers([$phrase]));
        if (empty($customer_list)) {
            return $this->json(['message' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }
        $customer_list = array_map(function (array $customer): array {
            return ['idCustomer' => $customer['id_customer'], 'firstname' => $customer['firstname'], 'lastname' => $customer['lastname'], 'email' => $customer['email'], 'nb_orders' => Order::get_customer_nb_orders($customer['id_customer']), 'customerData' => []];
        }, $customer_list);
        return $this->json($customer_list);
    }
    /**
     * Delete User data by customer id
     *
     *
     */
    public function delete_customer_data(Request $request): Response
    {
        $request_body_content = json_decode($request->get_content(), true);
        $data_type_requested = strval($request_body_content['dataTypeRequested']);
        $customer_data = strval($request_body_content['customerData']);
        try {
            $customer_data_responder_strategy = $this->back_responder_factory->get_strategy_by_type($data_type_requested);
            return $customer_data_responder_strategy->delete($customer_data);
        } catch (Delete_Exception $e) {
            return $this->json(['message' => 'A problem occurred while deleting please try again'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Get user data for the given customer
     *
     *
     */
    public function get_customer_data(Request $request): Response
    {
        $request_body_content = json_decode($request->get_content(), true);
        $data_type_requested = strval($request_body_content['dataTypeRequested']);
        $customer_data = strval($request_body_content['customerData']);
        try {
            $customer_data_responder_strategy = $this->back_responder_factory->get_strategy_by_type($data_type_requested);
            return $customer_data_responder_strategy->export($customer_data);
        } catch (Exception $e) {
            return $this->json(['message' => 'A problem occurred while retrieving customer data please try again'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Generate link to DownloadCustomerInvoicesController in order to download invoices
     *
     *
     */
    public function get_download_invoices_link_by_customer_id(Request $request, int $customer_id): Response
    {
        try {
            $customer_id = new Customer_Id($customer_id);
            $customer_has_invoices = $this->order_invoice_repository->find_if_invoices_exist_by_customer_id($customer_id);
            if (!$customer_has_invoices) {
                return $this->json(['message' => 'There is no invoices found for this customer'], Response::HTTP_NOT_FOUND);
            }
            return $this->json(['invoicesDownloadLink' => $this->generate_url('psgdpr_api_download_customer_invoices', ['customerId' => $customer_id->get_value()])]);
        } catch (Exception $e) {
            return $this->json(['message' => 'A problem occurred while retrieving number of invoices'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
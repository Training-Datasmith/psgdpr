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
namespace Presta_Shop\Module\Psgdpr\Service;

use Exception;
use Presta_Shop\Module\Psgdpr\Entity\Psgdpr_Log;
use Presta_Shop\Module\Psgdpr\Exception\Logger\Add_Log_Exception;
use Presta_Shop\Module\Psgdpr\Repository\Logger_Repository;
class Logger_Service
{
    public const REQUEST_TYPE_CONSENT_COLLECTING = 1;
    public const REQUEST_TYPE_EXPORT_PDF = 2;
    public const REQUEST_TYPE_EXPORT_CSV = 3;
    public const REQUEST_TYPE_DELETE = 4;
    /**
     * @var LoggerRepository
     */
    private $logger_repository;
    public function __construct(Logger_Repository $logger_repository)
    {
        $this->logger_repository = $logger_repository;
    }
    /**
     * Creates a GDPR activity log entry for a customer action (consent, export, or deletion).
     *
     * @param int    $customer_id  ID of the customer associated with this log entry
     * @param int    $request_type Type of GDPR request (use REQUEST_TYPE_* constants)
     * @param int    $module_id    ID of the module recording the log
     * @param int    $guest_id     ID of the guest if the customer is not registered (default 0)
     * @param string $client_data  Optional serialised client data to store with the log entry
     *
     * @throws Add_Log_Exception If saving the log entry fails
     */
    public function create_log(int $customer_id, int $request_type, int $module_id, int $guest_id = 0, string $client_data = ''): void
    {
        try {
            $log = new Psgdpr_Log();
            $log->set_customer_id($customer_id);
            $log->set_request_type($request_type);
            $log->set_module_id($module_id);
            $log->set_guest_id($guest_id);
            $log->set_client_data($client_data);
            $this->logger_repository->add($log);
        } catch (Exception $e) {
            throw new Add_Log_Exception($e->get_message());
        }
    }
    /**
     * Get logs
     */
    public function get_logs(): array
    {
        return $this->logger_repository->find_all();
    }
}
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
namespace Presta_Shop\Module\Psgdpr\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Presta_Shop\Module\Psgdpr\Exception\Logger\Request_Type_Validity_Exception;
use Presta_Shop\Module\Psgdpr\Service\Logger_Service;
/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="PrestaShop\Module\Psgdpr\Repository\LoggerRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Psgdpr_Log
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_gdpr_log", type="integer", length=10, nullable=false)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var int
     *
     * @ORM\Column(name="id_customer", type="integer", length=10, nullable=false)
     */
    private $customer_id;
    /**
     * @var int
     *
     * @ORM\Column(name="id_guest", type="integer", length=10, nullable=false)
     */
    private $guest_id;
    /**
     * @var string
     *
     * @ORM\Column(name="client_name", type="string", length=255, nullable=false)
     */
    private $client_data;
    /**
     * @var int
     *
     * @ORM\Column(name="id_module", type="integer", nullable=false)
     */
    private $module_id;
    /**
     * @var int
     *
     * @ORM\Column(name="request_type", type="integer", nullable=false)
     */
    private $request_type;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_add", type="datetime", nullable=false)
     */
    private $created_at;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_upd", type="datetime", nullable=false)
     */
    private $updated_at;
    public function get_id(): int
    {
        return $this->id;
    }
    public function get_customer_id(): int
    {
        return $this->customer_id;
    }
    /**
     * @return $this
     */
    public function set_customer_id(int $customer_id): self
    {
        $this->customer_id = $customer_id;
        return $this;
    }
    public function get_guest_id(): int
    {
        return $this->guest_id;
    }
    /**
     * @return $this
     */
    public function set_guest_id(int $guest_id): self
    {
        $this->guest_id = $guest_id;
        return $this;
    }
    public function get_client_data(): string
    {
        return $this->client_data;
    }
    /**
     * @return $this
     */
    public function set_client_data(string $client_data): self
    {
        $this->client_data = $client_data;
        return $this;
    }
    public function get_module_id(): int
    {
        return $this->module_id;
    }
    /**
     * @return $this
     */
    public function set_module_id(int $module_id): self
    {
        $this->module_id = $module_id;
        return $this;
    }
    /**
     * @return $this
     */
    public function set_request_type(int $request_type): self
    {
        $this->assert_request_type_is_valid($request_type);
        $this->request_type = $request_type;
        return $this;
    }
    public function get_request_type(): int
    {
        return $this->request_type;
    }
    /**
     * @return mixed
     */
    public function get_created_at()
    {
        return $this->created_at;
    }
    /**
     * @return $this
     */
    private function set_created_at(DateTime $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }
    public function get_updated_at(): DateTime
    {
        return $this->updated_at;
    }
    /**
     * @return $this
     */
    private function set_updated_at(DateTime $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }
    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updated_timestamps(): void
    {
        $date_time_now = new DateTime('now');
        if ($this->get_created_at() == null) {
            $this->set_created_at($date_time_now);
        }
        $this->set_updated_at($date_time_now);
    }
    /**
     * Asserts that request type is valid
     *
     *
     *
     * @throws InvalidArgumentException
     */
    private function assert_request_type_is_valid(int $request_type): void
    {
        $valid_types = [Logger_Service::REQUEST_TYPE_EXPORT_CSV, Logger_Service::REQUEST_TYPE_EXPORT_PDF, Logger_Service::REQUEST_TYPE_CONSENT_COLLECTING, Logger_Service::REQUEST_TYPE_DELETE];
        if (!in_array($request_type, $valid_types)) {
            throw new Request_Type_Validity_Exception(sprintf('Invalid request type %s', $request_type));
        }
    }
}
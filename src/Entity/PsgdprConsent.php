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
use Doctrine\Common\Collections\Array_Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Persistent_Collection;
/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="PrestaShop\Module\Psgdpr\Repository\ConsentRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Psgdpr_Consent
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="id_gdpr_consent", type="integer", length=10, nullable=false)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var int
     *
     * @ORM\Column(name="id_module", type="integer", nullable=false)
     */
    private $module_id;
    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active = true;
    /**
     * @var bool
     *
     * @ORM\Column(name="error", type="boolean", nullable=false)
     */
    private $error = false;
    /**
     * @var string
     *
     * @ORM\Column(name="error_message", type="string", length=255, nullable=false)
     */
    private $error_message = '';
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="PrestaShop\Module\Psgdpr\Entity\PsgdprConsentLang", cascade={"persist", "remove"}, mappedBy="consent")
     */
    private $consent_langs;
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
    public function __construct()
    {
        $this->consent_langs = new Array_Collection();
    }
    public function get_id(): int
    {
        return $this->id;
    }
    /**
     * @return $this
     */
    public function set_id(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function get_consent_langs()
    {
        return $this->consent_langs;
    }
    /**
     * @return $this
     */
    public function set_consent_langs(Array_Collection $consent_langs): self
    {
        $this->consent_langs = $consent_langs;
        return $this;
    }
    /**
     * @return $this
     */
    public function add_consent_lang(Psgdpr_Consent_Lang $consent_lang): self
    {
        $consent_lang->set_consent($this);
        $this->consent_langs->add($consent_lang);
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
    public function is_active(): bool
    {
        return $this->active;
    }
    /**
     * @return $this
     */
    public function set_active(bool $active): self
    {
        $this->active = $active;
        return $this;
    }
    public function is_error(): bool
    {
        return $this->error;
    }
    /**
     * @return $this
     */
    public function set_error(bool $error): self
    {
        $this->error = $error;
        return $this;
    }
    public function get_error_message(): string
    {
        return $this->error_message;
    }
    /**
     * @return $this
     */
    public function set_error_message(string $error_message): self
    {
        $this->error_message = $error_message;
        return $this;
    }
    public function get_consent_content(): string
    {
        if ($this->consent_langs->count() <= 0) {
            return '';
        }
        $consent_lang = $this->consent_langs->first();
        return $consent_lang->get_content();
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
}
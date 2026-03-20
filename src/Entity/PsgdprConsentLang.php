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

use Doctrine\ORM\Mapping as ORM;
use Presta_Shop_Bundle\Entity\Lang;
/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class Psgdpr_Consent_Lang
{
    /**
     * @var PsgdprConsent
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PrestaShop\Module\Psgdpr\Entity\PsgdprConsent", inversedBy="consentLangs", cascade={"persist", "merge", "remove"})
     * @ORM\JoinColumn(name="id_gdpr_consent", referencedColumnName="id_gdpr_consent", nullable=false)
     */
    private $consent;
    /**
     * @var Lang
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PrestaShopBundle\Entity\Lang")
     * @ORM\JoinColumn(name="id_lang", referencedColumnName="id_lang", nullable=false, onDelete="CASCADE")
     */
    private $lang;
    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=255, nullable=false)
     */
    private $message;
    /**
     * @var int
     * @ORM\Column(name="id_shop", type="integer", length=10, nullable=false)
     */
    private $shop_id;
    public function get_consent(): Psgdpr_Consent
    {
        return $this->consent;
    }
    /**
     * @return $this
     */
    public function set_consent(Psgdpr_Consent $consent): self
    {
        $this->consent = $consent;
        return $this;
    }
    public function get_lang(): Lang
    {
        return $this->lang;
    }
    /**
     * @return $this
     */
    public function set_lang(Lang $lang): self
    {
        $this->lang = $lang;
        return $this;
    }
    public function get_message(): string
    {
        return $this->message;
    }
    /**
     * @return $this
     */
    public function set_message(string $message): self
    {
        $this->message = $message;
        return $this;
    }
    public function get_shop_id(): int
    {
        return $this->shop_id;
    }
    /**
     * @return $this
     */
    public function set_shop_id(int $shop_id): self
    {
        $this->shop_id = $shop_id;
        return $this;
    }
}
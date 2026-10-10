<?php
/**
 * Copyright © Webjump. All rights reserved.
 * Customer Data Section for Spooky Session Scare Counter (Desafio 17.6)
 */

declare(strict_types=1);

namespace Webjump\Samuel\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;

class SpookySession implements SectionSourceInterface
{
    /**
     * @param CustomerSession $customerSession
     */
    public function __construct(
        private readonly CustomerSession $customerSession
    ) {
    }

    /**
     * Get section data for customerData.get('spooky-session')
     *
     * @return array
     */
    public function getSectionData(): array
    {
        $scaresCount = (int) $this->customerSession->getData('spooky_scares_count');

        if ($scaresCount <= 0) {
            $level = __('Inocente Desavisado');
        } elseif ($scaresCount <= 2) {
            $level = __('Aprendiz do Além');
        } elseif ($scaresCount <= 5) {
            $level = __('Caçador de Fantasmas');
        } else {
            $level = __('Arquimago das Trevas');
        }

        return [
            'scares_count' => $scaresCount,
            'scare_level' => (string) $level,
            'spooky_title' => (string) __('Contador de Sustos da Sessão'),
            'halloween_greeting' => (string) __('Sua alma foi registrada no Covil de Halloween!'),
            'updated_at' => time()
        ];
    }
}

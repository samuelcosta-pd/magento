<?php
/**
 * Copyright © Webjump. All rights reserved.
 * Customer Data Section for Spooky Session Scare Counter (Diferencial 17.6)
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
            $scaresCount = 1;
            $this->customerSession->setData('spooky_scares_count', $scaresCount);
        }

        $level = $scaresCount >= 5 ? __('Arquimago das Trevas') : __('Aprendiz do Além');

        return [
            'scares_count' => $scaresCount,
            'scare_level' => (string) $level,
            'spooky_title' => (string) __('Contador de Sustos da Sessão'),
            'halloween_greeting' => (string) __('Sua alma foi registrada no Covil de Halloween!'),
            'updated_at' => time()
        ];
    }
}

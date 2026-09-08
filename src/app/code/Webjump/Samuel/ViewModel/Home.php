<?php

namespace Webjump\Samuel\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class Home implements ArgumentInterface
{
    public function getMessage(): string
    {
        return 'Olá! Este bloco foi criado pelo módulo Webjump_Samuel.';
    }
}
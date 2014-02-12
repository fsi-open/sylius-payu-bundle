<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\SyliusPayuBundle;

use FSi\Bundle\SyliusPayuBundle\DependencyInjection\Factory\Payment\PayuPaymentFactory;
use Payum\Bundle\PayumBundle\DependencyInjection\PayumExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SyliusPayuBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        /** @var  PayumExtension $payumExtension */
        $payumExtension = $container->getExtension('payum');

        $payumExtension->addPaymentFactory(new PayuPaymentFactory());
    }
}
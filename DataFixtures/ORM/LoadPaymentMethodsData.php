<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\SyliusPayuBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Sylius\Bundle\CoreBundle\DataFixtures\ORM\DataFixture;

class LoadPaymentMethodsData extends DataFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager->persist($this->createPaymentMethod('PayU', 'payu'));
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 5;
    }

    /**
     * Create payment method.
     *
     * @param string  $name
     * @param string  $gateway
     * @param Boolean $enabled
     *
     * @return PaymentMethodInterface
     */
    private function createPaymentMethod($name, $gateway, $enabled = true)
    {
        $method = $this
            ->getPaymentMethodRepository()
            ->createNew()
        ;

        $method->setName($name);
        $method->setGateway($gateway);
        $method->setEnabled($enabled);

        $this->setReference('Sylius.PaymentMethod.'.$name, $method);

        return $method;
    }
}

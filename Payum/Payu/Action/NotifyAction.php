<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\SyliusPayuBundle\Payum\Payu\Action;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use FSi\Bundle\SyliusPayuBundle\Payum\Payu\Api;
use Payum\Bundle\PayumBundle\Request\ResponseInteractiveRequest;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\NotifyRequest;
use Sylius\Bundle\PaymentsBundle\SyliusPaymentEvents;
use Sylius\Bundle\PayumBundle\Payum\Request\StatusRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class NotifyAction extends PaymentAwareAction implements ApiAwareInterface
{
    /**
     * @var string
     */
    private $orderIdentifier;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ObjectRepository
     */
    private $objectRepository;

    /**
     * @var Api
     */
    private $api;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param ObjectRepository $objectRepository
     * @param ObjectManager $objectManager
     * @param string $orderIdentifier
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ObjectRepository $objectRepository,
        ObjectManager $objectManager,
        $orderIdentifier = 'id'
    ) {
        $this->objectRepository = $objectRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->objectManager = $objectManager;
        $this->orderIdentifier = $orderIdentifier;
    }

    /**
     * @param Api $api
     */
    public function setApi($api)
    {
        $this->api = $api;
    }

    /**
     * @inheritdoc
     */
    public function execute($request)
    {
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        try {
            $this->api->validatePaymentNotification($request->getNotification());
            $paymentDetails = $this->api->getPaymentDetails($request->getNotification());
            $this->api->validatePaymentDetails($paymentDetails);
        } catch (\Exception $e) {
            throw new BadRequestHttpException(new Response($e->getMessage()));
        }

        /* @var $order \Sylius\Bundle\CoreBundle\Model\OrderInterface */
        $order = $this->objectRepository->findOneBy(array(
            $this->orderIdentifier => $paymentDetails['order_id']
        ));

        if (!isset($order)) {
            throw new BadRequestHttpException(
                new Response(sprintf("Can't find order with id %d", $paymentDetails['order_id']))
            );
        }

        $payment = $order->getPayment();

        if ((int) $paymentDetails['amount'] !== $payment->getAmount()) {
            throw new BadRequestHttpException('Request amount cannot be verified against payment amount.');
        }

        $previousState = $payment->getState();

        $details = array_merge($payment->getDetails(), $paymentDetails);
        $payment->setDetails($details);

        $status = new StatusRequest($order);
        $this->payment->execute($status);

        $payment->setState($status->getStatus());

        if ($previousState !== $payment->getState()) {
            $this->eventDispatcher->dispatch(
                SyliusPaymentEvents::PRE_STATE_CHANGE,
                new GenericEvent($order->getPayment(), array('previous_state' => $previousState))
            );

            $this->objectManager->flush();

            $this->eventDispatcher->dispatch(
                SyliusPaymentEvents::POST_STATE_CHANGE,
                new GenericEvent($order->getPayment(), array('previous_state' => $previousState))
            );
        }

        throw new ResponseInteractiveRequest(new Response(Api::PAYMENT_STATUS_OK));
    }

    /**
     * @inheritdoc
     */
    public function supports($request)
    {
        return $request instanceof NotifyRequest;
    }
}
<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\SyliusPayuBundle\Payum\Payu\Action;

use FSi\Bundle\SyliusPayuBundle\Payum\Payu\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Request\PostRedirectUrlInteractiveRequest;
use Payum\Core\Request\SecuredCaptureRequest;
use Sylius\Bundle\CoreBundle\Model\OrderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class CaptureAction implements ActionInterface, ApiAwareInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Request
     */
    protected $httpRequest;

    function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function setApi($api)
    {
        if (false == $api instanceof Api) {
            throw new UnsupportedApiException('Not supported api type.');
        }

        $this->api = $api;
    }

    /**
     * Define the Symfony Request
     *
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->httpRequest = $request;
    }

    /**
     * @param mixed $request
     *
     * @throws \LogicException
     * @throws \Payum\Core\Request\PostRedirectUrlInteractiveRequest
     * @throws \InvalidArgumentException
     * @throws \Payum\Core\Exception\RequestNotSupportedException
     */
    function execute($request)
    {
        /* @var $request SecuredCaptureRequest */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        if (!$this->httpRequest) {
            throw new \LogicException('The action can be run only when http request is set.');
        }

        /* @var $order \Sylius\Bundle\CoreBundle\Model\OrderInterface */
        $order = $request->getModel();
        $payment = $order->getPayment();

        if ($order->getCurrency() != 'PLN') {
            throw new \InvalidArgumentException(
                sprintf("Currency %s is not supported in PayU payments", $order->getCurrency())
            );
        }

        $details = array(
            'session_id' => $this->httpRequest->getSession()->getId() . time(),
            'amount' => $order->getTotal(),
            'desc' => sprintf(
                'Zamówienie %d przedmiotów na kwotę %01.2f',
                $order->getItems()->count(),
                $order->getTotal() / 100
            ),
            'order_id' => $order->getId(),
            'first_name' => $order->getBillingAddress()->getFirstName(),
            'last_name' => $order->getBillingAddress()->getLastName(),
            'email' => $order->getUser()->getEmail(),
            'client_ip' => $this->httpRequest->getClientIp()
        );

        /* TODO this should be removed after PayumBundle 0.8 */
        $request->getToken()->setTargetUrl(
            $this->router->generate('sylius_payu_capture_do', array('payum_token' => $request->getToken()->getHash()))
        );

        $payment->setDetails($details);

        $request->setModel($payment);
        $this->httpRequest->getSession()->set('payum_token', $request->getToken()->getHash());
        $request->setModel($order);

        throw new PostRedirectUrlInteractiveRequest(
            $this->api->getNewPaymentUrl(),
            $this->api->prepareNewPaymentDetails($details)
        );
    }

    /**
     * @param mixed $request
     *
     * @return boolean
     */
    function supports($request)
    {
        return $request instanceof SecuredCaptureRequest &&
            $request->getModel() instanceof OrderInterface;
    }
}
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
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\StatusRequestInterface;

class StatusAction implements ActionInterface
{
    /**
     * @inheritdoc
     */
    function execute($request)
    {
        /* @var $request StatusRequestInterface */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        $paymentDetails = new ArrayObject($request->getModel());

        /* @var $request StatusRequestInterface */
        if (is_null($paymentDetails['status'])) {
            $request->markNew();
            return;
        }

        if ($paymentDetails['status'] === Api::PAYMENT_STATE_NEW) {
            $request->markNew();
            return;
        }

        if ($paymentDetails['status'] === Api::PAYMENT_STATE_COMPLETED) {
            $request->markSuccess();
            return;
        }

        if ($paymentDetails['status'] === Api::PAYMENT_STATE_CANCELLED) {
            $request->markCanceled();
            return;
        }

        if ($paymentDetails['status'] === Api::PAYMENT_STATE_PENDING) {
            $request->markPending();
            return;
        }

        $request->markFailed();
    }

    /**
     * @inheritdoc
     */
    function supports($request)
    {
        return $request instanceof StatusRequestInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
<?php

namespace Unzer\PAPI\Controller\Adminhtml\Order\Invoice;

use Magento\Sales\Controller\Adminhtml\Invoice\AbstractInvoice\View;

class Cancel extends View
{
    public const ADMIN_RESOURCE = 'Magento_Sales::invoice';

    public function execute()
    {
        $invoice = $this->getInvoice();
        if (!$invoice) {
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }

        try {
            $invoice->cancel();
            $invoice->getOrder()->setIsInProcess(true);
            $this->_objectManager->create(
                \Magento\Framework\DB\Transaction::class
            )->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            )->save();
            $this->messageManager->addSuccessMessage(__('You canceled the invoice.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Invoice canceling error'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/*/view', ['invoice_id' => $invoice->getId()]);
        return $resultRedirect;
    }
}

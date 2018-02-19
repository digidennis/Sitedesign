<?php
/**
 * Created by PhpStorm.
 * User: W10
 * Date: 03-01-2018
 * Time: 13:13
 */

class Digidennis_Sitedesign_Model_Order_Pdf_Invoice extends Digidennis_Sitedesign_Model_Order_Pdf_Abstract
{
    /**
     * Return PDF document
     *
     * @param  array $invoices
     * @return Zend_Pdf
     */
    public function getPdf($invoices = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');
        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
                Mage::app()->setCurrentStore($invoice->getStoreId());
            }

            $page  = $this->newPage();
            $order = $invoice->getOrder();
            //fakturamodtager
            $this->_setFontRegular($page, 10);
            $billingAddress = $this->_formatAddress($order->getBillingAddress()->format('pdf'));
            $addressboxtop = 740;
            foreach ($billingAddress as $value){
                if ($value !== '') {
                    $text = array();
                    foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                        $text[] = $_value;
                    }
                    foreach ($text as $part) {
                        $page->drawText(strip_tags(ltrim($part)),  $this->_leftstop, $addressboxtop, 'UTF-8');
                        $addressboxtop -= 14;
                    }
                }
            }

            /* Payment */
            $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())->setIsSecureMode(true)->toPdf();
            $paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
            $payment = explode('{{pdf_row_separator}}', $paymentInfo);
            foreach ($payment as $key=>$value){
                if (strip_tags(trim($value)) == '') {
                    unset($payment[$key]);
                }
            }
            reset($payment);

            //draw invoiced items, may span pages
            $this->y = 540;
            $this->_drawHeader($page);

            foreach ($invoice->getAllItems() as $item){
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.9));
                $page->drawLine($this->_leftstop, $this->y+3, 550, $this->y+3 );
                $this->y -= 15;
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

            }

            $this->insertTotals($page, $invoice);
            $pagecount = count($this->_getPdf()->pages);
            $numpage = 1;
            foreach ($this->_getPdf()->pages as $collectedpage )
            {
                //FAKTURA BIG BOLD
                $collectedpage->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                $this->_setFontBold($collectedpage, 18);
                $collectedpage->drawText("FAKTURA", $this->_leftstop, 600-36, 'UTF-8');
                //fakturanr, dato, ordrenr
                $this->_setFontRegular($collectedpage, 9);
                $collectedpage->drawText('Faktura: ' . $invoice->getIncrementId(), $this->_rightstop, 600, 'UTF-8');
                $collectedpage->drawText('Fakturadato: '  . Mage::helper('core')->formatDate($invoice->getCreatedAt(), 'long', false), $this->_rightstop, 600-12, 'UTF-8');
                $collectedpage->drawText('Ordre: ' . $order->getIncrementId(), $this->_rightstop, 600-24, 'UTF-8');
                $collectedpage->drawText('Side ' . $numpage . ' af ' . $pagecount, $this->_rightstop, 600-36, 'UTF-8' );
                $this->_renderPaymentInfo($payment, $collectedpage);
                $numpage++;
            }

            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->revert();
            }
        }
        $this->_afterGetPdf();
        return $pdf;
    }
    protected function _renderPaymentInfo( array $info, $page)
    {
        $yPayments = 70;
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.9));
        $page->drawLine($this->_leftstop, $yPayments+14, 550, $yPayments+14 );
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        foreach ($info as $value){
            if (trim($value) != '') {
                //Printing "Payment Method" lines
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                    $page->drawText(strip_tags(trim($_value)), $this->_leftstop, $yPayments, 'UTF-8');
                    $yPayments -= 12;
                }
            }
        }
    }
    /**
     * Insert totals to pdf page
     */
    protected function insertTotals($page, $source){
        $order = $source->getOrder();
        $totals = $this->_getTotalsList($source);
        $lineBlock = array(
            'lines'  => array(),
            'height' => 15
        );
        foreach ($totals as $total) {
            $total->setOrder($order)
                ->setSource($source);

            if ($total->canDisplay()) {
                $total->setFontSize(10);
                foreach ($total->getTotalsForDisplay() as $totalData) {
                    $lineBlock['lines'][] = array(
                        array(
                            'text'      => $totalData['label'],
                            'feed'      => 455,
                            'align'     => 'right',
                            'font_size' => $totalData['font_size'],
                            'font'      => 'bold'
                        ),
                        array(
                            'text'      => $totalData['amount'],
                            'feed'      => 545,
                            'align'     => 'right',
                            'font_size' => $totalData['font_size'],
                            'font'      => 'bold'
                        ),
                    );
                }
            }
        }

        $this->y -= 20;
        $page = $this->drawLineBlocks($page, array($lineBlock));
        return $page;
    }
    /**
     * Draw header for item table
     */
    protected function _drawHeader(Zend_Pdf_Page $page)
    {
        /* Add table head */
        $this->_setFontRegular($page, 9);
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle( $this->_leftstop, $this->y, 550, $this->y -15);
        $this->y -= 11;
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

        //columns headers
        $lines[0][] = array(
            'text' => Mage::helper('sales')->__('Product'),
            'font_size' => 9,
            'feed' =>  $this->_leftstop + 4
        );

        $lines[0][] = array(
            'text'  => Mage::helper('sales')->__('Qty'),
            'font_size' => 9,
            'feed'  => 390,
            'align' => 'center'
        );

        $lines[0][] = array(
            'text'  => Mage::helper('sales')->__('Price'),
            'feed'  => 420,
            'font_size' => 9,
            'align' => 'center'
        );

        $lines[0][] = array(
            'text'  => Mage::helper('sales')->__('Subtotal'),
            'feed'  => 545,
            'font_size' => 9,
            'align' => 'right'
        );

        $lineBlock = array(
            'lines'  => $lines,
            'height' => 5
        );

        $this->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }
}
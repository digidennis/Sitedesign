<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Sales
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales Order Shipment PDF model
 *
 * @category   Mage
 * @package    Mage_Sales
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Digidennis_Sitedesign_Model_Order_Pdf_Shipment extends Digidennis_Sitedesign_Model_Order_Pdf_Abstract
{
    /**
     * Return PDF document
     *
     * @param  array $invoices
     * @return Zend_Pdf
     * @throws Zend_Pdf_Exception
     */
    public function getPdf($shipments = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('shipment');
        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        foreach ($shipments as $shipment) {
            if ($shipment->getStoreId()) {
                Mage::app()->getLocale()->emulate($shipment->getStoreId());
                Mage::app()->setCurrentStore($shipment->getStoreId());
            }
            $page  = $this->newPage();
            $order = $shipment->getOrder();

            //modtager
            $shippingAddress = $this->_formatAddress($order->getShippingAddress()->format('pdf'));
            $this->y = 730;
            $this->_setFontBold($page, 10);
            $page->drawText('Leveres til',  $this->_leftstop+180, $this->y+14, 'UTF-8');
            $this->_setFontRegular($page, 10);
            foreach ($shippingAddress as $value){
                if ($value !== '') {
                    $text = array();
                    foreach (Mage::helper('core/string')->str_split($value, 160, true, true) as $_value) {
                        $text[] = $_value;
                    }
                    foreach ($text as $part) {
                        $page->drawText(strip_tags(ltrim($part)),  $this->_leftstop+180, $this->y, 'UTF-8');
                        $this->y -= 14;
                    }
                }
            }

            //betaler
            $billingAddress = $this->_formatAddress($order->getBillingAddress()->format('pdf'));
            $this->y = 730;
            $this->_setFontBold($page, 10);
            $page->drawText('Bestilt af',  $this->_leftstop, $this->y+14, 'UTF-8');
            $this->_setFontRegular($page, 10);
            foreach ($billingAddress as $value){
                if ($value !== '') {
                    $text = array();
                    foreach (Mage::helper('core/string')->str_split($value, 160, true, true) as $_value) {
                        $text[] = $_value;
                    }
                    foreach ($text as $part) {
                        $page->drawText(strip_tags(ltrim($part)),$this->_leftstop, $this->y, 'UTF-8');
                        $this->y -= 14;
                    }
                }
            }
            $page->drawText(strip_tags(ltrim($order->getBillingAddress()->getTelephone())),
                $this->_leftstop,
                $this->y,
                'UTF-8'
            );

            //draw invoiced items, may span pages
            $this->y = 600;
            $this->_drawHeader($page);
            foreach ($shipment->getAllItems() as $item){
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.6));
                $page->drawLine($this->_leftstop, $this->y+6, 550, $this->y+6 );
                $this->y -= 15;
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

            }
            
            //on all pages this info
            $pagecount = count($this->_getPdf()->pages);
            $numpage = 1;
            foreach ($this->_getPdf()->pages as $collectedpage )
            {
                //metode
                $this->y = 700;
                $this->_setFontRegular($page, 9);
                $shippingMethod  = $order->getShippingDescription();
                foreach (Mage::helper('core/string')->str_split($shippingMethod, 50, true, true) as $_value) {
                    $collectedpage->drawText(strip_tags(trim($_value)), $this->_rightstop, $this->y, 'UTF-8');
                    $this->y -= 14;
                }
                if(floatval($order->getShippingAmount()) > 0 ){

                    $totalShippingChargesText = "(" . Mage::helper('sales')->__('Beløb') . " "
                        . $order->formatPriceTxt($order->getShippingAmount()) . ")";
                    $collectedpage->drawText($totalShippingChargesText, $this->_rightstop, $this->y, 'UTF-8');
                }

                //BIG BOLD
                $collectedpage->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                $this->_setFontBold($collectedpage, 18);
                $collectedpage->drawText("Til Levering", $this->_leftstop, $this->y = 614, 'UTF-8');
                //Ordrenr, dato, ordrenr
                $this->_setFontRegular($collectedpage, 9);
                $collectedpage->drawText('Ordre: ' . $order->getIncrementId(), $this->_rightstop, $this->y+24, 'UTF-8');
                $collectedpage->drawText('Ordredato: '  . Mage::helper('core')->formatDate($shipment->getCreatedAt(), 'long', false), $this->_rightstop, $this->y+12, 'UTF-8');
                $collectedpage->drawText('Side ' . $numpage . ' af ' . $pagecount, $this->_rightstop, $this->y, 'UTF-8' );
                $numpage++;
            }

            if ($shipment->getStoreId()) {
                Mage::app()->getLocale()->revert();
            }
        }
        $this->_afterGetPdf();
        return $pdf;
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
            'feed' =>  $this->_leftstop + 40
        );

        $lines[0][] = array(
            'text'  => Mage::helper('sales')->__('Qty'),
            'font_size' => 9,
            'feed'  => $this->_leftstop + 4
        );

        $lineBlock = array(
            'lines'  => $lines,
            'height' => 5
        );

        $this->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }
    public function newPage(array $settings = array())
    {
        /* Add new table head */
        $template = Zend_Pdf::load('skin/frontend/kbhskum/default/images/pdfskabelon-side2.pdf');
        $page = new Zend_Pdf_Page(clone $template->pages[0]);
        $this->_getPdf()->pages[] = $page;
        $this->y = 600;
        if (!empty($settings['table_header'])) {
            $this->_drawHeader($page);
        }
        return $page;
    }
}

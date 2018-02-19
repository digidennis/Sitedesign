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
 * Sales Order Shipment Pdf default items renderer
 *
 * @category   Mage
 * @package    Mage_Sales
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Digidennis_Sitedesign_Model_Order_Pdf_Items_Shipment_Default extends Mage_Sales_Model_Order_Pdf_Items_Shipment_Default
{
    /**
     * Draw item line
     */
    public function draw()
    {
        $item   = $this->getItem();
        $pdf    = $this->getPdf();
        $page   = $this->getPage();
        $lines  = array();

        // draw Product name
        $lines[0] = array(array(
            'text' => Mage::helper('core/string')->str_split(strtoupper($item->getName()), 80, true, true),
            'feed' => 80,
        ));

        // draw QTY
        $lines[0][] = array(
            'text'  => $item->getQty()*1,
            'feed'  => 55
        );
        // custom options
        $options = $this->getItemOptions();
        $i = count($lines);
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[$i][] = array(
                    'text' => Mage::helper('core/string')->truncate(strip_tags($option['label']), 90),
                    'font' => 'italic',
                    'feed' => 80,
                    'height' => 14
                );

                if ($option['value']) {
                    if (isset($option['print_value'])) {
                        $_printValue = $option['print_value'];
                    } else {
                        $_printValue = strip_tags($option['value']);
                    }

                    $lines[$i][] = array(
                        'text' => Mage::helper('core/string')->truncate($_printValue, 340),
                        'feed' => 210,
                        'height' => 14
                    );
                }
                $i++;
            }
        }

        $lineBlock = array(
            'lines'  => $lines,
            'height' => 20
        );

        $page = $pdf->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $this->setPage($page);
    }
}

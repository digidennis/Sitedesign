<?php
/**
 * Dennis Jessen
 * 05-10-13
 * 18:50
 * 
 */

class Digidennis_Sitedesign_Block_Account_Navigation extends Mage_Customer_Block_Account_Navigation
{

    public function removeLinkByName($name)
    {
        unset($this->_links[$name]);
    }

}
<?php
/**
* Inchoo
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@magentocommerce.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Please do not edit or add to this file if you wish to upgrade
* Magento or this extension to newer versions in the future.
** Inchoo *give their best to conform to
* "non-obtrusive, best Magento practices" style of coding.
* However,* Inchoo *guarantee functional accuracy of
* specific extension behavior. Additionally we take no responsibility
* for any possible issue(s) resulting from extension usage.
* We reserve the full right not to provide any kind of support for our free extensions.
* Thank you for your understanding.
*
* @category Inchoo
* @package GoogleConnect
* @author Marko Martinović <marko.martinovic@inchoo.net>
* @copyright Copyright (c) Inchoo (http://inchoo.net/)
* @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*/

class Inchoo_GoogleConnect_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function disconnect(Mage_Customer_Model_Customer $customer) {
        $model = Mage::getSingleton('inchoo_googleconnect/client');
        $client = $model->getClient();
        
        $client->setAccessToken($customer->getInchooGoogleconnectToken());
        $client->revokeToken();   
        
        $pictureFilename = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA)
                .DS
                .'inchoo'
                .DS
                .'googleconnect'
                .DS
                .$customer->getInchooGoogleconnectId();
        
        if(file_exists($pictureFilename)) {
            @unlink($pictureFilename);
        }
        
        $customer->setInchooGoogleconnectId(null)
        ->setInchooGoogleconnectToken(null)
        ->save();   
    }
    
    public function connectByGoogleId(
            Mage_Customer_Model_Customer $customer,
            $googleId,
            $token)
    {
        $customer->setInchooGoogleconnectId($googleId)
                ->setInchooGoogleconnectToken($token)
                ->save();
        
        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
    }
    
    public function connectByCreatingAccount(
            Mage_Customer_Model_Customer $customer,
            $email,
            $firstName,
            $lastName,
            $googleId,
            $token)
    {
            $customer->setEmail($email)
                    ->setFirstname($firstName)
                    ->setLastname($lastName)
                    ->setInchooGoogleconnectId($googleId)
                    ->setInchooGoogleconnectToken($token)
                    ->setPassword($customer->generatePassword(10))
                    ->save();

            $customer->setConfirmation(null);
            $customer->save();

            $customer->sendNewAccountEmail();
            
            Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);            
        
    }
    
    public function loginByCustomer(Mage_Customer_Model_Customer $customer)
    {
        if($customer->getConfirmation()) {
            $customer->setConfirmation(null);
            $customer->save();
        }

        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);        
    }
    
    public function getCustomersByGoogleId($googleId)
    {
        $customer = Mage::getModel('customer/customer');

        $collection = $customer->getCollection()
            ->addAttributeToFilter('inchoo_googleconnect_id', $googleId)
            ->setPageSize(1);

        if($customer->getSharingConfig()->isWebsiteScope()) {
            $collection->addAttributeToFilter(
                'website_id',
                Mage::app()->getWebsite()->getId()
            );
        }

        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            if(Mage::getSingleton('customer/session')->isLoggedIn()) {
                $collection->addFieldToFilter(
                    'entity_id',
                    array('neq' => Mage::getSingleton('customer/session')->getCustomerId())
                );
            }
        }

        return $collection;
    }
    
    public function getProperDimensionsPictureUrl($googleId, $pictureUrl)
    {
        $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
                .'inchoo'
                .'/'
                .'googleconnect'
                .'/'
                .$googleId;

        $filename = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA)
                .DS
                .'inchoo'
                .DS
                .'googleconnect'
                .DS
                .$googleId;

        $directory = dirname($filename);

        if (!file_exists($directory) || !is_dir($directory)) {
            if (!@mkdir($directory, 0777, true))
                return null;
        }

        if(!file_exists($filename) || 
                (file_exists($filename) && (time() - filemtime($filename) >= 3600))){
            $client = new Zend_Http_Client($pictureUrl);
            $client->setStream();
            $response = $client->request('GET');
            stream_copy_to_stream($response->getStream(), fopen($filename, 'w'));

            $imageObj = new Varien_Image($filename);
            $imageObj->constrainOnly(true);
            $imageObj->keepAspectRatio(true);
            $imageObj->keepFrame(false);
            $imageObj->resize(150, 150);
            $imageObj->save($filename);
        }
        
        return $url;
    }
    
}

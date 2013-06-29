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

class Inchoo_GoogleConnect_Model_Userinfo
{
    protected $userInfo = null;

    public function __construct() {
        $model = Mage::getSingleton('inchoo_googleconnect/client');
        $client = $model->getClient();
        $oauth2 = $model->getOauth2();
        
        if(!Mage::getSingleton('customer/session')->isLoggedIn())
            return;
        
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $googleconnectId = $customer->getInchooGoogleconnectId();
        $googleconnectToken = $customer->getInchooGoogleconnectToken();
        if($googleconnectId && $googleconnectToken) {
            $helper = Mage::helper('inchoo_googleconnect');

            try{
                $client->setAccessToken($googleconnectToken);
                
                $this->userInfo = $oauth2->userinfo->get();

                /* The access token may have been updated automatically due to 
                 * access type 'offline' */
                $customer->setInchooGoogleconnectToken($client->getAccessToken());
                $customer->save();           

            } catch(Google_ServiceException $e) {
                // User revoked our credentials
                $helper->disconnect($customer);
                Mage::getSingleton('core/session')
                    ->addNotice(
                        $helper->__('Permission to access your Google account 
                        has been revoked. You can restore permissions by 
                        connecting your Google account again.')
                    );
            } catch(Google_AuthException $e) {
                /* Token expired (shouldn't happen due to access type 'offline',
                 * google client refreshes token automatically) */
                $helper->disconnect($customer);
                Mage::getSingleton('core/session')->addError($e->getMessage());
            } catch(Exception $e) {
                // General exception
                $helper->disconnect($customer);
                Mage::getSingleton('core/session')->addError($e->getMessage());
            }
            
        }
    }

    public function getUserInfo()
    {
        return $this->userInfo;
    }
}

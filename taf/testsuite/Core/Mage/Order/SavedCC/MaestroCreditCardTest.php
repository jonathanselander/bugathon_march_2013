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
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    tests
 * @package     selenium
 * @subpackage  tests
 * @author      Magento Core Team <core@magentocommerce.com>
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Test admin order workflow with SavedCC payment method with Maestro card
 *
 * @package     selenium
 * @subpackage  tests
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Core_Mage_Order_SavedCC_MaestroCreditCardTest extends Mage_Selenium_TestCase
{
    protected function assertPreConditions()
    {
        $this->loginAdminUser();
    }

    protected function tearDownAfterTestClass()
    {
        $this->loginAdminUser();
        $this->navigate('system_configuration');
        $this->systemConfigurationHelper()->configure('Currency/enable_usd');
    }

    /**
     * @return array
     * @test
     */
    public function preconditionsForTests()
    {
        //Data
        $productData = $this->loadDataSet('Product', 'simple_product_visible');
        //Steps
        $this->loginAdminUser();
        $this->navigate('manage_products');
        $this->productHelper()->createProduct($productData);
        $this->assertMessagePresent('success', 'success_saved_product');
        $this->systemConfigurationHelper()->useHttps('admin', 'yes');
        $this->systemConfigurationHelper()->configure('PaymentMethod/savedcc_with_3Dsecure');
        $this->systemConfigurationHelper()->configure('Currency/enable_gbp');
        $this->systemConfigurationHelper()->configure('PaymentMethod/enable_3d_secure');

        return $productData['general_sku'];
    }

    /**
     * <p>Create Orders using Switch/Maestro card</p>
     *
     * @param string $sku
     *
     * @return array
     * @test
     * @depends preconditionsForTests
     */
    public function orderWithSwitchMaestroCard($sku)
    {
        //Data
        $paymentInfo = $this->loadDataSet('Payment', 'saved_switch_maestro');
        $paymentData = $this->loadDataSet('Payment', 'payment_savedcc', array('payment_info' => $paymentInfo));
        $orderData = $this->loadDataSet('SalesOrder', 'order_newcustomer_checkmoney_flatrate_usa',
                                        array('filter_sku'  => $sku,
                                             'payment_data' => $paymentData));
        //Steps
        $this->navigate('manage_sales_orders');
        $this->orderHelper()->createOrder($orderData);
        //Verifying
        $this->assertMessagePresent('success', 'success_created_order');

        return $orderData;
    }

    /**
     * <p>Website payments pro. Full Invoice With different types of Capture</p>
     * <p>Steps:</p>
     * <p>1.Go to Sales-Orders.</p>
     * <p>2.Press "Create New Order" button.</p>
     * <p>3.Press "Create New Customer" button.</p>
     * <p>4.Choose 'Main Store' (First from the list of radiobuttons) if exists.</p>
     * <p>5.Fill all fields.</p>
     * <p>6.Press 'Add Products' button.</p>
     * <p>7.Add first two products.</p>
     * <p>8.Choose shipping address the same as billing.</p>
     * <p>9.Check payment method 'Saved Credit Card'</p>
     * <p>10.Fill in all required fields.</p>
     * <p>11.Choose first from 'Get shipping methods and rates'.</p>
     * <p>12.Submit order.</p>
     * <p>13.Create invoice.</p>
     * <p>Expected result:</p>
     * <p>New customer is created. Order is created for the new customer. Invoice is created</p>
     *
     * @param array $orderData
     *
     * @test
     * @depends orderWithSwitchMaestroCard
     */
    public function fullInvoiceWithDifferentTypesOfCapture($orderData)
    {
        //Steps
        $this->navigate('manage_sales_orders');
        $this->orderHelper()->createOrder($orderData);
        //Verifying
        $this->assertMessagePresent('success', 'success_created_order');
        //Steps
        $this->orderInvoiceHelper()->createInvoiceAndVerifyProductQty();
    }

    /**
     * <p>Partial invoice with different types of capture</p>
     *
     * @param array $orderData
     * @param string $sku
     *
     * @test
     * @depends orderWithSwitchMaestroCard
     * @depends preconditionsForTests
     */
    public function partialInvoiceWithDifferentTypesOfCapture($orderData, $sku)
    {
        //Data
        $orderData['products_to_add']['product_1']['product_qty'] = 10;
        $invoice = $this->loadDataSet('SalesOrder', 'products_to_invoice',
                                      array('invoice_product_sku' => $sku));
        //Steps
        $this->navigate('manage_sales_orders');
        $this->orderHelper()->createOrder($orderData);
        //Verifying
        $this->assertMessagePresent('success', 'success_created_order');
        //Steps
        $this->orderInvoiceHelper()->createInvoiceAndVerifyProductQty(null, $invoice);
    }

    /**
     * <p>Saved Credit Card. Full Refund</p>
     * <p>Steps:</p>
     * <p>1.Go to Sales-Orders.</p>
     * <p>2.Press "Create New Order" button.</p>
     * <p>3.Press "Create New Customer" button.</p>
     * <p>4.Choose 'Main Store' (First from the list of radiobuttons) if exists.</p>
     * <p>5.Fill all fields.</p>
     * <p>6.Press 'Add Products' button.</p>
     * <p>7.Add first two products.</p>
     * <p>8.Choose shipping address the same as billing.</p>
     * <p>9.Check payment method 'Saved Credit Card'</p>
     * <p>10. Fill in all required fields.</p>
     * <p>11.Choose first from 'Get shipping methods and rates'.</p>
     * <p>12.Submit order.</p>
     * <p>13.Invoice order.</p>
     * <p>14.Make refund offline.</p>
     * <p>Expected result:</p>
     * <p>New customer is created. Order is created for the new customer. Refund Offline is successful</p>
     *
     * @param array $orderData
     *
     * @test
     * @depends orderWithSwitchMaestroCard
     */
    public function fullCreditMemo($orderData)
    {
        //Steps and Verifying
        $this->navigate('manage_sales_orders');
        $this->orderHelper()->createOrder($orderData);
        $this->assertMessagePresent('success', 'success_created_order');
        $this->orderInvoiceHelper()->createInvoiceAndVerifyProductQty();
        $this->orderCreditMemoHelper()->createCreditMemoAndVerifyProductQty('refund_offline');
    }

    /**
     * <p>Partial Credit Memo</p>
     *
     * @param array $orderData
     * @param string $sku
     *
     * @test
     * @depends orderWithSwitchMaestroCard
     * @depends preconditionsForTests
     */
    public function partialCreditMemo($orderData, $sku)
    {
        //Data
        $orderData['products_to_add']['product_1']['product_qty'] = 10;
        $creditMemo = $this->loadDataSet('SalesOrder', 'products_to_refund',
                                         array('return_filter_sku' => $sku));
        //Steps and Verifying
        $this->navigate('manage_sales_orders');
        $this->orderHelper()->createOrder($orderData);
        $this->assertMessagePresent('success', 'success_created_order');
        $this->orderInvoiceHelper()->createInvoiceAndVerifyProductQty();
        $this->orderCreditMemoHelper()->createCreditMemoAndVerifyProductQty('refund_offline', $creditMemo);
    }

    /**
     * <p>Shipment for order</p>
     * <p>Steps:</p>
     * <p>1.Go to Sales-Orders;</p>
     * <p>2.Press "Create New Order" button;</p>
     * <p>3.Press "Create New Customer" button;</p>
     * <p>4.Choose 'Main Store' (First from the list of radiobuttons) if exists;</p>
     * <p>5.Fill all required fields;</p>
     * <p>6.Press 'Add Products' button;</p>
     * <p>7.Add products;</p>
     * <p>8.Choose shipping address the same as billing;</p>
     * <p>9.Check payment method 'Saved Credit Card';</p>
     * <p>10.Choose any from 'Get shipping methods and rates';</p>
     * <p>11. Submit order;</p>
     * <p>12. Invoice order;</p>
     * <p>13. Ship order;</p>
     * <p>Expected result:</p>
     * <p>New customer successfully created. Order is created for the new customer;</p>
     * <p>Message "The order has been created." is displayed.</p>
     * <p>Order is invoiced and shipped successfully</p>
     *
     * @param array $orderData
     *
     * @test
     * @depends orderWithSwitchMaestroCard
     */
    public function fullShipmentForOrderWithoutInvoice($orderData)
    {
        //Steps and Verifying
        $this->navigate('manage_sales_orders');
        $this->orderHelper()->createOrder($orderData);
        $this->assertMessagePresent('success', 'success_created_order');
        $this->orderShipmentHelper()->createShipmentAndVerifyProductQty();
    }

    /**
     * <p>Holding and unholding order after creation.</p>
     * <p>Steps:</p>
     * <p>1. Navigate to "Manage Orders" page;</p>
     * <p>2. Create new order for new customer;</p>
     * <p>3. Hold order;</p>
     * <p>Expected result:</p>
     * <p>Order is holden;</p>
     * <p>4. Unhold order;</p>
     * <p>Expected result:</p>
     * <p>Order is unholden;</p>
     *
     * @param array $orderData
     *
     * @test
     * @depends orderWithSwitchMaestroCard
     */
    public function holdAndUnholdPendingOrderViaOrderPage($orderData)
    {
        //Steps and Verifying
        $this->navigate('manage_sales_orders');
        $this->orderHelper()->createOrder($orderData);
        $this->assertMessagePresent('success', 'success_created_order');
        $this->clickButton('hold');
        $this->assertMessagePresent('success', 'success_hold_order');
        $this->clickButton('unhold');
        $this->assertMessagePresent('success', 'success_unhold_order');
    }

    /**
     * <p>Cancel Pending Order From Order Page</p>
     *
     * @param array $orderData
     *
     * @test
     * @depends orderWithSwitchMaestroCard
     */
    public function cancelPendingOrderFromOrderPage($orderData)
    {
        //Steps and Verifying
        $this->navigate('manage_sales_orders');
        $this->orderHelper()->createOrder($orderData);
        $this->assertMessagePresent('success', 'success_created_order');
        $this->clickButtonAndConfirm('cancel', 'confirmation_for_cancel');
        $this->assertMessagePresent('success', 'success_canceled_order');
    }

    /**
     * @test
     * @TODO temporary fix for tearDownAfterTestClass()
     */
    public function temporaryFixTearDownAfterTestClass()
    {
    }
}
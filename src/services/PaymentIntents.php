<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\commerce\stripe\services;

use Craft;
use craft\commerce\stripe\models\PaymentIntent;
use craft\commerce\stripe\records\PaymentIntent as PaymentIntentRecord;
use craft\db\Query;
use yii\base\Component;
use yii\base\Exception;

/**
 * Customer service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class PaymentIntents extends Component
{
    /**
     * Returns a payment intent by gateway, order id and stripe plugin customer id
     *
     * @param int $gatewayId The stripe gateway
     *
     * @return PaymentIntent|null
     */
    public function getPaymentIntent(int $gatewayId, $orderId, $customerId)
    {
        $result = $this->_createIntentQuery()
            ->where(['orderId' => $orderId, 'gatewayId' => $gatewayId, 'customerId' => $customerId])
            ->one();

        if ($result !== null) {
            return new PaymentIntent($result);
        }

        return null;
    }

    /**
     * Returns a payment intent by its reference
     *
     * @param string $reference
     *
     * @return PaymentIntent|null
     */
    public function getPaymentIntentByReference(string $reference)
    {
        $result = $this->_createIntentQuery()
            ->where(['reference' => $reference])
            ->one();

        if ($result !== null) {
            return new PaymentIntent($result);
        }

        return null;
    }

    /**
     * Save a customer
     *
     * @param PaymentIntent $paymentIntent The payment intent.
     * @return bool Whether the payment source was saved successfully
     * @throws Exception if payment source not found by id.
     */
    public function savePaymentIntent(PaymentIntent $paymentIntent): bool
    {
        if ($paymentIntent->id) {
            $record = PaymentIntentRecord::findOne($paymentIntent->id);

            if (!$record) {
                throw new Exception(Craft::t('commerce-stripe', 'No customer exists with the ID “{id}”',
                    ['id' => $paymentIntent->id]));
            }
        } else {
            $record = new PaymentIntentRecord();
        }

        $record->reference = $paymentIntent->reference;
        $record->gatewayId = $paymentIntent->gatewayId;
        $record->customerId = $paymentIntent->customerId;
        $record->orderId = $paymentIntent->orderId;
        $record->intentData = $paymentIntent->intentData;

        $paymentIntent->validate();

        if (!$paymentIntent->hasErrors()) {
            // Save it!
            $record->save(false);

            // Now that we have a record ID, save it on the model
            $paymentIntent->id = $record->id;

            return true;
        }

        return false;
    }

    /**
     * Returns a Query object prepped for retrieving customers.
     *
     * @return Query The query object.
     */
    private function _createIntentQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'gatewayId',
                'customerId',
                'reference',
                'orderId',
                'intentData',
            ])
            ->from(['{{%stripe_paymentintents}}']);
    }

}

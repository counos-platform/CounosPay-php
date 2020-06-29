<?php
/**
 * Created by PhpStorm.
 * User: Mojtaba
 * Date: 12/11/2019
 * Time: 5:38 PM
 */

namespace Counos\CounosPay\Models\Response;

use Counos\CounosPay\Framework\Foundations\Model;
use DateTime;
use DateTimeZone;

/**
 * Class Order
 * @package Counos\CounosPay\Models\Response
 * @property int id
 * @property float expectedAmount
 * @property \DateTime createdAt
 * @property \DateTime updatedAt
 * @property int merchantOrderId
 * @property int paymentConfirmations
 * @property int expectedConfirmations
 * @property float paidAmount
 * @property string orderAddress
 * @property \Counos\CounosPay\Models\Ticker ticker
 * @property string terminal
 * @property string transactionId
 * @property string paymentUriQrCode
 * @property bool paid
 */
class Order extends Model
{
    /**
     * @param string $value
     * @return DateTime
     */
    public function setUpdatedAt($value)
    {
        $data = new DateTime($value);
        $data->setTimezone(new DateTimeZone('Asia/Tehran'));
        return $data;
    }

    /**
     * @param DateTime $data_time
     * @return mixed
     */
    public function getUpdatedAt($data_time)
    {
        return $data_time->format(DATE_ATOM);
    }

    /**
     * @param string $value
     * @return DateTime
     */
    public function setCreatedAt($value)
    {
        $data = new DateTime($value);
        $data->setTimezone(new DateTimeZone('Asia/Tehran'));
        return $data;
    }

    /**
     * @param DateTime $data_time
     * @return mixed
     */
    public function getCreatedAt($data_time)
    {
        return $data_time->format(DATE_ATOM);
    }
}
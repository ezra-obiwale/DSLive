<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace dsLive\Forms;

use dScribe\Form\Form;

/**
 * Description of VoguePayForm
 *
 * @author Ezra Obiwale <contact@ezraobiwale.com>
 */
class VoguePayForm extends Form {

    private $items;
    private $total;

    /**
     * Class constructor
     * 
     * @param string $merchantId Your VoguePay Merchant ID. Can be found on the top right hand side after you login
     * @param string $name Form name
     * @param array $attributes Array of attributes to add to the element tag
     */
    public function __construct($merchantId, $name = 'form',
            array $attributes = array()) {
        parent::__construct($name, $attributes);
        $this->items = 1;

        $this->setAttributes(array(
            'method' => 'post',
            'action' => 'https://voguepay.com/pay/'
        ));

        $this->add(array(
            'name' => 'v_merchant_id',
            'type' => 'hidden',
            'options' => array(
                'value' => $merchantId
            )
        ))->add(array(
            'name' => 'csrf',
            'type' => 'hidden'
        ));
    }

    /**
     * Add the transaction summary that will show on your transaction history 
     * page when you login to VoguePay
     * @param string $value
     * @param string $type The type of element to use
     * @param string $label The label to use with the element if any
     * @return \dsLive\Forms\VoguePayForm
     */
    public function addMemo($value, $type = 'hidden', $label = null) {
        $memo = array(
            'name' => 'memo',
            'type' => $type,
            'options' => array(
                'value' => $value,
                'containerAttrs' => array(
                    'class' => 'col-md-8'
                ),
            )
        );

        if ($label)
                $memo['options']['label'] = array(
                'text' => $label,
                'attrs' => array(
                    'class' => 'col-md-3',
                ),
            );

        return $this->add($memo);
    }

    /**
     * Add any value that will be returned with the confirmation results from the 
     * confirmation api. VoguePay doesnt need this value, it is used by the 
     * merchant to store any data he wishess to retrieve later with the 
     * transaction details.
     * 
     * @param string $ref
     * @return \dsLive\Forms\VoguePayForm
     */
    public function addMerchantRef($ref) {
        return $this->add(array(
                    'name' => 'merchant_ref',
                    'type' => 'hidden',
                    'options' => array(
                        'value' => $ref
                    )
        ));
    }

    /**
     * Adds an items to the form
     * @param string $item The name of the product being purchased  
     * @param string $description The short description of the product being purchased
     * @param string $price The price of the product being purchased in Nigerian naira
     * @return \dsLive\Forms\VoguePayForm
     */
    public function addItem($item, $description, $price) {
        $this->add(array(
            'name' => 'item_' . $this->items,
            'type' => 'hidden',
            'options' => array(
                'value' => $item
            )
        ))->add(array(
            'name' => 'description_' . $this->items,
            'type' => 'hidden',
            'options' => array(
                'value' => $description
            )
        ))->add(array(
            'name' => 'price_' . $this->items,
            'type' => 'hidden',
            'options' => array(
                'value' => $price
            )
        ));

        $this->total += $price;
        $this->items++;

        return $this;
    }

    /**
     * Total of all the prices (price_1 + price_2 + price_3...)
     * 
     * Adds an optional field serving as a check for the form.
     * Can be ommited. If included, will be used instead of the sum of all the prices.
     * 
     * This method should be called after all items have been added with @see addItem()
     * @return \dsLive\Forms\VoguePayForm
     */
    public function addTotal() {
        return $this->add(array(
                    'name' => 'total',
                    'type' => 'hidden',
                    'options' => array(
                        'value' => $this->total
                    )
        ));
    }

    /**
     * Indicates bill is to be repeated at a specified interval.
     * @param int $daysInterval No of days between each recurrent billing if 
     * recurrent is set to true.
     * @return \dsLive\Forms\VoguePayForm
     */
    public function isRecurrent($daysInterval) {
        return $this->add(array(
                    'name' => 'recurrent',
                    'type' => 'hidden',
                    'options' => array(
                        'value' => true
                    )
                ))->add(array(
                    'name' => 'interval',
                    'type' => 'hidden',
                    'options' => array(
                        'value' => $daysInterval,
                    )
        ));
    }

    /**
     * Adds url to send payment notification to. 
     * If set, this will be used instead of the notification url on your account.
     * @param string $url
     * @return \dsLive\Forms\VoguePayForm
     */
    public function addNotifyUrl($url) {
        return $this->addUrl('notify', $url);
    }

    /**
     * Adds url to send buyer back to if payment is successful. 
     * If set, this will be used instead of the Success Return URL on your account.
     * @param string $url
     * @return \dsLive\Forms\VoguePayForm
     */
    public function addSuccessUrl($url) {
        return $this->addUrl('success', $url);
    }

    /**
     * Adds url to send buyer back to if payment is unsuccessful. 
     * If set, this will be used instead of the Failure Return URL on your account.
     * @param string $url
     * @return \dsLive\Forms\VoguePayForm
     */
    public function addFailUrl($url) {
        return $this->addUrl('fail', $url);
    }

    private function addUrl($type, $url) {
        return $this->add(array(
                    'name' => $type . '_url',
                    'type' => 'hidden',
                    'options' => array(
                        'value' => $url,
                    )
        ));
    }

    /**
     * Adds the image to use for submit button
     * @param string $type buynow | addtocart | checkout | donate | subscribe | make_payment
     * @param string $color blue | red | green | grey
     * @return \dsLive\Forms\VoguePayForm
     */
    public function addSubmitImage($type = 'make_payment', $color = 'green') {
        return $this->add(array(
                    'name' => 'submit',
                    'type' => 'image',
                    'options' => array(
                        'containerAttrs' => array(
                            'class' => 'col-md-offset-2 col-md-8'
                        ),
                    ),
                    'attributes' => array(
                        'src' => 'http://voguepay.com/images/buttons/' . $type . '_' . $color . '.png',
                        'alt' => 'Submit'
                    )
        ));
    }

}

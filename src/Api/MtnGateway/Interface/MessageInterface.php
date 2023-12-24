<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Interface;

interface MessageInterface
{
    /**
     * @return string
     *
     * <p>You can provide an <b>array('key' => 'value')</b> in argument. <br/>
     * The <b>key</b> of that array correspond to a variable.
     * The available variables are :
     * </p><table>
     *
     * <thead>
     * <tr>
     * <th>Variable</th>
     * <th>Meaning</th>
     * </tr>
     * </thead>
     *
     * <tbody class="tbody">
     * <tr>
     * <td><b>number</b></td>
     * <td>The client phone number</td>
     * </tr>
     *
     * <tr>
     * <td><b>amount</b></td>
     * <td>The amount of transaction</td>
     * </tr>
     */
    public function getPayerMessage(): string;


    /**
     * @return string
     *
     * <p>You can provide an <b>array('key' => 'value')</b> in argument. <br/>
     * The <b>key</b> of that array correspond to a variable.
     * The available variables are :
     * </p><table>
     *
     * <thead>
     * <tr>
     * <th>Variable</th>
     * <th>Meaning</th>
     * </tr>
     * </thead>
     *
     * <tbody class="tbody">
     * <tr>
     * <td><b>number</b></td>
     * <td>The client phone number</td>
     * </tr>
     *
     * <tr>
     * <td><b>amount</b></td>
     * <td>The amount of transaction</td>
     * </tr>
     */
    public function getPayeeNote(): string;

}
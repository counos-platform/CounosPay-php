<?php
/**
 * Created by PhpStorm.
 * User: Mojtaba
 * Date: 10/21/2019
 * Time: 4:14 PM
 */

spl_autoload_register('counosPayLoader');

function counosPayLoader($name)
{
    $path = '';
    $name = str_ireplace('Counos\\CounosPay\\', 'src/', $name);
    include $path . $name . '.php';
}
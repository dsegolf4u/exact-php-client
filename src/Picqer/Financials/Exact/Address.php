<?php
/**
 * Created by PhpStorm.
 * User: didier
 * Date: 10/11/15
 * Time: 10:34
 */

namespace Picqer\Financials\Exact;


class Address extends Model
{
    protected $fillable = array(
        'Account',
        'AddressLine1',
        'AddressLine2',
        'AddressLine3',
        'City',
        'Contact',
        'Country',
        'Postcode',
        'State',
        'Type'
    );

    protected $url = 'crm/Addresses';
}
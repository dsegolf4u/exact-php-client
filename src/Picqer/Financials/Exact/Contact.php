<?php namespace Picqer\Financials\Exact;

class Contact extends Model
{

    //use Query\Findable;
    //use Persistance\Storable;

    protected $fillable = array(
        'ID',
        'Account',
        'AccountIsCustomer',
        'AccountIsSupplier',
        'AccountName',
        'AddressStreet',
        'AddressStreetNumber',
        'FirstName',
        'LastName',
        'Phone',
        'Postcode',
        'City',
        'Code',
        'Country',
        'Division',
        'Email',
        'HID',
        'AccountMainContact',
        'IsMainContact',
        'Gender',
        'Title',
        'Mobile',
        'Initials'
    );

    protected $url = 'crm/Contacts';

}


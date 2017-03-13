<?php namespace Picqer\Financials\Exact;

class VatCode extends Model
{

    //use Query\Findable;

    protected $fillable = array(
        'Account',
        'Code',
        'Country',
        'ID',
        'Description',
        'Division',
        'Percentage'
    );

    protected $url = 'vat/VATCodes';

}
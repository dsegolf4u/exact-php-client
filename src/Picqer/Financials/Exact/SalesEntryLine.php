<?php namespace Picqer\Financials\Exact;

class SalesEntryLine extends Model
{

    //use Query\Findable;
    //use Persistance\Storable;

    protected $fillable = array(
        'AmountFC',
        'Description',
        'Division',
        'ID',
        'EntryID',
        'GLAccount',
        'GLAccountCode',
        'ItemDescription',
        'AmountDC',
        'Quantity',
        'VATCode',
        'VATPercentage',
        'Notes',

    );

    protected $url = 'salesentry/SalesEntryLines';

}
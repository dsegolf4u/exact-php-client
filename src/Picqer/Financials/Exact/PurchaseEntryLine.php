<?php namespace Picqer\Financials\Exact;

class PurchaseEntryLine extends Model
{
    use Query\Findable;
    use Persistance\Storable;

    protected $fillable = array(
        'ID',
        'AmountFC',
        'Asset',
        'CostCenter',
        'CostUnit',
        'Description',
        'EntryID',
        'GLAccount',
        'Notes',
        'Project',
        'Quantity',
        'SerialNumber',
        'Subscription',
        'TrackingNumber',
        'VATAmountFC',
        'VATBaseAmountFC',
        'VATCode',
        'VATPercentage',
    );

    protected $url = 'purchaseentry/PurchaseEntryLines';

}

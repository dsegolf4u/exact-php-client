<?php namespace Picqer\Financials\Exact;

class Journal extends Model
{

    //use Query\Findable;
    //use Persistance\Storable;

    protected $fillable = array(
        'Code',
        'Division',
        'Description',
        'ID',
        'Type',
    );

    protected $url = 'financial/Journals';

}

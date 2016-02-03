<?php
/**
 * Created by PhpStorm.
 * User: didier
 * Date: 03/02/16
 * Time: 14:43
 */

namespace Picqer\Financials\Exact;


class BankAccount extends Model
{
    protected $fillable = array(
        'ID',
        'Account',
        'BankAccount',
        'BankAccountHolderName',
        'BICCode',
        'Description'
    );

    protected $url = 'crm/BankAccounts';
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BranchModel;

class Sale extends Model
{
    //
     use SoftDeletes;
    
    protected $fillable = [
        'invoice_number', 'user_id', 'customer_id', 'subtotal',
        'discount', 'tax', 'total', 'payment_method', 'amount_paid',
        'change_amount', 'status', 'notes', 'sale_date', 'email_sent', 'branch_id'
    ];
    
    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'sale_date' => 'datetime',
        'email_sent' => 'boolean',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function details()
    {
        return $this->hasMany(SaleDetail::class);
    }
    public function branch()
    {
        return $this->belongsTo(BranchModel::class);
    }
    // Generar número de factura automático
    public static function generateInvoiceNumber()
    {
        $lastSale = self::orderBy('id', 'desc')->first();
        $number = $lastSale ? intval(substr($lastSale->invoice_number, 3)) + 1 : 1;
        return 'FAC' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

}

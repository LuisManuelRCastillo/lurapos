<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Models\Credit;
use App\Mail\SaleReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SaleService
{
    /**
     * Procesar una venta completa
     * 
     * @param array $data
     * @return Sale
     */
    public function processSale(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Crear venta
            $sale = Sale::create([
                'invoice_number' => Sale::generateInvoiceNumber(),
                'user_id' => auth()->id() ?? 1, // Por defecto usuario 1 si no hay auth
                'customer_id' => $data['customer_id'] ?? null,
                'subtotal' => $data['subtotal'],
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'total' => $data['total'],
                'payment_method' => $data['payment_method'],
                'amount_paid' => $data['amount_paid'],
                'change_amount' => $data['change_amount'] ?? 0,
                'status' => 'completada',
                'notes' => $data['notes'] ?? null,
                'sale_date' => now(),
                'branch_id' => $data['branch_id'] ?? null,
            ]);
            
            // Procesar detalles
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Verificar stock
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuficiente para {$product->name}. Disponible: {$product->stock}");
                }
                
                // Crear detalle de venta
                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => $item['total'],
                ]);
                
                // Actualizar stock (columna real en rodcas es 'existencia')
                $stockBefore = $product->stock;
                $product->decrement('existencia', $item['quantity']);
                $product->refresh();
                
                // Registrar movimiento de inventario
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id() ?? 1,
                    'type' => 'venta',
                    'quantity' => -$item['quantity'], // Negativo porque es salida
                    'stock_before' => $stockBefore,
                    'stock_after' => $product->stock,
                    'reference' => $sale->invoice_number,
                    'notes' => 'Venta registrada',
                    'movement_date' => now(),
                ]);
            }
            
            // Si el pago es a crédito, crear registro de crédito pendiente
            if ($data['payment_method'] === 'credito') {
                Credit::create([
                    'sale_id'         => $sale->id,
                    'customer_id'     => $data['customer_id'] ?? null,
                    'customer_name'   => $data['customer_name'] ?? 'Cliente',
                    'original_amount' => $data['total'],
                    'paid_amount'     => 0,
                    'status'          => 'pendiente',
                ]);
            }

            DB::commit();

            return $sale;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error procesando venta: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Enviar comprobante por email
     * 
     * @param Sale $sale
     * @return bool
     */
    public function sendReceipt(Sale $sale)
    {
        try {
            if (!$sale->customer || !$sale->customer->email) {
                return false;
            }
            
            Mail::to($sale->customer->email)->send(new SaleReceipt($sale));
            
            $sale->update(['email_sent' => true]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error enviando comprobante: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancelar una venta y restaurar el inventario
     * 
     * @param Sale $sale
     * @return bool
     */
    public function cancelSale(Sale $sale)
    {
        DB::beginTransaction();
        
        try {
            if ($sale->status === 'cancelada') {
                throw new \Exception('La venta ya está cancelada');
            }
            
            // Restaurar stock de cada producto
            foreach ($sale->details as $detail) {
                $product = $detail->product;
                
                if (!$product) {
                    continue; // Si el producto fue eliminado, continuar
                }
                
                $stockBefore = $product->stock;
                $product->increment('existencia', $detail->quantity);
                $product->refresh();
                
                // Registrar movimiento de devolución
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id() ?? 1,
                    'type' => 'devolucion',
                    'quantity' => $detail->quantity, // Positivo porque es entrada
                    'stock_before' => $stockBefore,
                    'stock_after' => $product->stock,
                    'reference' => $sale->invoice_number,
                    'notes' => 'Venta cancelada - Devolución de inventario',
                    'movement_date' => now(),
                ]);
            }
            
            // Actualizar estado de la venta
            $sale->update(['status' => 'cancelada']);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelando venta: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener resumen de ventas por período
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getSalesSummary($dateFrom, $dateTo)
    {
        $sales = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->where('status', 'completada')
            ->get();
        
        return [
            'total_sales' => $sales->sum('total'),
            'total_transactions' => $sales->count(),
            'average_ticket' => $sales->avg('total'),
            'sales_by_method' => $sales->groupBy('payment_method')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('total')
                ];
            }),
        ];
    }
    
    /**
     * Verificar y alertar sobre productos con stock bajo
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getLowStockProducts()
    {
        return Product::whereRaw('existencia <= inv_min')
            ->where('active', true)
            ->with('category')
            ->get();
    }
}
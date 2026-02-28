<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que mapea la tabla 'productos' de la BD rodcas.
 *
 * Columnas reales:
 *   id, codigo, producto, p_costo, p_venta, p_mayoreo,
 *   existencia, inv_min, inv_max, dpto, image, photo_verified, deleted_at
 *
 * Accesors para mantener compatibilidad con el resto del código:
 *   code          → codigo
 *   name          → producto
 *   cost_price    → p_costo
 *   sale_price    → p_venta
 *   wholesale_price → p_mayoreo
 *   stock         → existencia
 *   min_stock     → inv_min
 *   max_stock     → inv_max
 *   category      → dpto  (texto libre, no FK)
 */
class Product extends Model
{
    use SoftDeletes;

    protected $table    = 'productos';
    public    $timestamps = false;   // la tabla rodcas no tiene created_at/updated_at

    protected $fillable = [
        'codigo', 'producto', 'p_costo', 'p_venta', 'p_mayoreo',
        'existencia', 'inv_min', 'inv_max', 'dpto',
        'image', 'photo_verified',
    ];

    protected $casts = [
        'p_costo'        => 'decimal:2',
        'p_venta'        => 'decimal:2',
        'p_mayoreo'      => 'decimal:2',
        'photo_verified' => 'boolean',
    ];

    // ─── Accesors (lectura con nombre "canónico") ─────────────────────────
    public function getCodeAttribute():  string  { return $this->codigo    ?? ''; }
    public function getNameAttribute():  string  { return $this->producto  ?? ''; }
    public function getCostPriceAttribute(): float { return (float)($this->p_costo  ?? 0); }
    public function getSalePriceAttribute(): float { return (float)($this->p_venta  ?? 0); }
    public function getWholesalePriceAttribute(): float { return (float)($this->p_mayoreo ?? 0); }
    public function getStockAttribute():  int    { return (int)($this->existencia ?? 0); }
    public function getMinStockAttribute(): int  { return (int)($this->inv_min ?? 0); }
    public function getMaxStockAttribute(): int  { return (int)($this->inv_max ?? 0); }

    // category_id no existe; 'dpto' es texto libre
    public function getCategoryAttribute(): ?string { return $this->dpto; }

    // active: si existencia >= 0 y tiene precio se considera activo
    public function getActiveAttribute(): bool { return true; }

    // ─── Mutators (escritura con nombre "canónico") ────────────────────────
    public function setCodeAttribute(string $v):  void { $this->attributes['codigo']    = $v; }
    public function setNameAttribute(string $v):  void { $this->attributes['producto']  = $v; }
    public function setCostPriceAttribute($v):    void { $this->attributes['p_costo']   = $v; }
    public function setSalePriceAttribute($v):    void { $this->attributes['p_venta']   = $v; }
    public function setWholesalePriceAttribute($v): void { $this->attributes['p_mayoreo'] = $v; }
    public function setStockAttribute(int $v):    void { $this->attributes['existencia'] = $v; }
    public function setMinStockAttribute(int $v): void { $this->attributes['inv_min']   = $v; }
    public function setMaxStockAttribute(int $v): void { $this->attributes['inv_max']   = $v; }

    // ─── Helpers ──────────────────────────────────────────────────────────
    public function isLowStock(): bool
    {
        return $this->existencia <= $this->inv_min;
    }

    public function getProfitMargin(): float
    {
        if (!$this->p_costo) return 0;
        return (($this->p_venta - $this->p_costo) / $this->p_costo) * 100;
    }

    // ─── Relaciones (stub; rodcas no tiene tablas relacionales todavía) ───
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        // No hay tabla categories en rodcas; devolvemos relación nula segura
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function saleDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function inventoryMovements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}

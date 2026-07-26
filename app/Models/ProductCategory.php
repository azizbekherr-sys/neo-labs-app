<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $fillable = ['slug', 'name_uz', 'name_ru', 'name_en'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function localizedName(?string $locale = null): string
    {
        $locale = in_array($locale ?: app()->getLocale(), ['uz', 'ru', 'en'], true)
            ? ($locale ?: app()->getLocale())
            : 'uz';

        return (string) ($this->{'name_' . $locale} ?: $this->name_uz ?: $this->name_ru ?: $this->name_en);
    }
}

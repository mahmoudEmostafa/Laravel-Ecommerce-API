<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model
{
    use Sluggable;


    protected $fillable = [
    'name',
    'description',
    'price',
    'stock',
    'category_id',
    'is_active',
    'image'
];



    public function sluggable(): array
   {
    return [
        'slug' => [
            'source' => 'name'
        ]
    ];
  }


  public function category()
{
    return $this->belongsTo(Category::class);
}
}

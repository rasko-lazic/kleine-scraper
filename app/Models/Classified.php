<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $title
 * @property string $description
 * @property string $condition
 * @property string|DateTime $date
 * @property float $price
 * @property string $full_price
 * @property string $full_address
 * @property string $zipcode
 * @property string $city
 * @property string $seller
 * @property string $seller_name
 * @property boolean $in_english
 * @property boolean $negotiable
 * @property boolean $top_promotion
 * @property boolean $shipping_possible
 * @property boolean $buy_directly
 * @property boolean $flagged
 * @property string $url
 */
class Classified extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'condition',
        'date',
        'price',
        'full_price',
        'full_address',
        'zipcode',
        'city',
        'seller',
        'seller_name',
        'in_english',
        'negotiable',
        'top_promotion',
        'shipping_possible',
        'flagged',
        'url',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'updated_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}

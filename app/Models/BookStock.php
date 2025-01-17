<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

/**
 * @property int             $id
 * @property int             $book_id
 * @property CarbonImmutable $created_at
 * @property Book            $book
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookStock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookStock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookStock query()
 *
 * @mixin \Eloquent
 */
class BookStock extends Model
{
    public const null UPDATED_AT = null;

    protected $casts = [
        'id' => 'int',
        'book_id' => 'int',
        'created_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<Book>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\BookStock;
use Illuminate\Http\Exceptions\HttpResponseException;
use Validator;

class BookStockController
{
    //
    public function count(Book $book): array
    {
        // Book を一度取得しているのは，NotFound を出すため
        $count = BookStock::query()->where('book_id', $book->id)->count();
    
        return [
            'book_id' => $book->id,
            'quantity' => $count,
        ];
    }

    /**
     * @throws ValidationException
     * @throws \Throwable
     */
    public function adjustStock(Book $book, Request $request): Routing
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'quantity_change' => ['required', 'integer']
        ]);
        $validator->validate();

        // 増減数
        $quantity = $request->integer('quantity_change');

        return DB::transaction(function () use ($book, $quantity): Routing  {
            if ($quantity >= 0) {
                // 増減数が正の数ならその分だけレコードを追加

                BookStock::query()
                    ->insert(array_fill(0, $quantity,[
                        'book_id' => $book->id,
                        'created_at' => now(),
                    ]));
            } else {
                // 増減数が負の数ならその分だけレコードを削除
                // 減少数を正の数に変換
                $abs = abs($quantity);

                // 削除分レコードを取ってきて行ロック
                $stocks = BookStock::query()
                    ->where('book_id', $book->id)
                    ->orderBy('id')
                    ->limit($abs)
                    ->lockForUpdate()
                    ->get();

                // 在庫が不十分であればエラー
                if ($stocks->count() < $abs) {
                    throw new HttpResponseException(
                        response()->json(['message' => '在庫が足りません'], 400)
                    );
                }

                // 対象を全削除
                BookStock::query()
                    ->whereIn('id', $stocks->pluck('id'))
                    ->delete();
            }

            return response()->noContent();
        });
    }
}

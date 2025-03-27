<?php

declare(strict_types=1);

namespace App\Http\Controllers\Book;

use App\Models\Book;
use App\Service\Book\OpenBD\Fetcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * 専用 Client（実体）を DI するパターン.
 */
readonly class CreateControllerE
{
    public function __construct(
        private Fetcher $fetcher,
    ) {}

    /**
     * @throws ValidationException
     */
    public function __invoke(Request $request): Book
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'isbn' => ['required', 'string'],
        ]);
        $validator->validate();

        // HTTP リクエストを送信して JSON を得る
        $details = $this->fetcher->fetch($request->string('isbn')->value());

        return Book::create([
            'isbn' => $details->isbn,
            // title は必須なので仮タイトルを入れておいてあとから手動更新する想定
            'title' => $details->title ?: '仮タイトル',
            'publisher' => $details->publisher,
            'publish_date' => $details->publishDate,
            'authors' => $details->authors,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Validator;

class BookController
{
    //
    public function create(Request $request): Book
    {
        $validator = Validator::make($request->all(),[
            'title' => ['required', 'string'],
            'isbn' => ['required', 'string']
        ]);
        $validator->validate();

        $book = new Book($validator->validated());
        $book->save();

        return $book;
    }

    public function index(): Collection
    {
        return Book::all();
    }

    public function update(Book $book, Request $request): Book
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string'],
            'isbn' => ['required', 'string'],
        ]);
        $validator->validate();
        // 埋めて更新
        $book->fill($validator->validated());
        $book->save();

        return $book;
    }

}

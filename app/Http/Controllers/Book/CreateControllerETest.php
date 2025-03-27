<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Book;

use App\Models\Book;
use App\Service\Book\BookDetail;
use App\Service\Book\OpenBD\Fetcher;
use Brick\DateTime\LocalDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateControllerETest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 書籍情報を取得して保存できる(): void
    {
        // BookDetailオブジェクトを作成
        $bookDetail = new BookDetail(
            isbn: '9784000000000',
            title: 'テスト書籍',
            publisher: 'テスト出版社',
            publishDate: LocalDate::parse('2022-01-01'),
            authors: ['テスト著者1', 'テスト著者2']
        );

        // Fetcherをモック
        $fetcherMock = Mockery::mock(Fetcher::class);
        $fetcherMock->shouldReceive('fetch')
            ->with('9784000000000')
            ->once()
            ->andReturn($bookDetail);

        // モックをコンテナにバインド
        $this->app->instance(Fetcher::class, $fetcherMock);

        $response = $this->postJson('/api/books/create-e', [
            'isbn' => '9784000000000',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'isbn' => '9784000000000',
            'title' => 'テスト書籍',
            'publisher' => 'テスト出版社',
            'publish_date' => '2022-01-01',
        ]);

        $book = Book::query()->where('isbn', '9784000000000')->first();
        assert($book instanceof Book);
        $this->assertSame(['テスト著者1', 'テスト著者2'], $book->authors);
    }

    #[Test]
    public function タイトルがnullの場合に仮タイトルが設定される(): void
    {
        // タイトルがnullのBookDetailオブジェクトを作成
        $bookDetail = new BookDetail(
            isbn: '9784000000000',
            title: null, // タイトルがnull
            publisher: 'テスト出版社',
            publishDate: LocalDate::parse('2022-01-01'),
            authors: ['テスト著者1']
        );

        // Fetcherをモック
        $fetcherMock = Mockery::mock(Fetcher::class);
        $fetcherMock->shouldReceive('fetch')
            ->with('9784000000000')
            ->once()
            ->andReturn($bookDetail);

        // モックをコンテナにバインド
        $this->app->instance(Fetcher::class, $fetcherMock);

        $response = $this->postJson('/api/books/create-e', [
            'isbn' => '9784000000000',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'isbn' => '9784000000000',
            'title' => '仮タイトル', // 仮タイトルが設定される
            'publisher' => 'テスト出版社',
            'publish_date' => '2022-01-01',
        ]);
    }

    #[Test]
    public function タイトルが空文字の場合に仮タイトルが設定される(): void
    {
        // タイトルが空文字のBookDetailオブジェクトを作成
        $bookDetail = new BookDetail(
            isbn: '9784000000000',
            title: '', // タイトルが空文字
            publisher: 'テスト出版社',
            publishDate: LocalDate::parse('2022-01-01'),
            authors: ['テスト著者1']
        );

        // Fetcherをモック
        $fetcherMock = Mockery::mock(Fetcher::class);
        $fetcherMock->shouldReceive('fetch')
            ->with('9784000000000')
            ->once()
            ->andReturn($bookDetail);

        // モックをコンテナにバインド
        $this->app->instance(Fetcher::class, $fetcherMock);

        $response = $this->postJson('/api/books/create-e', [
            'isbn' => '9784000000000',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'isbn' => '9784000000000',
            'title' => '仮タイトル', // 仮タイトルが設定される
            'publisher' => 'テスト出版社',
            'publish_date' => '2022-01-01',
        ]);
    }

    #[Test]
    public function 出版社情報がnullの場合にnullが設定される(): void
    {
        // 出版社がnullのBookDetailオブジェクトを作成
        $bookDetail = new BookDetail(
            isbn: '9784000000000',
            title: 'テスト書籍',
            publisher: null, // 出版社がnull
            publishDate: LocalDate::parse('2022-01-01'),
            authors: ['テスト著者1']
        );

        // Fetcherをモック
        $fetcherMock = Mockery::mock(Fetcher::class);
        $fetcherMock->shouldReceive('fetch')
            ->with('9784000000000')
            ->once()
            ->andReturn($bookDetail);

        // モックをコンテナにバインド
        $this->app->instance(Fetcher::class, $fetcherMock);

        $response = $this->postJson('/api/books/create-e', [
            'isbn' => '9784000000000',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'isbn' => '9784000000000',
            'title' => 'テスト書籍',
            'publisher' => null, // nullが設定される
            'publish_date' => '2022-01-01',
        ]);
    }

    #[Test]
    public function 出版日がnullの場合にnullが設定される(): void
    {
        // 出版日がnullのBookDetailオブジェクトを作成
        $bookDetail = new BookDetail(
            isbn: '9784000000000',
            title: 'テスト書籍',
            publisher: 'テスト出版社',
            publishDate: null, // 出版日がnull
            authors: ['テスト著者1']
        );

        // Fetcherをモック
        $fetcherMock = Mockery::mock(Fetcher::class);
        $fetcherMock->shouldReceive('fetch')
            ->with('9784000000000')
            ->once()
            ->andReturn($bookDetail);

        // モックをコンテナにバインド
        $this->app->instance(Fetcher::class, $fetcherMock);

        $response = $this->postJson('/api/books/create-e', [
            'isbn' => '9784000000000',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'isbn' => '9784000000000',
            'title' => 'テスト書籍',
            'publisher' => 'テスト出版社',
            'publish_date' => null, // nullが設定される
        ]);
    }

    #[Test]
    public function 著者情報が空配列の場合に空配列が設定される(): void
    {
        // 著者が空配列のBookDetailオブジェクトを作成
        $bookDetail = new BookDetail(
            isbn: '9784000000000',
            title: 'テスト書籍',
            publisher: 'テスト出版社',
            publishDate: LocalDate::parse('2022-01-01'),
            authors: [] // 著者が空配列
        );

        // Fetcherをモック
        $fetcherMock = Mockery::mock(Fetcher::class);
        $fetcherMock->shouldReceive('fetch')
            ->with('9784000000000')
            ->once()
            ->andReturn($bookDetail);

        // モックをコンテナにバインド
        $this->app->instance(Fetcher::class, $fetcherMock);

        $response = $this->postJson('/api/books/create-e', [
            'isbn' => '9784000000000',
        ]);

        $response->assertStatus(201);

        $book = Book::query()->where('isbn', '9784000000000')->first();
        assert($book instanceof Book);
        $this->assertSame([], $book->authors);
    }

    #[Test]
    public function isbnが未入力の場合はバリデーションエラーを返す(): void
    {
        $response = $this->postJson('/api/books/create-e', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);

        $this->assertDatabaseCount('books', 0);
    }
}

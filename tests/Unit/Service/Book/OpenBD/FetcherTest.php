<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Book\OpenBD;

use App\Service\Book\BookDetail;
use App\Service\Book\OpenBD\DateParser;
use App\Service\Book\OpenBD\Fetcher;
use Brick\DateTime\LocalDate;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FetcherTest extends TestCase
{
    private DateParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DateParser();
    }

    #[Test]
    public function 完全なデータが返ってきた場合に正しく解析できる(): void
    {
        // 完全なデータを含むレスポンスを準備
        $mockResponseData = [
            [
                'onix' => [
                    'DescriptiveDetail' => [
                        'TitleDetail' => [
                            'TitleElement' => [
                                'TitleText' => [
                                    'content' => 'テスト書籍',
                                ],
                            ],
                        ],
                        'Contributor' => [
                            [
                                'PersonName' => [
                                    'content' => '著者1',
                                ],
                            ],
                            [
                                'PersonName' => [
                                    'content' => '著者2',
                                ],
                            ],
                        ],
                    ],
                    'PublishingDetail' => [
                        'Imprint' => [
                            'ImprintName' => 'テスト出版社',
                        ],
                        'PublishingDate' => [
                            [
                                'PublishingDateRole' => '01',
                                'Date' => '20220101',
                            ],
                        ],
                    ],
                ],
                'summary' => [
                    'pubdate' => '2022-01-01',
                ],
            ],
        ];

        $client = $this->createClientMock(json_encode($mockResponseData));
        $fetcher = new Fetcher($client, $this->parser);

        $result = $fetcher->fetch('9784000000000');

        $this->assertInstanceOf(BookDetail::class, $result);
        $this->assertSame('9784000000000', $result->isbn);
        $this->assertSame('テスト書籍', $result->title);
        $this->assertSame('テスト出版社', $result->publisher);
        $this->assertTrue(LocalDate::parse('2022-01-01')->isEqualTo($result->publishDate));
        $this->assertSame(['著者1', '著者2'], $result->authors);
    }

    #[Test]
    public function 出版日がPublishingDateにない場合にsummaryPubdateから取得できる(): void
    {
        $mockResponseData = [
            [
                'onix' => [
                    'DescriptiveDetail' => [
                        'TitleDetail' => [
                            'TitleElement' => [
                                'TitleText' => [
                                    'content' => 'テスト書籍',
                                ],
                            ],
                        ],
                    ],
                    'PublishingDetail' => [
                        'Imprint' => [
                            'ImprintName' => 'テスト出版社',
                        ],
                        // PublishingDateがない
                    ],
                ],
                'summary' => [
                    'pubdate' => '2022-01-01', // summaryから日付を取得
                ],
            ],
        ];

        $client = $this->createClientMock(json_encode($mockResponseData));
        $fetcher = new Fetcher($client, $this->parser);

        $result = $fetcher->fetch('9784000000000');

        $this->assertTrue(LocalDate::parse('2022-01-01')->isEqualTo($result->publishDate));
    }

    #[Test]
    public function 出版日が両方にない場合はnullが設定される(): void
    {
        $mockResponseData = [
            [
                'onix' => [
                    'DescriptiveDetail' => [
                        'TitleDetail' => [
                            'TitleElement' => [
                                'TitleText' => [
                                    'content' => 'テスト書籍',
                                ],
                            ],
                        ],
                    ],
                    'PublishingDetail' => [
                        'Imprint' => [
                            'ImprintName' => 'テスト出版社',
                        ],
                        // PublishingDateがない
                    ],
                ],
                'summary' => [
                    // pubdateもない
                ],
            ],
        ];

        $client = $this->createClientMock(json_encode($mockResponseData));
        $fetcher = new Fetcher($client, $this->parser);

        $result = $fetcher->fetch('9784000000000');

        $this->assertNull($result->publishDate);
    }

    #[Test]
    public function タイトルがない場合にnullが設定される(): void
    {
        $mockResponseData = [
            [
                'onix' => [
                    'DescriptiveDetail' => [
                        // TitleDetailがない
                    ],
                    'PublishingDetail' => [
                        'Imprint' => [
                            'ImprintName' => 'テスト出版社',
                        ],
                    ],
                ],
            ],
        ];

        $client = $this->createClientMock(json_encode($mockResponseData));
        $fetcher = new Fetcher($client, $this->parser);

        $result = $fetcher->fetch('9784000000000');

        $this->assertNull($result->title);
    }

    #[Test]
    public function 出版社情報がない場合にnullが設定される(): void
    {
        $mockResponseData = [
            [
                'onix' => [
                    'DescriptiveDetail' => [
                        'TitleDetail' => [
                            'TitleElement' => [
                                'TitleText' => [
                                    'content' => 'テスト書籍',
                                ],
                            ],
                        ],
                    ],
                    'PublishingDetail' => [
                        // Imprintがない
                    ],
                ],
            ],
        ];

        $client = $this->createClientMock(json_encode($mockResponseData));
        $fetcher = new Fetcher($client, $this->parser);

        $result = $fetcher->fetch('9784000000000');

        $this->assertNull($result->publisher);
    }

    #[Test]
    public function 著者情報がない場合に空配列が設定される(): void
    {
        $mockResponseData = [
            [
                'onix' => [
                    'DescriptiveDetail' => [
                        'TitleDetail' => [
                            'TitleElement' => [
                                'TitleText' => [
                                    'content' => 'テスト書籍',
                                ],
                            ],
                        ],
                        // Contributorがない
                    ],
                ],
            ],
        ];

        $client = $this->createClientMock(json_encode($mockResponseData));
        $fetcher = new Fetcher($client, $this->parser);

        $result = $fetcher->fetch('9784000000000');

        $this->assertIsArray($result->authors);
        $this->assertEmpty($result->authors);
    }

    #[Test]
    public function 著者情報に空文字やnullが含まれる場合に適切にフィルタリングされる(): void
    {
        $mockResponseData = [
            [
                'onix' => [
                    'DescriptiveDetail' => [
                        'TitleDetail' => [
                            'TitleElement' => [
                                'TitleText' => [
                                    'content' => 'テスト書籍',
                                ],
                            ],
                        ],
                        'Contributor' => [
                            [
                                'PersonName' => [
                                    'content' => '著者1',
                                ],
                            ],
                            [
                                'PersonName' => [
                                    'content' => '', // 空文字
                                ],
                            ],
                            [
                                'PersonName' => [
                                    // contentがない
                                ],
                            ],
                            [
                                'PersonName' => [
                                    'content' => '著者2',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client = $this->createClientMock(json_encode($mockResponseData));
        $fetcher = new Fetcher($client, $this->parser);

        $result = $fetcher->fetch('9784000000000');

        $this->assertSame(['著者1', '著者2'], $result->authors);
    }

    #[Test]
    public function 書籍情報が取得できない場合はnullを返す(): void
    {
        $mockResponseData = [null]; // APIがnullを返す場合

        $client = $this->createClientMock(json_encode($mockResponseData));
        $fetcher = new Fetcher($client, $this->parser);

        $result = $fetcher->fetch('9784000000000');

        $this->assertNull($result);
    }

    /**
     * モックレスポンスを返すクライアントを作成.
     */
    private function createClientMock(string $responseBody): Client
    {
        $mock = new MockHandler([
            new Response(200, [], $responseBody),
        ]);

        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack]);
    }
}

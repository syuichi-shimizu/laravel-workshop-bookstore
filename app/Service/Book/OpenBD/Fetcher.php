<?php

declare(strict_types=1);

namespace App\Service\Book\OpenBD;

use App\Service\Book\BookDetail;
use App\Service\Book\BookServiceException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

class Fetcher
{
    public const string BASE_URL = 'https://api.openbd.jp/v1/get';

    public function __construct(
        private readonly Client $client,
        private readonly DateParser $parser,
    ) {}

    public function fetch(string $isbn): ?BookDetail
    {
        try {
            // HTTP リクエストを送信して JSON を得る
            $url = self::BASE_URL.'?'.http_build_query(['isbn' => $isbn]);
            $response = $this->client
                ->get($url)
                ->getBody()
                ->getContents();
        } catch (GuzzleException $e) {
            throw new BookServiceException('API リクエストに失敗しました', previous: $e);
        }

        $json = json_decode($response, true)[0];

        // 書籍情報が取得できない場合
        if (null === $json) {
            return null;
        }

        $descriptiveDetail = $json['onix']['DescriptiveDetail'] ?? [];
        $publishingDetail = $json['onix']['PublishingDetail'] ?? [];

        $authors = array_map(
            fn (array $contributor) => $contributor['PersonName']['content'] ?? '',
            $descriptiveDetail['Contributor'] ?? [],[]
        );

        // まずは PublishingDate から探す
        /** @var Collection<int, array{PublishingDateRole: "01"|"02"|"09"|"25", Date: string}> $pubDates */
        $pubDates = Collection::make($publishingDetail['PublishingDate'] ?? [])
            ->filter(fn (array $d) => $d['PublishingDateRole'] ?? '' === '01');

        $pubDate = $pubDates->isNotEmpty()
            ? $this->parser->parse($pubDates->firstOrFail()['Date'])
            : $this->parser->parse($json['summary']['pubdate'] ?? '');

        return new BookDetail(
            isbn: $isbn,
            title: $descriptiveDetail['TitleDetail']['TitleElement']['TitleText']['content'] ?? null,
            publisher: $publishingDetail['Imprint']['ImprintName'] ?? null,
            publishDate: $pubDate,
            // null や空文字列を削除して index 振り直す
            authors: array_values(array_filter($authors)),
        );
    }
}

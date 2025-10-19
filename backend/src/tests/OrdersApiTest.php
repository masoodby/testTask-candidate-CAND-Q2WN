<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class OrdersApiTest extends TestCase
{
    private function httpGet(string $url): array {
        $json = file_get_contents($url);
        $this->assertNotFalse($json, "GET failed for $url");
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        return $data;
    }

    private function httpPatchJson(string $url, array $payload): array {
        $opts = [
            'http' => [
                'method' => 'PATCH',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($payload),
                'ignore_errors' => true,
            ]
        ];
        $ctx = stream_context_create($opts);
        $json = file_get_contents($url, false, $ctx);
        $this->assertNotFalse($json);
        return json_decode($json, true);
    }

    public function testOrdersIncludeNoteField(): void {
        $data = $this->httpGet(API . "/api/orders?user_id=1&per_page=5");
        $this->assertArrayHasKey('data', $data);
        if (!empty($data['data'])) {
            $row = $data['data'][0];
            $this->assertArrayHasKey('note', $row);
            $this->assertArrayHasKey('payment', $row);
        }
    }

    public function testPatchNoteUpdatesSuccessfully(): void {
        $list = $this->httpGet(API . "/api/orders?user_id=1&per_page=10");
        if (empty($list['data'])) {
            $this->markTestSkipped("No data to test note update");
        }
        $id = $list['data'][0]['id'];
        $newNote = 'phpunit-note-' . time();

        $res = $this->httpPatchJson(API . "/api/orders/$id/note", ['note' => $newNote]);
        $this->assertTrue($res['ok'] ?? false);

        $check = $this->httpGet(API . "/api/orders?user_id=1&per_page=10&_cb=" . time());
        $found = null;
        foreach ($check['data'] as $r) {
            if ($r['id'] == $id) $found = $r;
        }
        $this->assertSame($newNote, $found['note'] ?? null);
    }
}
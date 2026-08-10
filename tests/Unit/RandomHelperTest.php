<?php

use App\Utils\RandomHelper;

test('RandText tạo đúng độ dài và chỉ chứa chữ hoa chữ thường', function () {
    $value = RandomHelper::RandText(128);

    expect($value)
        ->toHaveLength(128)
        ->toMatch('/\A[A-Za-z]+\z/');
});

test('RandAll tạo đúng độ dài và chỉ chứa chữ hoa chữ thường chữ số', function () {
    $value = RandomHelper::RandAll(128);

    expect($value)
        ->toHaveLength(128)
        ->toMatch('/\A[A-Za-z0-9]+\z/');
});

test('trả chuỗi rỗng khi độ dài bằng không', function () {
    expect(RandomHelper::RandText(0))->toBe('')
        ->and(RandomHelper::RandAll(0))->toBe('');
});

test('từ chối độ dài âm', function (string $method) {
    expect(fn () => RandomHelper::$method(-1))
        ->toThrow(InvalidArgumentException::class, 'Độ dài chuỗi ngẫu nhiên không được nhỏ hơn 0.');
})->with(['RandText', 'RandAll']);

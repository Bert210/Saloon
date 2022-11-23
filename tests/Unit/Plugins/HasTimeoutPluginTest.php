<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Managers\RequestManager;
use Saloon\Http\Faking\MockResponse;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use Saloon\Tests\Fixtures\Requests\UserRequest;
use Saloon\Tests\Fixtures\Requests\TimeoutRequest;
use Saloon\Tests\Fixtures\Connectors\TimeoutConnector;

test('a request is given a default timeout and connect timeout', function () {
    $request = UserRequest::make();

    $request->sender()->addMiddleware(function (callable $handler) {
        return function (RequestInterface $request, array $options) use ($handler) {
            expect($options['connect_timeout'])->toEqual(10);
            expect($options['timeout'])->toEqual(30);

            return new FulfilledPromise(MockResponse::make()->getPsrResponse());
        };
    }, 'test');

    $request->send();
});

test('a request can set a timeout and connect timeout', function () {
    $requestManager = new RequestManager(new TimeoutRequest);
    $requestManager->hydrate();

    $config = $requestManager->getConfig();

    expect($config)->toHaveKey('connect_timeout', 1);
    expect($config)->toHaveKey('timeout', 2);
});

test('a connector is given a default timeout and connect timeout', function () {
    $mockClient = new MockClient([new MockResponse()]);

    $request = (new UserRequest)->setConnector(new TimeoutConnector);

    $request->addHandler('test', function (callable $handler) {
        return function (RequestInterface $request, array $options) use ($handler) {
            expect($options['connect_timeout'])->toEqual(10.0);
            expect($options['timeout'])->toEqual(5.0);

            return $handler($request, $options);
        };
    });

    $requestManager = $request->getRequestManager();
    $requestManager->hydrate();

    $config = $requestManager->getConfig();

    expect($config)->toHaveKey('connect_timeout', 10);
    expect($config)->toHaveKey('timeout', 5);

    $request->send($mockClient);
});

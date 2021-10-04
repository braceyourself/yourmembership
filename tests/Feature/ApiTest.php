<?php

use Braceyourself\Yourmembership\Clients\Client;
use Braceyourself\Yourmembership\Models\Person;
use Braceyourself\Yourmembership\Models\Registration;
use Braceyourself\Yourmembership\Yourmembership;
use Illuminate\Support\Collection;


it('can retrieve event ids', function () {
    // given
    /** @var Client $api */
    $api = app()->make('ym.key');

    // when
    $res = $api->event_ids();

    // then
    expect($res)->toBeInstanceOf(Collection::class);
    expect($res->toArray())->not()->toBeEmpty();
    expect($res->first())->toBeInt();
});

it('can retrieve registrations for an event', function () {
    // given
    $api = app()->make('ym.key');


    /** @var \Braceyourself\Yourmembership\Models\Event $event */
    $event = $api->futureEvents()->first();

    // when
    $res = $event->registrations();

    // then
    expect($res)->toBeInstanceOf(Collection::class);
    expect($res->first())->toBeInstanceOf(Registration::class);
});

it('can retrieve a list of registration ids', function () {
    // given
    /** @var Client $api */
    $api = app()->make('ym.key');
    /** @var \Braceyourself\Yourmembership\Models\Event $event */
    $event = $api->futureEvents()->first();

    $list = $event->registration_ids();

    expect($list)->toBeInstanceOf(Collection::class);
    expect($list->first())->toBeString();
});

it('can retrieve registration details', function () {
    $api = app()->make('ym.key');
    /** @var \Braceyourself\Yourmembership\Models\Event $event */
    $event = $api->futureEvents()->first();
    $registration_id = $event->registration_ids()->first();

    $r = $event->registration($registration_id);

    expect($r)->toBeInstanceOf(Registration::class);
});

it('can retrieve a list of person ids', function () {
    // given
    $api = app()->make('ym.key');

    // when
    $res = $api->people_ids();

    // then
    expect($res)->toBeInstanceOf(Collection::class);
    expect($res)->not()->toBeEmpty();
    expect($res->first())->toBeNumeric();
});

it('can retrieve a single person record', function () {
    // given
    /** @var Client $api */
    $api = app()->make('ym.key');

    // when
    $person = $api->person(
        $id = $api->people_ids()->first()
    );

    // then
    expect($person)->toBeInstanceOf(Person::class);
    expect($person->id)->tobe($id);
    expect($person->api())->toBe($api);
});


test('class map', function () {
    expect(Yourmembership::getMappedClass('registration'))
        ->toBe(Registration::class);

    expect(Yourmembership::getMappedClass('event'))
        ->toBe(\Braceyourself\Yourmembership\Models\Event::class);

    expect(Yourmembership::getMappedClass('person'))
        ->toBe(Person::class);
});
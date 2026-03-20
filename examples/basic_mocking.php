<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// --- Example 1: Basic method expectation ---
// Create a mock of a non-existent interface/class inline
$mailer = Mockery::mock('MailerInterface');

$mailer->shouldReceive('send')
    ->once()
    ->with('user@example.com', 'Welcome!', Mockery::type('string'))
    ->andReturn(true);

$result = $mailer->send('user@example.com', 'Welcome!', 'Hello, welcome aboard!');
echo "Send result: " . ($result ? 'true' : 'false') . "\n\n";

// Always clean up after each test
Mockery::close();

// --- Example 2: Stub returning different values on successive calls ---
$repository = Mockery::mock('UserRepository');

$repository->shouldReceive('findById')
    ->times(3)
    ->andReturn(
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
        null
    );

var_dump($repository->findById(1)); // ['id' => 1, 'name' => 'Alice']
var_dump($repository->findById(2)); // ['id' => 2, 'name' => 'Bob']
var_dump($repository->findById(3)); // null

Mockery::close();

// --- Example 3: Spy (records all calls, no expectations required upfront) ---
$cache = Mockery::spy('CacheInterface');

$cache->set('key', 'value', 3600);
$cache->get('key');

$cache->shouldHaveReceived('set')->once()->with('key', 'value', 3600);
$cache->shouldHaveReceived('get')->once()->with('key');

Mockery::close();
echo "All spy assertions passed.\n\n";

// --- Example 4: Argument matchers ---
$logger = Mockery::mock('Psr\Log\LoggerInterface');

$logger->shouldReceive('error')
    ->once()
    ->with(Mockery::pattern('/Connection failed/'), Mockery::any());

$logger->error('Connection failed to host db01', ['host' => 'db01', 'port' => 5432]);

Mockery::close();
echo "Argument matcher assertion passed.\n";

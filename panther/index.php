<?php

use Symfony\Component\Panther\Client;

require __DIR__.'/vendor/autoload.php'; // Composer's autoloader

$client = Client::createChromeClient();
// Or, if you care about the open web and prefer to use Firefox
// $client = Client::createFirefoxClient();

$client->request('GET', 'http://localhost:8080/web.php');
$crawler = $client->waitFor('#main');
echo $crawler->filter('#main')->text() .PHP_EOL;
exit;

$client->request('GET', 'https://api-platform.com'); // Yes, this website is 100% written in JavaScript
$client->clickLink('Get started');


// Wait for an element to be present in the DOM (even if hidden)
$crawler = $client->waitFor('#installing-the-framework');
// Alternatively, wait for an element to be visible
$crawler = $client->waitForVisibility('#installing-the-framework');

echo $crawler->filter('#installing-the-framework')->text() .PHP_EOL;
$client->takeScreenshot('screen.png'); // Yeah, screenshot!

<?php

require_once __DIR__ . '/Post.php';
require_once __DIR__ . '/ServiceClientExample.php';
require_once __DIR__ . '/NotFoundException.php';

use Digitick\Bridge\ServiceTransport\HttpTransport\GuzzleHttpTransport;
use GuzzleHttp\Client;

$autoloader = require __DIR__ . '/../vendor/autoload.php';

$serviceBaseUrl = 'http://jsonplaceholder.typicode.com/';
$connectionTimeout = 1;
$readTimeout = 1;

$cbMaxFailures = 1;
$cbRetryTimeout = 1;

$guzzleClient = new Client([
        'base_url' => $serviceBaseUrl,
        'connect_timeout' => $connectionTimeout,
        'timeout' => $readTimeout
    ]
);

$httpTransport = new GuzzleHttpTransport($guzzleClient);
$circuitBreaker = new \Digitick\Bridge\ServiceTransport\CircuitBreaker\ApcCircuitBreaker($cbMaxFailures, $cbRetryTimeout);
$jsonSerializer = new \Digitick\Bridge\ServiceTransport\Serializer\JsonSerializer();
$serviceTransport = new \Digitick\Bridge\ServiceTransport\ServiceTransport($httpTransport, $circuitBreaker, $jsonSerializer);
$clientService = new ServiceClientExample($serviceTransport);

$newPost = new Post(null, 6772, 'My new post', 'Vive la choucroute aux fruits de mer');

print "New post $newPost\n";
$postId = $clientService->createPost($newPost);
$newPost->setId($postId);
print "Created post $newPost\n";

try {
    $foundPost = $clientService->findPost(10);
    print "\nFound post $foundPost\n";
} catch (NotFoundException $exc) {
    print "Post with ID " . $createdPost->getId() . " not found \n";
}

try {
    $toUpdatePost = $foundPost;
    $toUpdatePost->setTitle ("Post culinaire");
    $updatedPost = $clientService->updatePost($toUpdatePost);
    print "\nUpdated post $updatedPost\n";
} catch (NotFoundException $exc) {
    print "Can not update post " . $toUpdatePost->getId() . ". Post can not be find. \n";
} catch (ForbiddenException $exc) {
    print "Can not update post " . $toUpdatePost->getId() . ". Update is forbidden. \n";
}

try {
    $toDeletePost = $newPost;
    $clientService->updatePost($toUpdatePost);
    print "\nDelete post $toDeletePost \n";
} catch (NotFoundException $exc) {
    print "Can not delete post " . $toDeletePost->getId() . ". Post can not be find. \n";
} catch (ForbiddenException $exc) {
    print "Can not delete post " . $toDeletePost->getId() . ". Update is forbidden. \n";
}

try {
    $userPosts = $clientService->findUserPosts(1);
    print "\nFound post for user : \n";
    foreach ($userPosts as $p) {
        print "$p \n";
    }
} catch (NotFoundException $exc) {
    print "Posts for user " . 1 . " not found \n";
}
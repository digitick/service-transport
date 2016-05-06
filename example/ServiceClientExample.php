<?php


class ServiceClientExample
{
    private $serviceTransport = null;
    
    public function __construct(\Digitick\Bridge\ServiceTransport\ServiceTransport $serviceTransport)
    {
        $this->serviceTransport = $serviceTransport;
    }
    
    public function findPost ($postId) {
        try {
            $rawResponse = $this->serviceTransport->retrieve('/posts/' . $postId, null);
        } catch (\Digitick\Bridge\ServiceTransport\Exception\ServiceLogicException $exc) {
            if ($exc->getPrevious() instanceof \Digitick\Bridge\ServiceTransport\Exception\NotFoundException) {
                throw new NotFoundException ($exc->getMessage(), $exc->getCode(), $exc);
            }
            else throw $exc;
        }

        return  Post::buildFromArray($rawResponse);
    }

    public function createPost (Post $newPost) {
        $rawResponse = $this->serviceTransport->create('/posts', null, $newPost);
        $post = Post::buildFromArray($rawResponse);
        return $post->getId();
    }

    public function updatePost (Post $post) {
        try {
            $rawResponse = $this->serviceTransport->update('/posts/' . $post->getId(), null, $post);
            $updatedPost = Post::buildFromArray($rawResponse);
        } catch (\Digitick\Bridge\ServiceTransport\Exception\ServiceLogicException $exc) {
            if ($exc->getPrevious() instanceof \Digitick\Bridge\ServiceTransport\Exception\NotFoundException) {
                throw new NotFoundException ($exc->getMessage(), $exc->getCode(), $exc);
            } else if ($exc->getPrevious() instanceof \Digitick\Bridge\ServiceTransport\Exception\ForbiddenException) {
                throw new ForbiddenException ($exc->getMessage(), $exc->getCode(), $exc);
            }
            throw $exc;
        }
        $post->setId($updatedPost->getId());
        return $post;
    }

    public function deletePost (Post $post) {
        try {
            $this->serviceTransport->delete('/posts/' . $post->getId(), null, $post);
        } catch (\Digitick\Bridge\ServiceTransport\Exception\ServiceLogicException $exc) {
            if ($exc->getPrevious() instanceof \Digitick\Bridge\ServiceTransport\Exception\NotFoundException) {
                throw new NotFoundException ($exc->getMessage(), $exc->getCode(), $exc);
            } else if ($exc->getPrevious() instanceof \Digitick\Bridge\ServiceTransport\Exception\ForbiddenException) {
                throw new ForbiddenException ($exc->getMessage(), $exc->getCode(), $exc);
            }
            throw $exc;
        }
    }

    public function findUserPosts ($userId) {
        try {
            $args = new \Digitick\Bridge\ServiceTransport\Request\RequestArguments(1);
            $args[0] = new \Digitick\Bridge\ServiceTransport\Request\RequestArgument('userId', $userId);
            $rawResponse = $this->serviceTransport->retrieve('/posts/', $args);
        } catch (\Digitick\Bridge\ServiceTransport\Exception\ServiceLogicException $exc) {
            if ($exc->getPrevious() instanceof \Digitick\Bridge\ServiceTransport\Exception\NotFoundException) {
                throw new NotFoundException ($exc->getMessage(), $exc->getCode(), $exc);
            }
            else throw $exc;
        }

        $posts = new \SplFixedArray(count($rawResponse));
        foreach ($rawResponse as $idx => $p) {
            $posts [$idx] = Post::buildFromArray($p);
        }
        return $posts;
    }
}
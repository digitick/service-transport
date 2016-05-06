<?php


class Post
{
    private $id;
    private $userId;
    private $title;
    private $body;

    /**
     * Post constructor.
     * @param $id
     * @param $userId
     * @param $title
     * @param $body
     */
    public function __construct($id = null, $userId = null, $title = null, $body = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return Post
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     * @return Post
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return Post
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     * @return Post
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public static function buildFromArray (array $data) {
        $post = new Post();

        if (isset ($data['id'])) $post->setId($data['id']);
        if (isset ($data['userId'])) $post->setUserId($data['userId']);
        if (isset ($data['title'])) $post->setTitle($data['title']);
        if (isset ($data['body'])) $post->setBody($data['body']);

        return $post;
    }

    public function __toString()
    {
        $str = sprintf("{#%d @%d} [%s] : %s",
            $this->getId(),
            $this->getUserId(),
            $this->getTitle(),
            $this->getBody()
        );

        return $str;
    }
}